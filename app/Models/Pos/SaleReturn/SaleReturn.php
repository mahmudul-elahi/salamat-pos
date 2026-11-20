<?php

namespace App\Models\Pos\SaleReturn;

use App\Models\Pos\Contact\Contact;
use App\Models\User;
use App\Scopes\TenantScope;
use App\Models\TenantBaseModel;
use App\Models\Customer\Customer;
use App\Models\Tenant\Tenant;
use Spatie\MediaLibrary\HasMedia;
use App\Models\Pos\Transaction\Transaction;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleReturn extends TenantBaseModel implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'contact_id',
        'customer_id',
        'tenant_id',
        'created_by',
        'date',
        'invoice_number',
        'full_invoice_number',
        'sub_total',
        'order_tax',
        'shipping_cost',
        'grand_total',
        'status',
        'payment_method_id',
        'amount',
        'note',
    ];

    public static function boot(): void
    {
        parent::boot();
        if (!app()->runningInConsole()) {
            $maxInvoiceNumber = self::withTrashed()->withoutGlobalScope(new TenantScope())->max('invoice_number') + 1;
            $formattedInvoiceNumber = str_pad($maxInvoiceNumber, 6, '0', STR_PAD_LEFT);
            static::creating(function ($model) use ($maxInvoiceNumber, $formattedInvoiceNumber) {
                $model->created_by = auth()->id();
                $model->invoice_number = $maxInvoiceNumber;
                $model->full_invoice_number = "SR-" . $formattedInvoiceNumber;
            });
        }
    }

    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function contact(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id', 'id');
    }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function items()
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function getSaleReturnBillAttachmentAttribute(): ?string
    {
        $saleReturnBillAttachment = $this->getFirstMedia('sale_return_bill_attachment');
        return $saleReturnBillAttachment?->getFullUrl();
    }

    public function payments()
    {
        return $this->morphMany(Transaction::class, 'model', 'model_type', 'model_id', 'id');
    }

    public function getTransactionAmountAttribute()
    {
        return $this->payments()->sum('amount');
    }
}
