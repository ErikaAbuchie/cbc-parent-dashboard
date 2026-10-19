<?php

namespace Tests\Feature;

use App\Jobs\SendParentAccessCode;
use App\Models\Assessment;
use App\Models\ParentAccess;
use App\Models\ParentSession;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\SubStrand;
use App\Models\TermReport;
use App\Models\User;
use App\Services\ParentAccessCodeGenerator;
use App\Services\ParentSmsSender;
use Database\Seeders\GradeFiveMathematicsSeeder;
use Database\Seeders\SprintTwoDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\DatabaseQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ParentAccessWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private Student $student;
    private SchoolClass $class;
    private array $headers;
    private array $selection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeTime();
        Queue::fake();
        config(['queue.default' => 'database', 'services.africastalking.enabled' => true,
            'services.africastalking.username' => 'sandbox', 'services.africastalking.key' => 'test-key']);
        $this->mock(ParentAccessCodeGenerator::class)->shouldReceive('generate')->andReturn('000042', '004321');
        $this->teacher = User::factory()->create(['email' => 'teacher@example.com', 'role' => 'teacher']);
        $this->seed([SprintTwoDemoSeeder::class, GradeFiveMathematicsSeeder::class]);
        $this->teacher->refresh();
        $this->class = SchoolClass::firstOrFail();
        $this->student = Student::firstOrFail();
        $this->student->update(['parent_phone_number' => '+254700000000']);
        $this->headers = ['Authorization' => 'Bearer '.$this->teacher->createToken('test')->plainTextToken];
        $sub = SubStrand::firstOrFail();
        $this->selection = ['term' => 'Term 1 2026', 'sub_strand_ids' => [$sub->id]];
        Assessment::create(['student_id' => $this->student->id, 'sub_strand_id' => $sub->id,
            'user_id' => $this->teacher->id, 'term' => 'Term 1 2026', 'competency_level' => 'ME', 'remarks' => 'Simulated parent report']);
    }

    private function finaliseUrl(): string
    {
        return '/api/teacher/classes/'.$this->class->id.'/students/'.$this->student->id.'/finalise-report';
    }

    private function issue(): ParentAccess
    {
        app('auth')->forgetGuards();
        $response = $this->postJson($this->finaliseUrl(), $this->selection, $this->headers)->assertStatus(202)->assertJsonMissingPath('code');
        $this->assertStringNotContainsString('000042', $response->getContent());
        return ParentAccess::latest('id')->firstOrFail();
    }

    private function verify(string $code = '000042', ?string $admission = null)
    {
        app('auth')->forgetGuards();
        return $this->postJson('/api/parent/verify', ['admission_number' => $admission ?? $this->student->admission_number, 'code' => $code]);
    }

    public function test_finalisation_queues_code_stores_only_hash_and_parent_reads_only_linked_report(): void
    {
        $access = $this->issue();
        $this->assertSame(hash('sha256', '000042'), $access->code_hash);
        $this->assertTrue($access->expires_at->equalTo(now()->addDays(30)->startOfSecond()));
        Queue::assertPushed(SendParentAccessCode::class, fn ($job) => $job->accessId === $access->id);
        $token = $this->verify()->assertOk()->json('token');
        $this->assertDatabaseHas('parent_sessions', ['token_hash' => hash('sha256', $token)]);
        $this->assertDatabaseMissing('parent_sessions', ['token_hash' => $token]);
        $other = Student::where('id', '!=', $this->student->id)->firstOrFail();
        Assessment::create(['student_id' => $other->id, 'sub_strand_id' => $this->selection['sub_strand_ids'][0], 'user_id' => $this->teacher->id,
            'term' => 'Term 1 2026', 'competency_level' => 'BE', 'remarks' => 'Other learner private remark']);
        $this->getJson('/api/parent/report?student_id='.$other->id, ['Authorization' => 'Bearer '.$token])
            ->assertOk()->assertJsonPath('student.admission_number', $this->student->admission_number)
            ->assertJsonCount(1, 'assessments')->assertJsonMissingPath('student.parent_phone_number')
            ->assertJsonMissingPath('code_hash')->assertDontSee('Other learner private remark');
    }

    public function test_completeness_is_checked_per_learner_teacher_term_and_grade(): void
    {
        $this->selection['sub_strand_ids'][] = SubStrand::orderBy('id')->skip(1)->first()->id;
        $this->postJson($this->finaliseUrl(), $this->selection, $this->headers)->assertUnprocessable()->assertJsonValidationErrors('sub_strand_ids');
        $this->assertDatabaseCount('parent_access', 0);
        $this->assertDatabaseCount('term_reports', 0);
        $this->postJson('/api/teacher/classes/'.$this->class->id.'/report-readiness', $this->selection, $this->headers)
            ->assertOk()->assertJsonCount(1, 'students.0.outstanding');
        $this->selection['sub_strand_ids'] = [999999];
        $this->postJson($this->finaliseUrl(), $this->selection, $this->headers)->assertUnprocessable();
        Queue::assertNothingPushed();
    }

    public function test_unassigned_teacher_and_missing_parent_phone_or_sms_configuration_cannot_issue_codes(): void
    {
        $this->teacher->assignedClasses()->detach($this->class->id);
        $this->postJson($this->finaliseUrl(), $this->selection, $this->headers)->assertNotFound();
        $this->teacher->assignedClasses()->attach($this->class->id);
        $this->student->update(['parent_phone_number' => 'DEMO-NO-SMS']);
        $this->postJson($this->finaliseUrl(), $this->selection, $this->headers)->assertUnprocessable();
        $this->student->update(['parent_phone_number' => '+254700000000']);
        config(['services.africastalking.enabled' => false]);
        $this->postJson($this->finaliseUrl(), $this->selection, $this->headers)->assertStatus(503);
        $this->assertDatabaseCount('parent_access', 0);
    }

    public function test_invalid_expired_codes_and_removed_learners_are_rejected_generically(): void
    {
        $access = $this->issue();
        $expected = ['message' => 'The admission number or access code is invalid or expired.'];
        $this->verify('999999')->assertUnauthorized()->assertExactJson($expected);
        $this->verify('000042', 'UNKNOWN')->assertUnauthorized()->assertExactJson($expected);
        $access->update(['expires_at' => now()->subSecond()]);
        $this->verify()->assertUnauthorized()->assertExactJson($expected);
        $access->update(['expires_at' => now()->addDay()]);
        $this->student->delete();
        $this->verify()->assertUnauthorized()->assertExactJson($expected);
    }

    public function test_five_failed_attempts_lock_ip_and_success_clears_consecutive_failures(): void
    {
        $this->issue();
        for ($i = 0; $i < 4; $i++) $this->verify('999999')->assertUnauthorized();
        $this->verify()->assertOk();
        for ($i = 0; $i < 5; $i++) $this->verify('999999')->assertUnauthorized();
        $this->verify()->assertStatus(429);
        $this->travel(11)->minutes();
        $this->verify()->assertOk();
    }

    public function test_replacement_invalidates_old_codes_and_existing_sessions(): void
    {
        $first = $this->issue();
        $token = $this->verify()->assertOk()->json('token');
        $this->postJson($this->finaliseUrl(), $this->selection, $this->headers)->assertStatus(429);
        $this->travel(61)->seconds();
        $this->issue();
        $this->assertFalse($first->fresh()->is_active);
        $this->verify()->assertUnauthorized();
        $this->verify('004321')->assertOk();
        $this->getJson('/api/parent/report', ['Authorization' => 'Bearer '.$token])->assertUnauthorized();
        $this->assertDatabaseCount('term_reports', 1);
    }

    public function test_parent_token_expires_logout_revokes_and_teacher_token_cannot_read_parent_report(): void
    {
        $this->issue();
        $token = $this->verify()->assertOk()->json('token');
        $headers = ['Authorization' => 'Bearer '.$token];
        $this->getJson('/api/parent/report', $headers)->assertOk();
        $this->postJson('/api/parent/logout', [], $headers)->assertOk();
        $this->getJson('/api/parent/report', $headers)->assertUnauthorized();
        $this->getJson('/api/parent/report', $this->headers)->assertUnauthorized();
        $token = $this->verify()->assertOk()->json('token');
        $this->travel(61)->minutes();
        $this->getJson('/api/parent/report', ['Authorization' => 'Bearer '.$token])->assertUnauthorized();
    }

    public function test_changing_parent_contact_or_removing_learner_revokes_existing_parent_access(): void
    {
        $this->issue();
        $token = $this->verify()->assertOk()->json('token');
        app('auth')->forgetGuards();
        $this->patchJson('/api/teacher/classes/'.$this->class->id.'/students/'.$this->student->id,
            ['full_name' => $this->student->full_name, 'admission_number' => $this->student->admission_number,
                'parent_phone_number' => '+254711111111'], $this->headers)->assertOk();
        $this->getJson('/api/parent/report', ['Authorization' => 'Bearer '.$token])->assertUnauthorized();
        $this->verify()->assertUnauthorized();
    }

    public function test_job_sends_via_fake_sender_marks_acceptance_and_never_resends_accepted_job(): void
    {
        $access = $this->issue();
        $sender = \Mockery::mock(ParentSmsSender::class);
        $sender->shouldReceive('send')->once()->with('+254700000000', \Mockery::on(fn ($message) => str_contains($message, '000042') && str_contains($message, '/parent/login')));
        $job = new SendParentAccessCode($access->id, '000042');
        $job->handle($sender);
        $job->handle($sender);
        $this->assertSame('accepted', $access->fresh()->sms_status);
        $this->assertSame(1, $access->fresh()->sms_attempts);
    }

    public function test_sms_failure_is_sanitised_and_terminal_failure_deactivates_code(): void
    {
        $access = $this->issue();
        $sender = \Mockery::mock(ParentSmsSender::class);
        $sender->shouldReceive('send')->once()->andThrow(new \RuntimeException('Sensitive provider body 000042'));
        $job = new SendParentAccessCode($access->id, '000042');
        try { $job->handle($sender); $this->fail('Expected safe job error.'); }
        catch (\RuntimeException $error) { $this->assertStringNotContainsString('000042', $error->getMessage()); }
        $this->assertSame('retrying', $access->fresh()->sms_status);
        $job->failed(new \RuntimeException('Failure'));
        $this->assertSame('failed', $access->fresh()->sms_status);
        $this->assertFalse($access->fresh()->is_active);
        $this->verify()->assertUnauthorized();
    }

    public function test_queued_job_payload_is_encrypted_and_stale_jobs_do_not_send(): void
    {
        $access = $this->issue();
        $queue = new DatabaseQueue(DB::connection(), 'jobs');
        $queue->setContainer($this->app);
        $queue->push(new SendParentAccessCode($access->id, '000042'));
        $payload = DB::table('jobs')->value('payload');
        $this->assertStringNotContainsString('000042', $payload);
        $encrypted = json_decode($payload, true)['data']['command'];
        $this->assertStringContainsString('000042', app('encrypter')->decrypt($encrypted));
        $access->update(['is_active' => false]);
        $sender = \Mockery::mock(ParentSmsSender::class);
        $sender->shouldNotReceive('send');
        (new SendParentAccessCode($access->id, '000042'))->handle($sender);
    }
}
