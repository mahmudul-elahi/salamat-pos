<?php

namespace App\Http\Controllers\Ecommerce\SslCommerz;

use App\Http\Controllers\Controller;
use App\Library\SslCommerz\SslCommerzNotification;
use App\Models\Ecommerce\Order\Order;
use Illuminate\Http\Request;

class SslCommerzController extends Controller
{
    public function ipn(Request $request)
    {
        #Received all the payement information from the gateway
        if ($request->input('tran_id')) #Check transation id is posted or not.
        {

            $tran_id = $request->input('tran_id');

            #Check order status in order tabel against the transaction id or order id.
            $order = Order::where('transaction_id', $tran_id)
                ->first();

            if (!$order) {
                throw new \Exception('Order not found with this transaction id');
            }




            $sslc = new SslCommerzNotification();
            $validation = $sslc->orderValidate($request->all(), $tran_id, $order->grand_total, "BDT");
            dd($validation);
            if ($validation == TRUE) {
                /*
                    That means IPN worked. Here you need to update order status
                    in order table as Processing or Complete.
                    Here you can also sent sms or email for successful transaction to customer
                    */
                // $update_product = Order::
                //     where('transaction_id', $tran_id)
                //     ->update(['status' => 'Processing']);

                // echo "Transaction is successfully Completed";
                // Order::find(Session::get('s_order_id'))->update([
                //     'status' => StatusEnum::Pending->value,
                // ]);
            }
        }
    }
}
