<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\ParentAccess;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\TermReport;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LisaAnnualDemoSeeder extends Seeder
{
    public function run(): void
    {
        $created = DB::transaction(function () {
            $student = Student::where('admission_number', '12345')->where('full_name', 'Lisa')->first();
            $teacher = User::where('email', 'teacher@example.com')->where('role', 'teacher')->first();
            if (! $student || ! $teacher || $student->schoolClass->name !== 'Grade 5 Demo'
                || $student->schoolClass->school->name !== 'CBC Demo Primary School'
                || (int) $student->schoolClass->grade_level !== 5
                || ! $teacher->assignedClasses()->whereKey($student->school_class_id)->exists()) {
                throw new RuntimeException('Requires Lisa (12345), Grade 5 Demo, CBC Demo Primary School and the assigned Demo Teacher.');
            }
            SchoolClass::whereKey($student->school_class_id)->lockForUpdate()->firstOrFail();
            $first = TermReport::where('student_id', $student->id)->where('user_id', $teacher->id)
                ->where('term', 'Term 1 2025')->lockForUpdate()->firstOrFail();
            $source = Assessment::where('student_id', $student->id)->where('user_id', $teacher->id)
                ->where('term', $first->term)->whereIn('sub_strand_id', $first->sub_strand_ids)->orderBy('sub_strand_id')->get();
            if ($source->count() !== 169 || count($first->sub_strand_ids) !== 169) {
                throw new RuntimeException('Prepare Lisa\'s full eight-subject Term 1 demo before adding Terms 2 and 3.');
            }
            $levels = ['BE', 'AE', 'ME', 'EE'];
            $created = 0;
            $reportIds = [$first->id];
            foreach ([2, 3] as $term) {
                $label = "Term {$term} 2025";
                foreach ($source->values() as $index => $original) {
                    $key = ['student_id' => $student->id, 'user_id' => $teacher->id,
                        'term' => $label, 'sub_strand_id' => $original->sub_strand_id];
                    if (Assessment::where($key)->exists()) continue;
                    // Repeatable illustrative progress; these are simulated rubric ratings.
                    $base = array_search($original->competency_level, $levels, true);
                    if ($base === false) throw new RuntimeException('Unexpected source rubric level.');
                    $increase = $term === 2 ? (int) ($index % 4 === 0) : (int) ($index % 4 !== 3);
                    $level = $levels[min(3, $base + $increase)];
                    $row = Assessment::create($key + ['competency_level' => $level,
                        'remarks' => '[DEMO] Simulated term '.$term.' rating for the annual trend preview; not an observed learner result.']);
                    $row->revisions()->create(['user_id' => $teacher->id, 'version' => 1, 'event' => 'created',
                        'competency_level' => $level, 'remarks' => $row->remarks, 'recorded_at' => now()]);
                    $created++;
                }
                $report = TermReport::firstOrCreate(['student_id' => $student->id, 'user_id' => $teacher->id, 'term' => $label],
                    ['sub_strand_ids' => $first->sub_strand_ids, 'finalised_at' => now()]);
                if ($report->sub_strand_ids !== $first->sub_strand_ids) {
                    $report->update(['sub_strand_ids' => $first->sub_strand_ids]);
                }
                $reportIds[] = $report->id;
            }
            // Explicit demo preview: retain code hashes, expiry and sessions, displaying the latest term.
            ParentAccess::where('student_id', $student->id)->whereIn('term_report_id', $reportIds)
                ->update(['term_report_id' => $report->id, 'term' => $report->term]);
            return $created;
        });
        $this->command?->info("Lisa annual demo: {$created} new ratings; Terms 2 and 3 prepared for 2025. Existing ratings preserved; parent preview now Term 3; no SMS sent.");
    }
}
