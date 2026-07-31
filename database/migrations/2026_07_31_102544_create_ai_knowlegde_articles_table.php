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
        Schema::create('ai_knowledge_articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('question')->nullable();
            $table->longText('answer');
            $table->string('category')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('is_active')->default(true);
            $table->tinyInteger('priority')->default(0);
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_knowlegde_articles');
    }
};
