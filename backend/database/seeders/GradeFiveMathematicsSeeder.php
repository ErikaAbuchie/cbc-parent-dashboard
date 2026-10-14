<?php

namespace Database\Seeders;

use App\Models\LearningArea;
use App\Models\Strand;
use App\Models\SubStrand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GradeFiveMathematicsSeeder extends Seeder
{
    public function run(): void
    {
        // KICD, Grade 5 Mathematics, Revised 2024: Summary of Strands and Sub Strands.
        // https://kicd.ac.ke/wp-content/uploads/2024/10/Grade-5-Mathematics.pdf
        // Names only; learning outcomes and lesson allocation are not imported here.
        $curriculum = [
            'Numbers' => ['Whole Numbers', 'Addition', 'Subtraction', 'Multiplication', 'Division', 'Fractions', 'Decimals', 'Simple Equations'],
            'Measurement' => ['Length', 'Area', 'Volume', 'Capacity', 'Mass', 'Time', 'Money'],
            'Geometry' => ['Lines', 'Angles', 'Three Dimension (3-D) Objects'],
            'Data Handling' => ['Data Representation'],
        ];

        DB::transaction(function () use ($curriculum) {
            $area = LearningArea::firstOrCreate(['name' => 'Mathematics', 'grade_level' => 5]);
            foreach ($curriculum as $strandName => $subStrands) {
                $strand = Strand::firstOrCreate(['learning_area_id' => $area->id, 'name' => $strandName]);
                foreach ($subStrands as $name) {
                    SubStrand::firstOrCreate(['strand_id' => $strand->id, 'name' => $name]);
                }
            }
        });

        $this->command?->info('Grade 5 Mathematics ready: four strands and 19 sub-strands.');
    }
}
