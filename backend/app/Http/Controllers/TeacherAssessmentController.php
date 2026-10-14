<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\LearningArea;
use App\Models\SchoolClass;
use App\Models\SubStrand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TeacherAssessmentController extends Controller
{
    private function requireTeacher(Request $request): void
    {
        abort_unless($request->user()->role === 'teacher' && $request->user()->school_id !== null, 403, 'A teacher account linked to a school is required.');
    }

    private function schoolClass(Request $request, int $classId): SchoolClass
    {
        $this->requireTeacher($request);

        return SchoolClass::where('school_id', $request->user()->school_id)->findOrFail($classId);
    }

    public function classes(Request $request): JsonResponse
    {
        $this->requireTeacher($request);

        return response()->json(['classes' => SchoolClass::where('school_id', $request->user()->school_id)
            ->orderBy('name')->get(['id', 'name', 'grade_level'])]);
    }

    public function options(Request $request, int $classId): JsonResponse
    {
        $class = $this->schoolClass($request, $classId);

        return response()->json([
            'class' => $class->only(['id', 'name', 'grade_level']),
            // Parent phone numbers are not needed by the assessment form.
            'students' => $class->students()->orderBy('full_name')->get(['id', 'full_name', 'admission_number']),
            'learning_areas' => LearningArea::where('grade_level', $class->grade_level)
                ->with(['strands' => fn ($query) => $query->orderBy('id'), 'strands.subStrands' => fn ($query) => $query->orderBy('id')])
                ->orderBy('name')->get(),
        ]);
    }

    private function selection(Request $request, SchoolClass $class): array
    {
        $data = $request->validate([
            'sub_strand_id' => ['required', 'integer', 'exists:sub_strands,id'],
            'term' => ['required', 'string', 'regex:/^Term [123] 20[0-9]{2}$/'],
        ]);

        $matchesGrade = SubStrand::whereKey($data['sub_strand_id'])
            ->whereHas('strand.learningArea', fn ($query) => $query->where('grade_level', $class->grade_level))->exists();
        if (! $matchesGrade) {
            throw ValidationException::withMessages(['sub_strand_id' => 'Select a sub-strand for this class grade.']);
        }

        return $data;
    }

    public function index(Request $request, int $classId): JsonResponse
    {
        $class = $this->schoolClass($request, $classId);
        $selection = $this->selection($request, $class);

        return response()->json(['assessments' => Assessment::where('user_id', $request->user()->id)
            ->whereIn('student_id', $class->students()->select('id'))
            ->where('sub_strand_id', $selection['sub_strand_id'])->where('term', $selection['term'])
            ->orderBy('student_id')->get(['id', 'student_id', 'sub_strand_id', 'competency_level', 'term', 'remarks'])]);
    }

    public function store(Request $request, int $classId): JsonResponse
    {
        $class = $this->schoolClass($request, $classId);
        $selection = $this->selection($request, $class);
        $data = $request->validate([
            'assessments' => ['required', 'array', 'min:1', 'max:200'],
            'assessments.*' => ['required', 'array:student_id,competency_level,remarks'],
            'assessments.*.student_id' => ['required', 'integer', 'distinct', Rule::exists('students', 'id')->where('school_class_id', $class->id)],
            'assessments.*.competency_level' => ['required', Rule::in(['EE', 'ME', 'AE', 'BE'])],
            'assessments.*.remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($data, $selection, $class, $request) {
            // Serialize saves for this class so concurrent retries cannot create duplicates.
            SchoolClass::whereKey($class->id)->lockForUpdate()->firstOrFail();
            foreach ($data['assessments'] as $row) {
                Assessment::updateOrCreate([
                    'student_id' => $row['student_id'],
                    'sub_strand_id' => $selection['sub_strand_id'],
                    'term' => $selection['term'],
                    'user_id' => $request->user()->id,
                ], ['competency_level' => $row['competency_level'], 'remarks' => $row['remarks'] ?? null]);
            }
        });

        return response()->json(['message' => 'Assessments saved.', 'saved_count' => count($data['assessments'])]);
    }
}
