<?php

namespace App\Http\Controllers\Ecommerce\SslCommerz;

use App\Enums\OnlinePaymentStatusEnum;
use App\Http\Controllers\Controller;
use App\Library\SslCommerz\SslCommerzNotification;
use App\Models\Ecommerce\Order\Order;
use Illuminate\Http\Request;

class SslCommerzController extends Controller
{
    public function ipn(Request $request)
    {
        // Received all the payment information from the gateway
        if ($request->input('tran_id')) {
            $tran_id = $request->input('tran_id');

            $order = Order::where('transaction_id', $tran_id)->first();

            if (! $order) {
                return response()->json(['message' => 'Order not found with this transaction id'], 404);
            }

            $sslc = new SslCommerzNotification;
            $validation = $sslc->orderValidate($request->all(), $tran_id, $order->grand_total, 'BDT');

            if ($validation == true) {
                if ($order->payment_status !== OnlinePaymentStatusEnum::Successful->value) {
                    $order->update([
                        'payment_status' => OnlinePaymentStatusEnum::Successful->value,
                    ]);
                }

                return response()->json(['message' => 'Transaction successfully completed']);
            }

            return response()->json(['message' => 'Order validation failed'], 400);
        }

        return response()->json(['message' => 'Transaction ID missing'], 400);
    }
}
