<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke( Request $request )
    {
        $orders_status = Order::query()->select([ Order::STATUS, DB::raw('count(*) as count') ])->groupBy(Order::STATUS)->get()->keyBy(Order::STATUS);

        $earning  = Payment::query()->sum(Payment::FIXED_AMOUNT);
        $payments = Payment::query()->latest()->limit(10)->get();
        return view('admin.dashboard', compact('orders_status', 'payments', 'earning'));
    }
}
