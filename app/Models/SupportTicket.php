<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SupportTicket extends Model
{
    protected $fillable = ['reference', 'installation_id', 'category', 'subject', 'body', 'expected_outcome', 'page_url', 'browser', 'requester', 'status', 'priority', 'progress'];

    protected function casts(): array
    {
        return ['requester' => 'array'];
    }

    public function replies(): HasMany
    {
        return $this->hasMany(SupportReply::class);
    }
}
