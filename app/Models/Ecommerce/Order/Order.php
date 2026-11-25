<?php

namespace App\Models\Ecommerce\Order;

use App\Helpers\General\AddressHelper;
use App\Models\Common\BaseModel;
use App\Models\Pos\Address\Area;
use App\Models\Pos\Address\CustomerAddress;
use App\Models\Pos\Address\NewDistrict;
use App\Models\Pos\Contact\Contact;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string|null $address
 * @property int|null $shipping_address_id
 * @property int|null $status
 */
class Order extends BaseModel
{
    use SoftDeletes;

    #[\Override]
    protected $fillable = [
        'contact_id',
        'tenant_id',
        'customer_id',
        'shipping_address_id',
        'payment_method',
        'payment_type',
        'paymentId',
        'transaction_id',
        'payment_status',
        'address',
        'area_id',
        'new_district_id',
        'total_booking_amount',
        'sub_total',
        'discount_total',
        'payable_amount',
        'due_amount',
        'shipping_charge',
        'coupon_code',
        'discount_amount',
        'grand_total',
        'invoice_number',
        'customer_note',
        'custom_value',
        'note',
        'status',
        'role',
    ];

    #[\Override]
    public $casts = [
        'total_booking_amount' => 'decimal:2',
        'sub_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'payable_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'shipping_charge' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::deleting(function ($order) {
            $order->items()->delete();
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id', 'id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function items()
    {
        return $this->hasMany(OrderList::class, 'order_id', 'id');
    }

    /**
     * @return BelongsTo<Area, $this>
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id', 'id');
    }

    /**
     * @return BelongsTo<NewDistrict, $this>
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(NewDistrict::class, 'new_district_id');
    }

    /**
     * @return BelongsTo<CustomerAddress, $this>
     */
    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'shipping_address_id', 'id');
    }

    /**
     * Where the order is delivered: the customer's saved shipping address when one was picked
     * at checkout, otherwise the address typed on the order.
     */
    public function getDeliveryAddressAttribute(): string
    {
        if ($this->shipping_address_id && $this->shippingAddress) {
            return $this->shippingAddress->full_address;
        }

        return AddressHelper::join($this->address, [
            $this->area?->name,
            $this->area?->thana,
            $this->district?->name,
            $this->area?->district?->name,
            $this->area?->postcode,
        ]);
    }
}
