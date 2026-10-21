<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\ParentAccess;
use App\Models\ParentSession;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\SubStrand;
use App\Models\TermReport;
use App\Models\User;
use App\Services\CompetencyReportSummary;
use Database\Seeders\GradeFiveMathematicsSeeder;
use Database\Seeders\LisaTermDemoSeeder;
use Database\Seeders\SprintTwoDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LisaTermReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_average_uses_all_sub_strands_and_documented_level_boundaries(): void
    {
        $service = app(CompetencyReportSummary::class);
        foreach ([['BE', 'BE'], ['AE', 'AE'], ['ME', 'ME'], ['EE', 'EE'], ['EE', 'BE'], ['ME', 'AE'], ['EE', 'ME']] as $codes) {
            $rows = collect($codes)->map(fn ($code) => (object) ['competency_level' => $code]);
            $actual = $service->aggregate($rows);
            $expected = array_sum(array_map(fn ($c) => ['BE' => 1, 'AE' => 2, 'ME' => 3, 'EE' => 4][$c], $codes)) / 2;
            $this->assertEquals($expected, $actual['average_score']);
            $this->assertSame(match (true) { $expected >= 3.5 => 'EE', $expected >= 2.5 => 'ME', $expected >= 1.5 => 'AE', default => 'BE' }, $actual['summary_level']);
        }
        $this->assertNull($service->aggregate(collect())['average_score']);
        $this->assertNull($service->aggregate(collect())['summary_level']);
    }

    public function test_demo_fills_eight_subjects_preserves_existing_data_and_expands_the_authorised_report(): void
    {
        Queue::fake();
        $teacher = User::factory()->create(['email' => 'teacher@example.com', 'role' => 'teacher']);
        $this->seed([SprintTwoDemoSeeder::class, GradeFiveMathematicsSeeder::class]);
        $lisa = Student::create(['full_name' => 'Lisa', 'admission_number' => '12345', 'school_class_id' => SchoolClass::firstOrFail()->id, 'parent_phone_number' => 'DEMO-NO-SMS']);
        $sub = SubStrand::where('name', 'Whole Numbers')->firstOrFail();
        $original = Assessment::create(['student_id' => $lisa->id, 'user_id' => $teacher->id, 'sub_strand_id' => $sub->id,
            'term' => 'Term 1 2025', 'competency_level' => 'BE', 'remarks' => 'Existing teacher rating']);
        $report = TermReport::create(['student_id' => $lisa->id, 'user_id' => $teacher->id, 'term' => 'Term 1 2025', 'sub_strand_ids' => [$sub->id], 'finalised_at' => now()]);
        $access = ParentAccess::create(['student_id' => $lisa->id, 'term_report_id' => $report->id, 'term' => $report->term,
            'code_hash' => hash('sha256', '123456'), 'recipient_hash' => hash('sha256', $lisa->parent_phone_number), 'expires_at' => now()->addDay(), 'is_active' => true]);
        $token = str_repeat('l', 64);
        ParentSession::create(['parent_access_id' => $access->id, 'token_hash' => hash('sha256', $token), 'expires_at' => now()->addHour()]);
        $this->seed(LisaTermDemoSeeder::class);
        $this->seed(LisaTermDemoSeeder::class);
        $this->assertDatabaseCount('assessments', 169);
        $this->assertDatabaseCount('assessment_revisions', 168);
        $this->assertSame('BE', $original->fresh()->competency_level);
        $this->assertSame('Existing teacher rating', $original->fresh()->remarks);
        $this->assertCount(169, $report->fresh()->sub_strand_ids);
        $this->assertTrue($access->fresh()->is_active);
        Queue::assertNothingPushed();
        $response = $this->getJson('/api/parent/report', ['Authorization' => 'Bearer '.$token])->assertOk()
            ->assertJsonCount(8, 'subjects')->assertJsonCount(169, 'assessments')->assertJsonPath('has_demo_results', true)->assertJsonPath('term', 'Term 1 2025');
        $names = array_column($response->json('subjects'), 'name');
        $this->assertContains('Christian Religious Education', $names);
        $this->assertNotContains('Hindu Religious Education', $names);
        $this->assertNotContains('Islamic Religious Education', $names);
        $maths = collect($response->json('subjects'))->firstWhere('name', 'Mathematics');
        $this->assertCount(4, $maths['strands']);
        $this->assertSame(19, $maths['assessed_count']);
        $this->assertEquals(round(Assessment::where('student_id', $lisa->id)->whereHas('subStrand.strand.learningArea', fn ($q) => $q->where('name', 'Mathematics'))
            ->get()->avg(fn ($a) => ['BE' => 1, 'AE' => 2, 'ME' => 3, 'EE' => 4][$a->competency_level]), 2), $maths['average_score']);
        $this->assertSame('BE', $maths['strands'][0]['sub_strands'][0]['competency_level']);
    }
}
