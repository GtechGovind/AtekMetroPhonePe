<?php

namespace App\Http\Controllers\Modules\Ticket;

use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TicketViewController extends Controller
{
    public function index($order_id): Response
    {
        $order = DB::table('sale_order')
            ->where('sale_or_no', '=', $order_id)
            ->orderBy('sale_or_id', 'desc')
            ->first();

        $productId = $order->product_id;

        if ($productId == env('PRODUCT_SJT'))
        {
            return Inertia::render('Modules/Ticket/View', [
                'type' => $productId,
                'order_id' => $order_id,
                'stations' => DB::table('stations')->get(['stn_id', 'stn_name']),
                'upwardTicket' => $this->getSjtTrips($order_id)
            ]);
        }
        else
        {
            return Inertia::render('Modules/Ticket/View', [
                'type' => $productId,
                'order_id' => $order_id,
                'stations' => DB::table('stations')->get(['stn_id', 'stn_name']),
                'upwardTicket' => $this->getRjtTrips($order_id, env('OUTWARD')),
                'returnTicket' => $this->getRjtTrips($order_id, env('RETURN'))
            ]);
        }
    }

    private function getSjtTrips($order_id): Collection
    {
        return DB::table('sjt_sl_booking as sjt')
            ->join('sale_order as so', 'so.sale_or_id', 'sjt.sale_or_id')
            ->join('stations as s', 's.id', 'so.src_stn_id')
            ->join('stations as d', 'd.id', 'so.des_stn_id')
            ->where('so.sale_or_no', '=', $order_id)
            ->where('sjt.qr_status', '!=', env('EXPIRED'))
            ->where('sjt.qr_status', '!=', env('COMPLETED'))
            ->select(['so.*', 's.stn_name as source', 'd.stn_name as destination', 'sjt.*'])
            ->orderBy('so.txn_date', 'desc')
            ->get();

    }

    private function getRjtTrips($order_id, $dir): Collection
    {
        return DB::table('rjt_sl_booking as rjt')
            ->join('sale_order as so', 'so.sale_or_id', 'rjt.sale_or_id')
            ->join('stations as s', 's.id', 'so.src_stn_id')
            ->join('stations as d', 'd.id', 'so.des_stn_id')
            ->where('so.sale_or_no', '=', $order_id)
            ->where('rjt.qr_dir', '=', $dir)
            ->select(['so.*', 's.stn_name as source', 'd.stn_name as destination', 'rjt.*'])
            ->orderBy('so.txn_date', 'desc')
            ->get();
    }
}
