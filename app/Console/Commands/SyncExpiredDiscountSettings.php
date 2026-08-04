<?php

namespace App\Console\Commands;

use App\Http\Services\DiscountSettingService;
use Illuminate\Console\Command;

class SyncExpiredDiscountSettings extends Command
{
    protected $signature = 'discount-settings:sync-expired';

    protected $description = 'Auto-disable discount settings whose period has already ended.';

    public function handle(DiscountSettingService $service): int
    {
        $count = $service->syncExpiredDiscountSettings();

        $this->info("Expired discount settings synchronized: {$count}");

        return self::SUCCESS;
    }
}
