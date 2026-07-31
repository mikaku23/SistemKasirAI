<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_messages', function (Blueprint $table) {
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

            $table->foreignId('ai_conversation_id')
                ->nullable()
                ->constrained('ai_conversations')
                ->nullOnDelete();

            $table->enum('recipient_type', ['user', 'visitor', 'admin', 'system'])->default('system');

            $table->enum('type', ['info', 'warning', 'prediction', 'security'])->default('info');

            $table->string('title');
            $table->longText('content');

            $table->string('target_url')->nullable();
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();

            $table->boolean('is_read')->default(false);
            $table->dateTime('read_at')->nullable();

            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            $table->tinyInteger('priority')->default(0);

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['ai_channel_id', 'recipient_type']);
            $table->index(['type', 'is_read']);
            $table->index(['target_type', 'target_id']);
            $table->index(['visitor_id']);
            $table->index(['ai_conversation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
    }
};