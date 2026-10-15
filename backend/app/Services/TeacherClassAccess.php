<?php

namespace App\Services;

use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class TeacherClassAccess
{
    public static function query(User $teacher): Builder
    {
        abort_unless($teacher->role === 'teacher' && $teacher->school_id !== null, 403, 'A teacher account linked to a school is required.');

        return SchoolClass::where('school_id', $teacher->school_id)
            ->whereHas('teachers', fn (Builder $query) => $query->where('users.id', $teacher->id));
    }
}
