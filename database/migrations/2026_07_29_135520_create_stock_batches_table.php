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
         Schema::create('stock_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('batch_code')->unique();
            $table->string('lot_number')->nullable();
            $table->decimal('qty_received', 15, 2);
            $table->decimal('qty_remaining', 15, 2);
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->date('production_date')->nullable();
            $table->date('expired_at')->nullable();
            $table->date('received_at');
            $table->enum('status', ['active', 'near_expired', 'expired', 'finished', 'depleted', 'no_tracking', 'unknown', 'grace_period', 'expires_today', 'expiring_soon'])->default('active');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['product_id', 'expired_at']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};
