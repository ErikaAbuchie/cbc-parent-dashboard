<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningArea extends Model
{
    protected $fillable = ['name', 'grade_level'];

    public function strands(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Strand::class);
    }
}
