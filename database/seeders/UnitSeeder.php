<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            [
                'name' => 'Pieces',
                'symbol' => 'pcs',
                'is_active' => true,
            ],
            [
                'name' => 'Box',
                'symbol' => 'box',
                'is_active' => true,
            ],
            [
                'name' => 'Kilogram',
                'symbol' => 'kg',
                'is_active' => true,
            ],
            [
                'name' => 'Liter',
                'symbol' => 'ltr',
                'is_active' => true,
            ],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(
                ['symbol' => $unit['symbol']],
                $unit
            );
        }
    }
}
