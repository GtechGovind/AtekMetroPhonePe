<?php

namespace App\Http\Controllers\Modules\Payment;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Modules\Processing\ProcessingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CallbackController extends Controller
{
    public function paymentCallback(Request $request, $id)
    {
        $response = base64_decode($request->input('response'));
        Log::debug("S2S RESPONSE [" . $id . "] -> " . $response);

        $responseData = json_decode($response);

        $order = DB::table('sale_order')
            ->where('sale_or_no', '=', $id)
            ->first();

        Log::debug("ORDER DETAILS -> " . json_encode($order));

        if ($responseData->success) {

            if ($responseData->code == "PAYMENT_SUCCESS") {

                DB::table('sale_order')
                    ->where('sale_or_no', '=', $order->sale_or_no)
                    ->update([
                        'pg_txn_no' => $responseData->data->providerReferenceId,
                        'sale_or_status' => env('ORDER_PAYMENT_SUCCESS')
                    ]);

                $getTkt = new ProcessingController();
                $getTkt->init($order);

            } else {

                DB::table('sale_order')
                    ->where('sale_or_no', '=', $order->sale_or_no)
                    ->update([
                        'sale_or_status' => env('ORDER_PAYMENT_FAILED')
                    ]);

            }

        } else {

            DB::table('sale_order')
                ->where('sale_or_no', '=', $order->sale_or_no)
                ->update([
                    'sale_or_status' => env('ORDER_PAYMENT_FAILED')
                ]);

        }
    }
}
