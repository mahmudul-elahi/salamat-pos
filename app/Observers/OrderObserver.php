<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Ecommerce\Order\Order;
use App\Services\Elite\PointService;

class OrderObserver
{
    public function __construct(private readonly PointService $pointService) {}

    public function updated(Order $order): void
    {
        if ($order->wasChanged('status') && (int) $order->status === 7) {
            $this->pointService->awardPointsForOrder($order);
        }
    }
}
