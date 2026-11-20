<?php

namespace App\Models\Pos\Transaction;

use App\Models\Pos\Expense\Expense;
use App\Models\Pos\PaymentMethod\PaymentMethod;
use App\Models\Pos\Purchase\Purchase;
use App\Models\Pos\PurchaseReturn\PurchaseReturn;
use App\Models\Pos\Sale\Sale;
use App\Models\Pos\SaleReturn\SaleReturn;
use App\Models\TenantBaseModel;

class Transaction extends TenantBaseModel
{
    protected $fillable = [
        'date',
        'invoice_number',
        'full_invoice_number',
        'model_type',
        'model_id',
        'amount',
        'payment_method_id',
        'tenant_id',
        'note'
    ];

    public function model(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function scopeSales($query)
    {
        return $query->where('model_type', Sale::class);
    }

    public function scopePurchases($query)
    {
        return $query->where('model_type', Purchase::class);
    }

    public function scopeExpenses($query)
    {
        return $query->where('model_type', Expense::class);
    }

    public function scopePurchaseReturns($query)
    {
        return $query->where('model_type', PurchaseReturn::class);
    }

    public function scopeSaleReturns($query)
    {
        return $query->where('model_type', SaleReturn::class);
    }


    public function paymentMethod(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }


    public function getNextInvoiceNumberAttribute()
    {
        return self::query()->max('invoice_number') + 1;
    }
}
