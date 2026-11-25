<?php

namespace App\Models\Pos\SaleReturn;

use App\Models\Customer\Customer;
use App\Models\Pos\Contact\Contact;
use App\Models\Pos\Transaction\Transaction;
use App\Models\Tenant\Tenant;
use App\Models\TenantBaseModel;
use App\Models\User;
use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SaleReturn extends TenantBaseModel implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    protected $dates = ['deleted_at'];

    #[\Override]
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
        if (! app()->runningInConsole()) {
            static::creating(function ($model) {
                $maxInvoiceNumber = self::withTrashed()
                    ->withoutGlobalScope(new TenantScope)
                    ->lockForUpdate()
                    ->max('invoice_number') + 1;

                $formattedInvoiceNumber = str_pad($maxInvoiceNumber, 6, '0', STR_PAD_LEFT);
                $model->created_by = auth()->id();
                $model->invoice_number = $maxInvoiceNumber;
                $model->full_invoice_number = 'SR-'.$formattedInvoiceNumber;
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

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id', 'id');
    }

    public function tenant(): BelongsTo
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
