<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\PracticeExercise;
use App\Models\QuizResponse;
use App\Models\QuizSession;
use App\Models\SubStrand;
use App\Models\TermReport;
use App\Services\ParentReportAccess;
use App\Services\PracticeQuestionBank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class ParentPracticeController extends Controller
{
    public function __construct(private ParentReportAccess $access, private PracticeQuestionBank $bank) {}

    private function assessments($student, $report)
    {
        return Assessment::where('student_id', $student->id)->where('user_id', $report->user_id)->where('term', $report->term)
            ->whereIn('sub_strand_id', $report->sub_strand_ids)->with('subStrand.strand.learningArea')->get();
    }

    private function topics($student, $report)
    {
        return $this->assessments($student, $report)->filter(fn ($a) => count($this->bank->questions($a->subStrand)) > 0);
    }

    private function published($student, $report)
    {
        return PracticeExercise::where('school_class_id', $student->school_class_id)->where('user_id', $report->user_id)
            ->where('status', 'published')->whereIn('sub_strand_id', $this->assessments($student, $report)->pluck('sub_strand_id'))
            ->with('subStrand.strand.learningArea');
    }

    private function permitted(Request $request, int $id, bool $lock = false): QuizSession
    {
        [, $student, $report] = $this->access->resolve($request);
        $query = QuizSession::where('student_id', $student->id)->where('term_report_id', $report->id)->whereNotNull('question_set');
        if ($lock) $query->lockForUpdate();
        $quiz = $query->findOrFail($id);
        // A published exercise may later be archived; its existing snapshot remains reviewable.
        abort_unless($this->assessments($student, $report)->contains('sub_strand_id', $quiz->sub_strand_id), 404);
        return $quiz;
    }

    private function payload(QuizSession $quiz): array
    {
        $questions = array_map(fn ($q) => ['id' => $q['id'], 'question' => $q['question'], 'options' => $q['options']], $quiz->question_set);
        $result = ['id' => $quiz->id, 'sub_strand' => SubStrand::findOrFail($quiz->sub_strand_id)->name,
            'sub_strand_id' => $quiz->sub_strand_id, 'exercise_title' => $quiz->exercise_title, 'exercise_id' => $quiz->practice_exercise_id,
            'completed' => $quiz->completed_at !== null, 'questions' => $questions];
        $saved = QuizResponse::where('quiz_session_id', $quiz->id)->get()->keyBy('question_id');
        $result['answered'] = array_values(array_map(fn ($q) => [
            'question_id' => $q['id'], 'selected_index' => $saved[$q['id']]->selected_index,
            'is_correct' => $saved[$q['id']]->is_correct, 'correct_answer' => $q['options'][$q['correct']],
            'feedback' => $q['feedback'],
        ], array_filter($quiz->question_set, fn ($q) => isset($saved[$q['id']]))));
        if ($quiz->completed_at) {
            $answers = QuizResponse::where('quiz_session_id', $quiz->id)->get()->keyBy('question_id');
            $result['correct_count'] = $answers->where('is_correct', true)->count();
            $result['total'] = count($questions);
            $result['review'] = array_map(fn ($q) => ['question' => $q['question'], 'selected_answer' => $answers[$q['id']]->selected_answer,
                'correct_answer' => $q['options'][$q['correct']], 'is_correct' => $answers[$q['id']]->is_correct, 'feedback' => $q['feedback']], $quiz->question_set);
        }
        return $result;
    }

    public function index(Request $request)
    {
        [, $student, $report] = $this->access->resolve($request);
        $topics = $this->topics($student, $report)->map(fn ($a) => ['id' => $a->sub_strand_id, 'name' => $a->subStrand->name,
            'learning_area' => $a->subStrand->strand->learningArea->name, 'competency_level' => $a->competency_level,
            'source' => 'pilot', 'question_count' => count($this->bank->questions($a->subStrand)), 'exercise_id' => null])->values();
        $topics = $topics->concat($this->published($student, $report)->orderBy('id')->get()->map(fn ($exercise) => [
            'id' => $exercise->sub_strand_id, 'name' => $exercise->title, 'sub_strand' => $exercise->subStrand->name,
            'learning_area' => $exercise->subStrand->strand->learningArea->name, 'exercise_id' => $exercise->id,
            'source' => 'teacher', 'question_count' => count($exercise->questions)]));
        $history = QuizSession::where('student_id', $student->id)->where('term_report_id', $report->id)
            ->whereIn('sub_strand_id', $this->assessments($student, $report)->pluck('sub_strand_id'))->whereNotNull('question_set')->whereNotNull('completed_at')
            ->orderByDesc('completed_at')->orderByDesc('id')->take(5)->get()
            ->map(fn ($q) => ['id' => $q->id, 'topic' => $q->exercise_title ?: SubStrand::findOrFail($q->sub_strand_id)->name,
                'completed_at' => $q->completed_at->toIso8601String(), 'correct_count' => QuizResponse::where('quiz_session_id', $q->id)->where('is_correct', true)->count(),
                'total' => count($q->question_set ?? [])]);
        return response()->json(['topics' => $topics, 'history' => $history])->header('Cache-Control', 'no-store');
    }

    public function start(Request $request)
    {
        $data = $request->validate(['sub_strand_id' => ['required_without:exercise_id', 'prohibits:exercise_id', 'integer'],
            'exercise_id' => ['required_without:sub_strand_id', 'prohibits:sub_strand_id', 'integer']]);
        $quiz = DB::transaction(function () use ($request, $data) {
            [$session, $student, $report] = $this->access->resolve($request);
            TermReport::whereKey($report->id)->lockForUpdate()->firstOrFail();
            $exercise = isset($data['exercise_id']) ? $this->published($student, $report)->lockForUpdate()->findOrFail($data['exercise_id']) : null;
            $subId = $exercise?->sub_strand_id ?? $data['sub_strand_id'];
            if (!$exercise) abort_unless($this->topics($student, $report)->contains('sub_strand_id', $subId), 404);
            $query = QuizSession::where('student_id', $student->id)->where('term_report_id', $report->id)->where('sub_strand_id', $subId)
                ->where('practice_exercise_id', $exercise?->id);
            if ($unfinished = $query->whereNull('completed_at')->whereNotNull('question_set')->latest('id')->first()) return $unfinished;
            $key = 'parent-practice:'.$session->parent_access_id;
            abort_if(RateLimiter::tooManyAttempts($key, 5), 429, 'Please wait before starting another practice attempt.');
            RateLimiter::hit($key, 600);
            return QuizSession::create(['student_id' => $student->id, 'term_report_id' => $report->id, 'sub_strand_id' => $subId,
                'practice_exercise_id' => $exercise?->id, 'exercise_title' => $exercise?->title,
                'question_set' => $exercise?->questions ?? $this->bank->questions(SubStrand::with('strand.learningArea')->findOrFail($subId))]);
        });
        return response()->json($this->payload($quiz))->header('Cache-Control', 'no-store');
    }

    public function show(Request $request, int $id)
    {
        return response()->json($this->payload($this->permitted($request, $id)))->header('Cache-Control', 'no-store');
    }

    public function answer(Request $request, int $id)
    {
        $quiz = DB::transaction(function () use ($request, $id) {
            $quiz = $this->permitted($request, $id, true);
            $data = $request->validate(['question_id' => ['required', 'integer'],
                'answer_index' => ['required', 'integer', 'min:0', 'max:3']]);
            $question = collect($quiz->question_set)->firstWhere('id', $data['question_id']);
            if (! $question || ! isset($question['options'][$data['answer_index']])) {
                throw ValidationException::withMessages(['question_id' => 'Choose a valid question and option.']);
            }
            $saved = QuizResponse::where('quiz_session_id', $quiz->id)->get()->keyBy('question_id');
            if (isset($saved[$question['id']])) {
                abort_unless($saved[$question['id']]->selected_index === (int) $data['answer_index'], 409, 'This answer is already locked.');
                return $quiz;
            }
            abort_if($quiz->completed_at !== null, 409, 'This attempt is complete.');
            $next = collect($quiz->question_set)->first(fn ($q) => ! isset($saved[$q['id']]));
            if ($next['id'] !== $question['id']) throw ValidationException::withMessages(['question_id' => 'Answer the current question first.']);
            $selected = (int) $data['answer_index'];
            QuizResponse::create(['quiz_session_id' => $quiz->id, 'question_id' => $question['id'], 'question' => $question['question'],
                'selected_index' => $selected, 'selected_answer' => $question['options'][$selected], 'is_correct' => $selected === $question['correct']]);
            if ($saved->count() + 1 === count($quiz->question_set)) $quiz->update(['completed_at' => now()]);
            return $quiz;
        });
        return response()->json($this->payload($quiz))->header('Cache-Control', 'no-store');
    }

    public function submit(Request $request, int $id)
    {
        $quiz = DB::transaction(function () use ($request, $id) {
            $quiz = $this->permitted($request, $id, true);
            if ($quiz->completed_at) return $quiz; // Retry after a lost response does not create another attempt.
            $data = $request->validate(['answers' => ['required', 'array', 'size:'.count($quiz->question_set)],
                'answers.*.question_id' => ['required', 'integer', 'distinct'], 'answers.*.answer_index' => ['required', 'integer', 'min:0', 'max:3']]);
            $answers = collect($data['answers'])->keyBy('question_id');
            foreach ($quiz->question_set as $q) {
                if (!isset($answers[$q['id']])) throw ValidationException::withMessages(['answers' => 'Answer every question once.']);
                $selected = (int) $answers[$q['id']]['answer_index'];
                $existing = QuizResponse::where('quiz_session_id', $quiz->id)->where('question_id', $q['id'])->first();
                if ($existing) {
                    abort_unless($existing->selected_index === $selected, 409, 'A saved answer cannot be changed.');
                    continue;
                }
                QuizResponse::create(['quiz_session_id' => $quiz->id, 'question_id' => $q['id'], 'question' => $q['question'],
                    'selected_index' => $selected, 'selected_answer' => $q['options'][$selected], 'is_correct' => $selected === $q['correct']]);
            }
            $quiz->update(['completed_at' => now()]);
            return $quiz;
        });
        return response()->json($this->payload($quiz))->header('Cache-Control', 'no-store');
    }
}
