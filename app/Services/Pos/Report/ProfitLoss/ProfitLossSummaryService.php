<?php

namespace App\Services\Pos\Report\ProfitLoss;

use App\Models\Pos\ProfitLoss\ProfitLossSummary;
use App\Models\Pos\Sale\Sale;
use App\Scopes\TenantScope;
use Illuminate\Support\Facades\DB;

class ProfitLossSummaryService
{

    public function recalculate(int $saleId): void
    {
        $sale = Sale::withTrashed()
            ->withoutGlobalScope(new TenantScope())
            ->find($saleId);


        if (!$sale || $sale->deleted_at) {
            $this->deleteSummaryBySaleId($saleId);
            return;
        }

        $rows = $this->buildRowsForSale($sale);

        DB::transaction(function () use ($saleId, $rows) {
            $this->deleteSummaryBySaleId($saleId);

            if (!empty($rows)) {
                ProfitLossSummary::query()->withoutGlobalScope(new TenantScope())->insert($rows);
            }
        });
    }


    public function rebuildAll(): void
    {
        $existingIds = ProfitLossSummary::query()
            ->withoutGlobalScope(new TenantScope())
            ->pluck('sale_id')
            ->toArray();

        Sale::query()
            ->withoutGlobalScope(new TenantScope())
            ->when(!empty($existingIds), fn($q) => $q->whereNotIn('id', $existingIds))
            ->orderBy('id')
            ->chunk(500, fn($sales) => $this->insertChunk($sales));
    }


    public function syncUpdated(): void
    {
        Sale::query()
            ->withoutGlobalScope(new TenantScope())
            ->where('updated_at', '>=', now()->subDay())
            ->orderBy('id')
            ->chunk(500, function ($sales) {
                foreach ($sales as $sale) {
                    $this->recalculate($sale->id);
                }
            });
    }


    private function insertChunk($sales): void
    {
        $saleIds = $sales->pluck('id')->toArray();
        $salesMap = $sales->keyBy('id');

        $saleItems = DB::table('sale_items')
            ->whereIn('sale_id', $saleIds)
            ->where('sale_type', '!=', 'exchange')
            ->orderBy('id', 'asc')
            ->get();

        if ($saleItems->isEmpty()) return;

        $rows = [];
        foreach ($saleItems->groupBy('sale_id') as $saleId => $items) {
            $sale = $salesMap[$saleId] ?? null;
            if (!$sale) continue;

            $rows = array_merge($rows, $this->buildRows($sale, $items));
        }

        if (!empty($rows)) {
            ProfitLossSummary::query()->withoutGlobalScope(new TenantScope())->insert($rows);
        }
    }


    private function buildRowsForSale($sale): array
    {
        $saleItems = DB::table('sale_items')
            ->where('sale_id', $sale->id)
            ->where('sale_type', '!=', 'exchange')
            ->orderBy('id', 'asc')
            ->get();

        return $this->buildRows($sale, $saleItems);
    }


    /**
     * The coupon lives on the sale, not on the items, so it has to be split
     * across the lines before each row can be written — otherwise every line
     * would carry the full coupon and the rows would no longer add up to the
     * sale's grand_total.
     */
    private function buildRows($sale, $items): array
    {
        $items = $items->values();

        $lineTotals = $items->map(fn($item) => $this->calculateDiscountedPrice($item))->all();
        $couponShares = $this->allocateCoupon($lineTotals, (float)($sale->coupon_amount ?? 0));

        return $items
            ->map(fn($item, $index) => $this->prepareSummaryRow($sale, $item, $lineTotals[$index], $couponShares[$index]))
            ->toArray();
    }


    /**
     * Splits the coupon proportionally to each line's discounted total, so a
     * free (0 price) line absorbs none of it. Rounding remainder goes to the
     * last line so the shares always sum back to the full coupon.
     */
    private function allocateCoupon(array $lineTotals, float $coupon): array
    {
        $count = count($lineTotals);

        if ($count === 0) return [];
        if ($coupon <= 0) return array_fill(0, $count, 0.0);

        $sum = array_sum($lineTotals);
        $shares = [];
        $allocated = 0.0;

        foreach ($lineTotals as $index => $lineTotal) {
            if ($index === $count - 1) {
                $shares[$index] = round($coupon - $allocated, 2);
                continue;
            }

            $share = $sum > 0
                ? round($coupon * ($lineTotal / $sum), 2)
                : round($coupon / $count, 2);

            $shares[$index] = $share;
            $allocated += $share;
        }

        return $shares;
    }


    private function prepareSummaryRow($sale, $item, float $totalSalePrice, float $couponShare): array
    {
        // FIFO Logice
        $occurrenceIndex = DB::table('sale_items')
            ->where('serial_number', $item->serial_number)
            ->where('id', '<', $item->id)
            ->count();


        $purchasePriceData = DB::table('purchase_items')
            ->where('serial_number', $item->serial_number)
            ->whereNull('deleted_at')
            ->orderBy('id', 'asc')
            ->skip($occurrenceIndex)
            ->first()
            ??
            DB::table('purchase_items') // Fallback
            ->where('serial_number', $item->serial_number)
            ->orderBy('id', 'desc')
            ->first();

        $purchaseUnitPrice = (float)($purchasePriceData->unit_price ?? 0);
        $totalPurchasePrice = $purchaseUnitPrice * $item->quantity;
        $netSalePrice = $totalSalePrice - $couponShare;

        return [
            'sale_id'              => $sale->id,
            'tenant_id'            => $sale->tenant_id,
            'product_id'           => $item->product_id,
            'product_variant_id'   => $item->product_variant_id,
            'sale_date'            => $sale->date,
            'full_invoice_number'  => $sale->full_invoice_number,
            'quantity'             => $item->quantity,
            'sale_unit_price'      => (float)($item->unit_price ?? 0),
            'purchase_unit_price'  => $purchaseUnitPrice,
            'discount_type'        => $item->discount_type,
            'discount_amount'      => $item->discount_amount + $couponShare,
            'total_sale_price'     => $netSalePrice,
            'total_purchase_price' => $totalPurchasePrice,
            'profit_loss'          => ($netSalePrice - $totalPurchasePrice),
            'created_at'           => now(),
            'updated_at'           => now(),
        ];
    }


    private function calculateDiscountedPrice($item): float
    {
        $unitPrice = (float)($item->unit_price ?? $item->sale_unit_price ?? 0);
        $total = $unitPrice * $item->quantity;

        if ($item->discount_type === 'fixed') {
            return max(0, $total - (float)$item->discount_amount);
        }

        if ($item->discount_type === 'percent') {
            $discount = ($total * (float)$item->discount_amount) / 100;
            return max(0, $total - $discount);
        }

        return $total;
    }


    private function deleteSummaryBySaleId(int $saleId): void
    {
        ProfitLossSummary::query()
            ->withoutGlobalScope(new TenantScope())
            ->where('sale_id', $saleId)
            ->delete();
    }
}
