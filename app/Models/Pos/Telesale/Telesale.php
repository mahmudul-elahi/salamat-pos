<?php

namespace App\Models\Pos\Telesale;

use App\Models\Common\BaseModel;
use App\Models\Pos\Contact\Contact;
use App\Models\Pos\Product\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Telesale extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'date',
        'contact_id',
        'is_existing_customer',
        'customer_name',
        'customer_number',
        'address',
        'product_id',
        'custom_product',
        'media',
        'note',
        'telesale_result_id',
        'salesman_id',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'is_existing_customer' => 'boolean',
    ];

    protected $appends = ['demand_product'];

    /**
     * What the customer asked for — the catalogue product when we stock it,
     * otherwise whatever the agent typed in.
     */
    public function getDemandProductAttribute(): ?string
    {
        return $this->product?->product_name ?? $this->custom_product;
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(TelesaleResult::class, 'telesale_result_id');
    }

    public function salesman(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesman_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * A caller counts as "existing" when their number is already on a contact,
     * as either the primary or the secondary phone.
     */
    public static function findContactByNumber(?string $number): ?Contact
    {
        $number = trim((string) $number);

        if ($number === '') {
            return null;
        }

        return Contact::query()
            ->where('phone', $number)
            ->orWhere('secondary_phone', $number)
            ->first();
    }
}
