<?php

namespace App\Services\Pos\SaleReturn;

use App\Exceptions\GeneralException;
use App\Models\Pos\Product\ProductSerial;
use App\Models\Pos\Product\TenantProductSerial;
use App\Models\Pos\Sale\Sale;
use App\Models\Pos\SaleReturn\SaleReturn;
use App\Models\Pos\SaleReturn\SaleReturnItem;
use App\Models\Pos\Stock\Stock;
use App\Models\Pos\Transaction\Transaction;
use App\Scopes\TenantScope;
use App\Services\BaseService;
use Illuminate\Http\Request;

class SaleReturnService extends BaseService
{
    public function __construct(SaleReturn $model)
    {
        $this->model = $model;
    }

    public function create($request): static
    {
        $this->model = $this->model::query()
            ->create([
                'contact_id' => $request['contact_id'],
                'date' => $request['date'],
                'sub_total' => $request['sub_total'],
                'order_tax' => $request['order_tax'],
                'shipping_cost' => $request['shipping_cost'],
                'grand_total' => $request['grand_total'],

            ]);

        return $this;
    }

    public function createItem($request): static
    {

        $now = now();

        // Build purchase items in one go
        $items = array_map(fn ($item) => [
            'sale_return_id' => $this->model->id,
            'product_id' => $item['product_id'],
            'product_variant_id' => $item['variant_id'],
            'sale_id' => $item['sale_id'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'discount_type' => $item['discount_type'],
            'discount_amount' => $item['discount_amount'],
            'serial_number' => $item['serial_number'],
            'note' => $item['note'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $request['items']);

        // Bulk insert purchase items
        SaleReturnItem::query()->insert($items);

        // Collect serial numbers + variant IDs for bulk update
        $serialNumbers = array_column($request['items'], 'serial_number');
        $variantIds = array_column($request['items'], 'variant_id');

        // Bulk update product serials in a single query
        ProductSerial::query()
            ->whereIn('serial_number', $serialNumbers)
            ->whereIn('product_variant_id', $variantIds)
            ->where('status', 'sale')
            ->lockForUpdate()
            ->update(['status' => 'sale_return', 'updated_at' => $now]);

        return $this;
    }

    public function updateSaleReturn(array $data): static
    {

        $this->model->update([
            'contact_id' => $data['contact_id'],
            'date' => $data['date'],
            'sub_total' => $data['sub_total'],
            'order_tax' => $data['order_tax'],
            'shipping_cost' => $data['shipping_cost'],
            'grand_total' => $data['grand_total'],
        ]);

        return $this;
    }

    /**
     * @throws GeneralException
     */
    public function updateSaleReturnItems(array $request, $isAppAdmin): static
    {
        if (empty($request['items']) || ! is_array($request['items'])) {
            return $this;
        }

        $incomingItems = collect($request['items']);
        $incomingSerials = $incomingItems->pluck('serial_number')->unique();

        $tenantId = $isAppAdmin ? $request['tenant_id'] : $this->model->tenant_id;

        $existingSerials = $this->model->items()
            ->pluck('serial_number');

        // 🔹 Fetch existing purchase items once
        $existingItems = SaleReturnItem::query()
            ->where('sale_return_id', $this->model->id)
            ->whereIn('serial_number', $incomingSerials)
            ->get()
            ->keyBy('serial_number');

        $newSerials = $incomingSerials->diff($existingSerials);
        if ($newSerials->isNotEmpty()) {
            $this->tenantNewSaleProductSerialLog($newSerials, $request, $isAppAdmin);
        }

        foreach ($incomingItems as $item) {
            // Find existing sale return item by serial_number and sale return id
            $saleReturnItem = $existingItems->get($item['serial_number']);

            if ($saleReturnItem) {
                // Update existing item
                $saleReturnItem->update([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['variant_id'],
                    'sale_id' => $item['sale_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_type' => $item['discount_type'],
                    'discount_amount' => $item['discount_amount'],
                    'note' => $item['note'] ?? null,
                ]);

            } else {
                SaleReturnItem::query()->create([
                    'sale_return_id' => $this->model->id,
                    'sale_id' => $item['sale_id'] ?? null,
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'serial_number' => $item['serial_number'],
                    'discount_type' => $item['discount_type'],
                    'discount_amount' => $item['discount_amount'],
                    'note' => $item['note'] ?? null,
                ]);

                Stock::query()
                    ->where('tenant_id', $tenantId)
                    ->where('product_id', $item['product_id'])
                    ->where('product_variant_id', $item['variant_id'])
                    ->increment('quantity', $item['quantity']);

                // Update serial status to 'sale'
                ProductSerial::query()
                    ->where('serial_number', $item['serial_number'])
                    ->where('product_variant_id', $item['variant_id'])
                    ->whereHas('tenantProductSerials', function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId)
                            ->whereIn('status', ['sale']);
                    })
                    ->lockForUpdate()
                    ->update(['status' => 'sale_return']);

            }
        }

        // Handle removed items

        $removedSerials = $existingSerials->diff($incomingSerials);

        if ($removedSerials->isNotEmpty()) {

            $removedItems = SaleReturnItem::query()
                ->where('sale_return_id', $this->model->id)
                ->whereIn('serial_number', $removedSerials)
                ->get();

            foreach ($removedItems as $item) {

                // 📦 Increment stock
                Stock::query()
                    ->where('tenant_id', $tenantId)
                    ->where('product_id', $item->product_id)
                    ->where('product_variant_id', $item->product_variant_id)
                    ->lockForUpdate()
                    ->decrement('quantity', $item->quantity);

                // 🔁 Reset serial
                $serials = ProductSerial::query()
                    ->where('serial_number', $item->serial_number)
                    ->where('product_variant_id', $item->product_variant_id)
                    ->whereHas('tenantProductSerials', function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId)
                            ->whereIn('status', ['sale_return']);
                    })
                    ->pluck('id');

                ProductSerial::query()
                    ->whereIn('id', $serials)
                    ->update(['status' => 'sale']);

                // 🗑 Delete tenant serials
                TenantProductSerial::query()
                    ->where('tenant_id', $tenantId)
                    ->whereIn('product_serial_id', $serials)
                    ->whereIn('status', ['sale_return'])
                    ->update([
                        'status' => 'sale',
                    ]);

                // 🗑 Delete purchase item
                $item->delete();
            }

        }

        return $this;
    }

