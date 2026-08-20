<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table): void {
            $table->index(['installation_id', 'status', 'created_at'], 'support_tickets_installation_status_created_index');
        });
        Schema::table('knowledge_articles', function (Blueprint $table): void {
            $table->index(['published', 'category', 'updated_at'], 'knowledge_articles_published_category_updated_index');
        });
        Schema::table('community_questions', function (Blueprint $table): void {
            $table->index(['status', 'category', 'created_at'], 'community_questions_status_category_created_index');
        });
        Schema::table('live_messages', function (Blueprint $table): void {
            $table->index(['installation_id', 'status', 'created_at'], 'live_messages_installation_status_created_index');
        });
    }

    public function down(): void
    {
        Schema::table('live_messages', fn (Blueprint $table) => $table->dropIndex('live_messages_installation_status_created_index'));
        Schema::table('community_questions', fn (Blueprint $table) => $table->dropIndex('community_questions_status_category_created_index'));
        Schema::table('knowledge_articles', fn (Blueprint $table) => $table->dropIndex('knowledge_articles_published_category_updated_index'));
        Schema::table('support_tickets', fn (Blueprint $table) => $table->dropIndex('support_tickets_installation_status_created_index'));
    }
};
