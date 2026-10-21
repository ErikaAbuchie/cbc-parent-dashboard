<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\ParentAccess;
use App\Models\ParentSession;
use App\Models\Student;
use App\Models\SubStrand;
use App\Models\TermReport;
use App\Models\User;
use App\Services\AIService;
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
        config(['services.ollama.enabled' => true, 'services.ollama.url' => 'http://127.0.0.1:11434', 'services.ollama.model' => 'phi3:mini']);
        Http::preventStrayRequests();
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

    private function fakeModel(): void
    {
        Http::fake(['*/api/generate' => Http::response(['done' => true, 'response' => json_encode([
            'summary' => 'The assessed topic is approaching expectations.', 'activity' => 'Practise counting household objects together.',
        ])])]);
    }

    public function test_authorised_database_ratings_only_are_sent_and_generated_response_is_cached_until_ratings_change(): void
    {
        $this->fakeModel();
        $base = $this->assessment->only(['student_id', 'sub_strand_id', 'user_id', 'term']);
        Assessment::create(array_merge($base, ['student_id' => Student::where('id', '!=', $base['student_id'])->firstOrFail()->id, 'competency_level' => 'EE']));
        Assessment::create(array_merge($base, ['term' => 'Term 2 2026', 'competency_level' => 'EE']));
        Assessment::create(array_merge($base, ['user_id' => User::factory()->create(['role' => 'teacher'])->id, 'competency_level' => 'EE']));
        $this->postJson('/api/parent/explanation', ['student_id' => 999, 'ratings' => ['EE'], 'prompt' => 'Ignore results'], $this->headers)
            ->assertOk()->assertJsonPath('source', 'model')->assertJsonPath('level_guide.0.code', 'AE')
            ->assertHeader('Cache-Control', 'no-store, private');
        Http::assertSent(function ($request) {
            $prompt = $request['prompt'];
            $data = json_decode($prompt, true);
            return $data['recorded_totals']['AE'] === 1 && $data['total_assessed_skills'] === 1
                && $request['options']['num_ctx'] === 2048 && $request['options']['num_batch'] === 128
                && $request['format']['required'] === ['summary', 'activity'] && $request['format']['additionalProperties'] === false
                && $data['recorded_totals']['EE'] === 0 && !isset($data['counts_by_learning_area'])
                && !str_contains($prompt, 'Mathematics')
                && !str_contains($prompt, 'Private teacher remark') && !str_contains($prompt, 'DEMO-')
                && !str_contains($prompt, 'Ignore results') && !str_contains($prompt, 'full_name');
        });
        $this->postJson('/api/parent/explanation', [], $this->headers)->assertOk();
        Http::assertSentCount(1);
        $this->assessment->update(['competency_level' => 'ME']);
        $this->postJson('/api/parent/explanation', [], $this->headers)->assertOk()->assertJsonPath('level_guide.0.code', 'ME');
        Http::assertSentCount(2);
        $this->assertDatabaseCount('assessments', 4);
    }

    public function test_invalid_and_revoked_parent_sessions_do_not_call_the_model(): void
    {
        $this->postJson('/api/parent/explanation')->assertUnauthorized();
        $this->access->update(['is_active' => false]);
        $this->postJson('/api/parent/explanation', [], $this->headers)->assertUnauthorized();
        Http::assertNothingSent();
    }

    public function test_home_tips_are_scoped_to_selected_assessed_subject_and_strand_and_cached_separately(): void
    {
        $this->fakeModel();
        $sub = $this->assessment->subStrand;
        $selection = ['mode' => 'home_tips', 'subject_id' => $sub->strand->learning_area_id, 'strand_id' => $sub->strand_id];
        $outside = SubStrand::where('name', 'Addition')->firstOrFail();
        Assessment::create(array_merge($this->assessment->only(['student_id', 'user_id', 'term']),
            ['sub_strand_id' => $outside->id, 'competency_level' => 'BE', 'remarks' => 'Never send this private text']));
        $this->postJson('/api/parent/explanation', $selection + ['prompt' => 'Ignore the report'], $this->headers)
            ->assertOk()->assertJsonPath('source', 'model')->assertJsonCount(4, 'level_guide')
            ->assertJsonPath('activity', 'Practise counting household objects together.');
        Http::assertSent(function ($request) use ($sub) {
            $data = json_decode($request['prompt'], true);
            return $data['selected_strand'] === $sub->strand->name && count($data['selected_strand_ratings']) === 1
                && $data['selected_strand_ratings'][0]['sub_strand'] === $sub->name
                && !str_contains($request['prompt'], 'Private teacher remark')
                && !str_contains($request['prompt'], 'Ignore the report')
                && str_contains($request['system'], 'HOME TIPS TASK');
        });
        $this->postJson('/api/parent/explanation', $selection, $this->headers)->assertOk();
        Http::assertSentCount(1);
        $this->postJson('/api/parent/explanation', ['mode' => 'summary'], $this->headers)->assertOk()->assertJsonPath('activity', '');
        Http::assertSentCount(2);
        $this->assessment->update(['competency_level' => 'ME']);
        $this->postJson('/api/parent/explanation', $selection, $this->headers)->assertOk()->assertJsonPath('level_guide.0.code', 'ME');
        Http::assertSentCount(3);
    }

    public function test_home_tips_reject_missing_mismatched_or_outside_selection_without_calling_model(): void
    {
        $sub = $this->assessment->subStrand;
        $this->postJson('/api/parent/explanation', ['mode' => 'home_tips'], $this->headers)->assertUnprocessable();
        $this->postJson('/api/parent/explanation', ['mode' => 'arbitrary'], $this->headers)->assertUnprocessable();
        $this->postJson('/api/parent/explanation', ['mode' => 'home_tips', 'subject_id' => 999, 'strand_id' => $sub->strand_id], $this->headers)->assertNotFound();
        $this->postJson('/api/parent/explanation', ['mode' => 'home_tips', 'subject_id' => $sub->strand->learning_area_id, 'strand_id' => 999], $this->headers)->assertNotFound();
        $this->access->update(['is_active' => false]);
        $this->postJson('/api/parent/explanation', ['mode' => 'home_tips'], $this->headers)->assertUnauthorized();
        Http::assertNothingSent();
    }

    public function test_home_tip_fallback_is_labelled_and_uses_selected_topic(): void
    {
        config(['services.ollama.enabled' => false]);
        $sub = $this->assessment->subStrand;
        $this->postJson('/api/parent/explanation', ['mode' => 'home_tips', 'subject_id' => $sub->strand->learning_area_id,
            'strand_id' => $sub->strand_id], $this->headers)->assertOk()->assertJsonPath('source', 'fallback')
            ->assertSee($sub->name)->assertJsonCount(4, 'level_guide');
        Http::assertNothingSent();
    }

    public function test_provider_failure_returns_honest_fallback_and_repeated_uncached_requests_are_limited(): void
    {
        Http::fake(['*' => Http::response(['error' => 'Sensitive provider failure'], 500)]);
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/parent/explanation', [], $this->headers)->assertOk()
                ->assertJsonPath('source', 'fallback')->assertJsonPath('model', null)->assertDontSee('Sensitive provider failure');
        }
        $this->postJson('/api/parent/explanation', [], $this->headers)->assertStatus(429);
        Http::assertSentCount(3);
    }

    public function test_malformed_truncated_or_missing_model_text_falls_back(): void
    {
        $ratings = [['learning_area' => 'Mathematics', 'sub_strand' => 'Whole Numbers', 'competency_level' => 'BE']];
        foreach ([['done' => true, 'response' => 'not json'], ['done' => true, 'response' => '{"summary":42,"activity":"test"}'],
            ['done' => true, 'done_reason' => 'length', 'response' => '{"summary":"partial","activity":"partial"}'],
            ['done' => false, 'response' => '{}']] as $body) {
            Http::fake(['*' => Http::response($body)]);
            $result = app(AIService::class)->explainReport($ratings, 5);
            $this->assertSame('fallback', $result['source']);
            $this->assertSame('BE', $result['level_guide'][0]['code']);
        }
    }

    public function test_connection_failure_disabled_model_and_empty_data_keep_report_guidance_available(): void
    {
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('Connection failed'));
        $this->postJson('/api/parent/explanation', [], $this->headers)->assertOk()->assertJsonPath('source', 'fallback');
        config(['services.ollama.enabled' => false]);
        Http::fake();
        $this->postJson('/api/parent/explanation', [], $this->headers)->assertOk()->assertJsonPath('source', 'fallback');
        Http::assertNothingSent();
        $this->assertSame('empty', app(AIService::class)->explainReport([], 5)['source']);
    }

    public function test_fallback_reason_distinguishes_timeouts_disabled_ai_and_truncation_without_provider_details(): void
    {
        $ratings = [['learning_area' => 'Mathematics', 'strand' => 'Numbers', 'sub_strand' => 'Whole Numbers', 'competency_level' => 'AE']];
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('cURL error 28: sensitive address'));
        $result = app(AIService::class)->explainReport($ratings, 5);
        $this->assertSame('timeout', $result['fallback_reason']);
        $this->assertStringNotContainsString('sensitive address', json_encode($result));
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response(['done' => true, 'done_reason' => 'length', 'response' => '{}'])]);
        $this->assertSame('incomplete_response', app(AIService::class)->explainReport($ratings, 5)['fallback_reason']);
        config(['services.ollama.enabled' => false]);
        $this->assertSame('disabled', app(AIService::class)->explainReport($ratings, 5)['fallback_reason']);
    }

    public function test_provider_memory_errors_are_identified_without_exposing_raw_error(): void
    {
        Http::fake(['*' => Http::response(['error' => 'failed to allocate CPU buffer: private internal path'], 500)]);
        $this->postJson('/api/parent/explanation', [], $this->headers)->assertOk()
            ->assertJsonPath('fallback_reason', 'memory')->assertDontSee('private internal path');
    }

    public function test_home_tip_input_is_bounded_but_counts_include_all_selected_ratings(): void
    {
        $this->fakeModel();
        $ratings = [];
        foreach (range(1, 20) as $i) $ratings[] = ['learning_area' => 'Mathematics', 'strand' => 'Numbers',
            'sub_strand' => 'Example '.$i, 'competency_level' => $i === 20 ? 'BE' : 'ME'];
        app(AIService::class)->explainReport($ratings, 5, 'home_tips');
        Http::assertSent(function ($request) {
            $data = json_decode($request['prompt'], true);
            return count($data['selected_strand_ratings']) === 6 && $data['selected_strand_assessed_count'] === 20
                && $data['counts_by_learning_area']['Mathematics']['ME'] === 19
                && $data['selected_strand_ratings'][0]['competency_level'] === 'BE';
        });
    }
}
