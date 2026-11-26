<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentRevision;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\SubStrand;
use App\Models\User;
use Database\Seeders\GradeFiveMathematicsSeeder;
use Database\Seeders\SprintTwoDemoSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AssessmentRevisionTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private SchoolClass $class;
    private Student $student;
    private array $headers;
    private array $payload;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacher = User::factory()->create(['email' => 'teacher@example.com', 'role' => 'teacher']);
        $this->seed([SprintTwoDemoSeeder::class, GradeFiveMathematicsSeeder::class]);
        $this->teacher->refresh();
        $this->class = SchoolClass::firstOrFail();
        $this->student = Student::firstOrFail();
        $this->headers = ['Authorization' => 'Bearer '.$this->teacher->createToken('test')->plainTextToken];
        $this->payload = ['term' => 'Term 1 2026', 'sub_strand_id' => SubStrand::firstOrFail()->id,
            'assessments' => [['student_id' => $this->student->id, 'competency_level' => 'ME', 'remarks' => 'First version']]];
    }

    private function url(): string
    {
        return '/api/teacher/classes/'.$this->class->id.'/assessments';
    }

    public function test_initial_save_and_changes_preserve_snapshots_but_retries_do_not_add_versions(): void
    {
        $this->postJson($this->url(), $this->payload, $this->headers)->assertOk();
        $this->postJson($this->url(), $this->payload, $this->headers)->assertOk();
        $this->assertDatabaseCount('assessment_revisions', 1);
        $this->payload['assessments'][0]['competency_level'] = 'EE';
        $this->postJson($this->url(), $this->payload, $this->headers)->assertOk();
        $this->payload['assessments'][0]['remarks'] = 'Updated remark';
        $this->postJson($this->url(), $this->payload, $this->headers)->assertOk();
        $this->assertDatabaseCount('assessments', 1);
        $this->assertDatabaseCount('assessment_revisions', 3);
        $this->assertDatabaseHas('assessment_revisions', ['version' => 1, 'event' => 'created', 'competency_level' => 'ME', 'remarks' => 'First version']);
        $this->assertDatabaseHas('assessment_revisions', ['version' => 2, 'competency_level' => 'EE', 'remarks' => 'First version']);
        $this->getJson($this->url().'/'.Assessment::first()->id.'/history', $this->headers)
            ->assertOk()->assertJsonCount(3, 'revisions')->assertJsonPath('revisions.0.version', 3)
            ->assertJsonPath('revisions.0.remarks', 'Updated remark')->assertJsonPath('revisions.0.user_id', $this->teacher->id);
    }

    public function test_invalid_batch_adds_neither_records_nor_revisions(): void
    {
        $this->payload['assessments'][] = ['student_id' => 99999, 'competency_level' => 'INVALID'];
        $this->postJson($this->url(), $this->payload, $this->headers)->assertUnprocessable();
        $this->assertDatabaseCount('assessments', 0);
        $this->assertDatabaseCount('assessment_revisions', 0);
    }

    public function test_history_requires_assignment_and_record_ownership_and_survives_student_removal(): void
    {
        $this->postJson($this->url(), $this->payload, $this->headers)->assertOk();
        $url = $this->url().'/'.Assessment::first()->id.'/history';
        // Each simulated caller needs a fresh guard within this reused test application.
        app('auth')->forgetGuards();
        $this->getJson($url)->assertUnauthorized();
        $other = User::factory()->create(['role' => 'teacher', 'school_id' => $this->teacher->school_id]);
        $other->assignedClasses()->attach($this->class->id);
        app('auth')->forgetGuards();
        $this->getJson($url, ['Authorization' => 'Bearer '.$other->createToken('test')->plainTextToken])->assertNotFound();
        $this->student->delete();
        app('auth')->forgetGuards();
        $this->getJson($url, $this->headers)->assertOk()->assertJsonCount(1, 'revisions');
        $this->teacher->assignedClasses()->detach($this->class->id);
        $this->getJson($url, $this->headers)->assertNotFound();
    }

    public function test_database_blocks_modifying_or_deleting_revision_rows(): void
    {
        $this->postJson($this->url(), $this->payload, $this->headers)->assertOk();
        foreach (['update', 'delete'] as $operation) {
            try {
                if ($operation === 'update') DB::table('assessment_revisions')->update(['remarks' => 'Overwrite']);
                else DB::table('assessment_revisions')->delete();
                $this->fail('Database must reject '.$operation);
            } catch (QueryException $exception) {
                $this->assertStringContainsString('append-only', $exception->getMessage());
            }
        }
        $this->assertDatabaseHas('assessment_revisions', ['remarks' => 'First version']);
    }

    public function test_migration_records_honest_baselines_for_older_assessments(): void
    {
        $assessment = Assessment::create(['student_id' => $this->student->id, 'sub_strand_id' => $this->payload['sub_strand_id'],
            'user_id' => $this->teacher->id, 'term' => $this->payload['term'], 'competency_level' => 'AE', 'remarks' => 'Legacy record']);
        $migration = require database_path('migrations/2026_10_18_100000_create_assessment_revisions_table.php');
        $migration->down();
        $migration->up();
        $this->assertDatabaseHas('assessment_revisions', ['assessment_id' => $assessment->id, 'version' => 1, 'event' => 'baseline', 'competency_level' => 'AE', 'remarks' => 'Legacy record']);
        $this->postJson($this->url(), $this->payload, $this->headers)->assertOk();
        $this->assertDatabaseCount('assessment_revisions', 2);
        $this->assertDatabaseHas('assessment_revisions', ['assessment_id' => $assessment->id, 'version' => 2, 'event' => 'updated', 'competency_level' => 'ME']);
    }
}
