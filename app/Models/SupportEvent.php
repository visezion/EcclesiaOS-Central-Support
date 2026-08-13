<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class SupportEvent extends Model
{
    protected $fillable = ['installation_id', 'event_id', 'event_type', 'occurred_at', 'payload'];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime', 'payload' => 'array'];
    }
}
