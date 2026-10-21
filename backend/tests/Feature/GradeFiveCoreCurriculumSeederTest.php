<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\LearningArea;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\SubStrand;
use App\Models\User;
use Database\Seeders\GradeFiveCoreCurriculumSeeder;
use Database\Seeders\GradeFiveMathematicsSeeder;
use Database\Seeders\SprintTwoDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeFiveCoreCurriculumSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeat_seeding_preserves_existing_assessments_and_other_grades(): void
    {
        $teacher = User::factory()->create(['email' => 'teacher@example.com', 'role' => 'teacher']);
        $this->seed([SprintTwoDemoSeeder::class, GradeFiveMathematicsSeeder::class]);
        $sub = SubStrand::where('name', 'Whole Numbers')->firstOrFail();
        $record = Assessment::create(['student_id' => Student::firstOrFail()->id, 'sub_strand_id' => $sub->id,
            'user_id' => $teacher->id, 'term' => 'Term 1 2026', 'competency_level' => 'ME', 'remarks' => 'Keep this assessment']);
        $gradeFour = LearningArea::create(['name' => 'English', 'grade_level' => 4]);
        $this->seed(GradeFiveCoreCurriculumSeeder::class);
        $ids = SubStrand::orderBy('id')->pluck('id')->all();
        $this->seed(GradeFiveCoreCurriculumSeeder::class);
        $this->assertSame($ids, SubStrand::orderBy('id')->pluck('id')->all());
        $this->assertDatabaseCount('learning_areas', 11);
        $this->assertDatabaseCount('strands', 47);
        $this->assertDatabaseCount('sub_strands', 205);
        $this->assertCount(0, $gradeFour->fresh()->strands);
        $this->assertSame('ME', $record->fresh()->competency_level);
        $this->assertSame('Keep this assessment', $record->fresh()->remarks);
        $this->assertSame($sub->id, SubStrand::where('name', 'Whole Numbers')->firstOrFail()->id);
        $this->assertDatabaseCount('assessments', 1);
        $this->assertDatabaseCount('parent_access', 0);
    }

    public function test_assigned_teacher_can_select_new_subjects_and_repeated_kiswahili_units_are_distinct(): void
    {
        $teacher = User::factory()->create(['email' => 'teacher@example.com', 'role' => 'teacher']);
        $this->seed([SprintTwoDemoSeeder::class, GradeFiveCoreCurriculumSeeder::class]);
        $teacher->refresh();
        $headers = ['Authorization' => 'Bearer '.$teacher->createToken('curriculum-test')->plainTextToken];
        $class = SchoolClass::firstOrFail();
        $response = $this->getJson('/api/teacher/classes/'.$class->id.'/assessment-options', $headers)->assertOk();
        $response->assertJsonCount(10, 'learning_areas');
        $this->assertContains('Science and Technology', array_column($response->json('learning_areas'), 'name'));
        $science = SubStrand::where('name', 'Water Pollution')->firstOrFail();
        $this->postJson('/api/teacher/classes/'.$class->id.'/assessments', [
            'term' => 'Term 1 2026', 'sub_strand_id' => $science->id,
            'assessments' => [['student_id' => Student::firstOrFail()->id, 'competency_level' => 'AE', 'remarks' => 'Science practice']],
        ], $headers)->assertSuccessful();
        $this->assertDatabaseHas('assessments', ['sub_strand_id' => $science->id, 'competency_level' => 'AE']);
        $first = SubStrand::where('name', '1.2.1 Kusoma kwa Ufahamu')->firstOrFail();
        $fifth = SubStrand::where('name', '5.2.1 Kusoma kwa Ufahamu')->firstOrFail();
        $this->assertNotSame($first->id, $fifth->id);
        $this->assertSame($first->strand_id, $fifth->strand_id);
    }
}
