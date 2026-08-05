<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'stock_on_hand')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE products MODIFY stock_on_hand INT UNSIGNED NULL DEFAULT NULL');
            } else {
                Schema::table('products', function (Blueprint $table) {
                    $table->integer('stock_on_hand')->nullable()->default(null)->change();
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'stock_on_hand')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE products MODIFY stock_on_hand INT UNSIGNED NOT NULL DEFAULT 0');
            } else {
                Schema::table('products', function (Blueprint $table) {
                    $table->unsignedInteger('stock_on_hand')->default(0)->change();
                });
            }
        }
    }
};
