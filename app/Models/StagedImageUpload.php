<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id',
    'path',
    'expected_size',
    'expires_at',
    'claimed_at',
    'prepared_at',
    'consumed_at',
    'destination_path',
])]
class StagedImageUpload extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'expected_size' => 'integer',
            'expires_at' => 'immutable_datetime',
            'claimed_at' => 'immutable_datetime',
            'prepared_at' => 'immutable_datetime',
            'consumed_at' => 'immutable_datetime',
        ];
    }
}
