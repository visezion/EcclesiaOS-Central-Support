<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class SupportTicket extends Model
{
    protected $fillable = ['reference', 'installation_id', 'subject', 'body', 'requester', 'status', 'priority'];

    protected function casts(): array
    {
        return ['requester' => 'array'];
    }
}
