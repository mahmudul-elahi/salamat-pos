<?php

namespace App\Models\Pos\Courier;

use App\Models\Common\BaseModel;
use App\Models\Pos\Sale\Sale;
use App\Models\User;
use App\Services\Courier\PathaoService;
use App\Services\Courier\RedxService;
use App\Services\Courier\SteadfastService;

class CourierOrder extends BaseModel
{
    #[\Override]
    protected $fillable = [
        'sale_id',
        'created_by',
        'provider',
        'store_id',
        'store_name',
        'consignment_id',
        'tracking_code',
        'merchant_order_id',
        'recipient_name',
        'recipient_phone',
        'recipient_alternative_phone',
        'recipient_address',
        'recipient_district',
        'recipient_thana',
        'recipient_email',
        'recipient_city_id',
        'recipient_city_name',
        'recipient_zone_id',
        'recipient_zone_name',
        'recipient_area_id',
        'recipient_area_name',
        'item_type',
        'delivery_type',
        'item_quantity',
        'item_weight',
        'amount_to_collect',
        'special_instruction',
        'item_description',
        'is_exchange',
        'status',
        'payment_status',
        'delivery_fee',
        'discount',
        'raw_response',
        'courier_created_at',
        'courier_updated_at',
    ];

    #[\Override]
    protected $casts = [
        'raw_response' => 'array',
        'courier_created_at' => 'datetime',
        'courier_updated_at' => 'datetime',
        'is_exchange' => 'boolean',
    ];

    /**
     * Public tracking page for this parcel, resolved per provider — each one
     * derives it differently, so the combined list and the per-provider lists
     * all come here rather than each rebuilding the rule.
     *
     * Steadfast is the odd one: its /tl/ URL takes a token it mints itself, not
     * the tracking code, so the link it returned at creation is read back off
     * the stored response instead of being rebuilt.
     */
    public function getTrackUrlAttribute(): ?string
    {
        return match ($this->provider) {
            'steadfast' => app(SteadfastService::class)->trackingUrlFrom($this->raw_response),
            'pathao' => $this->consignment_id
                ? app(PathaoService::class)->trackingUrl($this->consignment_id)
                : null,
            'redx' => $this->tracking_code
                ? app(RedxService::class)->trackingUrl($this->tracking_code)
                : null,
            default => null,
        };
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
