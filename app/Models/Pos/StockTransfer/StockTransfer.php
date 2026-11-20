<?php

namespace App\Models\Pos\StockTransfer;

use App\Models\Common\BaseModel;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockTransfer extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'from_tenant_id',
        'to_tenant_id',
        'reference_no',
        'transfer_date',
        'status',
        'note',
        'created_by',
        'approved_by',
        'cancelled_by',
    ];

    public function fromTenant()
    {
        return $this->belongsTo(Tenant::class, 'from_tenant_id');
    }

    public function toTenant()
    {
        return $this->belongsTo(Tenant::class, 'to_tenant_id');
    }

    public function items()
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public static function generateReferenceNo(): string
    {
        $last = static::withTrashed()->latest('id')->value('id') ?? 0;

        return 'ST-'.str_pad($last + 1, 6, '0', STR_PAD_LEFT);
    }
}
