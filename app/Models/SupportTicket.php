<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class SupportTicket extends Model
{
    protected $fillable = ['public_id', 'reference', 'installation_id', 'category', 'subject', 'body', 'expected_outcome', 'page_url', 'browser', 'requester', 'status', 'priority', 'progress'];

    protected function casts(): array
    {
        return ['requester' => 'array'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $ticket): void {
            $ticket->public_id ??= (string) Str::uuid();
        });
    }

    public function replies(): HasMany
    {
        return $this->hasMany(SupportReply::class);
    }
}
