<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\LearningArea;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Strand;
use App\Models\Student;
use App\Models\SubStrand;
use App\Models\User;
use Database\Seeders\GradeFiveMathematicsSeeder;
use Database\Seeders\SprintTwoDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherAssessmentTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private SchoolClass $schoolClass;
    private Student $student;
    private SubStrand $subStrand;
    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacher = User::factory()->create(['email' => 'teacher@example.com', 'role' => 'teacher']);
        $this->seed([SprintTwoDemoSeeder::class, GradeFiveMathematicsSeeder::class]);
        $this->teacher->refresh();
        $this->schoolClass = SchoolClass::firstOrFail();
        $this->student = Student::firstOrFail();
        $this->subStrand = SubStrand::firstOrFail();
        $this->headers = ['Authorization' => 'Bearer '.$this->teacher->createToken('test', ['teacher'])->plainTextToken];
    }

    private function endpoint(string $suffix = 'assessments'): string
    {
        return '/api/teacher/classes/'.$this->schoolClass->id.'/'.$suffix;
    }

    private function payload(): array
    {
        return ['sub_strand_id' => $this->subStrand->id, 'term' => 'Term 1 2026',
            'assessments' => [['student_id' => $this->student->id, 'competency_level' => 'ME', 'remarks' => 'Good progress.']]];
    }

    public function test_options_return_grade_curriculum_and_roster_without_parent_phones(): void
    {
        $this->getJson('/api/teacher/classes', $this->headers)->assertOk()->assertJsonCount(1, 'classes');
        $this->getJson($this->endpoint('assessment-options'), $this->headers)->assertOk()
            ->assertJsonCount(3, 'students')->assertJsonCount(1, 'learning_areas')
            ->assertJsonCount(4, 'learning_areas.0.strands')->assertJsonMissingPath('students.0.parent_phone_number');
    }

    public function test_save_read_and_repeat_save_update_the_same_record(): void
    {
        $payload = $this->payload();
        $this->postJson($this->endpoint(), $payload, $this->headers)->assertOk()->assertJsonPath('saved_count', 1);
        $payload['assessments'][0]['competency_level'] = 'EE';
        $this->postJson($this->endpoint(), $payload, $this->headers)->assertOk();
        $this->assertDatabaseCount('assessments', 1);
        $this->getJson($this->endpoint().'?'.http_build_query(['sub_strand_id' => $this->subStrand->id, 'term' => 'Term 1 2026']), $this->headers)
            ->assertOk()->assertJsonCount(1, 'assessments')->assertJsonPath('assessments.0.competency_level', 'EE');
        $record = Assessment::first();
        $this->assertSame($this->teacher->id, $record->teacher->id);
        $this->assertSame($this->student->id, $record->student->id);
        $this->assertSame($this->subStrand->id, $record->subStrand->id);
    }

    public function test_other_schools_are_hidden_for_reads_and_writes(): void
    {
        $school = School::create(['name' => 'Other School']);
        $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'Other Class', 'grade_level' => 5]);
        $url = '/api/teacher/classes/'.$class->id;
        $this->getJson($url.'/assessment-options', $this->headers)->assertNotFound();
        $this->getJson($url.'/assessments', $this->headers)->assertNotFound();
        $this->postJson($url.'/assessments', $this->payload(), $this->headers)->assertNotFound();
        $this->assertDatabaseCount('assessments', 0);
    }

    public function test_invalid_batch_is_rejected_without_partial_writes(): void
    {
        $payload = $this->payload();
        $payload['assessments'][] = ['student_id' => 999999, 'competency_level' => 'INVALID'];
        $this->postJson($this->endpoint(), $payload, $this->headers)->assertUnprocessable();
        $this->assertDatabaseCount('assessments', 0);
        $payload = $this->payload();
        $payload['assessments'][] = $payload['assessments'][0];
        $this->postJson($this->endpoint(), $payload, $this->headers)->assertUnprocessable();
        $payload = $this->payload();
        $payload['term'] = 'Term 4 2026';
        $this->postJson($this->endpoint(), $payload, $this->headers)->assertUnprocessable();
        $this->assertDatabaseCount('assessments', 0);
    }

    public function test_students_from_another_class_and_curriculum_from_another_grade_are_rejected(): void
    {
        $class = SchoolClass::create(['school_id' => $this->teacher->school_id, 'name' => 'Grade 4', 'grade_level' => 4]);
        $student = Student::create(['school_class_id' => $class->id, 'full_name' => 'Other Learner', 'admission_number' => 'OTHER-1', 'parent_phone_number' => 'DEMO-NO-SMS']);
        $payload = $this->payload();
        $payload['assessments'][0]['student_id'] = $student->id;
        $this->postJson($this->endpoint(), $payload, $this->headers)->assertUnprocessable();
        $area = LearningArea::create(['name' => 'Mathematics', 'grade_level' => 4]);
        $strand = Strand::create(['learning_area_id' => $area->id, 'name' => 'Numbers']);
        $sub = SubStrand::create(['strand_id' => $strand->id, 'name' => 'Whole Numbers']);
        $payload = $this->payload();
        $payload['sub_strand_id'] = $sub->id;
        $this->postJson($this->endpoint(), $payload, $this->headers)->assertUnprocessable();
        $this->assertDatabaseCount('assessments', 0);
    }

    public function test_other_teacher_records_are_not_overwritten_or_returned(): void
    {
        $other = User::factory()->create(['role' => 'teacher', 'school_id' => $this->teacher->school_id]);
        Assessment::create(['student_id' => $this->student->id, 'sub_strand_id' => $this->subStrand->id,
            'user_id' => $other->id, 'term' => 'Term 1 2026', 'competency_level' => 'BE']);
        $this->getJson($this->endpoint().'?'.http_build_query(['sub_strand_id' => $this->subStrand->id, 'term' => 'Term 1 2026']), $this->headers)
            ->assertOk()->assertJsonCount(0, 'assessments');
        $payload = $this->payload();
        $payload['user_id'] = $other->id;
        $this->postJson($this->endpoint(), $payload, $this->headers)->assertOk();
        $this->assertDatabaseHas('assessments', ['user_id' => $other->id, 'competency_level' => 'BE']);
        $this->assertDatabaseHas('assessments', ['user_id' => $this->teacher->id, 'competency_level' => 'ME']);
    }

    public function test_unauthenticated_admin_and_unassigned_accounts_cannot_access_assessment_api(): void
    {
        $this->getJson('/api/teacher/classes')->assertUnauthorized();
        $this->postJson($this->endpoint(), $this->payload())->assertUnauthorized();
        foreach ([['admin', $this->teacher->school_id], ['teacher', null]] as [$role, $schoolId]) {
            $user = User::factory()->create(['role' => $role, 'school_id' => $schoolId]);
            $headers = ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken];
            $this->getJson('/api/teacher/classes', $headers)->assertForbidden();
            $this->postJson($this->endpoint(), $this->payload(), $headers)->assertForbidden();
        }
    }
}
