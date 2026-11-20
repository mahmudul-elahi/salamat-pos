<?php

namespace App\Services\Elite;

use App\Models\Ecommerce\Elite\ContactEliteHistory;
use App\Models\Ecommerce\Elite\Elite;
use App\Models\Ecommerce\Elite\PointTransaction;
use App\Models\Ecommerce\Order\Order;
use App\Models\Pos\Contact\Contact;
use Illuminate\Support\Facades\DB;

class PointService
{
    public function awardPointsForOrder(Order $order): void
    {
        if (! $order->contact_id) {
            return;
        }

        $points = $this->calculateOrderPoints($order);

        if ($points <= 0) {
            return;
        }

        DB::transaction(function () use ($order, $points) {
            $contact = Contact::lockForUpdate()->find($order->contact_id);

            if (! $contact) {
                return;
            }

            $newBalance = $contact->total_points + $points;
            $oldEliteId = $contact->elite_id;
            $newEliteId = $this->resolveEliteId($newBalance);

            $contact->total_points = $newBalance;
            $contact->elite_id = $newEliteId;
            $contact->save();

            if ($newEliteId && $newEliteId !== $oldEliteId) {
                ContactEliteHistory::create([
                    'contact_id' => $contact->id,
                    'elite_id' => $newEliteId,
                    'achieved_at' => now(),
                ]);
            }

            PointTransaction::create([
                'contact_id' => $contact->id,
                'order_id' => $order->id,
                'type' => 'earn',
                'points' => $points,
                'balance_after' => $newBalance,
                'note' => "Order #{$order->invoice_number} delivered",
            ]);
        });
    }

    // public function spendPoints(Contact $contact, int $points, string $note = ''): bool
    // {
    //     if ($contact->total_points < $points) {
    //         return false;
    //     }

    //     DB::transaction(function () use ($contact, $points, $note) {
    //         $contact->lockForUpdate();
    //         $newBalance = $contact->total_points - $points;

    //         $contact->total_points = $newBalance;
    //         $contact->elite_id     = $this->resolveEliteId($newBalance);
    //         $contact->save();

    //         PointTransaction::create([
    //             'contact_id'   => $contact->id,
    //             'order_id'     => null,
    //             'type'         => 'spend',
    //             'points'       => $points,
    //             'balance_after' => $newBalance,
    //             'note'         => $note,
    //         ]);
    //     });

    //     return true;
    // }

    private function calculateOrderPoints(Order $order): int
    {
        return (int) $order->items()
            ->sum(DB::raw('purchase_point * quantity'));
    }

    private function resolveEliteId(int $totalPoints): ?int
    {

        return Elite::where('point', '<=', $totalPoints)
            ->orderByDesc('point')
            ->value('id');
    }
}
