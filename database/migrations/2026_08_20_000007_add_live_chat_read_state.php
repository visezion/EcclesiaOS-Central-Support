<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_messages', function (Blueprint $table): void {
            $table->timestamp('read_at')->nullable()->after('status');
            $table->index(['installation_id', 'read_at', 'created_at'], 'live_messages_installation_read_created_index');
        });
    }

    public function down(): void
    {
        Schema::table('live_messages', function (Blueprint $table): void {
            $table->dropIndex('live_messages_installation_read_created_index');
            $table->dropColumn('read_at');
        });
    }
};
