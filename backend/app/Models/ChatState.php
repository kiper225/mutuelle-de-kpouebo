<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatState extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['cleared_at' => 'datetime', 'blocked_at' => 'datetime'];
    }
}