<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParentAccess extends Model
{
    protected $table = 'parent_access';
    protected $guarded = ['id'];
    protected $hidden = ['code_hash', 'recipient_hash'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'is_active' => 'boolean', 'sms_accepted_at' => 'datetime'];
    }
}
