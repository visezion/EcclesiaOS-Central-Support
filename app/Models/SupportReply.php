<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class SupportReply extends Model
{
    protected $fillable = ['support_ticket_id', 'body', 'is_internal', 'author'];

    protected function casts(): array
    {
        return ['is_internal' => 'boolean', 'author' => 'array'];
    }
}
