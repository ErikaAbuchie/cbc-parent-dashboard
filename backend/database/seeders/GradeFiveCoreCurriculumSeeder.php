<?php

namespace Database\Seeders;

use App\Models\LearningArea;
use App\Models\Strand;
use App\Models\SubStrand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GradeFiveCoreCurriculumSeeder extends Seeder
{
    public function run(): void
    {
        // KICD Grade Five Designs. Provenance and scope notes travel with the data.
        $curriculum = json_decode(file_get_contents(database_path('data/grade-five-core-curriculum.json')), true, 512, JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($curriculum) {
            $this->call(GradeFiveMathematicsSeeder::class);
            foreach ($curriculum as $subject) {
                $area = LearningArea::firstOrCreate(['name' => $subject['name'], 'grade_level' => $subject['grade_level']]);
                foreach ($subject['strands'] as $strandName => $subStrands) {
                    $strand = Strand::firstOrCreate(['learning_area_id' => $area->id, 'name' => $strandName]);
                    foreach ($subStrands as $name) {
                        SubStrand::firstOrCreate(['strand_id' => $strand->id, 'name' => $name]);
                    }
                }
            }
        });

        $this->command?->info('Grade 5 core curriculum ready, including Mathematics and three religious education alternatives. Existing assessments are preserved.');
    }
}
