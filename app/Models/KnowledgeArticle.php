<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class KnowledgeArticle extends Model
{
    protected $fillable = ['slug', 'title', 'body', 'category', 'published'];

    protected function casts(): array
    {
        return ['published' => 'boolean'];
    }
}
