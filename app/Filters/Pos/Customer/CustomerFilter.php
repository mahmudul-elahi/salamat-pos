<?php

namespace App\Filters\Pos\Customer;

use App\Filters\BaseFilter;
use App\Filters\Common\Traits\SupplierShopFilterTrait;

class CustomerFilter extends BaseFilter
{
    use SupplierShopFilterTrait;

    /** Columns a typed term is matched against. */
    private const SEARCHABLE = ['name', 'email', 'business_name', 'phone'];

    /**
     * Only used by the merged People list — Customer/Supplier/OnlineContact
     * controllers each hardcode their own ->where('type', ...)/('online', ...)
     * instead, so this never fires for them (no 'contact_type' param sent).
     * 'online' is an independent flag on top of 'type', not a third value of
     * it — a consumer who registered online is excluded from 'offline' so
     * the three buckets stay mutually exclusive.
     */
    public function contactType($value = null): void
    {
        match ($value) {
            'supplier' => $this->builder->where('type', 'supplier'),
            'online' => $this->builder->where('online', true),
            'offline' => $this->builder->where('type', 'consumer')
                ->where(fn($q) => $q->where('online', false)->orWhereNull('online')),
            default => null,
        };
    }

    public function newDistrictId($districtId = null): void
    {
        if (!$districtId) return;
        $this->builder->where('new_district_id', $districtId);
    }

    public function areaId($areaId = null): void
    {
        if (!$areaId) return;
        $this->builder->where('area_id', $areaId);
    }

    public function search($search = null): void
    {
        $search = trim((string) $search);

        if ($search === '') {
            return;
        }

        // Business names get typed inconsistently — "Daily Gadgets", "DailyGadgets",
        // "Daily  Gadgets" — so every column is matched twice: once as typed, and
        // once with all whitespace stripped from both sides of the comparison.
        // Case needs no handling: the columns are utf8mb4_unicode_ci.
        $collapsed = preg_replace('/\s+/u', '', $search);

        // Grouped: without the closure these ORs would escape any constraint the
        // caller already applied (e.g. ->where('type', 'supplier')), quietly
        // matching rows the caller never asked for.
        $this->builder->where(function ($query) use ($search, $collapsed) {
            foreach (self::SEARCHABLE as $column) {
                $query->orWhere($column, 'LIKE', "%{$search}%");

                // Only worth the extra (unindexed) pass when it can differ.
                if ($collapsed !== $search && $collapsed !== '') {
                    $query->orWhereRaw("REPLACE({$column}, ' ', '') LIKE ?", ["%{$collapsed}%"]);
                }
            }

            // Someone typing without spaces should still find a name stored
            // with them.
            if ($collapsed === $search) {
                foreach (self::SEARCHABLE as $column) {
                    $query->orWhereRaw("REPLACE({$column}, ' ', '') LIKE ?", ["%{$search}%"]);
                }
            }
        });
    }
}
