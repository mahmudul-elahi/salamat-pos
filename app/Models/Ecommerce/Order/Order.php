<?php

namespace App\Models\Ecommerce\Order;

use App\Models\Common\BaseModel;
use App\Models\Customer\Customer;
use App\Models\Pos\Address\Area;
use App\Models\Pos\Address\CustomerAddress;
use App\Models\Pos\Address\NewDistrict;
use App\Models\Pos\Contact\Contact;
use App\Models\PreOwned\PreOwned;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends BaseModel
{
    use SoftDeletes;

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

    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id', 'id');
    }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function items()
    {
        return $this->hasMany(OrderList::class, 'order_id', 'id');
    }

    public function area(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id', 'id');
    }

    public function district(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(NewDistrict::class, 'new_district_id');
    }

    public function shippingAddress(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'shipping_address_id', 'id');
    }
}