    public function saleReturnTransaction(Request $request): static
    {
        if (round($request->return_amount, 2) > 0) {
            $invoiceNumber = Transaction::query()->withoutGlobalScope(new TenantScope)->max('invoice_number') + 1;

            $this->model->payments()->create([
                'date' => $request['date'],
                'invoice_number' => $invoiceNumber,
                'full_invoice_number' => 'SRT-'.$invoiceNumber,
                'payment_method_id' => $request['payment_method_id'],
                'amount' => $request['return_amount'],
                'tenant_id' => $this->model->tenant_id,
                'note' => $request['note'] ?? null,
            ]);

            $this->model->update([
                'status' => $this->model->grand_total == $this->model->getTransactionAmountAttribute() ? 'paid' : 'partially_paid',
            ]);
        }

        return $this;
    }

    /**
     * @throws GeneralException
     */
    public function tenantProductSerialLog($request, $isAppAdmin): static
    {
        foreach ($request['items'] as $item) {

            $productSerial = ProductSerial::query()
                ->where('serial_number', $item['serial_number'])
                ->where('product_variant_id', $item['variant_id'])
                ->first();

            $tenantSerial = TenantProductSerial::query()
                ->when($isAppAdmin, fn ($query) => $query->where('tenant_id', $request['tenant_id']))
                ->where('product_serial_id', $productSerial->id)
                ->where('status', 'sale')
                ->lockForUpdate()
                ->first();

            if (! $tenantSerial) {
                throw new GeneralException(
                    "Serial {$productSerial->serial_number} is not for sale"
                );
            }

            $tenantSerial->update([
                'status' => 'sale_return',
            ]);
        }

        return $this;
    }

    public function tenantNewSaleProductSerialLog($serials, $request, $isAppAdmin): static
    {
        foreach ($serials as $item) {

            $productSerial = ProductSerial::query()
                ->where('serial_number', $item)
                ->first();

            $tenantSerial = TenantProductSerial::query()
                ->when($isAppAdmin, fn ($query) => $query->where('tenant_id', $request['tenant_id']))
                ->where('product_serial_id', $productSerial->id)
                ->where('status', 'sale')
                ->lockForUpdate()
                ->first();

            if (! $tenantSerial) {
                throw new GeneralException(
                    "Serial {$productSerial->serial_number} is not for sale"
                );
            }
            TenantProductSerial::query()->create([
                'product_serial_id' => $productSerial->id,
                'status' => 'sale_return',
            ]);

        }

        return $this;
    }

    public function updateStock($request, $isAppAdmin): static
    {
        foreach ($request['items'] as $item) {
            Stock::query()
                ->when($isAppAdmin, fn ($query) => $query->where('tenant_id', $request['tenant_id']))
                ->where('product_id', $item['product_id'])
                ->where('product_variant_id', $item['variant_id'])
                ->lockForUpdate()
                ->increment('quantity', $item['quantity']);
        }

        return $this;
    }

    public function stockIncrementAndStatusUpdate($isAppAdmin): static
    {
        $items = $this->model->items;
        $tenantId = $this->model->tenant_id;

        if ($items->isEmpty()) {
            return $this;
        }

        foreach ($items as $item) {
            Stock::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)
                ->where('product_id', $item->product_id)
                ->where('product_variant_id', $item->product_variant_id)
                ->decrement('quantity', $item->quantity);
        }

        $serialNumbers = $items->pluck('serial_number')->filter()->values();
        if ($serialNumbers->isNotEmpty()) {
            $serials = ProductSerial::query()
                ->whereIn('serial_number', $serialNumbers)
                ->whereIn('product_variant_id', $items->pluck('product_variant_id'))
                ->whereHas('tenantProductSerials', function ($query) use ($tenantId) {
                    $query->withoutGlobalScope(TenantScope::class)
                        ->where('tenant_id', $tenantId)
                        ->where('status', 'sale_return');
                })
                ->lockForUpdate()
                ->get();

            if ($serials->isNotEmpty()) {
                ProductSerial::query()
                    ->whereIn('id', $serials->pluck('id'))
                    ->update([
                        'status' => 'sale',
                        'updated_at' => now(),
                    ]);

                TenantProductSerial::withoutGlobalScope(TenantScope::class)
                    ->where('tenant_id', $tenantId)
                    ->whereIn('product_serial_id', $serials->pluck('id'))
                    ->update([
                        'status' => 'sale',
                        'updated_at' => now(),
                    ]);
            }
        }

        return $this;
    }

    public function itemsDelete(): static
    {
        $this->model->items()->delete();

        return $this;
    }

    public function saleReturnTransactionsDelete(): static
    {
        $this->model->payments()->delete();

        return $this;
    }

    public function saleReturnDelete(): static
    {
        $this->model->delete();

        return $this;
    }
}
