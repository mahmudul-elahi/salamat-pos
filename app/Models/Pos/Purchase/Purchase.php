<?php

namespace App\Models\Pos\Purchase;

use App\Filters\Pos\Shop\ShopDynamicTableFilter;
use App\Models\Pos\Contact\Contact;
use App\Models\Pos\PurchaseReturn\PurchaseReturnItem;
use App\Models\Pos\Supplier\Supplier;
use App\Models\Pos\Transaction\Transaction;
use App\Models\Tenant\Tenant;
use App\Models\TenantBaseModel;
use App\Models\User;
use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Purchase extends TenantBaseModel implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'supplier_id',
        'contact_id',
        'tenant_id',
        'created_by',
        'date',
        'invoice_number',
        'full_invoice_number',
        'sub_total',
        'discount_type',
        'discount_amount',
        'order_tax',
        'shipping_cost',
        'grand_total',
        'status',
        'note',
    ];

    public $casts = [
        'grand_total' => 'decimal:2',
    ];

    public static function boot(): void
    {
        parent::boot();
        if (! app()->runningInConsole()) {
            $maxInvoiceNumber = self::withTrashed()->withoutGlobalScope(new TenantScope)->max('invoice_number') + 1;
            $formattedInvoiceNumber = str_pad($maxInvoiceNumber, 6, '0', STR_PAD_LEFT);
            static::creating(function ($model) use ($maxInvoiceNumber, $formattedInvoiceNumber) {
                $model->created_by = auth()->id();
                $model->invoice_number = $maxInvoiceNumber;
                $model->full_invoice_number = 'PP-'.$formattedInvoiceNumber;
            });
        }
    }

    public function purchaseReturnItems()
    {
        return $this->hasMany(PurchaseReturnItem::class, 'purchase_id', 'id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id', 'id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'model', 'model_type', 'model_id', 'id');
    }

    public function purchasesReturnItems()
    {
        return $this->hasMany(PurchaseReturnItem::class, 'purchase_id');
    }

    public function getDueAmountAttribute()
    {
        return Transaction::query()
            ->filters(new ShopDynamicTableFilter('transactions'))
            ->where('model_id', $this->id)
            ->where('model_type', Purchase::class)
            ->sum('amount');
    }

    public function getTransactionAmountAttribute()
    {
        return $this->payments()
            ->filters(new ShopDynamicTableFilter('transactions'))
            ->sum('amount');
    }

    public static function totalPaidAmount($modelIds = null): float
    {
        return Transaction::query()
            ->filters(new ShopDynamicTableFilter('transactions'))
            ->where('model_type', self::class)
            ->when($modelIds, fn ($q) => $q->whereIn('model_id', $modelIds))
            ->sum('amount');
    }

    public function getPurchaseBillAttachmentAttribute(): ?string
    {
        $purchaseBillAttachment = $this->getFirstMedia('purchase_bill_attachment');

        return $purchaseBillAttachment?->getFullUrl();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('purchase_bill_attachment');
    }
}
