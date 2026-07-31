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
       Schema::create('ai_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique(); // admin_chatbot, website_chatbot, customer_service, product_search
            $table->enum('type', ['admin', 'public', 'customer_service', 'search']);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->longText('system_prompt')->nullable();
            $table->json('allowed_tools')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_channels');
    }
};
