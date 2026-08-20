<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_article_feedback', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('knowledge_article_id')->constrained('knowledge_articles')->cascadeOnDelete();
            $table->string('installation_id')->index();
            $table->string('voter_id')->default('installation');
            $table->boolean('helpful');
            $table->timestamps();
            $table->unique(['knowledge_article_id', 'installation_id', 'voter_id'], 'knowledge_article_feedback_vote_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_article_feedback');
    }
};
