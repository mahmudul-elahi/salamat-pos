<?php

namespace App\Models\Pos\Ledger;

use App\Models\Common\BaseModel;
use App\Models\Pos\Contact\Contact;
use App\Models\Pos\Transaction\Transaction;
use App\Models\Tenant\PaymentMethod\PaymentMethod;
use App\Models\Tenant\Tenant;

class Ledger extends BaseModel
{
    protected $fillable = [
        'date',
        'invoice_number',
        'full_invoice_number',
        'contact_id',
        'tenant_id',
        'payment_method_id',
        'debit',
        'credit',
        'type',
        'note'
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id', 'id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    public function transactions()
    {
        return $this->belongsToMany(
            Transaction::class,
            'ledger_transactions',
            'ledger_id',
            'transaction_id'
        );
    }
}
