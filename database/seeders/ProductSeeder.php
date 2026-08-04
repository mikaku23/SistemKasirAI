<?php

namespace Database\Seeders;

use App\Models\Categories;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockBatches;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Categories::query()->pluck('id')->all();
        $units = Unit::query()->pluck('id')->all();
        $suppliers = Supplier::query()->pluck('id')->all();
        $locations = Location::query()->pluck('id')->all();

        if (empty($categories) || empty($units) || empty($suppliers) || empty($locations)) {
            return;
        }

        $products = [
            [
                'name' => 'Kopi Arabica',
                'sku' => 'PRD-001',
                'barcode' => 'BAR-001',
                'purchase_price' => 25000,
                'sale_price' => 35000,
                'min_stock' => 10,
                'stock_on_hand' => 50,
                'description' => 'Kopi arabica premium',
                'is_active' => true,
            ],
            [
                'name' => 'Keripik Singkong',
                'sku' => 'PRD-002',
                'barcode' => 'BAR-002',
                'purchase_price' => 12000,
                'sale_price' => 18000,
                'min_stock' => 20,
                'stock_on_hand' => 80,
                'description' => 'Keripik singkong renyah',
                'is_active' => true,
            ],
            [
                'name' => 'Sabun Cuci',
                'sku' => 'PRD-003',
                'barcode' => 'BAR-003',
                'purchase_price' => 8000,
                'sale_price' => 12000,
                'min_stock' => 15,
                'stock_on_hand' => 60,
                'description' => 'Sabun cuci piring',
                'is_active' => true,
            ],
            [
                'name' => 'Baterai AAA',
                'sku' => 'PRD-004',
                'barcode' => 'BAR-004',
                'purchase_price' => 15000,
                'sale_price' => 22000,
                'min_stock' => 12,
                'stock_on_hand' => 40,
                'description' => 'Baterai ukuran AAA',
                'is_active' => true,
            ],
        ];

        foreach ($products as $index => $product) {
            $productModel = Product::firstOrCreate(
                ['sku' => $product['sku']],
                [
                    'category_id' => $categories[$index % count($categories)],
                    'unit_id' => $units[$index % count($units)],
                    'supplier_id' => $suppliers[$index % count($suppliers)],
                    'location_id' => $locations[$index % count($locations)],
                    'name' => $product['name'],
                    'slug' => Str::slug($product['name']),
                    'barcode' => $product['barcode'],
                    'sku' => $product['sku'],
                    'description' => $product['description'],
                    'short_description' => $product['description'],
                    'purchase_price' => $product['purchase_price'],
                    'sale_price' => $product['sale_price'],
                    'min_stock' => $product['min_stock'],
                    'stock_on_hand' => $product['stock_on_hand'],
                    'is_active' => $product['is_active'],
                ]
            );

            $batchCode = 'BATCH-' . $productModel->sku . '-001';
            $quantity = max(1, (int) $product['stock_on_hand']);

            StockBatches::firstOrCreate(
                ['batch_code' => $batchCode],
                [
                    'product_id' => $productModel->id,
                    'supplier_id' => $productModel->supplier_id,
                    'location_id' => $productModel->location_id,
                    'received_by' => null,
                    'batch_code' => $batchCode,
                    'lot_number' => 'LOT-' . $productModel->sku,
                    'qty_received' => $quantity,
                    'qty_remaining' => $quantity,
                    'purchase_price' => $productModel->purchase_price,
                    'production_date' => now()->subMonths(1)->toDateString(),
                    'expired_at' => now()->addMonths(6)->toDateString(),
                    'received_at' => now()->subDays(7)->toDateString(),
                    'status' => 'active',
                    'notes' => 'Seeded batch untuk transaksi demo',
                    'metadata' => [
                        'expiry_mode' => 'fixed_date',
                        'expiry_warning_days' => 30,
                        'expiry_grace_days' => 0,
                    ],
                ]
            );
        }
    }
}
