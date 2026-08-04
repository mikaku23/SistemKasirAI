<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('discount_type', ['percent', 'fixed'])->default('percent');
            $table->unsignedInteger('discount_value')->default(0);
            $table->unsignedInteger('minimum_total_amount')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->unsignedSmallInteger('priority')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['is_active', 'is_default']);
            $table->index(['minimum_total_amount']);
            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_settings');
    }
};
