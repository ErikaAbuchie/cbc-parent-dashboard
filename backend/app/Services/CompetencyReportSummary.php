<?php

namespace App\Services;

use Illuminate\Support\Collection;

class CompetencyReportSummary
{
    public function aggregate(Collection $assessments): array
    {
        $points = ['BE' => 1, 'AE' => 2, 'ME' => 3, 'EE' => 4];
        $values = $assessments->map(fn ($a) => $points[$a->competency_level] ?? null)->filter(fn ($v) => $v !== null);
        if ($values->isEmpty()) return ['summary_level' => null, 'average_score' => null];
        // Report a two-decimal mean, then apply the documented project thresholds.
        $average = round($values->avg(), 2);
        $level = match (true) { $average >= 3.5 => 'EE', $average >= 2.5 => 'ME', $average >= 1.5 => 'AE', default => 'BE' };
        return ['summary_level' => $level, 'average_score' => $average];
    }

    public function subjects(Collection $assessments): array
    {
        return $assessments->groupBy(fn ($a) => $a->subStrand->strand->learning_area_id)->map(function ($rows) {
            $area = $rows->first()->subStrand->strand->learningArea;
            return ['id' => $area->id, 'name' => $area->name, ...$this->aggregate($rows), 'assessed_count' => $rows->count(),
                'strands' => $rows->groupBy('subStrand.strand_id')->map(function ($entries) {
                    $strand = $entries->first()->subStrand->strand;
                    return ['id' => $strand->id, 'name' => $strand->name, ...$this->aggregate($entries), 'assessed_count' => $entries->count(),
                        'sub_strands' => $entries->sortBy('sub_strand_id')->map(fn ($a) => ['id' => $a->sub_strand_id,
                            'name' => $a->subStrand->name, 'competency_level' => $a->competency_level, 'remarks' => $a->remarks])->values()->all()];
                })->values()->all()];
        })->sortBy('name')->values()->all();
    }
}
