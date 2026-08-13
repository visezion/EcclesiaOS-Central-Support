<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installations', function (Blueprint $table): void {
            $table->id();
            $table->string('installation_id')->unique();
            $table->string('church_name')->nullable();
            $table->string('version')->nullable();
            $table->string('token_hash', 64)->unique();
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
        Schema::create('support_events', function (Blueprint $table): void {
            $table->id();
            $table->string('installation_id')->index();
            $table->uuid('event_id')->unique();
            $table->string('event_type');
            $table->timestamp('occurred_at')->nullable();
            $table->json('payload');
            $table->timestamps();
        });
        Schema::create('community_questions', function (Blueprint $table): void {
            $table->id();
            $table->string('installation_id')->index();
            $table->string('category', 40);
            $table->string('title');
            $table->text('body');
            $table->json('author');
            $table->json('church');
            $table->string('status')->default('pending_review');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_questions');
        Schema::dropIfExists('support_events');
        Schema::dropIfExists('installations');
    }
};
