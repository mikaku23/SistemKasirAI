<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('tax_type', ['fixed', 'percent'])->default('fixed');
            $table->unsignedInteger('tax_value')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_default', 'is_active']);
            $table->index(['tax_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_settings');
    }
};
