<?php

namespace Tests\Feature;

use App\Models\LearningArea;
use App\Models\SubStrand;
use Database\Seeders\GradeFiveMathematicsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeFiveMathematicsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_curriculum_is_repeatable_and_isolated_from_other_grades(): void
    {
        $gradeFour = LearningArea::create(['name' => 'Mathematics', 'grade_level' => 4]);
        $this->seed(GradeFiveMathematicsSeeder::class);
        $this->seed(GradeFiveMathematicsSeeder::class);
        $this->assertDatabaseCount('learning_areas', 2);
        $this->assertDatabaseCount('strands', 4);
        $this->assertDatabaseCount('sub_strands', 19);
        $this->assertCount(0, $gradeFour->strands);
        $area = LearningArea::where('grade_level', 5)->firstOrFail();
        $this->assertCount(4, $area->strands);
        $numbers = $area->strands->firstWhere('name', 'Numbers');
        $this->assertCount(8, $numbers->subStrands);
        $wholeNumbers = SubStrand::where('name', 'Whole Numbers')->firstOrFail();
        $this->assertSame($area->id, $wholeNumbers->strand->learningArea->id);
    }
}
