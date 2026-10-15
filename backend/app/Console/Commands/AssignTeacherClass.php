<?php

namespace App\Console\Commands;

use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Console\Command;

class AssignTeacherClass extends Command
{
    protected $signature = 'teacher:assign-class {email} {classId} {--remove : Revoke this assignment}';
    protected $description = 'Assign or revoke a teacher class from the trusted operator console';

    public function handle(): int
    {
        $teacher = User::where('email', $this->argument('email'))->where('role', 'teacher')->first();
        $class = SchoolClass::find($this->argument('classId'));
        if (! $teacher || ! $class || $teacher->school_id === null || (int) $teacher->school_id !== (int) $class->school_id) {
            $this->error('Teacher and class must exist and belong to the same school.');
            return self::FAILURE;
        }
        if ($this->option('remove')) {
            $teacher->assignedClasses()->detach($class->id);
            $this->info('Class assignment revoked.');
        } else {
            $teacher->assignedClasses()->syncWithoutDetaching([$class->id]);
            $this->info('Teacher assigned to '.$class->name.'.');
        }
        return self::SUCCESS;
    }
}
