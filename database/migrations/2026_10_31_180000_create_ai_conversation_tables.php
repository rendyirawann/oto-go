<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 120)->nullable();
            $table->string('channel', 16)->default('web'); // web | mobile
            $table->timestampsTz();

            // "My conversations, newest first" is the only listing query.
            $table->index(['user_id', 'updated_at']);
        });

        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('ai_conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->string('role', 16); // user | assistant
            $table->text('content');
            $table->json('meta')->nullable(); // sources, model, tools used
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['ai_conversation_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
    }
};
