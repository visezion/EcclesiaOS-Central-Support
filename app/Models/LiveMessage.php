<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class LiveMessage extends Model
{
    protected $fillable = ['installation_id', 'author', 'body', 'status'];

    protected function casts(): array
    {
        return ['author' => 'array'];
    }
}
