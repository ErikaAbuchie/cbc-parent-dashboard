<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizResponse extends Model
{
    protected $guarded = ['id'];
    protected $hidden = [];
    protected function casts(): array { return ['is_correct' => 'boolean', 'selected_index' => 'integer', 'question_id' => 'integer']; }
}
