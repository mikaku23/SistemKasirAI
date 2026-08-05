<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'promo_starts_at')) {
                $table->dateTime('promo_starts_at')->nullable()->after('promo_discount_is_active');
            }

            if (! Schema::hasColumn('products', 'promo_ends_at')) {
                $table->dateTime('promo_ends_at')->nullable()->after('promo_starts_at');
            }

            if (! Schema::hasColumn('products', 'promo_metadata')) {
                $table->json('promo_metadata')->nullable()->after('promo_ends_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            foreach (['promo_metadata', 'promo_ends_at', 'promo_starts_at'] as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
