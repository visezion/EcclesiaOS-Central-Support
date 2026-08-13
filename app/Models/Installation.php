<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Installation extends Model
{
    protected $fillable = ['installation_id', 'church_name', 'version', 'token_hash', 'enabled', 'last_seen_at'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'last_seen_at' => 'datetime'];
    }
}
