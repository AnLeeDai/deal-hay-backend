<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaOperation extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['payload' => 'array', 'claimed_at' => 'immutable_datetime'];
    }
}
