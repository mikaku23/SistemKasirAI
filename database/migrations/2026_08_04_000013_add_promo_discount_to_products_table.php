<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'promo_discount_amount')) {
                $table->unsignedInteger('promo_discount_amount')->default(0)->after('expired_at');
            }

            if (! Schema::hasColumn('products', 'promo_discount_is_active')) {
                $table->boolean('promo_discount_is_active')->default(false)->after('promo_discount_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'promo_discount_is_active')) {
                $table->dropColumn('promo_discount_is_active');
            }

            if (Schema::hasColumn('products', 'promo_discount_amount')) {
                $table->dropColumn('promo_discount_amount');
            }
        });
    }
};
