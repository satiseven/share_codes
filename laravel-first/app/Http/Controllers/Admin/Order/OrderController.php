<?php

namespace App\Http\Controllers\Admin\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;

class OrderController extends Controller
{
    public function index()
    {
        //$orders = Order::query()->orderByDesc(Order::ID);
        //dd($orders);
        return view('admin.order.index');
        //return view('admin.order.index', compact('orders'));
    }
}
