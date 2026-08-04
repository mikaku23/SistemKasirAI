<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("UPDATE transactions SET status = 'draft' WHERE status NOT IN ('draft', 'paid', 'cancelled', 'refunded')");
        DB::statement("ALTER TABLE transactions MODIFY status VARCHAR(20) NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY status ENUM('draft','paid','cancelled','refunded') NOT NULL DEFAULT 'draft'");
    }
};
