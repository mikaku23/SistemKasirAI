<?php

namespace Database\Seeders;

use App\Models\Categories;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Minuman',
                'description' => 'Kategori produk minuman',
                'sku' => 'CAT-MIN-001',
                'is_active' => true,
            ],
            [
                'name' => 'Makanan Ringan',
                'description' => 'Kategori produk snack dan jajanan',
                'sku' => 'CAT-SNK-001',
                'is_active' => true,
            ],
            [
                'name' => 'Kebutuhan Rumah Tangga',
                'description' => 'Kategori kebutuhan rumah tangga',
                'sku' => 'CAT-HOME-001',
                'is_active' => true,
            ],
            [
                'name' => 'Elektronik',
                'description' => 'Kategori produk elektronik',
                'sku' => 'CAT-ELEC-001',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            Categories::firstOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'slug' => Str::slug($category['name']),
                    'description' => $category['description'],
                    'sku' => $category['sku'],
                    'is_active' => $category['is_active'],
                ]
            );
        }
    }
}
