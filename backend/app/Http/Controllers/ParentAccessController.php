<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\ParentAccess;
use App\Models\ParentSession;
use App\Models\Student;
use App\Models\TermReport;
use App\Services\AIService;
use Illuminate\Support\Facades\Cache;
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
        return app(\App\Services\ParentReportAccess::class)->resolve($request);
    }

    public function report(Request $request, \App\Services\CompetencyReportSummary $summary)
    {
        [, $student, $report] = $this->context($request);
        $assessments = Assessment::where('student_id', $student->id)->where('user_id', $report->user_id)
            ->where('term', $report->term)->whereIn('sub_strand_id', $report->sub_strand_ids)->with('subStrand.strand.learningArea')->get();
        $subjects = $summary->subjects($assessments);
        return response()->json([
            'student' => ['full_name' => $student->full_name, 'admission_number' => $student->admission_number,
                'class_name' => $student->schoolClass->name, 'school_name' => $student->schoolClass->school->name, 'grade_level' => $student->schoolClass->grade_level],
            'term' => $report->term, 'finalised_at' => $report->finalised_at->toIso8601String(),
            'has_demo_results' => $assessments->contains(fn ($a) => str_starts_with($a->remarks ?? '', '[DEMO]')),
            'summary_method' => 'KNEC rubric levels: BE=1, AE=2, ME=3, EE=4. Recorded performance levels are based on assessment criteria and descriptors. Subject and strand averages are supplementary summaries of included ratings, not KNEC overall grades or percentages. No overall level is assigned automatically.',
            'subjects' => $subjects,
            'trend' => app(\App\Services\ParentReportTrend::class)->build($student, $report, $subjects),
            'assessments' => $assessments->map(fn ($a) => ['learning_area' => $a->subStrand->strand->learningArea->name,
                'strand' => $a->subStrand->strand->name, 'sub_strand' => $a->subStrand->name,
                'competency_level' => $a->competency_level, 'remarks' => $a->remarks]),
        ])->header('Cache-Control', 'no-store');
    }

    public function explanation(Request $request, AIService $ai)
    {
        [$session, $student, $report] = $this->context($request);
        // Ignore submitted ratings, learner IDs and prompts: use only authorised records.
        $ratings = Assessment::where('student_id', $student->id)->where('user_id', $report->user_id)
            ->where('term', $report->term)->whereIn('sub_strand_id', $report->sub_strand_ids)
            ->with('subStrand.strand.learningArea')->orderBy('sub_strand_id')->get()
            ->map(fn ($a) => ['learning_area' => $a->subStrand->strand->learningArea->name,
                'sub_strand' => $a->subStrand->name, 'competency_level' => $a->competency_level])->all();
        $cacheKey = 'parent-explanation:v1:'.hash('sha256', json_encode([$report->id, $student->schoolClass->grade_level,
            config('services.ollama'), $ratings], JSON_THROW_ON_ERROR));
        if ($cached = Cache::get($cacheKey)) return response()->json($cached)->header('Cache-Control', 'no-store');
        $limitKey = 'parent-explanation:'.$session->parent_access_id;
        abort_if(RateLimiter::tooManyAttempts($limitKey, 3), 429, 'Please wait a minute before requesting another explanation.');
        RateLimiter::hit($limitKey, 60);
        $lock = Cache::lock($cacheKey.':lock', 55);
        abort_unless($lock->get(), 429, 'An explanation is already being prepared. Please try again shortly.');
        try {
            $result = $ai->explainReport($ratings, (int) $student->schoolClass->grade_level);
            if ($result['source'] === 'model') Cache::put($cacheKey, $result, 600);
        } finally {
            $lock->release();
        }
        // Re-check access after inference in case a teacher revoked it while waiting.
        $this->context($request);
        return response()->json($result)->header('Cache-Control', 'no-store');
    }

    public function logout(Request $request)
    {
        $token = $request->bearerToken();
        if (is_string($token)) ParentSession::where('token_hash', hash('sha256', $token))->delete();
        return response()->json(['message' => 'Parent session ended.']);
    }
}
