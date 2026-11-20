<?php

namespace App\Models\Pos\Supplier;

use App\Filters\Pos\Shop\ShopFilter;
use App\Models\Common\BaseModel;
use App\Models\Pos\Address\Area;
use App\Models\Pos\Address\NewDistrict;
use App\Models\Pos\Country\Country;
use App\Models\Pos\Purchase\Purchase;
use App\Models\Pos\PurchaseReturn\PurchaseReturn;
use App\Models\Pos\Transaction\Transaction;
use App\Models\Tenant\Tenant;
use App\Models\TenantBaseModel;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Supplier extends BaseModel implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'name',
        'company_name',
        'phone',
        'secondary_phone',
        'email',
        'address',
        'status',
        'country_id',
        'area_id',
        'new_district_id',
    ];

    protected $casts = [
        'new_district_id' => 'integer',
        'area_id' => 'integer',
        'country_id' => 'integer',
    ];

    public function district(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(NewDistrict::class, 'new_district_id');
    }

    public function area(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function country(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function purchases(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Purchase::class, 'supplier_id');
    }

    public function purchaseReturns(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PurchaseReturn::class, 'supplier_id');
    }

    public function tenants(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'supplier_tenant', 'supplier_id', 'tenant_id');
    }

    public function getDueAmountAttribute()
    {
        $totalPurchase = $this->purchases()
            ->filters(new ShopFilter())
            ->sum('grand_total');


        $totalPaidForPurchases = Transaction::query()
            ->filters(new ShopFilter())
            ->whereHasMorph('model', [Purchase::class], fn($query) => $query->where('supplier_id', $this->id))
            ->sum('amount');

        $totalPaidForReturns = Transaction::query()
            ->filters(new ShopFilter())
            ->whereHasMorph('model', [PurchaseReturn::class], fn($query) => $query->where('supplier_id', $this->id))
            ->sum('amount');

        return ($totalPurchase - $totalPaidForPurchases) - $totalPaidForReturns;
    }


    public function getPaidAmountAttribute()
    {
        return Transaction::query()
            ->filters(new ShopFilter())
            ->whereHasMorph('model', [Purchase::class], fn($query) => $query->where('supplier_id', $this->id))
            ->sum('amount');
    }

    public function getPurchaseReturnDueAmountAttribute()
    {
        $totalPurchaseReturnAmount = $this->purchaseReturns()
            ->filters(new ShopFilter())
            ->sum('grand_total');

        $totalPaid = $this->getPurchaseReturnPaidAmountAttribute();

        return $totalPurchaseReturnAmount - $totalPaid;
    }

    public function getPurchaseReturnPaidAmountAttribute()
    {
        return Transaction::query()
            ->filters(new ShopFilter())
            ->whereHasMorph(
                'model',
                [PurchaseReturn::class],
                fn($query) => $query->where('supplier_id', $this->id)
            )->sum('amount');
    }


    public function getImageAttribute(): ?string
    {
        $image = $this->getFirstMedia("company_logo");
        return $image?->getFullUrl();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('company_logo')
            ->singleFile();
    }
}
