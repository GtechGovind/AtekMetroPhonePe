<?php

namespace App\Http\Controllers\Modules\Ticket;

use App\Http\Controllers\Api\PhonePe\PhonePePaymentController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Modules\Utility;
use App\Models\SaleOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TicketOrderController extends Controller
{
    public function index()
    {
        return Inertia::render('Ticket/Order', [
            'stations' => DB::table('stations')->get(['stn_id', 'stn_name'])
        ]);
    }

    public function create(Request $request)
    {
        $request -> validate([
            'source_id' => ['required'],
            'destination_id' => ['required'],
            'pass_id' => ['required'],
            'quantity' => ['required'],
            'fare' => ['required']
        ]);

        $saleOrderNumber = Utility::genSaleOrderNumber(
            $request->input('pass_id')
        );

        $saleOrder = new SaleOrder();
        $saleOrder -> store($request, $saleOrderNumber);

        $order = DB::table('sale_order as so')
            ->join('stations as s', 's.stn_id', '=', 'so.src_stn_id')
            ->join('stations as d', 'd.stn_id', '=', 'so.des_stn_id')
            ->where('sale_or_no', '=', $saleOrderNumber)
            ->select(['so.*', 's.stn_name as source_name', 'd.stn_name as destination_name'])
            ->first();

        $api = new PhonePePaymentController();
        $response = $api->pay($order);



        if ($response->success == true) {
            return response([
                'status'  =>true,
                'response'=>$response
            ]);

           /* return Inertia::render('Ticket/Order', [
                'readyToPay' => true,
                'redirectUrl' => $response->data->redirectUrl,
                'response' =>$response
            ]);*/
        }

        return redirect()->back()->withErrors([
            'hasError' => false,
            'error' => $response->code
        ]);



    }
}
