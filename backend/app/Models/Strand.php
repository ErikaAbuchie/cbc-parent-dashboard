<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Strand extends Model
{
    protected $fillable = ['learning_area_id', 'name'];

    public function learningArea(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(LearningArea::class);
    }

    public function subStrands(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SubStrand::class);
    }
}
