<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\ParentAccess;
use App\Models\ParentSession;
use App\Models\Student;
use App\Models\SubStrand;
use App\Models\TermReport;
use App\Models\User;
use Database\Seeders\GradeFiveMathematicsSeeder;
use Database\Seeders\SprintTwoDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentPracticeTest extends TestCase
{
    use RefreshDatabase;

    private array $headers;
    private Assessment $assessment;
    private ParentAccess $access;

    protected function setUp(): void
    {
        parent::setUp();
        $teacher = User::factory()->create(['email' => 'teacher@example.com', 'role' => 'teacher']);
        $this->seed([SprintTwoDemoSeeder::class, GradeFiveMathematicsSeeder::class]);
        $student = Student::firstOrFail();
        $sub = SubStrand::firstOrFail();
        $this->assessment = Assessment::create(['student_id' => $student->id, 'sub_strand_id' => $sub->id,
            'user_id' => $teacher->id, 'term' => 'Term 1 2026', 'competency_level' => 'AE', 'remarks' => 'Private teacher remark']);
        $report = TermReport::create(['student_id' => $student->id, 'user_id' => $teacher->id,
            'term' => 'Term 1 2026', 'sub_strand_ids' => [$sub->id], 'finalised_at' => now()]);
        $this->access = ParentAccess::create(['student_id' => $student->id, 'term_report_id' => $report->id,
            'term' => $report->term, 'code_hash' => hash('sha256', '123456'), 'recipient_hash' => hash('sha256', $student->parent_phone_number),
            'expires_at' => now()->addDay(), 'is_active' => true, 'sms_status' => 'accepted']);
        $token = str_repeat('p', 64);
        ParentSession::create(['parent_access_id' => $this->access->id, 'token_hash' => hash('sha256', $token), 'expires_at' => now()->addHour()]);
        $this->headers = ['Authorization' => 'Bearer '.$token];
    }

    private function start(): int
    {
        return $this->postJson('/api/parent/practice', ['sub_strand_id' => $this->assessment->sub_strand_id], $this->headers)
            ->assertOk()->assertJsonPath('completed', false)->assertJsonCount(3, 'questions')
            ->assertJsonMissingPath('questions.0.correct')->assertJsonMissingPath('question_set')->json('id');
    }

    public function test_practice_saves_feedback_is_idempotent_and_never_changes_teacher_rating(): void
    {
        $id = $this->start();
        $this->assertSame($id, $this->start());
        $answers = ['answers' => [['question_id' => 1, 'answer_index' => 2], ['question_id' => 2, 'answer_index' => 0], ['question_id' => 3, 'answer_index' => 2]]];
        $this->postJson('/api/parent/practice/'.$id.'/submit', $answers, $this->headers)->assertOk()
            ->assertJsonPath('completed', true)->assertJsonPath('correct_count', 2)->assertJsonCount(3, 'review');
        $this->postJson('/api/parent/practice/'.$id.'/submit', $answers, $this->headers)->assertOk()->assertJsonPath('correct_count', 2);
        $this->assertDatabaseCount('quiz_responses', 3);
        $this->assertSame('AE', $this->assessment->fresh()->competency_level);
        $this->assertDatabaseCount('assessments', 1);
        $this->getJson('/api/parent/practice', $this->headers)->assertOk()->assertJsonCount(1, 'topics')->assertJsonCount(1, 'history');
        $this->getJson('/api/parent/practice/'.$id, $this->headers)->assertOk()->assertJsonPath('correct_count', 2);
    }

    public function test_partial_duplicate_or_invalid_answers_do_not_partially_save(): void
    {
        $id = $this->start();
        foreach ([[], [['question_id' => 1, 'answer_index' => 2]],
            [['question_id' => 1, 'answer_index' => 2], ['question_id' => 1, 'answer_index' => 2], ['question_id' => 3, 'answer_index' => 2]],
            [['question_id' => 1, 'answer_index' => 2], ['question_id' => 2, 'answer_index' => 2], ['question_id' => 99, 'answer_index' => 2]],
            [['question_id' => 1, 'answer_index' => 2], ['question_id' => 2, 'answer_index' => 9], ['question_id' => 3, 'answer_index' => 2]]] as $answers) {
            $this->postJson('/api/parent/practice/'.$id.'/submit', ['answers' => $answers], $this->headers)->assertUnprocessable();
            $this->assertDatabaseCount('quiz_responses', 0);
        }
        $this->getJson('/api/parent/practice/'.$id, $this->headers)->assertOk()->assertJsonPath('completed', false)->assertJsonMissingPath('review');
    }

    public function test_outside_report_other_learner_and_revoked_access_are_rejected(): void
    {
        $id = $this->start();
        $outside = SubStrand::where('name', 'Addition')->firstOrFail();
        $this->postJson('/api/parent/practice', ['sub_strand_id' => $outside->id], $this->headers)->assertNotFound();
        $quiz = \App\Models\QuizSession::findOrFail($id)->replicate();
        $quiz->student_id = Student::where('id', '!=', $this->assessment->student_id)->firstOrFail()->id;
        $quiz->save();
        $this->getJson('/api/parent/practice/'.$quiz->id, $this->headers)->assertNotFound();
        $this->postJson('/api/parent/practice/'.$quiz->id.'/submit', [], $this->headers)->assertNotFound();
        $this->access->update(['is_active' => false]);
        $this->getJson('/api/parent/practice', $this->headers)->assertUnauthorized();
        $this->postJson('/api/parent/practice/'.$id.'/submit', [], $this->headers)->assertUnauthorized();
        $this->getJson('/api/parent/practice')->assertUnauthorized();
    }

    public function test_unavailable_topics_have_no_quiz_and_new_attempts_are_limited(): void
    {
        $sub = SubStrand::where('name', 'Subtraction')->firstOrFail();
        $report = TermReport::findOrFail($this->access->term_report_id);
        $report->update(['sub_strand_ids' => [$sub->id]]);
        $this->assessment->update(['sub_strand_id' => $sub->id]);
        $this->getJson('/api/parent/practice', $this->headers)->assertOk()->assertJsonCount(0, 'topics');
        $this->postJson('/api/parent/practice', ['sub_strand_id' => $sub->id], $this->headers)->assertNotFound();
        $this->assessment->update(['sub_strand_id' => SubStrand::where('name', 'Whole Numbers')->firstOrFail()->id]);
        $report->update(['sub_strand_ids' => [$this->assessment->sub_strand_id]]);
        for ($i = 0; $i < 5; $i++) {
            $id = $this->start();
            \App\Models\QuizSession::findOrFail($id)->update(['completed_at' => now()]);
        }
        $this->postJson('/api/parent/practice', ['sub_strand_id' => $this->assessment->sub_strand_id], $this->headers)->assertStatus(429);
    }
}
