<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\ParentAccess;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\SubStrand;
use App\Models\User;
use Database\Seeders\GradeFiveMathematicsSeeder;
use Database\Seeders\SprintTwoDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherStudentTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private SchoolClass $schoolClass;
    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacher = User::factory()->create(['email' => 'teacher@example.com', 'role' => 'teacher']);
        $this->seed([SprintTwoDemoSeeder::class, GradeFiveMathematicsSeeder::class]);
        $this->teacher->refresh();
        $this->schoolClass = SchoolClass::firstOrFail();
        $this->headers = ['Authorization' => 'Bearer '.$this->teacher->createToken('test')->plainTextToken];
    }

    private function url(?int $studentId = null): string
    {
        return '/api/teacher/classes/'.$this->schoolClass->id.'/students'.($studentId ? '/'.$studentId : '');
    }

    private function details(): array
    {
        return ['full_name' => 'Simulated Joining Learner', 'admission_number' => 'JOIN-001', 'parent_phone_number' => '+254700000000'];
    }

    public function test_teacher_can_create_read_and_update_students_in_assigned_class(): void
    {
        $data = $this->details();
        $data['school_class_id'] = 9999;
        $id = $this->postJson($this->url(), $data, $this->headers)->assertCreated()->json('student.id');
        $this->assertDatabaseHas('students', ['id' => $id, 'school_class_id' => $this->schoolClass->id]);
        $this->getJson($this->url(), $this->headers)->assertOk()->assertJsonCount(4, 'students');
        $data['full_name'] = 'Corrected Learner';
        $this->patchJson($this->url($id), $data, $this->headers)->assertOk();
        $this->assertDatabaseHas('students', ['id' => $id, 'full_name' => 'Corrected Learner']);
    }

    public function test_remove_hides_student_retains_assessments_revokes_parent_access_and_can_restore(): void
    {
        $student = Student::firstOrFail();
        $sub = SubStrand::firstOrFail();
        Assessment::create(['student_id' => $student->id, 'sub_strand_id' => $sub->id, 'user_id' => $this->teacher->id, 'term' => 'Term 1 2026', 'competency_level' => 'ME']);
        $code = new ParentAccess;
        $code->student_id = $student->id; $code->code_hash = hash('sha256', '123456');
        $code->term = 'Term 1 2026'; $code->expires_at = now()->addDays(30); $code->is_active = true; $code->save();
        $this->deleteJson($this->url($student->id), [], $this->headers)->assertOk();
        $this->assertSoftDeleted('students', ['id' => $student->id]);
        $this->assertDatabaseCount('assessments', 1);
        $this->assertFalse((bool) $code->fresh()->is_active);
        $this->getJson('/api/teacher/classes/'.$this->schoolClass->id.'/assessment-options', $this->headers)
            ->assertOk()->assertJsonCount(2, 'students');
        $selection = ['sub_strand_id' => $sub->id, 'term' => 'Term 1 2026'];
        $this->getJson('/api/teacher/classes/'.$this->schoolClass->id.'/assessments?'.http_build_query($selection), $this->headers)
            ->assertOk()->assertJsonCount(0, 'assessments');
        $this->postJson('/api/teacher/classes/'.$this->schoolClass->id.'/assessments', $selection + ['assessments' => [['student_id' => $student->id, 'competency_level' => 'EE']]], $this->headers)->assertUnprocessable();
        $this->postJson($this->url($student->id).'/restore', [], $this->headers)->assertOk();
        $this->assertNotNull(Student::find($student->id));
        $this->assertFalse((bool) $code->fresh()->is_active);
        $this->getJson('/api/teacher/classes/'.$this->schoolClass->id.'/assessments?'.http_build_query($selection), $this->headers)
            ->assertOk()->assertJsonCount(1, 'assessments')->assertJsonPath('assessments.0.competency_level', 'ME');
    }

    public function test_same_school_unassigned_class_is_hidden_for_every_operation_and_revocation_takes_effect(): void
    {
        $this->teacher->assignedClasses()->detach($this->schoolClass->id);
        $this->getJson('/api/teacher/classes', $this->headers)->assertOk()->assertJsonCount(0, 'classes');
        $this->getJson($this->url(), $this->headers)->assertNotFound();
        $this->postJson($this->url(), $this->details(), $this->headers)->assertNotFound();
        $student = Student::firstOrFail();
        $this->patchJson($this->url($student->id), $this->details(), $this->headers)->assertNotFound();
        $this->deleteJson($this->url($student->id), [], $this->headers)->assertNotFound();
        $this->postJson($this->url($student->id).'/restore', [], $this->headers)->assertNotFound();
        foreach (['assessments', 'assessment-options'] as $path) {
            $this->getJson('/api/teacher/classes/'.$this->schoolClass->id.'/'.$path, $this->headers)->assertNotFound();
        }
        $this->postJson('/api/teacher/classes/'.$this->schoolClass->id.'/assessments', [], $this->headers)->assertNotFound();
    }

    public function test_assignment_command_is_idempotent_revocable_and_rejects_cross_school_or_admin(): void
    {
        $email = $this->teacher->email; $classId = $this->schoolClass->id;
        $this->artisan('teacher:assign-class', ['email' => $email, 'classId' => $classId])->assertSuccessful();
        $this->assertDatabaseCount('class_teacher', 1);
        $this->artisan('teacher:assign-class', ['email' => $email, 'classId' => $classId, '--remove' => true])->assertSuccessful();
        $this->assertDatabaseCount('class_teacher', 0);
        $school = School::create(['name' => 'Other School']);
        $other = SchoolClass::create(['school_id' => $school->id, 'name' => 'Other', 'grade_level' => 5]);
        $this->artisan('teacher:assign-class', ['email' => $email, 'classId' => $other->id])->assertFailed();
        $admin = User::factory()->create(['role' => 'admin', 'school_id' => $this->teacher->school_id]);
        $this->artisan('teacher:assign-class', ['email' => $admin->email, 'classId' => $classId])->assertFailed();
    }

    public function test_validation_rejects_duplicates_including_removed_students_and_invalid_phone(): void
    {
        $this->postJson($this->url(), [], $this->headers)->assertUnprocessable();
        $data = $this->details(); $data['parent_phone_number'] = 'not-a-phone';
        $this->postJson($this->url(), $data, $this->headers)->assertUnprocessable();
        $data = $this->details();
        $id = $this->postJson($this->url(), $data, $this->headers)->assertCreated()->json('student.id');
        $this->postJson($this->url(), $data, $this->headers)->assertUnprocessable();
        $this->deleteJson($this->url($id), [], $this->headers)->assertOk();
        $this->postJson($this->url(), $data, $this->headers)->assertUnprocessable();
        $this->patchJson($this->url($id), $data, $this->headers)->assertNotFound();
    }

    public function test_student_ids_cannot_be_used_to_change_another_class_and_cross_school_pivot_is_ignored(): void
    {
        $school = School::create(['name' => 'Other School']);
        $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'Other', 'grade_level' => 5]);
        $student = Student::create(['school_class_id' => $class->id, 'full_name' => 'Other Learner', 'admission_number' => 'OTHER', 'parent_phone_number' => 'DEMO-NO-SMS']);
        $this->teacher->assignedClasses()->attach($class->id);
        $this->getJson('/api/teacher/classes', $this->headers)->assertOk()->assertJsonCount(1, 'classes');
        $this->getJson('/api/teacher/classes/'.$class->id.'/students', $this->headers)->assertNotFound();
        $this->patchJson($this->url($student->id), $this->details(), $this->headers)->assertNotFound();
        $this->deleteJson($this->url($student->id), [], $this->headers)->assertNotFound();
        $this->assertNotNull(Student::find($student->id));
    }

    public function test_guests_admins_and_unlinked_teachers_cannot_manage_students(): void
    {
        $this->getJson($this->url())->assertUnauthorized();
        $this->postJson($this->url(), $this->details())->assertUnauthorized();
        foreach ([['admin', $this->teacher->school_id], ['teacher', null]] as [$role, $schoolId]) {
            $user = User::factory()->create(['role' => $role, 'school_id' => $schoolId]);
            $headers = ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken];
            $this->getJson($this->url(), $headers)->assertForbidden();
            $this->postJson($this->url(), $this->details(), $headers)->assertForbidden();
        }
    }

    public function test_reseeding_does_not_restore_leavers(): void
    {
        $student = Student::firstOrFail();
        $student->delete();
        $this->seed(SprintTwoDemoSeeder::class);
        $this->assertSoftDeleted('students', ['id' => $student->id]);
        $this->assertDatabaseCount('students', 3);
    }
}
