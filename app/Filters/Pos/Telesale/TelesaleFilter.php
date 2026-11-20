<?php

namespace App\Filters\Pos\Telesale;

use App\Exceptions\GeneralException;
use App\Filters\BaseFilter;
use Illuminate\Support\Carbon;

class TelesaleFilter extends BaseFilter
{
    public function search($keyword = null): void
    {
        $this->builder->when($keyword, function ($query, $keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('customer_name', 'like', "%{$keyword}%")
                    ->orWhere('customer_number', 'like', "%{$keyword}%")
                    ->orWhere('custom_product', 'like', "%{$keyword}%")
                    ->orWhere('note', 'like', "%{$keyword}%");
            });
        });
    }

    public function result($resultId = null): void
    {
        $this->builder->when($resultId, function ($query, $resultId) {
            $query->whereIn('telesale_result_id', explode(',', $resultId));
        });
    }

    public function salesman($salesmanId = null): void
    {
        $this->builder->when($salesmanId, function ($query, $salesmanId) {
            $query->whereIn('salesman_id', explode(',', $salesmanId));
        });
    }

    public function media($media = null): void
    {
        $this->builder->when($media, function ($query, $media) {
            $query->whereIn('media', explode(',', $media));
        });
    }

    public function customerType($type = null): void
    {
        // 'existing' / 'new' — anything else is treated as no filter.
        $this->builder->when(in_array($type, ['existing', 'new'], true), function ($query) use ($type) {
            $query->where('is_existing_customer', $type === 'existing');
        });
    }

    public function date($range = null): void
    {
        try {
            if (empty($range)) {
                return;
            }

            if (is_string($range)) {
                $range = json_decode($range, true);
            }

            if (! is_array($range) || count($range) < 2) {
                return;
            }

            [$startDate, $endDate] = $range;

            $this->builder->whereBetween('date', [
                Carbon::parse($startDate)->toDateString(),
                Carbon::parse($endDate)->toDateString(),
            ]);
        } catch (\Exception) {
            throw new GeneralException('Invalid date range format.');
        }
    }
}
