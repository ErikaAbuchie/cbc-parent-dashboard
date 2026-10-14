<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SprintTwoDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('Demo data can only be prepared locally or in tests.');
        }

        DB::transaction(function () {
            $teacher = User::where('email', 'teacher@example.com')->first();
            if (! $teacher || $teacher->role !== 'teacher') {
                throw new RuntimeException('Create the Demo Teacher account before running this seeder.');
            }

            $school = School::firstOrCreate(['name' => 'CBC Demo Primary School'], ['county' => 'Nairobi']);
            if ($teacher->school_id !== null && (int) $teacher->school_id !== $school->id) {
                throw new RuntimeException('Demo Teacher already belongs to another school. No changes were saved.');
            }

            $class = SchoolClass::firstOrCreate([
                'school_id' => $school->id,
                'name' => 'Grade 5 Demo',
            ], ['grade_level' => 5]);

            foreach (['DEMO-001', 'DEMO-002', 'DEMO-003'] as $index => $admission) {
                $student = Student::firstOrCreate(['admission_number' => $admission], [
                    'school_class_id' => $class->id,
                    'full_name' => 'Demo Learner '.($index + 1),
                    // Deliberately not a telephone number: demo data must not send real SMS.
                    'parent_phone_number' => 'DEMO-NO-SMS',
                ]);
                if ((int) $student->school_class_id !== $class->id) {
                    throw new RuntimeException("Admission number {$admission} already belongs to another class. No changes were saved.");
                }
            }

            $teacher->school_id = $school->id;
            $teacher->save();
        });

        $this->command?->info('Demo Teacher linked to CBC Demo Primary School. Grade 5 Demo and three simulated learners are ready.');
    }
}
