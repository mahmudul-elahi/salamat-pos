<?php

namespace App\Models\Pos\Contact;

use App\Filters\Pos\Shop\ShopFilter;
use App\Models\Common\BaseModel;
use App\Models\Pos\Address\Area;
use App\Models\Pos\Address\NewDistrict;
use App\Models\Pos\Purchase\Purchase;
use App\Models\Pos\PurchaseReturn\PurchaseReturn;
use App\Models\Pos\Sale\Sale;
use App\Models\Pos\Transaction\Transaction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Contact extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'secondary_phone',
        'business_name',
        'country_id',
        'new_district_id',
        'area_id',
        'address',
        'type',
        'status',
        'online',
        'rating',
        'note',
        'price_document',
        'platform_url',
        'business_logo',
        'profile_picture',
        'order_channel',
        'trade_license_number',
        'tin_number',
        'password',
        'email_verified_at',
        'phone_verified_at',
        'last_login_at',
    ];

    public $casts = [
        'country_id' => 'integer',
        'new_district_id' => 'integer',
        'area_id' => 'integer',
        'password' => 'hashed',
        'online' => 'boolean',
        'rating' => 'integer',
    ];

    public $hidden = [
        'password',
    ];

    protected $appends = ['price_document_url'];

    /**
     * `price_document` holds the storage path; lists and modals need a link
     * they can open directly, so expose the resolved URL alongside it. The URL
     * comes from the default disk (FILESYSTEM_DISK), matching where the upload
     * was written.
     */
    public function getPriceDocumentUrlAttribute(): ?string
    {
        if (! $this->price_document) {
            return null;
        }

        return str_starts_with($this->price_document, 'http')
            ? $this->price_document
            : Storage::url($this->price_document);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'contact_id', 'id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'contact_id', 'id');
    }

    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class, 'contact_id', 'id');
    }

    public function getPaidAmountAttribute()
    {
        return Transaction::query()
            ->filters(new ShopFilter)
            ->whereHasMorph('model', [Purchase::class], fn ($query) => $query->where('contact_id', $this->id))
            ->sum('amount');
    }

    public function getPurchaseReturnDueAmountAttribute()
    {
        $totalPurchaseReturnAmount = $this->purchaseReturns()
            ->filters(new ShopFilter)
            ->sum('grand_total');

        $totalPaid = $this->getPurchaseReturnPaidAmountAttribute();

        return $totalPurchaseReturnAmount - $totalPaid;
    }

    public function getPurchaseReturnPaidAmountAttribute()
    {
        return Transaction::query()
            ->filters(new ShopFilter)
            ->whereHasMorph(
                'model',
                [PurchaseReturn::class],
                fn ($query) => $query->where('contact_id', $this->id)
            )->sum('amount');
    }

    public function getReceivableAmountAttribute()
    {
        $totalSales = $this->sales()
            ->filters(new ShopFilter)
            ->sum('grand_total');

        $totalPaidForSales = Transaction::query()
            ->filters(new ShopFilter)
            ->whereHasMorph('model', [Sale::class], fn ($q) => $q->where('contact_id', $this->id))
            ->sum('amount');

        return $totalSales - $totalPaidForSales;
    }

    public function getDueAmountAttribute()
    {
        $totalPurchase = $this->purchases()
            ->filters(new ShopFilter)
            ->sum('grand_total');

        $totalPaidForPurchases = Transaction::query()
            ->filters(new ShopFilter)
            ->whereHasMorph('model', [Purchase::class], fn ($query) => $query->where('contact_id', $this->id))
            ->sum('amount');

        $totalPaidForReturns = Transaction::query()
            ->filters(new ShopFilter)
            ->whereHasMorph('model', [PurchaseReturn::class], fn ($query) => $query->where('contact_id', $this->id))
            ->sum('amount');

        return ($totalPurchase - $totalPaidForPurchases) - $totalPaidForReturns;
    }

    public function getImageAttribute(): ?string
    {
        $image = $this->getFirstMedia('company_logo');

        return $image?->getFullUrl();
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(NewDistrict::class, 'new_district_id', 'id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id', 'id');
    }

    public function getFullAddress()
    {
        $addressParts = [];

        if ($this->district?->name) {
            $addressParts[] = $this->district->name;
        }
        if ($this->area?->name) {
            $addressParts[] = $this->area->name;
        }
        if ($this->address) {
            $addressParts[] = $this->address;
        }

        return implode(', ', $addressParts);
    }
}
