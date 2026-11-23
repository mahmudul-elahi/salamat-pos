<?php

namespace App\Models\Pos\Partners;

use App\Enums\CustomerTypeEnum;
use App\Enums\StatusEnum;
use App\Models\Common\BaseModel;
use App\Models\Pos\Address\Area;
use App\Models\Pos\Address\NewDistrict;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfflineCustomer extends BaseModel
{
    #[\Override]
    protected $fillable = [
        'name',
        'email',
        'primary_phone',
        'secondary_phone',
        'business_name',
        'platform_url',
        'business_logo',
        'order_channel',
        'customer_type',
        'area_id',
        'customer_code',
        'street_address',
        'trade_license_number',
        'tin_number',
        'status',
    ];

    #[\Override]
    protected $casts = [
        'status' => StatusEnum::class,
        'customer_type' => CustomerTypeEnum::class,
    ];

    #[\Override]
    protected $appends = ['full_address'];

    public function district(): BelongsTo
    {
        return $this->belongsTo(NewDistrict::class, 'new_district_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function getFullAddressAttribute()
    {
        $street = $this->street_address;
        $area = $this->area->name ?? '';
        $district = $this->area->district->name ?? '';

        return trim("$street, $area, $district", ' ,');
    }
}
