<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class KnowledgeArticleFeedback extends Model
{
    protected $table = 'knowledge_article_feedback';

    protected $fillable = [
        'knowledge_article_id',
        'installation_id',
        'voter_id',
        'helpful',
    ];

    protected function casts(): array
    {
        return ['helpful' => 'boolean'];
    }
}
