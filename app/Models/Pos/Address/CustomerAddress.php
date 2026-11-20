<?php

namespace App\Models\Pos\Address;

use App\Models\Customer\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    protected $fillable = [
        'customer_id',
        'new_district_id',
        'area_id',
        'street',
        'label',
        'is_default_shipping',
        'is_default_billing',
        'new_district_id',
        'is_default',
    ];

    protected $guarded = [];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(NewDistrict::class, 'new_district_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function getFullAddressAttribute(): string
    {
        $street = $this->street;
        $area = $this->area->name ?? '';
        $district = $this->area?->district?->name ?? '';
        // $thana = $this->area?->thana ?? '';
        $postcode = $this->area?->postcode ?? '';

        return trim("$street, $area, $district, $postcode", ' ,');
    }
}
