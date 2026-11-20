<?php

namespace App\Services\Pos\Purchase;

use App\Exceptions\GeneralException;
use App\Models\Pos\Product\ProductSerial;
use App\Models\Pos\Product\TenantProductSerial;
use App\Models\Pos\Purchase\Purchase;
use App\Models\Pos\Purchase\PurchaseItem;
use App\Models\Pos\Sale\SaleProductTemp;
use App\Models\Pos\Stock\Stock;
use App\Services\BaseService;
use App\Services\Pos\Report\ProfitLoss\ProfitLossSummaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseService extends BaseService
{
    protected array $saleProductTempSerials = [];

    public function __construct(Purchase $model)
    {
        $this->model = $model;
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

    public function create($request): static
    {
        $this->model = $this->model::query()
            ->create([
                'contact_id' => $request['contact_id'] ?? null,
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
                ->toMediaCollection('purchase_bill_attachment', config('media-library.disk_name', 's3'));
        }

        return $this;
    }

    public function createItem(array $request): static
    {
        $now = now();

        // Build purchase items in one go
        $items = array_map(fn($item) => [
            'purchase_id' => $this->model->id,
            'product_id' => $item['product_id'],
            'product_variant_id' => $item['variant_id'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'serial_number' => $item['serial_number'],
            'note' => $item['note'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $request['items']);

        // Bulk insert purchase items
        PurchaseItem::query()->insert($items);

        // Collect serial numbers + variant IDs for bulk update
        $serialNumbers = array_column($request['items'], 'serial_number');
        $variantIds = array_column($request['items'], 'variant_id');

        // Bulk update product serials in a single query
        ProductSerial::query()
            ->whereIn('serial_number', $serialNumbers)
            ->whereIn('product_variant_id', $variantIds)
            ->update(['status' => 'purchase', 'updated_at' => $now]);


        return $this;
    }

    public function processSaleProductTemps(array $request): static
    {
        $serialNumbers = array_column($request['items'], 'serial_number');

        $saleTemps = SaleProductTemp::query()
            ->whereIn('serial_number', $serialNumbers)
            ->get();

        if ($saleTemps->isEmpty()) {
            return $this;
        }

        // A serial sold in "product" mode was sold OUT of a specific
        // warehouse (SaleProductTemp.tenant_id, set at sale time) before it
        // was ever purchased. Buying it into a different warehouse now would
        // silently attribute the incoming stock to the wrong tenant relative
        // to where the customer's sale actually happened — same class of
        // mismatch tenantProductSerialLog() above already guards against for
        // ordinary re-purchases, just against SaleProductTemp instead of
        // TenantProductSerial.
        $purchaseTenantId = (int) ($request['tenant_id'] ?? optional(tenant())->id);

        foreach ($saleTemps as $temp) {
            if ((int) $temp->tenant_id !== $purchaseTenantId) {
                $tenantName = DB::table('tenants')->where('id', $temp->tenant_id)->value('name')
                    ?? "#{$temp->tenant_id}";

                throw new GeneralException(
                    "Serial {$temp->serial_number} was sold from warehouse \"{$tenantName}\" — it must be purchased into that same warehouse."
                );
            }
        }

        $this->saleProductTempSerials = $saleTemps->pluck('serial_number')->toArray();

        foreach ($saleTemps as $temp) {
            $productSerial = ProductSerial::query()
                ->where('serial_number', $temp->serial_number)
                ->where('product_variant_id', $temp->product_variant_id)
                ->first();

            if ($productSerial) {

                TenantProductSerial::query()
                    ->updateOrCreate([
                        'product_serial_id' => $productSerial->id,
                        'tenant_id' => $temp->tenant_id
                    ], [
                        'status' => 'sale',
                    ]);
            }
        }

        SaleProductTemp::query()
            ->whereIn('serial_number', $serialNumbers)
            ->delete();

        return $this;
    }

    /**
     * A "product" mode sale (sale_type = 'product', see SaleService's
     * SaleProductTemp handling) lets a serial be sold before it's ever been
     * purchased — at that point ProfitLossSummaryService has no matching
     * purchase_items row for the serial, so it records purchase_unit_price
     * as 0 and the sale looks like pure profit. Once processSaleProductTemps()
     * above resolves which of THIS purchase's serials were fulfilling one of
     * those pending sales, the originating sale(s) need their profit/loss
     * row recomputed now that real cost data finally exists — nothing else
     * in the app re-visits an already-recorded sale when a later purchase
     * retroactively supplies its cost.
     */
    public function recalculateProfitLossForFulfilledSales(): static
    {
        if (empty($this->saleProductTempSerials)) {
            return $this;
        }

        $saleIds = DB::table('sale_items')
            ->whereIn('serial_number', $this->saleProductTempSerials)
            ->where('sale_type', 'product')
            ->pluck('sale_id')
            ->unique();

        $service = app(ProfitLossSummaryService::class);

        foreach ($saleIds as $saleId) {
            $service->recalculate($saleId);
        }

        return $this;
    }

    public function updateStock(Request $request): static
    {
        foreach ($request['items'] as $item) {

            if (in_array($item['serial_number'], $this->saleProductTempSerials)) {
                continue;
            }

            Stock::query()
                ->when(auth()->user()->isAppAdmin(), fn($query) => $query->where('tenant_id', $request['tenant_id']))
                ->updateOrCreate([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['variant_id'],
                ], [
                    'quantity' => DB::raw('quantity + ' . (int)$item['quantity']),
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


            $latestSerial = TenantProductSerial::query()
                ->where('product_serial_id', $productSerial->id)
                ->latest()
                ->first();

            if ($isAppAdmin) {
                if ($latestSerial) {
                    $isSameTenant = $latestSerial->tenant_id == $request['tenant_id'];
                    $isSaleStatus = $latestSerial->status === 'sale';

                    if ($isSameTenant) {
                        throw new GeneralException(
                            "Serial {$productSerial->serial_number} is already in your warehouse."
                        );
                    }

                    if (!$isSaleStatus) {
                        throw new GeneralException(
                            "Serial {$productSerial->serial_number} is currently in stock under another warehouse."
                        );
                    }
                }
            } else {
                $alreadyPurchased = TenantProductSerial::query()
                    ->where('product_serial_id', $productSerial->id)
                    ->whereIn('status', ['purchase'])
                    ->exists();
                if ($alreadyPurchased) {
                    throw new GeneralException(
                        "Serial {$productSerial->serial_number} already purchased"
                    );
                }
            }

            $targetTenantId = $isAppAdmin ? ($request['tenant_id'] ?? null) : tenant()?->id;

            // A serial being bought back after it was sold (the $isSaleStatus
            // case above) must reuse its existing row - updating its status
            // and tenant in place - rather than piling up a second row next
            // to the one already there. That second row is exactly what made
            // an already-sold serial show back up as available stock.
            if ($latestSerial) {
                $latestSerial->update([
                    'status'    => 'purchase',
                    'tenant_id' => $targetTenantId ?? $latestSerial->tenant_id,
                ]);
            } else {
                TenantProductSerial::query()->create([
                    'product_serial_id' => $productSerial->id,
                    'tenant_id'         => $targetTenantId,
                    'status'            => 'purchase',
                ]);
            }
        }
        return $this;
    }

    /**
     * @throws GeneralException
     */
    public function tenantNewPurchaseProductSerialLog($serials, $request, $isAppAdmin): static
    {
        foreach ($serials as $item) {

            $productSerial = ProductSerial::query()
                ->where('serial_number', $item)
                ->first();

            $latestSerial = TenantProductSerial::query()
                ->where('product_serial_id', $productSerial->id)
                ->latest()
                ->first();

            if ($isAppAdmin) {

                if ($latestSerial) {
                    $isSameTenant = $latestSerial->tenant_id == $request['tenant_id'];
                    $isSaleStatus = $latestSerial->status === 'sale';

                    if ($isSameTenant) {
                        throw new GeneralException(
                            "Serial {$productSerial->serial_number} is already in your warehouse."
                        );
                    }

                    if (!$isSaleStatus) {
                        throw new GeneralException(
                            "Serial {$productSerial->serial_number} is currently in stock under another warehouse."
                        );
                    }
                }
            } else {
                $alreadyPurchased = TenantProductSerial::query()
                    ->where('product_serial_id', $productSerial->id)
                    ->whereIn('status', ['sale', 'purchase', 'sale_return', 'purchase_return'])
                    ->exists();
                if ($alreadyPurchased) {
                    throw new GeneralException(
                        "Serial {$productSerial->serial_number} already purchased"
                    );
                }
            }

            $targetTenantId = $isAppAdmin ? ($request['tenant_id'] ?? null) : tenant()?->id;

            // Same fix as tenantProductSerialLog() - reuse the existing row
            // for a serial being bought back after being sold, instead of
            // inserting a second row alongside it.
            if ($latestSerial) {
                $latestSerial->update([
                    'status'    => 'purchase',
                    'tenant_id' => $targetTenantId ?? $latestSerial->tenant_id,
                ]);
            } else {
                TenantProductSerial::query()->create([
                    'product_serial_id' => $productSerial->id,
                    'tenant_id'         => $targetTenantId,
                    'status'            => 'purchase',
                ]);
            }
        }

        return $this;
    }

    public function updatePurchase(array $data): static
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
    public function updateItems(array $request, $isAppAdmin): static
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
        $existingItems = PurchaseItem::query()
            ->where('purchase_id', $this->model->id)
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

                $this->tenantNewPurchaseProductSerialLog($newSerials, $request, $isAppAdmin);
                // ➕ Create new purchase item
                PurchaseItem::query()->create([
                    'purchase_id' => $this->model->id,
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'serial_number' => $item['serial_number'],
                    'note' => $item['note'] ?? null,
                ]);

                // 📦 Increase stock safely
                Stock::query()
                    ->where('tenant_id', $tenantId)
                    ->where('product_id', $item['product_id'])
                    ->where('product_variant_id', $item['variant_id'])
                    ->lockForUpdate()
                    ->increment('quantity', $item['quantity']);

                // 🔢 Update serial status
                ProductSerial::query()
                    ->where('serial_number', $item['serial_number'])
                    ->where('product_variant_id', $item['variant_id'])
                    ->whereHas('tenantProductSerials', function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId)
                            ->where('status', 'purchase');
                    })
                    ->update(['status' => 'purchase']);
            }
        }

        // Handle removed items


        $removedSerials = $existingSerials->diff($incomingSerials);

        if ($removedSerials->isNotEmpty()) {

            $removedItems = PurchaseItem::query()
                ->where('purchase_id', $this->model->id)
                ->whereIn('serial_number', $removedSerials)
                ->get();

            foreach ($removedItems as $item) {

                // 📦 Decrease stock
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
                            ->where('status', 'purchase');
                    })
                    ->pluck('id');

                ProductSerial::query()
                    ->whereIn('id', $serials)
                    ->update(['status' => 'generated']);

                // 🗑 Delete tenant serials
                TenantProductSerial::query()
                    ->where('tenant_id', $tenantId)
                    ->whereIn('product_serial_id', $serials)
                    ->where('status', 'purchase')
                    ->delete();

                // 🗑 Delete purchase item
                $item->delete();
            }
        }


        return $this;
    }


    public function stockAndSerialNumberUpdate($isAppAdmin): static
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
                ->lockForUpdate()
                ->decrement('quantity', $item->quantity);

            // 2️⃣ Fetch related product serials (locked)
            $serials = ProductSerial::query()
                ->whereIn('serial_number', $items->pluck('serial_number'))
                ->whereIn('product_variant_id', $items->pluck('product_variant_id'))
                ->whereHas('tenantProductSerials', function ($query) use ($isAppAdmin, $tenantId) {
                    $query->when($isAppAdmin, fn($query) => $query->where('tenant_id', $tenantId))
                        ->where('status', 'purchase');
                })
                ->lockForUpdate()
                ->get();

            if ($serials->isEmpty()) {
                return $this;
            }

            // 3️⃣ Update product serial status
            ProductSerial::query()
                ->whereIn('id', $serials->pluck('id'))
                ->lockForUpdate()
                ->update([
                    'status' => 'generated',
                    'updated_at' => now(),
                ]);

            // 4️⃣ Delete tenant product serials safely
            TenantProductSerial::query()
                ->when($isAppAdmin, fn($query) => $query->where('tenant_id', $tenantId))
                ->whereIn('product_serial_id', $serials->pluck('id'))
                ->where('status', 'purchase')
                ->delete();
        }

        return $this;
    }


    public function itemsDelete(): static
    {
        $this->model->items()->delete();

        return $this;
    }

    public function transactionsDelete(): static
    {
        $this->model->payments()->delete();
        return $this;
    }

    public function purchaseDelete(): static
    {
        $this->model->delete();

        return $this;
    }
}
