<?php

namespace App\Models\Pos\OrderConfirmation;

use App\Models\Common\BaseModel;
use App\Models\Ecommerce\Order\Order;
use App\Models\Pos\Contact\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderConfirmation extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'date',
        'confirmation_date',
        'order_id',
        'contact_id',
        'customer_name',
        'mobile',
        'address',
        'source_media',
        'order_type',
        'order_person_id',
        'advance_media_id',
        'advance_amount',
        'selling_price',
        'shipping_charge',
        'cod',
        'note',
        'delivery_status_id',
        'invoice_no',
        'delivery_by_id',
        'track_url',
        'delivery_note',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'confirmation_date' => 'date',
        'advance_amount' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'shipping_charge' => 'decimal:2',
        'cod' => 'decimal:2',
    ];

    protected $appends = ['product_name', 'is_confirmed'];

    /**
     * A one-line summary of the order for the list column: the first product,
     * plus a count of the rest so a multi-product order is never mistaken for a
     * single-product one.
     */
    public function getProductNameAttribute(): ?string
    {
        $names = $this->items->map(fn (OrderConfirmationItem $item) => $item->product_name)->filter()->values();

        if ($names->isEmpty()) {
            return null;
        }

        return $names->count() === 1
            ? $names->first()
            : $names->first().' +'.($names->count() - 1);
    }

    /**
     * The confirmation call is what this list exists to track, so expose it as
     * a flag rather than making every caller test the date for null.
     */
    public function getIsConfirmedAttribute(): bool
    {
        return (bool) $this->confirmation_date;
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderConfirmationItem::class);
    }

    public function orderPerson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'order_person_id');
    }

    public function advanceMedia(): BelongsTo
    {
        return $this->belongsTo(OrderConfirmationOption::class, 'advance_media_id');
    }

    public function deliveryStatus(): BelongsTo
    {
        return $this->belongsTo(OrderConfirmationOption::class, 'delivery_status_id');
    }

    public function deliveryBy(): BelongsTo
    {
        return $this->belongsTo(OrderConfirmationOption::class, 'delivery_by_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
