<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubStrand extends Model
{
    protected $fillable = ['strand_id', 'name'];

    public function strand(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Strand::class);
    }

    public function assessments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Assessment::class);
    }
}
