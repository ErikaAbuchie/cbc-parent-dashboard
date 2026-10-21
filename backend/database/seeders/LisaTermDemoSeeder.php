<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\SubStrand;
use App\Models\TermReport;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LisaTermDemoSeeder extends Seeder
{
    public function run(): void
    {
        $result = DB::transaction(function () {
            $student = Student::where('admission_number', '12345')->where('full_name', 'Lisa')->first();
            $teacher = User::where('email', 'teacher@example.com')->where('role', 'teacher')->first();
            if (! $student || ! $teacher || $student->schoolClass->name !== 'Grade 5 Demo'
                || $student->schoolClass->school->name !== 'CBC Demo Primary School'
                || (int) $student->schoolClass->grade_level !== 5
                || ! $teacher->assignedClasses()->whereKey($student->school_class_id)->exists()) {
                throw new RuntimeException('This preview requires Lisa (12345) in Grade 5 Demo at CBC Demo Primary School and the assigned Demo Teacher.');
            }
            SchoolClass::whereKey($student->school_class_id)->lockForUpdate()->firstOrFail();
            $report = TermReport::where('student_id', $student->id)->where('user_id', $teacher->id)->latest('id')->lockForUpdate()->first();
            if (! $report) throw new RuntimeException('Finalise a demo report for Lisa before preparing this preview.');
            $this->call(GradeFiveCoreCurriculumSeeder::class);
            $baseLevels = ['Mathematics' => 'ME', 'English' => 'AE', 'Kiswahili' => 'ME', 'Science and Technology' => 'ME',
                'Social Studies' => 'EE', 'Agriculture' => 'ME', 'Creative Arts' => 'EE', 'Christian Religious Education' => 'ME'];
            $subs = SubStrand::whereHas('strand.learningArea', fn ($q) => $q->where('grade_level', 5)->whereIn('name', array_keys($baseLevels)))
                ->where('name', '!=', 'Swimming (Optional)')->with('strand.learningArea')->orderBy('id')->get();
            $created = 0;
            foreach ($subs->groupBy(fn ($s) => $s->strand->learning_area_id) as $subject) {
                foreach ($subject->values() as $index => $sub) {
                    $key = ['student_id' => $student->id, 'user_id' => $teacher->id, 'term' => $report->term, 'sub_strand_id' => $sub->id];
                    if (Assessment::where($key)->exists()) continue;
                    $base = $baseLevels[$sub->strand->learningArea->name];
                    $level = match ($index % 7) { 4 => 'AE', 5 => 'EE', 6 => 'BE', default => $base };
                    $assessment = Assessment::create($key + ['competency_level' => $level,
                        'remarks' => '[DEMO] Simulated rating for a full-term report preview; not an observed learner result.']);
                    $assessment->revisions()->create(['user_id' => $teacher->id, 'version' => 1, 'event' => 'created',
                        'competency_level' => $level, 'remarks' => $assessment->remarks, 'recorded_at' => now()]);
                    $created++;
                }
            }
            $ids = $subs->pluck('id')->all();
            // Explicitly requested demo preview: expand the existing report without sending SMS or replacing its code.
            if ($report->sub_strand_ids !== $ids) $report->update(['sub_strand_ids' => $ids, 'finalised_at' => now()]);
            return ['term' => $report->term, 'created' => $created, 'included' => count($ids)];
        });
        $this->command?->info("Lisa demo preview: {$result['term']}; {$result['created']} new ratings; {$result['included']} included sub-strands. Existing ratings preserved; no SMS sent.");
    }
}
