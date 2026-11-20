<?php

namespace App\Models\Pos\Sale;

use App\Filters\Pos\Shop\ShopDynamicTableFilter;
use App\Models\Customer\Customer;
use App\Models\Pos\Contact\Contact;
use App\Models\Pos\Courier\CourierOrder;
use App\Models\Pos\SaleReturn\SaleReturnItem;
use App\Models\Pos\Transaction\Transaction;
use App\Models\Tenant\Tenant;
use App\Models\TenantBaseModel;
use App\Models\User;
use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Sale extends TenantBaseModel implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'contact_id',
        'customer_id',
        'tenant_id',
        'created_by',
        'seller_id',
        'address',
        'date',
        'invoice_number',
        'full_invoice_number',
        'sub_total',
        'order_tax',
        'shipping_cost',
        'grand_total',
        'additional_charge_name',
        'additional_charge',
        'status',
        'note',
        'coupon_code',
        'coupon_amount',
        'payment_method_id',
    ];

    public static function boot(): void
    {
        parent::boot();
        if (! app()->runningInConsole()) {
            static::creating(function ($model) {
                $maxInvoiceNumber = self::withTrashed()
                    ->withoutGlobalScope(new TenantScope)
                    ->lockForUpdate()
                    ->max('invoice_number') + 1;

                $formattedInvoiceNumber = str_pad($maxInvoiceNumber, 6, '0', STR_PAD_LEFT);
                $model->created_by = auth()->id();
                $model->invoice_number = $maxInvoiceNumber;
                $model->full_invoice_number = 'PS-'.$formattedInvoiceNumber;
            });
        }
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id', 'id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function courierOrder(): HasOne
    {
        return $this->hasOne(CourierOrder::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function saleReturnItems()
    {
        return $this->hasMany(SaleReturnItem::class, 'sale_id', 'id');
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'model', 'model_type', 'model_id', 'id');
    }

    public function getTransactionAmountAttribute()
    {
        return $this->payments()
            ->filters(new ShopDynamicTableFilter('transactions'))
            ->sum('amount');
    }

    public static function totalReceivedAmount($modelIds = null): float
    {
        return Transaction::query()
            ->filters(new ShopDynamicTableFilter('transactions'))
            ->where('model_type', self::class)
            ->when($modelIds, fn ($q) => $q->whereIn('model_id', $modelIds))
            ->sum('amount');
    }

    public function getSaleBillAttachmentAttribute(): ?string
    {
        $saleBillAttachment = $this->getFirstMedia('sale_bill_attachment');

        return $saleBillAttachment?->getFullUrl();
    }

    public function paymentSummary()
    {
        return $this->payments
            ->groupBy('payment_method_id')
            ->map(fn ($transactions) => [
                'method' => optional($transactions->first()->paymentMethod)->name,
                'amount' => $transactions->sum('amount'),
            ]);
    }
}
