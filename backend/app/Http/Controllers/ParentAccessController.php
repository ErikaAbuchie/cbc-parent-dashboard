<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\ParentAccess;
use App\Models\ParentSession;
use App\Models\Student;
use App\Models\TermReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ParentAccessController extends Controller
{
    private function clientIp(Request $request): string
    {
        $secret = config('services.parent_access.proxy_secret');
        $ip = (string) $request->header('X-Parent-Client-IP');
        $timestamp = (string) $request->header('X-Parent-Timestamp');
        if ($secret && filter_var($ip, FILTER_VALIDATE_IP) && ctype_digit($timestamp) && abs(time() - (int) $timestamp) < 60
            && hash_equals(hash_hmac('sha256', $timestamp.'|'.$ip, $secret), (string) $request->header('X-Parent-Signature'))) return $ip;
        return $request->ip();
    }

    public function verify(Request $request)
    {
        $key = 'parent-verification:'.hash('sha256', $this->clientIp($request));
        if (RateLimiter::tooManyAttempts($key, 5)) return response()->json(['message' => 'Too many attempts. Please try again later.'], 429);
        $valid = ! Validator::make($request->only('admission_number', 'code'), [
            'admission_number' => ['required', 'string', 'max:50'], 'code' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
        ])->fails();
        $student = $valid ? Student::where('admission_number', $request->input('admission_number'))->first() : null;
        $access = $student ? ParentAccess::where('student_id', $student->id)->where('is_active', true)
            ->where('expires_at', '>', now())->whereNotNull('term_report_id')->where('code_hash', hash('sha256', $request->input('code')))->latest('id')->first() : null;
        $report = $access ? TermReport::whereKey($access->term_report_id)->where('student_id', $student->id)->first() : null;
        if (! $access || ! $report || ! hash_equals($access->recipient_hash ?? '', hash('sha256', $student->parent_phone_number))) {
            RateLimiter::hit($key, 600);
            return response()->json(['message' => 'The admission number or access code is invalid or expired.'], 401);
        }
        RateLimiter::clear($key);
        $token = Str::random(64);
        $expires = now()->addHour()->min($access->expires_at);
        ParentSession::create(['parent_access_id' => $access->id, 'token_hash' => hash('sha256', $token), 'expires_at' => $expires]);
        return response()->json(['token' => $token, 'expires_at' => $expires->toIso8601String()]);
    }

    private function context(Request $request): array
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

    public function report(Request $request)
    {
        [, $student, $report] = $this->context($request);
        $assessments = Assessment::where('student_id', $student->id)->where('user_id', $report->user_id)
            ->where('term', $report->term)->whereIn('sub_strand_id', $report->sub_strand_ids)->with('subStrand.strand.learningArea')->get();
        return response()->json([
            'student' => ['full_name' => $student->full_name, 'admission_number' => $student->admission_number,
                'class_name' => $student->schoolClass->name, 'school_name' => $student->schoolClass->school->name, 'grade_level' => $student->schoolClass->grade_level],
            'term' => $report->term, 'finalised_at' => $report->finalised_at->toIso8601String(),
            'assessments' => $assessments->map(fn ($a) => ['learning_area' => $a->subStrand->strand->learningArea->name,
                'strand' => $a->subStrand->strand->name, 'sub_strand' => $a->subStrand->name,
                'competency_level' => $a->competency_level, 'remarks' => $a->remarks]),
        ])->header('Cache-Control', 'no-store');
    }

    public function logout(Request $request)
    {
        $token = $request->bearerToken();
        if (is_string($token)) ParentSession::where('token_hash', hash('sha256', $token))->delete();
        return response()->json(['message' => 'Parent session ended.']);
    }
}
