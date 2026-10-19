<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class AssessmentRevision extends Model
{
    public $timestamps = false;

    protected $fillable = ['assessment_id', 'user_id', 'version', 'event', 'competency_level', 'remarks', 'recorded_at'];

    protected function casts(): array
    {
        return ['recorded_at' => 'datetime', 'version' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Assessment revisions are append-only.'));
        static::deleting(fn () => throw new LogicException('Assessment revisions are append-only.'));
    }
}
