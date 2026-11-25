<?php

namespace App\Models\Pos\Address;

use App\Helpers\General\AddressHelper;
use App\Models\Customer\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $contact_id
 * @property string|null $street
 * @property bool $is_default_shipping
 */
class CustomerAddress extends Model
{
    #[\Override]
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

    #[\Override]
    protected $guarded = [];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<NewDistrict, $this>
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(NewDistrict::class, 'new_district_id');
    }

    /**
     * @return BelongsTo<Area, $this>
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function getFullAddressAttribute(): string
    {
        return AddressHelper::join($this->street, [
            $this->area?->name,
            $this->area?->thana,
            $this->district?->name ?? $this->area?->district?->name,
            $this->area?->postcode,
        ]);
    }
}
