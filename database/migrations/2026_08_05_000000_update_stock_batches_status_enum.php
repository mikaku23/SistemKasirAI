<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `stock_batches` MODIFY `status` ENUM('active','near_expired','expired','finished','depleted','no_tracking','unknown','grace_period','expires_today','expiring_soon') NOT NULL DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `stock_batches` MODIFY `status` ENUM('active','near_expired','expired','finished') NOT NULL DEFAULT 'active'");
    }
};
