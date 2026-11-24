<?php

namespace App\Jobs;

use App\Models\ParentAccess;
use App\Models\Student;
use App\Models\TermReport;
use App\Services\ParentSmsSender;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendParentProgressNotification implements ShouldQueue, ShouldBeEncrypted
{
    use Queueable;

    public int $tries = 4;
    public int $timeout = 30;
    public array $backoff = [30, 60, 120];

    public function __construct(public int $accessId, public int $reportId) {}

    public function handle(ParentSmsSender $sms): void
    {
        $access = ParentAccess::find($this->accessId);
        $student = $access ? Student::find($access->student_id) : null;
        $report = TermReport::find($this->reportId);
        $anchor = $access ? TermReport::find($access->term_report_id) : null;
        if (! $student || ! $report || ! $anchor || ! $access->is_active || $access->expires_at->isPast()
            || $access->sms_status !== 'accepted' || $report->student_id !== $student->id
            || $anchor->user_id !== $report->user_id
            || ! hash_equals($access->recipient_hash ?? '', hash('sha256', $student->parent_phone_number))) return;
        try {
            $sms->send($student->parent_phone_number,
                "New assessment results for {$report->term}. Open the parent portal with your existing access code and admission number. Previous terms are still available.");
        } catch (Throwable) {
            throw new \RuntimeException('Parent progress notification failed; retry scheduled if attempts remain.');
        }
    }

    public function failed(?Throwable $exception): void
    {
        // A failed update notification must never revoke an otherwise valid login code.
        Log::warning('Parent progress notification exhausted its retries', ['report_id' => $this->reportId]);
    }
}
