<?php

namespace App\Http\Controllers\Pos\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Pos\Purchase\Purchase;
use App\Models\Pos\Transaction\Transaction;
use Illuminate\Http\Request;

class PurchaseTransactionController extends Controller
{
    /**
     * Get all transactions for a purchase.
     */
    public function index(Purchase $purchase)
    {
        return Transaction::query()
            ->where('model_type', Purchase::class)
            ->where('model_id', $purchase->id)
            ->with('paymentMethod:id,name')
            ->orderByDesc('date')
            ->get();
    }
}
