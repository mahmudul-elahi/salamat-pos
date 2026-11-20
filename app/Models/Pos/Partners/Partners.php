<?php

declare(strict_types=1);

namespace App\Models\Pos\Partners;

use App\Enums\CustomerTypeEnum;
use App\Enums\StatusEnum;
use App\Models\Common\BaseModel;
use App\Models\Pos\Address\Area;

class Partners extends BaseModel
{
    protected $fillable = [
        'partner_type',
        'name',
        'business_name',
        'primary_phone',
        'secondary_phone',
        'email',
        'area_id',
        'street_address',
        'trade_license_number',
        'tin_number',
        'business_owner_nid',
        'business_trade_license',
        'business_logo',
        'business_owner_photo',
        'platform_url',
        'status',
    ];

    protected $casts = [
        'status' => StatusEnum::class,
        'partner_type' => CustomerTypeEnum::class,
    ];

    protected $appends = ['full_address'];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function getFullAddressAttribute()
    {
        $street = $this->street_address;
        $area = $this->area->name ?? '';
        $district = $this->area->district->name ?? '';

        return trim("$street, $area, $district", ' ,');
    }
}
