<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('transactions', 'tax_setting_id')) {
                $table->foreignId('tax_setting_id')
                    ->nullable()
                    ->after('cashier_id')
                    ->constrained('tax_settings')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'tax_setting_id')) {
                $table->dropConstrainedForeignId('tax_setting_id');
            }
        });
    }
};
