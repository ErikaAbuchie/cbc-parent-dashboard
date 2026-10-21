<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizSession extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['question_set'];
    protected function casts(): array { return ['question_set' => 'array', 'completed_at' => 'datetime']; }
}
