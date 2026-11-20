<?php

namespace App\Models\Customer;

use App\Models\Common\BaseModel;
use App\Models\Pos\Address\Area;
use App\Models\Pos\Address\NewDistrict;
use App\Models\Pos\PartyMapping\PartyMapping;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Customer extends BaseModel
{

    protected $fillable = [
        'name',
        'email',
        'phone',
        'secondary_phone',
        'customer_type',
        'business_name',
        'platform_url',
        'business_logo',
        'order_channel',
        'trade_license_number',
        'tin_number',
        'address',
        'area_id',
        'new_district_id',
    ];

    public $casts = [
        'new_district_id' => 'integer',
        'area_id' => 'integer',
    ];
    protected $appends = ['full_address'];

    public function district(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(NewDistrict::class, 'new_district_id');
    }

    public function area(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function getFullAddressAttribute(): string
    {
        $street = $this->address;
        $area = $this->area->name ?? '';
        $district = $this->area->district->name ?? '';
        // $thana = $this->area->thana ?? '';
        $postcode = $this->area?->postcode ?? '';

        return trim("$street, $area, $district, $postcode", " ,");
    }

    public function payments(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\App\Models\Pos\Transaction\Transaction::class, 'model', 'model_type', 'model_id', 'id');
    }

    public function partyMapping()
    {
        return $this->hasOne(PartyMapping::class, 'customer_id', 'id');
    }
}
