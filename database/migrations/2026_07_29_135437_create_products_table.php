<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                ->constrained('categories')
                ->restrictOnDelete();

            $table->foreignId('unit_id')
                ->constrained('units')
                ->restrictOnDelete();

            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->nullOnDelete();

            $table->foreignId('location_id')
                ->nullable()
                ->constrained('locations')
                ->nullOnDelete();

            $table->string('name');
            $table->string('slug')->nullable()->unique();
            $table->string('barcode')->unique();
            $table->string('sku')->nullable()->unique();

            $table->text('description')->nullable();
            $table->text('short_description')->nullable();

            $table->string('image')->nullable();

            $table->json('search_keywords')->nullable();

            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->decimal('sale_price', 15, 2)->default(0);
            $table->unsignedInteger('min_stock')->default(0);
            $table->unsignedInteger('stock_on_hand')->default(0);

            $table->boolean('tracks_expiry')->default(false);
            $table->string('expiry_type', 20)->default('none');
            $table->date('production_date')->nullable();
            $table->date('expired_at')->nullable();
            $table->unsignedSmallInteger('shelf_life_days')->nullable();
            $table->unsignedSmallInteger('expiry_warning_days')->default(30);
            $table->unsignedSmallInteger('expiry_grace_days')->default(0);

            $table->boolean('is_featured')->default(false);
            $table->boolean('is_available_online')->default(true);
            $table->decimal('popularity_score', 8, 2)->default(0);
            $table->timestamp('last_sold_at')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'unit_id']);
            $table->index(['name']);
            $table->index(['is_featured', 'is_available_online']);
            $table->index(['popularity_score']);
            $table->index(['tracks_expiry', 'expiry_type']);
            $table->index(['expired_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
