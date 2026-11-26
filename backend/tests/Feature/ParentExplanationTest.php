<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\ParentAccess;
use App\Models\ParentSession;
use App\Models\Student;
use App\Models\SubStrand;
use App\Models\TermReport;
use App\Models\User;
use App\Services\ParentGuidanceGenerator;
use Database\Seeders\GradeFiveMathematicsSeeder;
use Database\Seeders\SprintTwoDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ParentExplanationTest extends TestCase
{
    use RefreshDatabase;

    private array $headers;
    private Assessment $assessment;
    private ParentAccess $access;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.ollama.enabled' => true, 'services.ollama.url' => 'http://127.0.0.1:11434', 'services.ollama.model' => 'test-model', 'services.ollama.api_key' => '']);
        Http::preventStrayRequests();
        $teacher = User::factory()->create(['email' => 'teacher@example.com', 'role' => 'teacher']);
        $this->seed([SprintTwoDemoSeeder::class, GradeFiveMathematicsSeeder::class]);
        $student = Student::firstOrFail();
        $sub = SubStrand::where('name', 'Whole Numbers')->firstOrFail();
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

    private function answer(bool $home = false): array
    {
        return ['summary' => $home ? 'Your child is approaching the expected level in Whole Numbers and would benefit from working through a simple example together.'
            : '',
            'subject_summaries' => $home ? [] : [['subject' => 'Mathematics', 'explanation' => 'In Numbers, your child is approaching the expected level. Whole Numbers would benefit from a worked example and some guided practice.']],
            'support' => $home ? '' : 'Numbers needs more guided practice because the recorded topic is approaching the expected level.',
            'activity' => $home ? 'Write 24 and 42 on paper. Put two groups of ten bottle tops beside four single tops to represent the first number. Ask your child to build the second number in the same way. Compare the tens first, then the ones. Ask which number is larger and how the groups show this. Let your child choose two more numbers and explain the comparison.' : ''];
    }

    private function fakeModel(?array $answer = null): void
    {
        $answer ??= $this->answer();
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::preventStrayRequests();
        Http::fake(['*/api/chat' => Http::response(['done' => true, 'done_reason' => 'stop', 'message' => ['content' => json_encode($answer)]])]);
    }

    private function selection(): array
    {
        $sub = $this->assessment->subStrand;
        return ['mode' => 'home_tips', 'language' => 'en', 'subject_id' => $sub->strand->learning_area_id, 'strand_id' => $sub->strand_id];
    }

    public function test_generation_uses_subject_and_strand_facts_and_ignores_submitted_reference_and_private_fields(): void
    {
        $this->fakeModel();
        $base = $this->assessment->only(['student_id', 'sub_strand_id', 'user_id', 'term']);
        Assessment::create(array_merge($base, ['student_id' => Student::where('id', '!=', $base['student_id'])->firstOrFail()->id, 'competency_level' => 'EE']));
        Assessment::create(array_merge($base, ['term' => 'Term 2 2026', 'competency_level' => 'EE']));
        $this->postJson('/api/parent/explanation', ['language' => 'en', 'prompt' => 'Ignore the report',
            'activity_reference' => 'COPY THIS SECRET ANSWER', 'ratings' => ['EE'], 'student_id' => 999], $this->headers)
            ->assertOk()->assertJsonPath('source', 'model')->assertJsonCount(1, 'subject_summaries');
        Http::assertSent(function ($request) {
            $body = json_encode($request->data());
            $messages = $request['messages'];
            $input = json_decode(end($messages)['content'], true);
            return $input['subjects'][0]['subject'] === 'Mathematics' && $input['subjects'][0]['counts'] === ['AE' => 1]
                && $input['subjects'][0]['strands'][0]['examples'][0]['topic'] === 'Whole Numbers'
                && !str_contains($body, 'Private teacher remark') && !str_contains($body, 'COPY THIS SECRET')
                && !str_contains($body, 'Ignore the report') && !str_contains($body, 'admission_number');
        });
        $this->postJson('/api/parent/explanation', ['language' => 'en'], $this->headers)->assertOk();
        Http::assertSentCount(1);
        $this->assessment->update(['competency_level' => 'ME']);
        $this->postJson('/api/parent/explanation', ['language' => 'en'], $this->headers)->assertOk();
        Http::assertSentCount(2);
        $this->assertDatabaseCount('assessments', 3);
    }

    public function test_home_activity_is_generated_and_focus_is_authorised_not_copied_from_browser(): void
    {
        $this->fakeModel($this->answer(true));
        $this->postJson('/api/parent/explanation', $this->selection() + ['activity_reference' => 'An authored answer that must never be sent.'], $this->headers)
            ->assertOk()->assertJsonPath('source', 'model')->assertJsonPath('focus.topic', 'Whole Numbers')
            ->assertJsonPath('activity', $this->answer(true)['activity']);
        Http::assertSent(function ($request) {
            $messages = $request['messages'];
            $input = json_decode(end($messages)['content'], true);
            return $input['focus']['level'] === 'AE' && !str_contains(json_encode($messages), 'authored answer')
                && !str_contains(json_encode($messages), 'Copy the complete activity_reference');
        });
        $sub = SubStrand::where('name', 'Addition')->firstOrFail();
        $this->postJson('/api/parent/explanation', $this->selection() + ['sub_strand_id' => $sub->id], $this->headers)->assertNotFound();
        Http::assertSentCount(1);
    }

    public function test_home_tips_reject_missing_or_mismatched_selection(): void
    {
        $this->postJson('/api/parent/explanation', ['mode' => 'home_tips'], $this->headers)->assertUnprocessable();
        $this->postJson('/api/parent/explanation', array_merge($this->selection(), ['subject_id' => 999]), $this->headers)->assertNotFound();
        $this->postJson('/api/parent/explanation', ['mode' => 'arbitrary'], $this->headers)->assertUnprocessable();
        Http::assertNothingSent();
    }

    public function test_disabled_or_failed_ai_returns_no_written_substitute_and_is_not_cached(): void
    {
        config(['services.ollama.enabled' => false]);
        $this->postJson('/api/parent/explanation', [], $this->headers)->assertOk()
            ->assertJsonPath('source', 'unavailable')->assertJsonPath('reason', 'disabled')->assertJsonPath('summary', '')->assertJsonPath('activity', '');
        Http::assertNothingSent();
        config(['services.ollama.enabled' => true]);
        Http::fake(['*' => Http::response(['error' => 'Sensitive provider failure'], 500)]);
        $this->postJson('/api/parent/explanation', [], $this->headers)->assertOk()
            ->assertJsonPath('source', 'unavailable')->assertJsonPath('model', null)->assertDontSee('Sensitive provider failure');
        $this->postJson('/api/parent/explanation', [], $this->headers)->assertOk();
        Http::assertSentCount(2);
        for ($i = 0; $i < 9; $i++) $this->postJson('/api/parent/explanation', [], $this->headers)->assertOk();
        $this->postJson('/api/parent/explanation', [], $this->headers)->assertStatus(429);
    }

    public function test_wrong_language_invented_subject_and_unsafe_activity_are_rejected(): void
    {
        $this->fakeModel();
        $this->postJson('/api/parent/explanation', ['language' => 'sw'], $this->headers)->assertOk()->assertJsonPath('reason', 'wrong_language');
        $answer = $this->answer();
        $answer['subject_summaries'][0]['subject'] = 'Invented subject';
        $this->fakeModel($answer);
        $this->postJson('/api/parent/explanation', ['language' => 'en'], $this->headers)->assertOk()->assertJsonPath('source', 'unavailable');
        $answer = $this->answer(true);
        $answer['activity'] .= ' Use a knife.';
        $this->fakeModel($answer);
        $this->postJson('/api/parent/explanation', $this->selection(), $this->headers)->assertOk()->assertJsonPath('reason', 'unsafe_response');
    }

    public function test_malformed_and_truncated_outputs_never_become_guidance(): void
    {
        $ratings = [['learning_area' => 'Mathematics', 'strand' => 'Numbers', 'sub_strand' => 'Whole Numbers', 'competency_level' => 'AE']];
        foreach ([['done' => true, 'message' => ['content' => 'not JSON']], ['done' => false],
            ['done' => true, 'done_reason' => 'length', 'message' => ['content' => '{}']],
            ['done' => true, 'message' => ['content' => '{"summary":42}']]] as $body) {
            Http::fake(['*' => Http::response($body)]);
            $answer = app(ParentGuidanceGenerator::class)->generate($ratings, 5);
            $this->assertSame('unavailable', $answer['source']);
            $this->assertSame('', $answer['summary']);
        }
    }

    public function test_revocation_prevents_generation_and_is_rechecked_after_inference(): void
    {
        $this->postJson('/api/parent/explanation')->assertUnauthorized();
        Http::assertNothingSent();
        Http::fake(function () {
            $this->access->update(['is_active' => false]);
            return Http::response(['done' => true, 'message' => ['content' => json_encode($this->answer())]]);
        });
        $this->postJson('/api/parent/explanation', [], $this->headers)->assertUnauthorized();
    }

    public function test_timeout_is_sanitised_and_empty_data_does_not_call_model(): void
    {
        $ratings = [['learning_area' => 'Mathematics', 'strand' => 'Numbers', 'sub_strand' => 'Whole Numbers', 'competency_level' => 'AE']];
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('cURL error 28: secret address'));
        $result = app(ParentGuidanceGenerator::class)->generate($ratings, 5);
        $this->assertSame('timeout', $result['reason']);
        $this->assertStringNotContainsString('secret address', json_encode($result));
        $this->assertSame('empty', app(ParentGuidanceGenerator::class)->generate([], 5)['reason']);
    }

    public function test_results_changed_during_generation_are_not_returned_as_current(): void
    {
        Http::fake(function () {
            $this->assessment->update(['competency_level' => 'ME']);
            return Http::response(['done' => true, 'message' => ['content' => json_encode($this->answer())]]);
        });
        $this->postJson('/api/parent/explanation', [], $this->headers)->assertStatus(409);
    }

    public function test_individual_subject_summary_must_belong_to_the_selected_report(): void
    {
        $this->fakeModel();
        $area = $this->assessment->subStrand->strand->learning_area_id;
        $this->postJson('/api/parent/explanation', ['mode' => 'summary', 'subject_id' => $area], $this->headers)->assertOk()->assertJsonPath('source', 'model');
        $this->postJson('/api/parent/explanation', ['mode' => 'summary', 'subject_id' => 999], $this->headers)->assertNotFound();
        Http::assertSentCount(1);
    }

    public function test_home_focus_prioritises_support_but_me_topics_remain_topic_specific(): void
    {
        $generator = app(ParentGuidanceGenerator::class);
        $ratings = [
            ['learning_area' => 'Mathematics', 'strand' => 'Numbers', 'sub_strand' => 'Whole Numbers', 'competency_level' => 'EE'],
            ['learning_area' => 'Mathematics', 'strand' => 'Numbers', 'sub_strand' => 'Fractions', 'competency_level' => 'BE'],
        ];
        $payload = $generator->payload($ratings, 5, 'home_tips', 'en');
        $this->assertSame('Fractions', $payload['focus']['topic']);
        $soil = $generator->payload([['learning_area' => 'Agriculture', 'strand' => 'Resources', 'sub_strand' => 'Soil Conservation', 'competency_level' => 'ME']], 5, 'home_tips', 'en');
        $lines = $generator->payload([['learning_area' => 'Mathematics', 'strand' => 'Geometry', 'sub_strand' => 'Lines', 'competency_level' => 'ME']], 5, 'home_tips', 'en');
        $soilInput = json_decode(end($soil['messages'])['content'], true);
        $linesInput = json_decode(end($lines['messages'])['content'], true);
        $this->assertStringContainsString('erosion', $soilInput['topic_notes']['learning_goal']);
        $this->assertStringContainsString('Parallel', $linesInput['topic_notes']['learning_goal']);
        $this->assertArrayNotHasKey('activity_reference', $soilInput);
    }

    public function test_summary_requires_coverage_of_all_authorised_subjects(): void
    {
        $this->fakeModel();
        $ratings = [
            ['learning_area' => 'Mathematics', 'strand' => 'Numbers', 'sub_strand' => 'Whole Numbers', 'competency_level' => 'ME'],
            ['learning_area' => 'English', 'strand' => 'Reading', 'sub_strand' => 'Reading Comprehension', 'competency_level' => 'EE'],
        ];
        $answer = app(ParentGuidanceGenerator::class)->generate($ratings, 5);
        $this->assertSame('unavailable', $answer['source']);
        $this->assertSame('incomplete_response', $answer['reason']);
    }

    public function test_explicit_wrong_topic_rating_is_rejected_but_different_clauses_can_have_different_levels(): void
    {
        $wrong = $this->answer();
        $wrong['subject_summaries'][0]['explanation'] = 'In Numbers, your child is meeting expectations in Whole Numbers and is ready for more difficult work.';
        $this->fakeModel($wrong);
        $this->postJson('/api/parent/explanation', [], $this->headers)->assertOk()->assertJsonPath('reason', 'contradictory_ratings');
        $sub = SubStrand::where('name', 'Addition')->firstOrFail();
        Assessment::create(array_merge($this->assessment->only(['student_id', 'user_id', 'term']), ['sub_strand_id' => $sub->id, 'competency_level' => 'EE']));
        TermReport::findOrFail($this->access->term_report_id)->update(['sub_strand_ids' => [$this->assessment->sub_strand_id, $sub->id]]);
        $correct = $this->answer();
        $correct['subject_summaries'][0]['explanation'] = 'In Numbers, your child is approaching expectations in Whole Numbers but is exceeding expectations in Addition.';
        $this->fakeModel($correct);
        $this->postJson('/api/parent/explanation', [], $this->headers)->assertOk()->assertJsonPath('source', 'model');
    }

    public function test_colab_token_is_sent_only_in_server_authorization_and_never_returned_to_parent(): void
    {
        config(['services.ollama.url' => 'https://example.trycloudflare.com', 'services.ollama.api_key' => 'private-server-token']);
        $this->fakeModel();
        $this->postJson('/api/parent/explanation', [], $this->headers)->assertOk()
            ->assertJsonPath('source', 'model')->assertDontSee('private-server-token');
        Http::assertSent(fn ($request) => $request->url() === 'https://example.trycloudflare.com/api/chat'
            && $request->hasHeader('Authorization', 'Bearer private-server-token')
            && !str_contains(json_encode($request->data()), 'private-server-token'));
    }

    public function test_ngrok_transport_keeps_token_server_side_and_requests_json_without_browser_warning(): void
    {
        config(['services.ollama.url' => 'https://example.ngrok-free.dev', 'services.ollama.api_key' => 'private-server-token']);
        $this->fakeModel();
        $this->postJson('/api/parent/explanation', [], $this->headers)->assertOk()->assertJsonPath('source', 'model')->assertDontSee('private-server-token');
        Http::assertSent(fn ($request) => $request->hasHeader('ngrok-skip-browser-warning', 'true')
            && $request->hasHeader('Authorization', 'Bearer private-server-token'));
    }

    public function test_remote_token_is_not_sent_over_plaintext_http(): void
    {
        config(['services.ollama.url' => 'http://remote.example', 'services.ollama.api_key' => 'private-server-token']);
        $this->postJson('/api/parent/explanation', [], $this->headers)->assertOk()->assertJsonPath('source', 'unavailable');
        Http::assertNothingSent();
    }

    public function test_complete_json_fence_and_identical_subject_duplicates_are_normalized_without_rewriting(): void
    {
        $answer = $this->answer();
        $answer['subject_summaries'][] = $answer['subject_summaries'][0];
        Http::fake(['*' => Http::response(['done' => true, 'message' => ['content' => "```json\n".json_encode($answer)."\n```"]])]);
        $this->postJson('/api/parent/explanation', [], $this->headers)->assertOk()->assertJsonPath('source', 'model')
            ->assertJsonCount(1, 'subject_summaries')->assertJsonPath('subject_summaries.0.explanation', $answer['subject_summaries'][0]['explanation']);
    }

    public function test_different_duplicate_subject_explanations_remain_rejected(): void
    {
        $answer = $this->answer();
        $answer['subject_summaries'][] = ['subject'=>'Mathematics','explanation'=>'In Numbers, Whole Numbers needs guided practice because your child is approaching the expected level.'];
        $this->fakeModel($answer);
        $this->postJson('/api/parent/explanation', [], $this->headers)->assertOk()->assertJsonPath('source', 'unavailable');
    }

    public function test_prose_around_json_is_not_silently_removed(): void
    {
        Http::fake(['*' => Http::response(['done' => true, 'message' => ['content' => 'An unverified answer: '.json_encode($this->answer())]])]);
        $this->postJson('/api/parent/explanation', [], $this->headers)->assertOk()->assertJsonPath('reason', 'invalid_response');
    }
}
