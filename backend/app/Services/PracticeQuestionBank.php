<?php

namespace App\Services;

use App\Models\SubStrand;

class PracticeQuestionBank
{
    public function questions(SubStrand $sub): array
    {
        if ((int) $sub->strand->learningArea->grade_level !== 5 || $sub->strand->learningArea->name !== 'Mathematics'
            || $sub->strand->name !== 'Numbers') return [];
        // Authored pilot questions, not official examination items or AI output.
        $bank = [
            'Whole Numbers' => [
                ['question' => 'What is the value of the digit 7 in 47,582?', 'options' => ['70', '700', '7,000', '70,000'], 'correct' => 2,
                    'feedback' => 'The digit 7 is in the thousands place, so its value is 7,000.'],
                ['question' => 'Which number is the greatest?', 'options' => ['58,409', '58,940', '58,490', '58,904'], 'correct' => 1,
                    'feedback' => 'After comparing the ten-thousands, thousands and hundreds digits, compare the tens: 58,940 is greatest.'],
                ['question' => 'Round 36,748 to the nearest thousand.', 'options' => ['36,000', '36,700', '37,000', '40,000'], 'correct' => 2,
                    'feedback' => 'The hundreds digit is 7, so round the thousands up to 37,000.'],
            ],
            'Addition' => [
                ['question' => 'What is 2,475 + 1,368?', 'options' => ['3,733', '3,843', '3,743', '4,843'], 'correct' => 1,
                    'feedback' => 'Add each place value and regroup where needed: 2,475 + 1,368 = 3,843.'],
                ['question' => 'A school has 1,250 blue books and 875 green books. How many books are there altogether?', 'options' => ['2,025', '2,125', '2,225', '1,125'], 'correct' => 1,
                    'feedback' => 'Add the two groups of books: 1,250 + 875 = 2,125.'],
                ['question' => 'Find the missing number: 3,460 + ___ = 5,000.', 'options' => ['1,440', '1,640', '2,540', '1,540'], 'correct' => 3,
                    'feedback' => 'Find the difference: 5,000 minus 3,460 is 1,540.'],
            ],
        ];
        return array_map(fn ($q, $index) => ['id' => $index + 1] + $q, $bank[$sub->name] ?? [], array_keys($bank[$sub->name] ?? []));
    }
}
