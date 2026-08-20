<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class LiveMessage extends Model
{
    protected $fillable = ['installation_id', 'author', 'body', 'status', 'read_at'];

    protected function casts(): array
    {
        return ['author' => 'array', 'read_at' => 'datetime'];
    }
}
