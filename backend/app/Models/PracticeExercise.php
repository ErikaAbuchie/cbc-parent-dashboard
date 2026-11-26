<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PracticeExercise extends Model
{
    protected $guarded = ['id'];
    protected $attributes = ['status' => 'draft'];
    protected function casts(): array { return ['questions' => 'array', 'published_at' => 'datetime']; }
    public function subStrand() { return $this->belongsTo(SubStrand::class); }
}
