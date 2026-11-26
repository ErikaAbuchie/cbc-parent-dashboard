<?php

namespace Tests\Feature;

use App\Models\{Assessment, LearningArea, ParentAccess, ParentSession, PracticeExercise, SchoolClass, Student, SubStrand, TermReport, User};
use Database\Seeders\{GradeFiveMathematicsSeeder, SprintTwoDemoSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherPracticeTest extends TestCase
{
    use RefreshDatabase;
    private User $teacher;
    private SchoolClass $class;
    private SubStrand $sub;
    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacher = User::factory()->create(['email' => 'teacher@example.com', 'role' => 'teacher']);
        $this->seed([SprintTwoDemoSeeder::class, GradeFiveMathematicsSeeder::class]);
        $this->teacher->refresh();
        $this->class = SchoolClass::firstOrFail();
        $this->sub = SubStrand::firstOrFail();
        $this->headers = ['Authorization' => 'Bearer '.$this->teacher->createToken('test')->plainTextToken];
    }

    private function url(?int $id = null, string $action = ''): string
    {
        return '/api/teacher/classes/'.$this->class->id.'/practice-exercises'.($id ? '/'.$id : '').($action ? '/'.$action : '');
    }

    private function data(): array
    {
        return ['title' => 'Number practice', 'sub_strand_id' => $this->sub->id,
            'questions' => [['question' => 'What is 2 + 3?', 'options' => ['4', '5', '6', '7'], 'correct' => 1, 'feedback' => 'Count two, then three more: five.']]];
    }

    private function draft(): int
    {
        return $this->postJson($this->url(), $this->data(), $this->headers)->assertCreated()->assertJsonPath('exercise.status', 'draft')->json('exercise.id');
    }

    private function parentHeaders(): array
    {
        $student = Student::firstOrFail();
        Assessment::create(['student_id' => $student->id, 'sub_strand_id' => $this->sub->id, 'user_id' => $this->teacher->id, 'term' => 'Term 1 2026', 'competency_level' => 'AE']);
        $report = TermReport::create(['student_id' => $student->id, 'user_id' => $this->teacher->id, 'term' => 'Term 1 2026', 'sub_strand_ids' => [$this->sub->id], 'finalised_at' => now()]);
        $access = ParentAccess::create(['student_id' => $student->id, 'term_report_id' => $report->id, 'term' => $report->term,
            'code_hash' => hash('sha256', '123456'), 'recipient_hash' => hash('sha256', $student->parent_phone_number), 'is_active' => true, 'expires_at' => now()->addDay()]);
        $token = str_repeat('p', 64);
        ParentSession::create(['parent_access_id' => $access->id, 'token_hash' => hash('sha256', $token), 'expires_at' => now()->addHour()]);
        return ['Authorization' => 'Bearer '.$token];
    }

    public function test_drafts_can_be_saved_edited_deleted_and_must_be_complete_before_publishing(): void
    {
        $data = $this->data(); $data['questions'] = [];
        $id = $this->postJson($this->url(), $data + ['status' => 'published', 'user_id' => 999], $this->headers)->assertCreated()->assertJsonPath('exercise.status', 'draft')->json('exercise.id');
        $this->postJson($this->url($id, 'publish'), [], $this->headers)->assertUnprocessable();
        $partial = $this->data(); $partial['questions'][0]['correct'] = null;
        $this->patchJson($this->url($id), $partial, $this->headers)->assertOk();
        $this->postJson($this->url($id, 'publish'), [], $this->headers)->assertUnprocessable();
        $this->patchJson($this->url($id), $this->data(), $this->headers)->assertOk();
        $this->getJson($this->url(), $this->headers)->assertOk()->assertJsonCount(1, 'exercises');
        $this->deleteJson($this->url($id), [], $this->headers)->assertOk();
        $this->assertDatabaseMissing('practice_exercises', ['id' => $id]);
    }

    public function test_published_exercises_are_locked_and_can_be_duplicated_or_archived(): void
    {
        $id = $this->draft();
        $this->postJson($this->url($id, 'publish'), [], $this->headers)->assertOk()->assertJsonPath('exercise.status', 'published');
        $this->patchJson($this->url($id), $this->data(), $this->headers)->assertStatus(409);
        $this->deleteJson($this->url($id), [], $this->headers)->assertStatus(409);
        $copy = $this->postJson($this->url($id, 'duplicate'), [], $this->headers)->assertCreated()->assertJsonPath('exercise.status', 'draft')->json('exercise.id');
        $this->assertNotEquals($id, $copy);
        $this->postJson($this->url($id, 'archive'), [], $this->headers)->assertOk()->assertJsonPath('exercise.status', 'archived');
    }

    public function test_question_validation_rejects_wrong_grade_invalid_options_and_oversized_sets(): void
    {
        $area = LearningArea::create(['name' => 'Other grade', 'grade_level' => 6]);
        $strand = $area->strands()->create(['name' => 'Other strand']);
        $sub = $strand->subStrands()->create(['name' => 'Other topic']);
        $data = $this->data(); $data['sub_strand_id'] = $sub->id;
        $this->postJson($this->url(), $data, $this->headers)->assertUnprocessable();
        $data = $this->data(); $data['questions'][0]['options'] = ['A', 'B'];
        $this->postJson($this->url(), $data, $this->headers)->assertUnprocessable();
        $data = $this->data(); $data['questions'] = array_fill(0, 31, $data['questions'][0]);
        $this->postJson($this->url(), $data, $this->headers)->assertUnprocessable();
        $data = $this->data(); $data['questions'][0]['options'] = ['Same', 'same', 'C', 'D'];
        $id = $this->postJson($this->url(), $data, $this->headers)->assertCreated()->json('exercise.id');
        $this->postJson($this->url($id, 'publish'), [], $this->headers)->assertUnprocessable();
    }

    public function test_teachers_cannot_access_unassigned_classes_or_another_teachers_exercises(): void
    {
        $id = $this->draft();
        $other = User::factory()->create(['role' => 'teacher', 'school_id' => $this->teacher->school_id]);
        $other->assignedClasses()->attach($this->class->id);
        $headers = ['Authorization' => 'Bearer '.$other->createToken('test')->plainTextToken];
        // Separate HTTP clients must not reuse the previous request's cached guard.
        app('auth')->forgetGuards();
        $this->getJson($this->url(), $headers)->assertOk()->assertJsonCount(0, 'exercises');
        foreach (['publish', 'archive', 'duplicate'] as $action) $this->postJson($this->url($id, $action), [], $headers)->assertNotFound();
        $this->patchJson($this->url($id), $this->data(), $headers)->assertNotFound();
        $this->deleteJson($this->url($id), [], $headers)->assertNotFound();
        $this->teacher->assignedClasses()->detach($this->class->id);
        app('auth')->forgetGuards();
        $this->getJson($this->url(), $this->headers)->assertNotFound();
        $this->postJson($this->url(), $this->data(), $this->headers)->assertNotFound();
        $this->postJson($this->url($id, 'publish'), [], $this->headers)->assertNotFound();
    }

    public function test_parent_sees_only_published_report_scoped_exercises_and_archived_history_survives(): void
    {
        $headers = $this->parentHeaders(); $id = $this->draft();
        $this->postJson('/api/parent/practice', ['exercise_id' => $id], $headers)->assertNotFound();
        $this->getJson('/api/parent/practice', $headers)->assertOk()->assertJsonCount(1, 'topics');
        $this->postJson($this->url($id, 'publish'), [], $this->headers)->assertOk();
        $this->getJson('/api/parent/practice', $headers)->assertOk()->assertJsonCount(2, 'topics')->assertJsonMissingPath('topics.1.questions');
        $quiz = $this->postJson('/api/parent/practice', ['exercise_id' => $id], $headers)->assertOk()->assertJsonCount(1, 'questions')
            ->assertJsonPath('exercise_title', 'Number practice')->assertJsonMissingPath('questions.0.correct')->json('id');
        $this->postJson('/api/parent/practice', ['exercise_id' => $id], $headers)->assertOk()->assertJsonPath('id', $quiz);
        $this->postJson('/api/parent/practice/'.$quiz.'/answer', ['question_id' => 1, 'answer_index' => 1], $headers)->assertOk()->assertJsonPath('correct_count', 1);
        $this->postJson($this->url($id, 'archive'), [], $this->headers)->assertOk();
        $this->postJson('/api/parent/practice', ['exercise_id' => $id], $headers)->assertNotFound();
        $this->getJson('/api/parent/practice/'.$quiz, $headers)->assertOk()->assertJsonPath('review.0.question', 'What is 2 + 3?');
        $this->getJson('/api/parent/practice', $headers)->assertOk()->assertJsonCount(1, 'topics')->assertJsonPath('history.0.topic', 'Number practice');
        $this->assertSame('AE', Assessment::firstOrFail()->competency_level);
        $this->assertDatabaseCount('assessments', 1);
    }

    public function test_parent_cannot_start_exercises_from_another_teacher_class_or_unassessed_topic(): void
    {
        $headers = $this->parentHeaders();
        $other = User::factory()->create(['role' => 'teacher', 'school_id' => $this->teacher->school_id]);
        $exercise = PracticeExercise::create($this->data() + ['school_class_id' => $this->class->id, 'user_id' => $other->id, 'status' => 'published']);
        $this->postJson('/api/parent/practice', ['exercise_id' => $exercise->id], $headers)->assertNotFound();
        $exercise->update(['user_id' => $this->teacher->id, 'sub_strand_id' => SubStrand::where('id', '!=', $this->sub->id)->firstOrFail()->id]);
        $this->postJson('/api/parent/practice', ['exercise_id' => $exercise->id], $headers)->assertNotFound();
        $otherClass = SchoolClass::create(['school_id' => $this->class->school_id, 'name' => 'Other class', 'grade_level' => 5]);
        $exercise->update(['sub_strand_id' => $this->sub->id, 'school_class_id' => $otherClass->id]);
        $this->postJson('/api/parent/practice', ['exercise_id' => $exercise->id], $headers)->assertNotFound();
        $this->getJson('/api/parent/practice', $headers)->assertOk()->assertJsonCount(1, 'topics');
    }

    public function test_authoring_requires_teacher_authentication(): void
    {
        $this->getJson($this->url())->assertUnauthorized();
        $user = User::factory()->create(['role' => 'admin', 'school_id' => $this->class->school_id]);
        app('auth')->forgetGuards();
        $this->postJson($this->url(), $this->data(), ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken])->assertForbidden();
        $this->assertDatabaseCount('practice_exercises', 0);
    }

    public function test_different_exercises_on_the_same_topic_have_separate_attempts_and_snapshots(): void
    {
        $headers = $this->parentHeaders(); $id = $this->draft();
        $this->postJson($this->url($id, 'publish'), [], $this->headers)->assertOk();
        $first = $this->postJson('/api/parent/practice', ['exercise_id' => $id], $headers)->assertOk()->json('id');
        $copy = $this->postJson($this->url($id, 'duplicate'), [], $this->headers)->assertCreated()->json('exercise.id');
        $data = $this->data(); $data['title'] = 'Second exercise'; $data['questions'][0]['question'] = 'What is 4 + 1?';
        $this->patchJson($this->url($copy), $data, $this->headers)->assertOk();
        $this->postJson($this->url($copy, 'publish'), [], $this->headers)->assertOk();
        $second = $this->postJson('/api/parent/practice', ['exercise_id' => $copy], $headers)->assertOk()->assertJsonPath('questions.0.question', 'What is 4 + 1?')->json('id');
        $this->assertNotEquals($first, $second);
        $this->getJson('/api/parent/practice/'.$first, $headers)->assertOk()->assertJsonPath('questions.0.question', 'What is 2 + 3?');
        $this->postJson('/api/parent/practice', ['exercise_id' => $id], $headers)->assertOk()->assertJsonPath('id', $first);
    }
}
