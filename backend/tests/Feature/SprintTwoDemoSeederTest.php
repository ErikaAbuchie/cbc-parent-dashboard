<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\SprintTwoDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class SprintTwoDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_setup_links_teacher_and_can_be_repeated_without_duplicates(): void
    {
        $teacher = User::factory()->create(['email' => 'teacher@example.com', 'role' => 'teacher']);
        $this->seed(SprintTwoDemoSeeder::class);
        $this->seed(SprintTwoDemoSeeder::class);
        $this->assertDatabaseCount('schools', 1);
        $this->assertDatabaseCount('school_classes', 1);
        $this->assertDatabaseCount('students', 3);
        $school = $teacher->fresh()->school;
        $this->assertSame('CBC Demo Primary School', $school->name);
        $this->assertCount(3, $school->schoolClasses->first()->students);
        $this->assertSame($school->id, Student::first()->schoolClass->school->id);
    }

    public function test_existing_school_assignment_is_preserved_and_setup_is_rolled_back(): void
    {
        $school = School::create(['name' => 'Existing School']);
        $teacher = User::factory()->create(['email' => 'teacher@example.com', 'role' => 'teacher', 'school_id' => $school->id]);
        try {
            $this->seed(SprintTwoDemoSeeder::class);
            $this->fail('Conflicting school assignment should be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('already belongs to another school', $exception->getMessage());
        }
        $this->assertSame($school->id, $teacher->fresh()->school_id);
        $this->assertDatabaseCount('schools', 1);
        $this->assertDatabaseCount('students', 0);
    }
}
