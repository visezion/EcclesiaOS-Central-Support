<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class AuditLog extends Model
{
    protected $fillable = ['user_id', 'action', 'installation_id', 'auditable_type', 'auditable_id', 'metadata', 'ip_address', 'user_agent'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }
}
