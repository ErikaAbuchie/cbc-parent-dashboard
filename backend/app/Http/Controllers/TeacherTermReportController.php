<?php

namespace App\Http\Controllers;

use App\Jobs\SendParentAccessCode;
use App\Models\Assessment;
use App\Models\ParentAccess;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\SubStrand;
use App\Models\TermReport;
use App\Services\ParentAccessCodeGenerator;
use App\Services\TeacherClassAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeacherTermReportController extends Controller
{
    private function selection(Request $request, SchoolClass $class): array
    {
        $data = $request->validate([
            'term' => ['required', 'string', 'regex:/^Term [123] 20[0-9]{2}$/'],
            'sub_strand_ids' => ['required', 'array', 'min:1', 'max:200'],
            'sub_strand_ids.*' => ['required', 'integer', 'distinct'],
        ]);
        $ids = array_map('intval', $data['sub_strand_ids']);
        if (SubStrand::whereIn('id', $ids)->whereHas('strand.learningArea', fn ($q) => $q->where('grade_level', $class->grade_level))->count() !== count($ids)) {
            throw ValidationException::withMessages(['sub_strand_ids' => 'Select valid sub-strands for this class grade.']);
        }
        return ['term' => $data['term'], 'sub_strand_ids' => $ids];
    }

    private function outstanding(Student $student, int $teacherId, array $data): array
    {
        $recorded = Assessment::where('student_id', $student->id)->where('user_id', $teacherId)
            ->where('term', $data['term'])->whereIn('sub_strand_id', $data['sub_strand_ids'])->pluck('sub_strand_id')->all();
        return SubStrand::whereIn('id', array_diff($data['sub_strand_ids'], $recorded))->get(['id', 'name'])->toArray();
    }

    public function readiness(Request $request, int $classId)
    {
        $class = TeacherClassAccess::query($request->user())->findOrFail($classId);
        $data = $this->selection($request, $class);
        $learners = $class->students()->orderBy('full_name')->get()->map(function ($student) use ($request, $data) {
            $access = ParentAccess::where('student_id', $student->id)->where('term', $data['term'])->latest('id')->first();
            return ['id' => $student->id, 'full_name' => $student->full_name, 'admission_number' => $student->admission_number,
                'outstanding' => $this->outstanding($student, $request->user()->id, $data),
                'phone_ready' => (bool) preg_match('/^\+254[17][0-9]{8}$/', $student->parent_phone_number),
                'sms_status' => $access?->sms_status, 'code_active' => $access?->is_active && $access->expires_at->isFuture()];
        });
        return response()->json(['students' => $learners, 'sms_configured' => $this->smsConfigured()]);
    }

    private function smsConfigured(): bool
    {
        return (bool) config('services.africastalking.enabled') && filled(config('services.africastalking.key'))
            && filled(config('services.africastalking.username')) && in_array(config('queue.default'), ['database', 'redis']);
    }

    public function finalise(Request $request, int $classId, int $studentId, ParentAccessCodeGenerator $generator)
    {
        $class = TeacherClassAccess::query($request->user())->findOrFail($classId);
        $data = $this->selection($request, $class);
        $access = DB::transaction(function () use ($class, $studentId, $data, $request, $generator) {
            SchoolClass::whereKey($class->id)->lockForUpdate()->firstOrFail();
            $student = $class->students()->findOrFail($studentId);
            $outstanding = $this->outstanding($student, $request->user()->id, $data);
            if ($outstanding) {
                throw ValidationException::withMessages(['sub_strand_ids' => ['Missing assessments: '.implode(', ', array_column($outstanding, 'name'))]]);
            }
            if (! preg_match('/^\+254[17][0-9]{8}$/', $student->parent_phone_number)) {
                throw ValidationException::withMessages(['parent_phone_number' => 'Set a valid parent phone number in Manage students first.']);
            }
            abort_unless($this->smsConfigured(), 503, 'Configure sandbox SMS and a database or Redis queue before finalising.');
            $recent = ParentAccess::where('student_id', $student->id)->where('term', $data['term'])->where('created_at', '>', now()->subMinute())->exists();
            abort_if($recent, 429, 'Wait one minute before issuing another code.');
            $report = TermReport::updateOrCreate(['student_id' => $student->id, 'user_id' => $request->user()->id, 'term' => $data['term']],
                ['sub_strand_ids' => $data['sub_strand_ids'], 'finalised_at' => now()]);
            ParentAccess::where('student_id', $student->id)->where('term', $data['term'])->update(['is_active' => false]);
            $code = null;
            for ($attempt = 0; $attempt < 20; $attempt++) {
                $candidate = $generator->generate();
                if (! ParentAccess::where('student_id', $student->id)->where('code_hash', hash('sha256', $candidate))->exists()) {
                    $code = $candidate;
                    break;
                }
            }
            abort_unless($code !== null, 503, 'Could not issue a new access code. Please try again.');
            $access = ParentAccess::create(['student_id' => $student->id, 'term_report_id' => $report->id,
                'term' => $data['term'], 'code_hash' => hash('sha256', $code), 'recipient_hash' => hash('sha256', $student->parent_phone_number),
                'expires_at' => now()->addDays(30), 'is_active' => true, 'sms_status' => 'queued']);
            // The raw code is carried only inside Laravel's encrypted queue payload.
            SendParentAccessCode::dispatch($access->id, $code)->afterCommit();
            return $access;
        });
        return response()->json(['message' => 'Report finalised. SMS queued; delivery is not yet confirmed.', 'sms_status' => $access->sms_status], 202);
    }
}
