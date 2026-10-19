<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TermReport extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['sub_strand_ids' => 'array', 'finalised_at' => 'datetime'];
    }
}
