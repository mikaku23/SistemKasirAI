<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ai_channel_id')
                ->nullable()
                ->constrained('ai_channels')
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('visitor_id')
                ->nullable()
                ->constrained('visitors')
                ->nullOnDelete();

            $table->enum('conversation_type', [
                'admin_chatbot',
                'website_chatbot',
                'customer_service',
                'product_search'
            ])->default('admin_chatbot');

            $table->string('title')->nullable();

            $table->enum('status', ['active', 'archived'])->default('active');

            $table->string('last_intent_key')->nullable();
            $table->dateTime('last_message_at')->nullable();
            $table->dateTime('last_activity_at')->nullable();

            $table->boolean('is_handoff')->default(false);

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['ai_channel_id', 'conversation_type']);
            $table->index(['user_id', 'status']);
            $table->index(['visitor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_conversations');
    }
};