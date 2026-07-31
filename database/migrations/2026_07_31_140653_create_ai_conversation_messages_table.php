<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversation_messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ai_conversation_id')
                ->constrained('ai_conversations')
                ->cascadeOnDelete();

            $table->foreignId('ai_channel_id')
                ->nullable()
                ->constrained('ai_channels')
                ->nullOnDelete();

            $table->enum('sender_type', ['user', 'ai', 'system']);
            $table->enum('sender_role', ['user', 'admin', 'visitor', 'ai', 'system'])->default('user');

            $table->enum('message_type', [
                'text',
                'image',
                'product_result',
                'knowledge_reply',
                'handoff',
                'tool_call',
                'tool_result'
            ])->default('text');

            $table->longText('message');

            $table->string('intent_key')->nullable();
            $table->decimal('confidence_score', 5, 2)->nullable();

            $table->string('tool_name')->nullable();
            $table->json('tool_payload')->nullable();
            $table->json('tool_result')->nullable();

            $table->enum('status', [
                'draft',
                'waiting_confirmation',
                'confirmed',
                'executed',
                'failed'
            ])->default('draft');

            $table->boolean('requires_confirmation')->default(false);
            $table->boolean('requires_handoff')->default(false);

            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('executed_at')->nullable();

            $table->json('payload')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['ai_conversation_id', 'sender_type']);
            $table->index(['ai_channel_id', 'message_type']);
            $table->index(['intent_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_conversation_messages');
    }
};