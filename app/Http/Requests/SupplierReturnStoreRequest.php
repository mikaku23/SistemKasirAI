<?php

namespace App\Http\Requests;

use App\Models\StockBatches;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierReturnStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->map(function ($item) {
                return [
                    'product_id' => isset($item['product_id']) && $item['product_id'] !== '' ? (int) $item['product_id'] : null,
                    'stock_batch_id' => isset($item['stock_batch_id']) && $item['stock_batch_id'] !== '' ? (int) $item['stock_batch_id'] : null,
                    'quantity' => isset($item['quantity']) && $item['quantity'] !== '' ? (int) preg_replace('/[^0-9]/', '', (string) $item['quantity']) : null,
                    'unit_price' => isset($item['unit_price']) && $item['unit_price'] !== '' ? (int) preg_replace('/[^0-9]/', '', (string) $item['unit_price']) : null,
                    'notes' => isset($item['notes']) ? trim((string) $item['notes']) : null,
                ];
            })
            ->filter(function (array $item): bool {
                return collect($item)->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty();
            })
            ->values()
            ->all();

        $this->merge([
            'return_code' => trim((string) $this->input('return_code')),
            'supplier_id' => $this->input('supplier_id') !== null ? (int) $this->input('supplier_id') : null,
            'location_id' => $this->input('location_id') !== null ? (int) $this->input('location_id') : null,
            'return_at' => trim((string) $this->input('return_at')),
            'reason' => trim((string) $this->input('reason')),
            'items' => $items,
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $supplierId = (int) $this->input('supplier_id');
            $locationId = (int) $this->input('location_id');

            foreach ((array) $this->input('items', []) as $index => $item) {
                $batchId = (int) ($item['stock_batch_id'] ?? 0);
                $productId = (int) ($item['product_id'] ?? 0);
                $quantity = (int) ($item['quantity'] ?? 0);

                if ($batchId <= 0 || $productId <= 0) {
                    continue;
                }

                $batch = StockBatches::query()
                    ->with(['product'])
                    ->whereNull('deleted_at')
                    ->find($batchId);

                if (! $batch) {
                    $validator->errors()->add("items.$index.stock_batch_id", 'Batch stok tidak ditemukan atau sudah tidak aktif.');
                    continue;
                }

                if ((int) $batch->product_id !== $productId) {
                    $validator->errors()->add("items.$index.stock_batch_id", 'Batch tidak sesuai dengan product yang dipilih.');
                }

                if ($supplierId > 0 && (int) $batch->supplier_id !== $supplierId) {
                    $validator->errors()->add("items.$index.stock_batch_id", 'Batch bukan milik supplier yang dipilih.');
                }

                if ($locationId > 0 && (int) $batch->location_id !== $locationId) {
                    $validator->errors()->add("items.$index.stock_batch_id", 'Batch bukan berasal dari location yang dipilih.');
                }

                if ($quantity > 0 && (int) $batch->qty_remaining !== $quantity) {
                    $validator->errors()->add("items.$index.quantity", 'Qty harus sama dengan sisa stok batch karena batch akan diarsipkan penuh.');
                }
            }
        });
    }

    public function rules(): array
    {
        return [
            'return_code' => ['nullable', 'string', 'max:120'],
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')],
            'location_id' => ['required', 'integer', Rule::exists('locations', 'id')],
            'return_at' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'items.*.stock_batch_id' => ['required', 'integer', Rule::exists('stock_batches', 'id')],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'integer', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Supplier wajib dipilih.',
            'location_id.required' => 'Location wajib dipilih.',
            'return_at.required' => 'Tanggal return wajib diisi.',
            'reason.required' => 'Alasan return wajib diisi.',
            'items.required' => 'Minimal 1 item return harus diisi.',
            'items.*.product_id.required' => 'Produk pada item return wajib dipilih.',
            'items.*.stock_batch_id.required' => 'Batch stok pada item return wajib dipilih.',
            'items.*.quantity.required' => 'Qty return wajib diisi.',
            'items.*.quantity.min' => 'Qty return minimal 1.',
        ];
    }
}
