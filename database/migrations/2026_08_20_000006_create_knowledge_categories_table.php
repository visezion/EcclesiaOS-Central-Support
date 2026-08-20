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
        Schema::create('knowledge_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 80)->unique();
            $table->string('slug', 100)->unique();
            $table->timestamps();
        });

        DB::table('knowledge_articles')->select('category')->distinct()->pluck('category')->filter()->each(function (string $name): void {
            DB::table('knowledge_categories')->insert(['name' => $name, 'slug' => Str::slug($name), 'created_at' => now(), 'updated_at' => now()]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_categories');
    }
};
