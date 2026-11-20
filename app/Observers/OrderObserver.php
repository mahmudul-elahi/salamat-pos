<?php

namespace App\Observers;

use App\Models\Ecommerce\Order\Order;
use App\Services\Elite\PointService;

class OrderObserver
{
    public function __construct(private PointService $pointService) {}

    public function updated(Order $order): void
    {
        if ($order->wasChanged('status') && (int) $order->status === 7) {
            $this->pointService->awardPointsForOrder($order);
        }
    }
}
