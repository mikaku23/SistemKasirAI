<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            [
                'name' => 'Gudang Utama',
                'code' => 'WH-001',
                'address' => 'Jl. Merdeka No. 1',
                'phone' => '081234567890',
                'is_active' => true,
            ],
            [
                'name' => 'Toko Pusat',
                'code' => 'ST-001',
                'address' => 'Jl. Sudirman No. 10',
                'phone' => '081234567891',
                'is_active' => true,
            ],
            [
                'name' => 'Cabang Bandung',
                'code' => 'BR-001',
                'address' => 'Jl. Asia Afrika No. 15',
                'phone' => '081234567892',
                'is_active' => true,
            ],
        ];

        foreach ($locations as $location) {
            Location::firstOrCreate(
                ['code' => $location['code']],
                $location
            );
        }
    }
}
