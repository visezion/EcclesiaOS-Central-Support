<?php

use Database\Seeders\KnowledgeBaseSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('knowledge_articles')) {
            (new KnowledgeBaseSeeder)->run();
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('knowledge_articles')) {
            DB::table('knowledge_articles')->where('slug', 'updating-ecclesiaos-safely-with-central-support')->delete();
        }
    }
};
