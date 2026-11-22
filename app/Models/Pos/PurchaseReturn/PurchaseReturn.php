<?php

namespace App\Models\Pos\PurchaseReturn;

use App\Models\Pos\Contact\Contact;
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

class PurchaseReturn extends TenantBaseModel implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'contact_id',
        'supplier_id',
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
                $model->full_invoice_number = 'PR-'.$formattedInvoiceNumber;
            });
        }
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id', 'id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
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
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function getPurchaseReturnBillAttachmentAttribute(): ?string
    {
        $purchaseReturnBillAttachment = $this->getFirstMedia('purchase_return_bill_attachment');

        return $purchaseReturnBillAttachment?->getFullUrl();
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'model', 'model_type', 'model_id', 'id');
    }

    public function getTransactionAmountAttribute()
    {
        return $this->payments()->sum('amount');
    }

    public function getDueAmountAttribute()
    {
        return Transaction::query()
            ->where('model_id', $this->id)
            ->where('model_type', PurchaseReturn::class)
            ->sum('amount');
    }
}
