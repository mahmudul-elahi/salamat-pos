<?php

declare(strict_types=1);

namespace App\Models\Ecommerce\Elite;

use App\Models\Ecommerce\Order\Order;
use App\Models\Pos\Contact\Contact;
use Illuminate\Database\Eloquent\Model;

class PointTransaction extends Model
{
    #[\Override]
    protected $fillable = [
        'contact_id',
        'order_id',
        'type',
        'points',
        'balance_after',
        'note',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
