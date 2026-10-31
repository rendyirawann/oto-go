<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 100)->unique();
            $table->timestampsTz();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_category_id')->nullable()->constrained('post_categories')->nullOnDelete();
            $table->foreignUuid('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 180);
            $table->string('slug', 200)->unique();
            $table->string('excerpt', 400)->nullable();
            $table->longText('content');
            $table->string('cover_path')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestampTz('published_at')->nullable();
            $table->unsignedSmallInteger('reading_minutes')->default(1);
            $table->string('meta_description', 300)->nullable();
            $table->timestampsTz();

            // Public listing: WHERE status = 'published' AND published_at <= now() ORDER BY published_at DESC
            $table->index(['status', 'published_at']);
            $table->index('post_category_id');
            $table->index('author_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
        Schema::dropIfExists('post_categories');
    }
};
