<?php

namespace App\Models\Customer;

use App\Models\Common\BaseModel;
use App\Models\Pos\Address\Area;
use App\Models\Pos\Address\NewDistrict;
use App\Models\Pos\PartyMapping\PartyMapping;
use App\Models\Pos\Transaction\Transaction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Customer extends BaseModel
{
    #[\Override]
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

    #[\Override]
    public $casts = [
        'new_district_id' => 'integer',
        'area_id' => 'integer',
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

    public function getFullAddressAttribute(): string
    {
        $street = $this->address;
        $area = $this->area->name ?? '';
        $district = $this->area->district->name ?? '';
        // $thana = $this->area->thana ?? '';
        $postcode = $this->area?->postcode ?? '';

        return trim("$street, $area, $district, $postcode", ' ,');
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'model', 'model_type', 'model_id', 'id');
    }

    public function partyMapping()
    {
        return $this->hasOne(PartyMapping::class, 'customer_id', 'id');
    }
}
