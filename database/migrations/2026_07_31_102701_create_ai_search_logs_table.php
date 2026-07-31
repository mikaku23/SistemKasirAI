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
        Schema::create('ai_search_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_channel_id')->nullable()->constrained('ai_channels')->nullOnDelete();
            $table->foreignId('visitor_id')->nullable()->constrained('visitors')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ai_conversation_id')->nullable()->constrained('ai_conversations')->nullOnDelete();
            $table->string('query_text');
            $table->string('resolved_intent')->nullable();
            $table->unsignedInteger('result_count')->default(0);
            $table->foreignId('clicked_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->json('filters')->nullable();
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['query_text']);
            $table->index(['resolved_intent']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_search_logs');
    }
};
