<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
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

    private function topics($student, $report)
    {
        return Assessment::where('student_id', $student->id)->where('user_id', $report->user_id)->where('term', $report->term)
            ->whereIn('sub_strand_id', $report->sub_strand_ids)->with('subStrand.strand.learningArea')->get()
            ->filter(fn ($a) => count($this->bank->questions($a->subStrand)) > 0);
    }

    private function permitted(Request $request, int $id, bool $lock = false): QuizSession
    {
        [, $student, $report] = $this->access->resolve($request);
        $query = QuizSession::where('student_id', $student->id)->where('term_report_id', $report->id)->whereNotNull('question_set');
        if ($lock) $query->lockForUpdate();
        $quiz = $query->findOrFail($id);
        abort_unless($this->topics($student, $report)->contains('sub_strand_id', $quiz->sub_strand_id), 404);
        return $quiz;
    }

    private function payload(QuizSession $quiz): array
    {
        $questions = array_map(fn ($q) => ['id' => $q['id'], 'question' => $q['question'], 'options' => $q['options']], $quiz->question_set);
        $result = ['id' => $quiz->id, 'sub_strand' => SubStrand::findOrFail($quiz->sub_strand_id)->name,
            'completed' => $quiz->completed_at !== null, 'questions' => $questions];
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
            'learning_area' => $a->subStrand->strand->learningArea->name, 'competency_level' => $a->competency_level])->values();
        $history = QuizSession::where('student_id', $student->id)->where('term_report_id', $report->id)
            ->whereIn('sub_strand_id', $topics->pluck('id'))->whereNotNull('completed_at')->latest('id')->take(5)->get()
            ->map(fn ($q) => ['id' => $q->id, 'topic' => SubStrand::findOrFail($q->sub_strand_id)->name,
                'completed_at' => $q->completed_at->toIso8601String(), 'correct_count' => QuizResponse::where('quiz_session_id', $q->id)->where('is_correct', true)->count(),
                'total' => count($q->question_set ?? [])]);
        return response()->json(['topics' => $topics, 'history' => $history])->header('Cache-Control', 'no-store');
    }

    public function start(Request $request)
    {
        $data = $request->validate(['sub_strand_id' => ['required', 'integer']]);
        $quiz = DB::transaction(function () use ($request, $data) {
            [$session, $student, $report] = $this->access->resolve($request);
            TermReport::whereKey($report->id)->lockForUpdate()->firstOrFail();
            abort_unless($this->topics($student, $report)->contains('sub_strand_id', $data['sub_strand_id']), 404);
            $query = QuizSession::where('student_id', $student->id)->where('term_report_id', $report->id)->where('sub_strand_id', $data['sub_strand_id']);
            if ($unfinished = $query->whereNull('completed_at')->whereNotNull('question_set')->latest('id')->first()) return $unfinished;
            $key = 'parent-practice:'.$session->parent_access_id;
            abort_if(RateLimiter::tooManyAttempts($key, 5), 429, 'Please wait before starting another practice attempt.');
            RateLimiter::hit($key, 600);
            return QuizSession::create(['student_id' => $student->id, 'term_report_id' => $report->id, 'sub_strand_id' => $data['sub_strand_id'],
                'question_set' => $this->bank->questions(SubStrand::with('strand.learningArea')->findOrFail($data['sub_strand_id']))]);
        });
        return response()->json($this->payload($quiz))->header('Cache-Control', 'no-store');
    }

    public function show(Request $request, int $id)
    {
        return response()->json($this->payload($this->permitted($request, $id)))->header('Cache-Control', 'no-store');
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
                QuizResponse::create(['quiz_session_id' => $quiz->id, 'question_id' => $q['id'], 'question' => $q['question'],
                    'selected_index' => $selected, 'selected_answer' => $q['options'][$selected], 'is_correct' => $selected === $q['correct']]);
            }
            $quiz->update(['completed_at' => now()]);
            return $quiz;
        });
        return response()->json($this->payload($quiz))->header('Cache-Control', 'no-store');
    }
}
