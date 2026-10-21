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

class ParentReportTrendTest extends TestCase
{
    use RefreshDatabase;

    public function test_trend_uses_finalised_scopes_for_same_learner_teacher_year_and_no_future_terms(): void
    {
        $teacher = User::factory()->create(['email' => 'teacher@example.com', 'role' => 'teacher']);
        $this->seed([SprintTwoDemoSeeder::class, GradeFiveMathematicsSeeder::class]);
        $student = Student::firstOrFail();
        $sub = SubStrand::where('name', 'Whole Numbers')->firstOrFail();
        $reports = [];
        foreach ([1 => 'AE', 2 => 'ME', 3 => 'EE'] as $term => $level) {
            $label = "Term {$term} 2025";
            $reports[$term] = TermReport::create(['student_id' => $student->id, 'user_id' => $teacher->id,
                'term' => $label, 'sub_strand_ids' => [$sub->id], 'finalised_at' => now()]);
            Assessment::create(['student_id' => $student->id, 'user_id' => $teacher->id, 'sub_strand_id' => $sub->id,
                'term' => $label, 'competency_level' => $level, 'remarks' => 'Private source remark']);
        }
        $outside = SubStrand::where('name', 'Addition')->firstOrFail();
        Assessment::create(['student_id' => $student->id, 'user_id' => $teacher->id, 'sub_strand_id' => $outside->id,
            'term' => 'Term 1 2025', 'competency_level' => 'EE']);
        $other = User::factory()->create(['role' => 'teacher']);
        TermReport::create(['student_id' => $student->id, 'user_id' => $other->id, 'term' => 'Term 1 2025', 'sub_strand_ids' => [$sub->id], 'finalised_at' => now()]);
        Assessment::create(['student_id' => $student->id, 'user_id' => $other->id, 'sub_strand_id' => $sub->id, 'term' => 'Term 1 2025', 'competency_level' => 'BE']);
        $access = ParentAccess::create(['student_id' => $student->id, 'term_report_id' => $reports[2]->id, 'term' => 'Term 2 2025',
            'code_hash' => hash('sha256', '123456'), 'recipient_hash' => hash('sha256', $student->parent_phone_number), 'is_active' => true, 'expires_at' => now()->addDay()]);
        $token = str_repeat('t', 64);
        ParentSession::create(['parent_access_id' => $access->id, 'token_hash' => hash('sha256', $token), 'expires_at' => now()->addHour()]);
        $headers = ['Authorization' => 'Bearer '.$token];
        $this->getJson('/api/parent/report?student_id=999', $headers)->assertOk()
            ->assertJsonPath('trend.year', 2025)->assertJsonCount(1, 'trend.subjects')
            ->assertJsonPath('trend.subjects.0.name', 'Mathematics')
            ->assertJsonPath('trend.subjects.0.points.0.average_score', 2)
            ->assertJsonPath('trend.subjects.0.points.0.assessed_count', 1)
            ->assertJsonPath('trend.subjects.0.points.1.average_score', 3)
            ->assertJsonPath('trend.subjects.0.points.2.average_score', null)
            ->assertJsonMissingPath('trend.subjects.0.points.0.remarks');
        $reports[1]->delete();
        $this->getJson('/api/parent/report', $headers)->assertOk()->assertJsonPath('trend.subjects.0.points.0.average_score', null);
        $access->update(['is_active' => false]);
        $this->getJson('/api/parent/report', $headers)->assertUnauthorized();
    }
}
