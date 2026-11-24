<?php

namespace App\Services;

use App\Jobs\SendParentAccessCode;
use App\Jobs\SendParentProgressNotification;
use App\Models\Assessment;
use App\Models\ParentAccess;
use App\Models\Student;
use App\Models\TermReport;

class ParentProgressPublisher
{
    // Called inside the assessment-save transaction, after the class lock is acquired.
    public function publish(Student $student, int $teacherId, string $term): bool
    {
        $ids = Assessment::where('student_id', $student->id)->where('user_id', $teacherId)
            ->where('term', $term)->orderBy('sub_strand_id')->pluck('sub_strand_id')->all();
        $report = TermReport::updateOrCreate(
            ['student_id' => $student->id, 'user_id' => $teacherId, 'term' => $term],
            ['sub_strand_ids' => $ids, 'finalised_at' => now()]
        );
        if (! config('services.africastalking.enabled') || ! filled(config('services.africastalking.key'))
            || ! filled(config('services.africastalking.username'))
            || ! in_array(config('queue.default'), ['database', 'redis'])
            || ! preg_match('/^\+254[17][0-9]{8}$/', $student->parent_phone_number ?? '')) return false;

        $recipientHash = hash('sha256', $student->parent_phone_number);
        $access = ParentAccess::where('student_id', $student->id)->where('is_active', true)
            ->where('expires_at', '>', now())->where('recipient_hash', $recipientHash)
            ->where('sms_status', 'accepted')
            ->whereIn('term_report_id', TermReport::where('student_id', $student->id)
                ->where('user_id', $teacherId)->select('id'))->latest('id')->first();
        if ($access) {
            SendParentProgressNotification::dispatch($access->id, $report->id)->afterCommit();
            return true;
        }
        // Reuse an in-flight first code instead of creating another during a batch of saves.
        $pending = ParentAccess::where('student_id', $student->id)->where('is_active', true)
            ->where('expires_at', '>', now())->where('recipient_hash', $recipientHash)
            ->whereIn('sms_status', ['queued', 'retrying'])
            ->whereIn('term_report_id', TermReport::where('student_id', $student->id)
                ->where('user_id', $teacherId)->select('id'))->exists();
        if ($pending) return false;

        $generator = app(ParentAccessCodeGenerator::class);
        $code = null;
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $candidate = $generator->generate();
            if (! ParentAccess::where('student_id', $student->id)->where('code_hash', hash('sha256', $candidate))->exists()) {
                $code = $candidate;
                break;
            }
        }
        if ($code === null) return false;
        $access = ParentAccess::create(['student_id' => $student->id, 'term_report_id' => $report->id,
            'term' => $term, 'code_hash' => hash('sha256', $code), 'recipient_hash' => $recipientHash,
            'expires_at' => now()->addDays(30), 'is_active' => true, 'sms_status' => 'queued']);
        SendParentAccessCode::dispatch($access->id, $code)->afterCommit();
        return true;
    }
}
