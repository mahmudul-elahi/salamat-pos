<?php

namespace App\Services\Pos\Sale;

use App\Exceptions\GeneralException;
use App\Models\Pos\Income\Income;
use App\Models\Pos\Income\IncomeCategory;
use App\Models\Pos\Investment\Investment;
use App\Models\Pos\Investment\InvestmentCategory;
use App\Models\Pos\Ledger\Ledger;
use App\Models\Pos\Product\ProductSerial;
use App\Models\Pos\Product\TenantProductSerial;
use App\Models\Pos\Purchase\Purchase;
use App\Models\Pos\Purchase\PurchaseItem;
use App\Models\Pos\Sale\Sale;
use App\Models\Pos\Sale\SaleItem;
use App\Models\Pos\Sale\SaleProductTemp;
use App\Models\Pos\Stock\Stock;
use App\Models\Pos\Transaction\Transaction;
use App\Models\Tenant\PaymentMethod\PaymentMethod;
use App\Scopes\TenantScope;
use App\Services\BaseService;
use App\Services\Pos\Report\ProfitLoss\ProfitLossSummaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleService extends BaseService
{
    public function __construct(Sale $model)
    {
        $this->model = $model;
    }

    public function getModelId()
    {
        return $this->model->id ?? null;
    }

    public function create($request): static
    {
        $shippingCost = $request['shipping_cost'] ?? 0;
        $this->model = $this->model::query()
            ->create([
                'contact_id' => $request['contact_id'],
                'seller_id' => $request['seller_id'] ?? null,
                'address' => $request['address'] ?? null,
                'date' => $request['date'],
                'sub_total' => $request['sub_total'],
                'order_tax' => $request['order_tax'],
                'shipping_cost' => $shippingCost,
                'grand_total' => $request['grand_total'] - $shippingCost,
                'additional_charge_name' => $request['additional_charge_name'] ?? null,
                'additional_charge' => $request['additional_charge'] ?? 0,
                'coupon_code' => null,
                'coupon_amount' => 0,

            ]);

        return $this;
    }

    public function createItem($request): static
    {

        $now = now();
        // Build purchase items in one go
        $items = array_map(fn ($item) => [
            'sale_id' => $this->model->id,
            'product_id' => $item['product_id'],
            'product_variant_id' => $item['variant_id'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'serial_number' => $item['serial_number'],
            'sale_type' => $item['sale_type'] ?? 'regular',
            'discount_type' => $item['discount_type'],
            'discount_amount' => $item['discount_amount'],
            'note' => $item['note'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $request['items']);

        // Bulk insert purchase items
        SaleItem::query()->insert($items);

        return $this;
    }

    public function createEcommerceItem($request): static
    {
        $now = now();
        $tenantId = $request['tenant_id'] ?? $this->model->tenant_id;

        // Build items in one go
        $items = array_map(fn ($item) => [
            'sale_id' => $this->model->id,
            'product_id' => $item['product_id'],
            'product_variant_id' => $item['variant_id'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'serial_number' => $item['serial_number'],
            'discount_type' => $item['discount_type'],
            'discount_amount' => $item['discount_amount'],
            'note' => $item['note'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $request['items']);

        // Bulk insert sale items
        SaleItem::query()->insert($items);

        // Collect serial numbers + variant IDs for bulk update
        $serialNumbers = array_column($request['items'], 'serial_number');
        $variantIds = array_column($request['items'], 'variant_id');

        $productSerialIds = ProductSerial::query()
            ->whereIn('serial_number', $serialNumbers)
            ->whereIn('product_variant_id', $variantIds)
            ->lockForUpdate()
            ->pluck('id')
            ->toArray();

        TenantProductSerial::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereIn('product_serial_id', $productSerialIds)
            ->whereIn('status', ['purchase', 'sale_return'])
            ->update([
                'status' => 'sale',
            ]);

        return $this;
    }

    /**
     * @throws GeneralException
     */
    public function tenantProductSerialLog($request, $isAppAdmin): static
    {
        $tenantId = $isAppAdmin ? $request['tenant_id'] : tenant()->id;

        foreach ($request['items'] as $item) {
            $saleType = $item['sale_type'] ?? null;

            if (in_array($saleType, ['product', 'exchange'])) {
                $this->upsertProductSerial($item, $tenantId, $isAppAdmin, $saleType);
            } else {
                $this->strictSerialValidation($item, $request, $isAppAdmin);
            }
        }

        return $this;
    }

    /**
     * Turn "product" (sold-before-purchase) items whose serial is already in stock into
     * regular stock sales, so serial validation, sale items and stock updates all treat
     * them as normal sales instead of backorders. Runs before validation, so items may be malformed.
     *
     * @param  array<int|string, mixed>  $items
     * @return array<int|string, mixed>
     */
    public function convertInStockProductItemsToRegular(array $items, ?int $tenantId, bool $isAppAdmin): array
    {
        return array_map(function (mixed $item) use ($tenantId, $isAppAdmin): mixed {
            $isInStockProductItem = is_array($item)
                && ($item['sale_type'] ?? null) === 'product'
                && filled($item['serial_number'] ?? null)
                && filled($item['variant_id'] ?? null)
                && $this->isSerialInStock($item, $tenantId, $isAppAdmin);

            if ($isInStockProductItem) {
                $item['sale_type'] = null;
            }

            return $item;
        }, $items);
    }

    /**
     * Determine whether the item's serial is already in the tenant's stock (purchased or returned).
     *
     * @param  array{serial_number: string, variant_id: int}  $item
     */
    private function isSerialInStock(array $item, ?int $tenantId, bool $isAppAdmin): bool
    {
        return TenantProductSerial::query()
            ->when($isAppAdmin, fn ($query) => $query->where('tenant_id', $tenantId))
            ->whereIn('status', ['purchase', 'sale_return'])
            ->whereHas('productSerial', fn ($query) => $query
                ->where('serial_number', $item['serial_number'])
                ->where('product_variant_id', $item['variant_id']))
            ->exists();
    }

    private function upsertProductSerial(array $item, ?int $tenantId, bool $isAppAdmin, ?string $saleType = null): void
    {
        $productSerial = ProductSerial::query()
            ->where('serial_number', $item['serial_number'])
            ->where('product_variant_id', $item['variant_id'])
            ->first();

        if ($productSerial) {
            $alreadyInStock = TenantProductSerial::query()
                ->when($isAppAdmin, fn ($query) => $query->where('tenant_id', $tenantId))
                ->where('product_serial_id', $productSerial->id)
                ->whereIn('status', ['purchase', 'sale_return'])
                ->exists();

            if ($alreadyInStock) {
                throw new GeneralException(
                    "Serial {$item['serial_number']} is already in stock"
                );
            }
        }

        if ($saleType === 'exchange') {
            if (! $productSerial) {
                $productSerial = ProductSerial::query()->create([
                    'serial_number' => $item['serial_number'],
                    'product_variant_id' => $item['variant_id'],
                    'product_id' => $item['product_id'],
                    'status' => 'purchase',
                    'max_retail_price' => $item['unit_price'] ?? 0,
                    'tenant_id' => $tenantId,
                    'generated_by' => auth()->id(),
                ]);

                TenantProductSerial::query()->create([
                    'product_serial_id' => $productSerial->id,
                    'tenant_id' => $tenantId,
                    'status' => 'purchase',
                ]);
            } else {
                TenantProductSerial::withoutGlobalScope(TenantScope::class)->updateOrCreate([
                    'product_serial_id' => $productSerial->id,
                    'tenant_id' => $tenantId,
                ], [
                    'status' => 'purchase',
                ]);
            }
        }
        if (! $productSerial) {
            $productSerial = ProductSerial::query()->create([
                'serial_number' => $item['serial_number'],
                'product_variant_id' => $item['variant_id'],
                'product_id' => $item['product_id'],
                'status' => 'generated',
                'max_retail_price' => $item['unit_price'] ?? 0,
                'tenant_id' => $tenantId,
                'generated_by' => auth()->id(),
            ]);
        }

        if ($saleType === 'product') {
            SaleProductTemp::query()->create([
                'product_id' => $item['product_id'],
                'product_variant_id' => $item['variant_id'],
                'serial_number' => $item['serial_number'],
                'tenant_id' => $tenantId,
            ]);
        }
    }

    /**
     * @throws GeneralException
     */
    private function strictSerialValidation(array $item, array $request, bool $isAppAdmin): void
    {
        $productSerial = ProductSerial::query()
            ->where('serial_number', $item['serial_number'])
            ->where('product_variant_id', $item['variant_id'])
            ->first();

        if (! $productSerial) {
            throw new GeneralException("Serial {$item['serial_number']} does not exist");
        }

        $tenantSerial = TenantProductSerial::query()
            ->when($isAppAdmin, fn ($query) => $query->where('tenant_id', $request['tenant_id']))
            ->where('product_serial_id', $productSerial->id)
            ->whereIn('status', ['purchase', 'sale_return'])
            ->lockForUpdate()
            ->first();

        if (! $tenantSerial) {
            throw new GeneralException(
                "Serial {$productSerial->serial_number} is not available for sale"
            );
        }

        $tenantSerial->update(['status' => 'sale']);
    }

    /**
     * @throws GeneralException
     */
    public function tenantNewSaleProductSerialLog($serials, $request, $isAppAdmin): static
    {
        foreach ($serials as $item) {

            $productSerial = ProductSerial::query()
                ->where('serial_number', $item)
                ->first();

            if (! $productSerial) {
                throw new GeneralException("Serial {$item} does not exist.", 400);
            }

            $tenantSerial = TenantProductSerial::query()
                ->when($isAppAdmin, fn ($query) => $query->where('tenant_id', $request['tenant_id']))
                ->where('product_serial_id', $productSerial->id)
                ->whereIn('status', ['purchase', 'sale_return'])
                ->lockForUpdate()
                ->first();

            if (! $tenantSerial) {
                throw new GeneralException(
                    "Serial {$productSerial->serial_number} is not available for sale"
                );
            }

            $tenantSerial->update([
                'status' => 'sale',
            ]);
        }

        return $this;
    }

    public function orderCreateSale($request): static
    {
        $this->model = $this->model::query()
            ->withoutGlobalScope(new TenantScope)
            ->create([
                'contact_id' => $request['contact_id'],
                'tenant_id' => $request['tenant_id'],
                'date' => $request['date'],
                'address' => $request['address'] ?? null,
                'sub_total' => $request['sub_total'],
                'order_tax' => $request['order_tax'],
                'shipping_cost' => $request['shipping_cost'] ?? 0,
                'grand_total' => $request['grand_total'] - ($request['coupon_amount'] ?? 0),
                'additional_charge_name' => $request['additional_charge_name'] ?? null,
                'additional_charge' => $request['additional_charge'] ?? 0,
                'coupon_code' => $request['coupon_code'] ?? null,
                'coupon_amount' => $request['coupon_amount'] ?? 0,

            ]);

        return $this;
    }

    public function saleTransaction(Request $request): static
    {
        if (! empty($request->payments) && is_array($request->payments)) {

            $latestInvoiceNumber = Transaction::query()
                ->withoutGlobalScope(new TenantScope)
                ->lockForUpdate()
                ->max('invoice_number');

            $ledgerInvoiceNumber = Ledger::query()
                ->lockForUpdate()
                ->max('invoice_number');

            // ✅ Payment method n+1 ignore
            $paymentMethodIds = collect($request->payments)
                ->pluck('payment_method_id')
                ->filter()
                ->unique();

            $paymentMethods = PaymentMethod::query()
                ->whereIn('id', $paymentMethodIds)
                ->whereNotNull('director_id')
                ->get()
                ->keyBy('id');

            // ✅ Income category
            $directorIds = $paymentMethods->pluck('director_id')->filter()->unique();

            $incomeCategories = IncomeCategory::query()
                ->whereIn('director_id', $directorIds)
                ->get()
                ->keyBy('director_id');

            $investormentCategories = InvestmentCategory::query()
                ->whereIn('director_id', $directorIds)
                ->get()
                ->keyBy('director_id');

            foreach ($request->payments as $index => $payment) {
                if (round($payment['amount'], 2) > 0) {

                    $invoiceNumber = $latestInvoiceNumber + $index + 1;

                    $transaction = $this->model->payments()->create([
                        'date' => $request['date'],
                        'invoice_number' => $invoiceNumber,
                        'full_invoice_number' => 'SLT-'.$invoiceNumber,
                        'payment_method_id' => $payment['payment_method_id'],
                        'amount' => $payment['amount'],
                        'tenant_id' => $this->model->tenant_id,
                        'note' => $request['note'] ?? null,
                    ]);

                    $lInvoiceNumber = $ledgerInvoiceNumber + $index + 1;
                    $isExchangeOverpaid = $this->model->grand_total < 0;

                    // ✅ ledger create
                    $ledger = Ledger::query()->create([
                        'date' => $request['date'],
                        'invoice_number' => $lInvoiceNumber,
                        'full_invoice_number' => 'SLT-'.$lInvoiceNumber,
                        'contact_id' => $request['contact_id'],
                        'tenant_id' => $this->model->tenant_id,
                        'payment_method_id' => $payment['payment_method_id'],
                        'debit' => $isExchangeOverpaid ? $payment['amount'] : 0,
                        'credit' => $isExchangeOverpaid ? 0 : $payment['amount'],
                        'note' => $request['note'] ?? null,
                        'type' => $isExchangeOverpaid ? 'payment_sent' : 'payment_received',
                    ]);

                    $ledger->transactions()->attach($transaction->id);

                    if ($isExchangeOverpaid) {
                        // ✅ Exchange overpaid: create Investment (paying out to customer)
                        $method = $paymentMethods[$payment['payment_method_id']] ?? null;
                        $investmentCategory = $method ? ($investormentCategories[$method->director_id] ?? null) : null;

                        if ($method && $investmentCategory) {
                            Investment::query()->create([
                                'title' => 'Exchange payment to customer',
                                'date' => $request['date'] ?? now()->toDateString(),
                                'amount' => $payment['amount'],
                                'user_id' => $method->director_id,
                                'investment_category_id' => $investmentCategory->id,
                                'tenant_id' => $this->model->tenant_id,
                                'contact_id' => $request['contact_id'] ?? null,
                                'ledger_id' => $ledger->id,
                                'created_by' => auth()->id(),
                            ]);
                        }
                    } else {
                        // ✅ Normal sale: create Income
                        $method = $paymentMethods[$payment['payment_method_id']] ?? null;
                        $incomeCategory = $method ? ($incomeCategories[$method->director_id] ?? null) : null;

                        if ($method && $incomeCategory) {
                            Income::query()->create([
                                'title' => 'Sale income via '.$method->name,
                                'date' => $request['date'] ?? now()->toDateString(),
                                'amount' => $payment['amount'],
                                'note' => 'Auto-generated from sale on '.($request['date'] ?? now()->toDateString()),
                                'status' => 'paid',
                                'user_id' => $method->director_id,
                                'income_category_id' => $incomeCategory->id,
                                'tenant_id' => $this->model->tenant_id,
                                'contact_id' => $request['contact_id'] ?? null,
                                'ledger_id' => $ledger->id,
                                'created_by' => auth()->id(),
                            ]);
                        }
                    }
                }
            }

            $this->model->update([
                'status' => round(abs($this->model->grand_total), 2) === round($this->model->getTransactionAmountAttribute(), 2)
                    ? 'paid'
                    : 'partially_paid',
            ]);
        }

        return $this;
    }

    public function stockUpdate(Request $request): static
    {
        $tenantId = $request['tenant_id'] ?? $this->model->tenant_id;

        foreach ($request['items'] as $item) {
            $saleType = $item['sale_type'] ?? null;

            if ($saleType == 'product') {
                continue;
            }

            if ($saleType == 'exchange') {
                Stock::withoutGlobalScope(TenantScope::class)
                    ->where('tenant_id', $tenantId)
                    ->updateOrCreate([
                        'product_id' => $item['product_id'],
                        'product_variant_id' => $item['variant_id'],
                    ], [
                        'quantity' => DB::raw('quantity + '.(int) $item['quantity']),
                    ]);

                continue;
            }

            Stock::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)
                ->where('product_variant_id', $item['variant_id'])
                ->where('product_id', $item['product_id'])
                ->decrement('quantity', $item['quantity']);
        }

        return $this;
    }

    public function createPurchaseForProductItems(array $request, bool $isAppAdmin): static
    {
        $tenantId = $isAppAdmin ? ($request['tenant_id'] ?? null) : null;

        $exchangeItems = collect($request['items'] ?? [])
            ->filter(fn ($item) => ($item['sale_type'] ?? null) === 'exchange');

        if ($exchangeItems->isEmpty()) {
            return $this;
        }

        $grandTotal = $exchangeItems->sum(
            fn ($item) => ($item['unit_price'] ?? 0) * ($item['quantity'] ?? 1)
        );

        $purchase = Purchase::query()->create([
            'contact_id' => $request['contact_id'],
            'tenant_id' => $tenantId,
            'date' => $request['date'],
            'sub_total' => $grandTotal,
            'grand_total' => $grandTotal,
            'status' => 'due',
        ]);

        foreach ($exchangeItems as $item) {
            PurchaseItem::query()->create([
                'purchase_id' => $purchase->id,
                'product_id' => $item['product_id'],
                'product_variant_id' => $item['variant_id'],
                'quantity' => $item['quantity'] ?? 1,
                'unit_price' => $item['unit_price'] ?? 0,
                'serial_number' => $item['serial_number'],
                'purchase_type' => 'exchange',
            ]);
        }

        return $this;
    }

    public function profitLossRecalculate(): static
    {
        app(ProfitLossSummaryService::class)->recalculate($this->model->id);

        return $this;
    }

    public function updateSale(array $data): static
    {
        $shippingCost = $data['shipping_cost'] ?? 0;
        $this->model->update([
            'contact_id' => $data['contact_id'],
            'seller_id' => $data['seller_id'] ?? null,
            'address' => $data['address'] ?? null,
            'date' => $data['date'],
            'sub_total' => $data['sub_total'],
            'order_tax' => $data['order_tax'],
            'shipping_cost' => $shippingCost,
            'grand_total' => $data['grand_total'] - $shippingCost,
            'additional_charge_name' => $data['additional_charge_name'] ?? null,
            'additional_charge' => $data['additional_charge'] ?? 0,

        ]);

        return $this;
    }

    /**
     * @throws GeneralException
     */
    public function updateSaleItems(array $request, $isAppAdmin): static
    {
        if (empty($request['items']) || ! is_array($request['items'])) {
            return $this;
        }

        $incomingItems = collect($request['items']);
        $incomingSerials = $incomingItems->pluck('serial_number')->unique();

        $tenantId = $isAppAdmin ? $request['tenant_id'] : $this->model->tenant_id;

        $existingSerials = $this->model
            ->items()
            ->pluck('serial_number');

        // 🔹 Fetch existing purchase items once
        $existingItems = SaleItem::query()
            ->where('sale_id', $this->model->id)
            ->whereIn('serial_number', $incomingSerials)
            ->get()
            ->keyBy('serial_number');

        $newSerials = $incomingSerials->diff($existingSerials);
        if ($newSerials->isNotEmpty()) {
            $this->tenantNewSaleProductSerialLog($newSerials, $request, $isAppAdmin);
        }

        foreach ($incomingItems as $item) {
            // Find existing sale item by serial_number and sale_id
            $purchaseItem = $existingItems->get($item['serial_number']);

            if ($purchaseItem) {
                // Update existing item
                $purchaseItem->update([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_type' => $item['discount_type'],
                    'discount_amount' => $item['discount_amount'],
                    'note' => $item['note'] ?? null,
                ]);
            } else {
                SaleItem::query()->create([
                    'sale_id' => $this->model->id,
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'serial_number' => $item['serial_number'],
                    'discount_type' => $item['discount_type'],
                    'discount_amount' => $item['discount_amount'],
                    'note' => $item['note'] ?? null,
                ]);

                // Decrement stock quantity (sale reduces stock)
                Stock::withoutGlobalScope(TenantScope::class)
                    ->where('tenant_id', $tenantId)
                    ->where('product_id', $item['product_id'])
                    ->where('product_variant_id', $item['variant_id'])
                    ->decrement('quantity', $item['quantity']);
            }
        }

        // Handle removed items

        $removedSerials = $existingSerials->diff($incomingSerials);

        if ($removedSerials->isNotEmpty()) {

            $removedItems = SaleItem::query()
                ->where('sale_id', $this->model->id)
                ->whereIn('serial_number', $removedSerials)
                ->get();

            foreach ($removedItems as $item) {

                // 📦 increment stock
                Stock::query()
                    ->when($isAppAdmin, fn ($query) => $query->where('tenant_id', $tenantId))
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
                            ->whereIn('status', ['sale']);
                    })
                    ->pluck('id')
                    ->toArray();

                TenantProductSerial::query()
                    ->when($isAppAdmin, fn ($query) => $query->where('tenant_id', $tenantId))
                    ->whereIn('product_serial_id', $serials)
                    ->update([
                        'status' => 'purchase',
                        'updated_at' => now(),
                    ]);

                $item->delete();
            }
        }

        return $this;
    }

    public function stockIncrementAndStatusUpdate($isAppAdmin): static
    {
        $items = $this->model->items;
        if ($items->isEmpty()) {
            return $this;
        }

        // 1️⃣ Restore stock for all items
        foreach ($items as $item) {
            Stock::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $this->model->tenant_id)
                ->where('product_id', $item->product_id)
                ->where('product_variant_id', $item->product_variant_id)
                ->lockForUpdate()
                ->increment('quantity', $item->quantity);
        }

        // 2️⃣ Get related product serials (locked) and restore status
        $serialNumbers = $items->pluck('serial_number')->filter()->values();
        if ($serialNumbers->isNotEmpty()) {
            $serials = ProductSerial::query()
                ->whereIn('serial_number', $serialNumbers)
                ->whereIn('product_variant_id', $items->pluck('product_variant_id'))
                ->whereHas('tenantProductSerials', function ($query) {
                    $query->withoutGlobalScope(TenantScope::class)
                        ->where('tenant_id', $this->model->tenant_id)
                        ->where('status', 'sale');
                })
                ->lockForUpdate()
                ->get();

            if ($serials->isNotEmpty()) {
                TenantProductSerial::withoutGlobalScope(TenantScope::class)
                    ->where('tenant_id', $this->model->tenant_id)
                    ->whereIn('product_serial_id', $serials->pluck('id'))
                    ->update([
                        'status' => 'purchase',
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

    public function paymentDelete(): static
    {
        $transactions = $this->model->payments()->get();
        foreach ($transactions as $transaction) {
            $ledgers = $transaction->ledgers()->get();
            foreach ($ledgers as $ledger) {
                Income::query()->where('ledger_id', $ledger->id)->delete();
                Investment::query()->where('ledger_id', $ledger->id)->delete();
                $transaction->ledgers()->detach($ledger->id);
                $ledger->delete();
            }
            $transaction->delete();
        }

        $serialNumbers = $this->model->items()->pluck('serial_number')->filter()->values();
        if ($serialNumbers->isNotEmpty()) {
            $exchangePurchaseItemIds = PurchaseItem::query()
                ->where('purchase_type', 'exchange')
                ->whereIn('serial_number', $serialNumbers)
                ->pluck('purchase_id')
                ->unique();

            if ($exchangePurchaseItemIds->isNotEmpty()) {
                PurchaseItem::whereIn('purchase_id', $exchangePurchaseItemIds)->delete();
                Purchase::whereIn('id', $exchangePurchaseItemIds)->delete();
            }

            SaleProductTemp::whereIn('serial_number', $serialNumbers)->delete();
        }

        return $this;
    }

    public function saleDelete(): static
    {
        $this->model->delete();

        return $this;
    }

    public function createDirectorIncomeFromPayments(array $data, bool $isAppAdmin)
    {
        $payments = $data['payments'] ?? [];

        if (empty($payments)) {
            return $this;
        }

        $paymentMethodIds = collect($payments)->pluck('payment_method_id')->filter()->unique();

        // Load payment methods with their directors in one query
        $paymentMethods = PaymentMethod::query()
            ->whereIn('id', $paymentMethodIds)
            ->whereNotNull('director_id')
            ->get()
            ->keyBy('id');

        foreach ($payments as $payment) {
            $methodId = $payment['payment_method_id'] ?? null;
            $amount = $payment['amount'] ?? 0;

            if (! $methodId || ! isset($paymentMethods[$methodId])) {
                continue;
            }

            $method = $paymentMethods[$methodId];

            if (! $method->director_id) {
                continue;
            }

            $incomeCategory = IncomeCategory::query()
                ->where('director_id', $method->director_id)
                ->first();
            if (! $incomeCategory) {
                continue;
            }

            Income::query()->create([
                'title' => 'Sale income via '.$method->name,
                'date' => $data['date'] ?? now()->toDateString(),
                'amount' => $amount,
                'note' => 'Auto-generated from sale on '.($data['date'] ?? now()->toDateString()),
                'status' => 'paid',
                'user_id' => $method->director_id,
                'income_category_id' => $incomeCategory->id,
                'tenant_id' => $isAppAdmin ? $data['tenant_id'] : tenant()->id,
            ]);
        }

        return $this;
    }
}
