<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table): void {
            $table->id();
            $table->string('reference')->unique();
            $table->string('installation_id')->index();
            $table->string('subject');
            $table->text('body');
            $table->json('requester')->nullable();
            $table->string('status')->default('new');
            $table->string('priority')->default('normal');
            $table->timestamps();
        });
        Schema::create('knowledge_articles', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('category')->default('General');
            $table->longText('body');
            $table->boolean('published')->default(false);
            $table->timestamps();
        });
        Schema::create('live_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('installation_id')->index();
            $table->json('author')->nullable();
            $table->text('body');
            $table->string('status')->default('open');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_messages');
        Schema::dropIfExists('knowledge_articles');
        Schema::dropIfExists('support_tickets');
    }
};
