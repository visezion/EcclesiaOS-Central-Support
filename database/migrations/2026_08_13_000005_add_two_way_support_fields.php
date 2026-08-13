<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installations', function (Blueprint $table): void {
            $table->string('callback_url')->nullable()->after('church_name');
            $table->text('token_encrypted')->nullable()->after('token_hash');
        });
        Schema::table('support_tickets', function (Blueprint $table): void {
            $table->string('category')->nullable()->after('installation_id');
            $table->text('expected_outcome')->nullable()->after('body');
            $table->string('page_url')->nullable()->after('expected_outcome');
            $table->string('browser')->nullable()->after('page_url');
            $table->unsignedTinyInteger('progress')->default(0)->after('priority');
        });
        Schema::create('support_replies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_internal')->default(false);
            $table->json('author')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_replies');
        Schema::table('support_tickets', function (Blueprint $table): void {
            $table->dropColumn(['category', 'expected_outcome', 'page_url', 'browser', 'progress']);
        });
        Schema::table('installations', function (Blueprint $table): void {
            $table->dropColumn(['callback_url', 'token_encrypted']);
        });
    }
};
