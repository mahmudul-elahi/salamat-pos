<?php

namespace App\Filters\Pos\OrderConfirmation;

use App\Exceptions\GeneralException;
use App\Filters\BaseFilter;
use Illuminate\Support\Carbon;

class OrderConfirmationFilter extends BaseFilter
{
    public function search($keyword = null): void
    {
        $this->builder->when($keyword, function ($query, $keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('customer_name', 'like', "%{$keyword}%")
                    ->orWhere('mobile', 'like', "%{$keyword}%")
                    ->orWhere('invoice_no', 'like', "%{$keyword}%")
                    ->orWhere('custom_product', 'like', "%{$keyword}%")
                    ->orWhere('address', 'like', "%{$keyword}%");
            });
        });
    }

    public function sourceMedia($media = null): void
    {
        $this->builder->when($media, fn ($q, $media) => $q->whereIn('source_media', explode(',', $media)));
    }

    public function orderType($type = null): void
    {
        $this->builder->when(in_array($type, ['online', 'offline'], true), fn ($q) => $q->where('order_type', $type));
    }

    public function orderPerson($personId = null): void
    {
        $this->builder->when($personId, fn ($q, $personId) => $q->whereIn('order_person_id', explode(',', $personId)));
    }

    public function advanceMedia($optionId = null): void
    {
        $this->builder->when($optionId, fn ($q, $optionId) => $q->whereIn('advance_media_id', explode(',', $optionId)));
    }

    public function deliveryStatus($optionId = null): void
    {
        $this->builder->when($optionId, fn ($q, $optionId) => $q->whereIn('delivery_status_id', explode(',', $optionId)));
    }

    public function deliveryBy($optionId = null): void
    {
        $this->builder->when($optionId, fn ($q, $optionId) => $q->whereIn('delivery_by_id', explode(',', $optionId)));
    }

    public function confirmed($state = null): void
    {
        // 'yes' / 'no' — anything else is treated as no filter.
        $this->builder->when(in_array($state, ['yes', 'no'], true), function ($query) use ($state) {
            $state === 'yes'
                ? $query->whereNotNull('confirmation_date')
                : $query->whereNull('confirmation_date');
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
