<?php

use Database\Seeders\ChurchMemberKnowledgeSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('knowledge_articles')) {
            (new ChurchMemberKnowledgeSeeder)->run();
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('knowledge_articles')) {
            DB::table('knowledge_articles')->whereIn('slug', [
                'getting-started-with-your-ecclesiaos-account',
                'managing-member-information-and-privacy',
                'attendance-and-event-check-in-guide',
                'giving-and-financial-records-safety',
                'church-messages-and-notification-preferences',
                'roles-permissions-and-safe-administration',
                'how-to-submit-a-useful-support-ticket',
                'data-import-export-and-backup-practices',
                'church-events-volunteers-and-communication-planning',
            ])->delete();
        }
    }
};
