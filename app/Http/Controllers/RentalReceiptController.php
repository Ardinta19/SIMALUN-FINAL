<?php

namespace App\Http\Controllers;

use App\Models\RentalOrder;
use Illuminate\Http\Request;

class RentalReceiptController extends Controller
{
    public function show(Request $request, RentalOrder $order)
    {
        $user = $request->user();
        abort_unless($user->role === 'admin' || ($user->role === 'customer' && $order->customer_id === $user->id), 403);
        $order->load(['customer', 'units.payments.refunds']);
        return response()->view('rental.receipt', compact('order'))->header('Cache-Control', 'private, no-store');
    }
}
