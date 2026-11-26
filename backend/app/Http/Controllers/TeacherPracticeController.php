<?php

namespace App\Http\Controllers;

use App\Models\PracticeExercise;
use App\Models\SchoolClass;
use App\Models\SubStrand;
use App\Services\TeacherClassAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TeacherPracticeController extends Controller
{
    private function schoolClass(Request $request, int $classId): SchoolClass
    {
        abort_unless($request->user()->role === 'teacher' && $request->user()->school_id, 403);
        return TeacherClassAccess::query($request->user())->findOrFail($classId);
    }

    private function exercise(Request $request, int $classId, int $id): PracticeExercise
    {
        $this->schoolClass($request, $classId);
        return PracticeExercise::where('school_class_id', $classId)->where('user_id', $request->user()->id)->lockForUpdate()->findOrFail($id);
    }

    private function rules(bool $complete): array
    {
        $text = $complete ? 'required' : 'nullable';
        return ['title' => ['required', 'string', 'max:160'], 'sub_strand_id' => ['required', 'integer', 'exists:sub_strands,id'],
            'questions' => ['present', 'array', 'max:30', 'min:'.($complete ? 1 : 0)],
            'questions.*.question' => [$text, 'string', 'max:1000'],
            'questions.*.options' => ['required', 'array', 'size:4'],
            'questions.*.options.*' => [$text, 'string', 'max:400'],
            'questions.*.correct' => [$text, 'integer', 'between:0,3'],
            'questions.*.feedback' => [$text, 'string', 'max:1000']];
    }

    private function grade(SchoolClass $class, int $subId): void
    {
        if (!SubStrand::whereKey($subId)->whereHas('strand.learningArea', fn ($q) => $q->where('grade_level', $class->grade_level))->exists()) {
            throw ValidationException::withMessages(['sub_strand_id' => 'Choose a sub-strand for this class grade.']);
        }
    }

    private function data(Request $request, SchoolClass $class): array
    {
        $data = $request->validate($this->rules(false));
        $this->grade($class, $data['sub_strand_id']);
        $data['questions'] = array_map(fn ($q, $i) => ['id' => $i + 1, 'question' => trim($q['question'] ?? ''),
            'options' => array_map(fn ($value) => trim($value ?? ''), array_values($q['options'])),
            'correct' => isset($q['correct']) ? (int) $q['correct'] : null, 'feedback' => trim($q['feedback'] ?? '')], array_values($data['questions']), array_keys(array_values($data['questions'])));
        return $data;
    }

    public function index(Request $request, int $classId)
    {
        $this->schoolClass($request, $classId);
        return response()->json(['exercises' => PracticeExercise::where('school_class_id', $classId)->where('user_id', $request->user()->id)
            ->with('subStrand.strand.learningArea')->latest('id')->get()])->header('Cache-Control', 'no-store');
    }

    public function store(Request $request, int $classId)
    {
        $class = $this->schoolClass($request, $classId);
        $exercise = PracticeExercise::create($this->data($request, $class) + ['school_class_id' => $class->id, 'user_id' => $request->user()->id]);
        return response()->json(['exercise' => $exercise], 201);
    }

    public function update(Request $request, int $classId, int $id)
    {
        $exercise = DB::transaction(function () use ($request, $classId, $id) {
            $exercise = $this->exercise($request, $classId, $id);
            abort_unless($exercise->status === 'draft', 409, 'Only drafts can be edited. Duplicate this exercise to make a new version.');
            $exercise->update($this->data($request, $this->schoolClass($request, $classId)));
            return $exercise;
        });
        return response()->json(['exercise' => $exercise]);
    }

    public function publish(Request $request, int $classId, int $id)
    {
        $exercise = DB::transaction(function () use ($request, $classId, $id) {
            $exercise = $this->exercise($request, $classId, $id);
            abort_unless($exercise->status === 'draft', 409, 'Only a draft can be published.');
            Validator::make($exercise->toArray(), $this->rules(true))->validate();
            $this->grade($this->schoolClass($request, $classId), $exercise->sub_strand_id);
            foreach ($exercise->questions as $i => $question) {
                $options = array_map(fn ($option) => mb_strtolower(trim($option)), $question['options']);
                if (count(array_unique($options)) !== 4) throw ValidationException::withMessages(["questions.$i.options" => 'Use four different answer options.']);
            }
            $exercise->update(['status' => 'published', 'published_at' => now()]);
            return $exercise;
        });
        return response()->json(['exercise' => $exercise]);
    }

    public function archive(Request $request, int $classId, int $id)
    {
        $exercise = DB::transaction(function () use ($request, $classId, $id) {
            $exercise = $this->exercise($request, $classId, $id);
            abort_unless(in_array($exercise->status, ['published', 'archived']), 409, 'Delete an unwanted draft instead.');
            $exercise->update(['status' => 'archived']);
            return $exercise;
        });
        return response()->json(['exercise' => $exercise]);
    }

    public function duplicate(Request $request, int $classId, int $id)
    {
        $copy = DB::transaction(function () use ($request, $classId, $id) {
            $source = $this->exercise($request, $classId, $id);
            return PracticeExercise::create(['title' => mb_substr($source->title, 0, 153).' (copy)', 'user_id' => $source->user_id,
                'school_class_id' => $classId, 'sub_strand_id' => $source->sub_strand_id, 'questions' => $source->questions]);
        });
        return response()->json(['exercise' => $copy], 201);
    }

    public function destroy(Request $request, int $classId, int $id)
    {
        DB::transaction(function () use ($request, $classId, $id) {
            $exercise = $this->exercise($request, $classId, $id);
            abort_unless($exercise->status === 'draft', 409, 'Published exercises must be archived so attempts are preserved.');
            $exercise->delete();
        });
        return response()->json(['message' => 'Draft deleted.']);
    }
}
