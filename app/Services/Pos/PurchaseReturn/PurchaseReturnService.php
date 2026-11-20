<?php

namespace App\Services\Pos\PurchaseReturn;

use App\Exceptions\GeneralException;
use App\Models\Pos\Product\Product;
use App\Models\Pos\Product\ProductSerial;
use App\Models\Pos\Product\TenantProductSerial;
use App\Models\Pos\Product\VariantInventory;
use App\Models\Pos\Purchase\Purchase;
use App\Models\Pos\Purchase\PurchaseItem;
use App\Models\Pos\PurchaseReturn\PurchaseReturn;
use App\Models\Pos\PurchaseReturn\PurchaseReturnItem;
use App\Models\Pos\Stock\Stock;
use App\Scopes\TenantScope;
use App\Services\BaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseReturnService extends BaseService
{

    public function __construct(PurchaseReturn $model)
    {
        $this->model = $model;
    }

    /**
     * @throws GeneralException
     */
    public function checkPurchaseReturn($request): static
    {
        $purchase = Purchase::query()
            ->where('id', $request['purchase_id'])
            ->first();

        if (!$purchase) {
            throw new GeneralException('Purchase not found');
        }
        if ($purchase->status == 'return') {
            throw new GeneralException('Purchase already returned');
        }

        return $this;
    }

    public function assignSupplerToTenant($request): static
    {
        $tenantId = $request->tenant_id ?? tenant()->id;

        $tenantExist = DB::table('supplier_tenant')
            ->where('supplier_id', $request['supplier_id'])
            ->where('tenant_id', $tenantId)
            ->exists();

        if (!$tenantExist) {
            DB::table('supplier_tenant')
                ->insert([
                    'supplier_id' => $request['supplier_id'],
                    'tenant_id' => $tenantId,
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
                ->when($isAppAdmin, fn($query) => $query->where('tenant_id', $request['tenant_id']))
                ->where('product_serial_id', $productSerial->id)
                ->whereIn('status', ['purchase', 'sale_return'])
                ->lockForUpdate()
                ->first();


            if (!$tenantSerial) {
                throw new GeneralException(
                    "Serial {$productSerial->serial_number} is not for purchases"
                );
            }

            $tenantSerial->update([
                'status' => 'purchase_return'
            ]);
        }


        return $this;
    }

    public function tenantNewPurchaseReturnProductSerialLog($serials, $request, $isAppAdmin): static
    {

        foreach ($serials as $item) {

            $productSerial = ProductSerial::query()
                ->where('serial_number', $item)
                ->first();


            $alreadyPurchased = TenantProductSerial::query()
                ->when($isAppAdmin, fn($query) => $query->where('tenant_id', $request['tenant_id']))
                ->where('product_serial_id', $productSerial->id)
                ->where('status', 'purchase')
                ->lockForUpdate()
                ->first();

            if (!$alreadyPurchased) {
                throw new GeneralException(
                    "Serial {$productSerial->serial_number} is not for purchases"
                );
            }

            $alreadyPurchased->update([
                'status' => 'purchase_return'
            ]);
        }


        return $this;
    }


    public function create($request): static
    {
        $this->model = $this->model::query()
            ->create([
                'contact_id' => $request['contact_id'],
                'date' => $request['date'],
                'sub_total' => $request['sub_total'],
                'discount_type' => $request['discount_type'],
                'discount_amount' => $request['discount_amount'],
                'order_tax' => $request['order_tax'],
                'shipping_cost' => $request['shipping_cost'],
                'grand_total' => $request['grand_total'],

            ]);

        return $this;
    }

    public function createInvoiceBill(Request $request): static
    {

        if ($request->hasFile('invoice_bill')) {
            $this->model->addMediaFromRequest('invoice_bill')
                ->toMediaCollection('purchase_return_bill_attachment');
        }

        return $this;
    }

    public function createItem(array $request): static
    {
        $now = now();

        // Build purchase items in one go
        $items = array_map(fn($item) => [
            'purchase_return_id' => $this->model->id,
            'product_id' => $item['product_id'],
            'product_variant_id' => $item['variant_id'],
            'purchase_id' => $item['purchase_id'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'serial_number' => $item['serial_number'],
            'note' => $item['note'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $request['items']);

        // Bulk insert purchase items
        PurchaseReturnItem::query()->insert($items);

        // Collect serial numbers + variant IDs for bulk update
        $serialNumbers = array_column($request['items'], 'serial_number');
        $variantIds = array_column($request['items'], 'variant_id');

        // Bulk update product serials in a single query
        ProductSerial::query()
            ->whereIn('serial_number', $serialNumbers)
            ->whereIn('product_variant_id', $variantIds)
            ->lockForUpdate()
            ->update(['status' => 'purchase_return', 'updated_at' => $now]);

        return $this;
    }

    public function updateStock($request, $isAppAdmin): static
    {
        $tenantId = $isAppAdmin ? $request['tenant_id'] : null;
        foreach ($request['items'] as $item) {
            Stock::query()
                ->when($isAppAdmin, fn($query) => $query->where('tenant_id', $tenantId))
                ->where('product_id', $item['product_id'])
                ->where('product_variant_id', $item['variant_id'])
                ->decrement('quantity', $item['quantity']);

        }
        return $this;
    }

    public function updatePurchaseReturn(array $data): static
    {

        $this->model->update([
            'contact_id' => $data['contact_id'],
            'date' => $data['date'],
            'sub_total' => $data['sub_total'],
            'discount_type' => $data['discount_type'],
            'discount_amount' => $data['discount_amount'],
            'order_tax' => $data['order_tax'],
            'shipping_cost' => $data['shipping_cost'],
            'grand_total' => $data['grand_total'],
        ]);


        return $this;
    }

    /**
     * @throws GeneralException
     */
    public function updatePurchaseReturnItem(array $request, $isAppAdmin): static
    {

        if (empty($request['items']) || !is_array($request['items'])) {
            return $this;
        }

        $incomingItems = collect($request['items']);
        $incomingSerials = $incomingItems->pluck('serial_number')->unique();

        $tenantId = $isAppAdmin
            ? ($request['tenant_id'] ?? null)
            : $this->model->tenant_id;

        $existingSerials = $this->model->items()
            ->pluck('serial_number');


        // 🔹 Fetch existing purchase items once
        $existingItems = PurchaseReturnItem::query()
            ->where('purchase_return_id', $this->model->id)
            ->whereIn('serial_number', $incomingSerials)
            ->get()
            ->keyBy('serial_number');


        foreach ($incomingItems as $item) {

            $purchaseItem = $existingItems->get($item['serial_number']);
            if ($purchaseItem) {
                // 🔄 Update existing item
                $purchaseItem->update([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'note' => $item['note'] ?? null,
                ]);
            } else {
                $newSerials = $incomingSerials->diff($existingSerials);

                $this->tenantNewPurchaseReturnProductSerialLog($newSerials, $request, $isAppAdmin);
                // ➕ Create new purchase item
                PurchaseReturnItem::query()->create([
                    'purchase_return_id' => $this->model->id,
                    'purchase_id' => $item['purchase_id'] ?? null,
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'serial_number' => $item['serial_number'],
                    'note' => $item['note'] ?? null,
                ]);

                // 📦 Decrement stock safely
                Stock::query()
                    ->where('tenant_id', $tenantId)
                    ->where('product_id', $item['product_id'])
                    ->where('product_variant_id', $item['variant_id'])
                    ->lockForUpdate()
                    ->decrement('quantity', $item['quantity']);

                // 🔢 Update serial status
                ProductSerial::query()
                    ->where('serial_number', $item['serial_number'])
                    ->where('product_variant_id', $item['variant_id'])
                    ->update(['status' => 'purchase_return']);

            }
        }

        // Handle removed items

        $removedSerials = $existingSerials->diff($incomingSerials);

        if ($removedSerials->isNotEmpty()) {

            $removedItems = PurchaseReturnItem::query()
                ->where('purchase_return_id', $this->model->id)
                ->whereIn('serial_number', $removedSerials)
                ->get();

            foreach ($removedItems as $item) {

                // 📦 Decrease stock
                Stock::query()
                    ->where('tenant_id', $tenantId)
                    ->where('product_id', $item->product_id)
                    ->where('product_variant_id', $item->product_variant_id)
                    ->lockForUpdate()
                    ->increment('quantity', $item->quantity);

                // 🔁 Reset serial
                $serials = ProductSerial::query()
                    ->where('serial_number', $item->serial_number)
                    ->where('product_variant_id', $item->product_variant_id)
                    ->whereHas('tenantProductSerials', function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId)
                            ->where('status', 'purchase_return');
                    })
                    ->pluck('id');

                ProductSerial::query()
                    ->whereIn('id', $serials)
                    ->update(['status' => 'purchase']);

                // 🗑 Delete tenant serials
                TenantProductSerial::query()
                    ->where('tenant_id', $tenantId)
                    ->whereIn('product_serial_id', $serials)
                    ->where('status', 'purchase_return')
                    ->update([
                        'status' => 'purchase'
                    ]);

                // 🗑 Delete purchase item
                $item->delete();
            }
        }

        return $this;
    }


    public function stockDecrementAndStatusUpdate($isAppAdmin): static
    {

        $items = $this->model->items;
        $tenantId = $isAppAdmin ? $this->model->tenant_id : null;

        if ($items->isEmpty()) {
            return $this;
        }

        foreach ($items as $item) {
            Stock::query()
                ->when($isAppAdmin, fn($query) => $query->where('tenant_id', $tenantId))
                ->where('product_id', $item->product_id)
                ->where('product_variant_id', $item->product_variant_id)
                ->increment('quantity', $item->quantity);

            // 2️⃣ Get related product serials (locked)
            $serials = ProductSerial::query()
                ->whereIn('serial_number', $items->pluck('serial_number'))
                ->whereIn('product_variant_id', $items->pluck('product_variant_id'))
                ->whereHas('tenantProductSerials', function ($query) use ($isAppAdmin, $tenantId) {
                    $query->when($isAppAdmin, fn($query) => $query->where('tenant_id', $tenantId))
                        ->where('status', 'purchase_return');
                })
                ->lockForUpdate()
                ->get();

            if ($serials->isEmpty()) {
                return $this;
            }

            // 3️⃣ Restore product serial status
            ProductSerial::query()
                ->whereIn('id', $serials->pluck('id'))

                ->update([
                    'status' => 'purchase',
                    'updated_at' => now()
                ]);

            // 4️⃣ Restore tenant product serial status
            TenantProductSerial::query()
                ->when($isAppAdmin, fn($query) => $query->where('tenant_id', $tenantId))
                ->whereIn('product_serial_id', $serials->pluck('id'))
                ->update([
                    'status' => 'purchase',
                    'updated_at' => now()
                ]);
        }

        return $this;
    }

    public function itemsDelete(): static
    {
        $this->model->items()->delete();

        return $this;
    }

    public function transactionDelete(): static
    {
        $this->model->payments()->delete();

        return $this;
    }

    public function purchaseReturnDelete(): static
    {
        $this->model->delete();

        return $this;
    }
}
