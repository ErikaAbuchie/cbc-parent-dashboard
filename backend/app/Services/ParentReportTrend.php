<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Student;
use App\Models\TermReport;

class ParentReportTrend
{
    public function build(Student $student, TermReport $current, array $subjects): array
    {
        if (!preg_match('/^Term ([123]) (20[0-9]{2})$/', $current->term, $matches)) return ['year' => null, 'terms' => [], 'subjects' => []];
        $year = (int) $matches[2];
        $terms = array_map(fn ($n) => "Term {$n} {$year}", [1, 2, 3]);
        $reports = TermReport::where('student_id', $student->id)->where('user_id', $current->user_id)
            ->whereIn('term', array_slice($terms, 0, (int) $matches[1]))->whereNotNull('finalised_at')->get();
        $byTerm = [];
        foreach ($reports as $report) {
            $rows = Assessment::where('student_id', $student->id)->where('user_id', $current->user_id)->where('term', $report->term)
                ->whereIn('sub_strand_id', $report->sub_strand_ids)->with('subStrand.strand.learningArea')->get();
            foreach ($rows->groupBy(fn ($a) => $a->subStrand->strand->learning_area_id) as $areaId => $entries) {
                $byTerm[$report->term][$areaId] = app(CompetencyReportSummary::class)->aggregate($entries) + [
                    'assessed_count' => $entries->count(),
                    'has_demo_results' => $entries->contains(fn ($a) => str_starts_with($a->remarks ?? '', '[DEMO]')),
                ];
            }
        }
        return ['year' => $year, 'terms' => $terms, 'subjects' => array_map(fn ($subject) => [
            'id' => $subject['id'], 'name' => $subject['name'],
            'points' => array_map(fn ($term) => ['term' => $term] + ($byTerm[$term][$subject['id']] ?? [
                'average_score' => null, 'summary_level' => null, 'assessed_count' => 0, 'has_demo_results' => false,
            ]), $terms),
        ], $subjects)];
    }
}
