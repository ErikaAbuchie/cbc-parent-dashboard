<?php

namespace App\Jobs;

use App\Models\ParentAccess;
use App\Models\Student;
use App\Services\ParentSmsSender;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendParentAccessCode implements ShouldQueue, ShouldBeEncrypted
{
    use Queueable;

    public int $tries = 4;
    public int $timeout = 30;
    public array $backoff = [30, 60, 120];

    public function __construct(public int $accessId, private string $code) {}

    public function handle(ParentSmsSender $sms): void
    {
        $access = ParentAccess::find($this->accessId);
        if (! $access || ! $access->is_active || $access->expires_at->isPast() || $access->sms_status === 'accepted') return;
        $student = Student::find($access->student_id);
        if (! $student || ! hash_equals($access->recipient_hash ?? '', hash('sha256', $student->parent_phone_number))) {
            $access->update(['is_active' => false, 'sms_status' => 'cancelled']);
            return;
        }
        $access->increment('sms_attempts');
        try {
            $url = rtrim(config('services.parent_access.frontend_url'), '/').'/parent/login';
            $sms->send($student->parent_phone_number, "CBC report for {$access->term}. Access code: {$this->code}. Use the learner's admission number at {$url}. Code expires in 30 days.");
            $access->update(['sms_status' => 'accepted', 'sms_accepted_at' => now()]);
        } catch (Throwable) {
            $access->update(['sms_status' => 'retrying']);
            throw new \RuntimeException('Parent access SMS submission failed; retry scheduled if attempts remain.');
        }
    }

    public function failed(?Throwable $exception): void
    {
        ParentAccess::whereKey($this->accessId)->update(['is_active' => false, 'sms_status' => 'failed']);
    }
}
