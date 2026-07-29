<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::create('ai_conversation_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->enum('sender_type', ['user', 'ai', 'system']);
            $table->longText('message');
            $table->string('intent_key')->nullable();
            $table->json('payload')->nullable();
            $table->enum('status', ['draft', 'waiting_confirmation', 'confirmed', 'executed', 'failed'])->default('draft');
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('executed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['ai_conversation_id', 'sender_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_conversation_messages');
    }
};
