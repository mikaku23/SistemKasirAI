<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('transactions', 'discount_setting_id')) {
                $table->foreignId('discount_setting_id')->nullable()->after('tax_setting_id')->constrained('discount_settings')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'discount_setting_id')) {
                $table->dropConstrainedForeignId('discount_setting_id');
            }
        });
    }
};
