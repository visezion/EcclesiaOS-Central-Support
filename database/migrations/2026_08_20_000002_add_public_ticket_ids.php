<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table): void {
            $table->uuid('public_id')->nullable()->unique()->after('id');
        });

        DB::table('support_tickets')->whereNull('public_id')->orderBy('id')->eachById(function (object $ticket): void {
            DB::table('support_tickets')->where('id', $ticket->id)->update(['public_id' => (string) Str::uuid()]);
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table): void {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });
    }
};
