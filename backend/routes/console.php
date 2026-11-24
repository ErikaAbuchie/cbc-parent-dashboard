<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('parent:sync-progress {admission : Learner admission number}', function () {
    $student = \App\Models\Student::where('admission_number', $this->argument('admission'))->firstOrFail();
    $count = \Illuminate\Support\Facades\DB::transaction(function () use ($student) {
        \App\Models\SchoolClass::whereKey($student->school_class_id)->lockForUpdate()->firstOrFail();
        $reports = \App\Models\TermReport::where('student_id', $student->id)->get();
        foreach ($reports as $report) {
            $ids = \App\Models\Assessment::where('student_id', $student->id)->where('user_id', $report->user_id)
                ->where('term', $report->term)->orderBy('sub_strand_id')->pluck('sub_strand_id')->all();
            $report->update(['sub_strand_ids' => $ids]);
        }
        return $reports->count();
    });
    $this->info("Updated {$count} report scopes from saved assessments. No grades changed and no SMS sent.");
})->purpose('Include all saved assessments in an existing learner report history');
