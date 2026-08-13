<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class CommunityQuestion extends Model
{
    protected $fillable = ['installation_id', 'category', 'title', 'body', 'author', 'church', 'status'];

    protected function casts(): array
    {
        return ['author' => 'array', 'church' => 'array'];
    }
}
