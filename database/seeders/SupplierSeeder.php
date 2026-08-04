<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'Supplier Utama',
                'code' => 'SUP-001',
                'phone' => '081111111111',
                'email' => 'supplier@utama.com',
                'address' => 'Jl. Raya No. 100',
                'notes' => 'Supplier utama harian',
                'is_active' => true,
            ],
            [
                'name' => 'CV. Sejahtera',
                'code' => 'SUP-002',
                'phone' => '082222222222',
                'email' => 'cs@sejahtera.com',
                'address' => 'Jl. Cendana No. 22',
                'notes' => 'Supplier barang konsumsi',
                'is_active' => true,
            ],
            [
                'name' => 'PT. Global Supply',
                'code' => 'SUP-003',
                'phone' => '083333333333',
                'email' => 'sales@globalsupply.com',
                'address' => 'Jl. Industri No. 77',
                'notes' => 'Supplier barang elektronik',
                'is_active' => true,
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::firstOrCreate(
                ['code' => $supplier['code']],
                $supplier
            );
        }
    }
}
