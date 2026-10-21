<?php

namespace App\Services;

use App\Models\ParentAccess;
use App\Models\ParentSession;
use App\Models\Student;
use App\Models\TermReport;
use Illuminate\Http\Request;

class ParentReportAccess
{
    public function resolve(Request $request): array
    {
        $token = $request->bearerToken();
        abort_unless(is_string($token) && strlen($token) === 64, 401, 'Parent session expired. Please verify your code again.');
        $session = ParentSession::where('token_hash', hash('sha256', $token))->where('expires_at', '>', now())->first();
        $access = $session ? ParentAccess::whereKey($session->parent_access_id)->where('is_active', true)->where('expires_at', '>', now())->first() : null;
        $student = $access ? Student::with('schoolClass.school')->find($access->student_id) : null;
        $report = $access ? TermReport::find($access->term_report_id) : null;
        abort_unless($student && $report && (int) $report->student_id === $student->id
            && hash_equals($access->recipient_hash ?? '', hash('sha256', $student->parent_phone_number)), 401, 'Parent session expired. Please verify your code again.');
        return [$session, $student, $report];
    }
}
