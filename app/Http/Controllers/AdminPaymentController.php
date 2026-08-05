<?php

namespace App\Http\Controllers;

use App\Models\Payment;

class AdminPaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::query()
            ->with(['order.customer'])
            ->orderByDesc('paid_at')
            ->orderByDesc('created_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.payments.index', compact('payments'));
    }

    public function show(int $paymentId)
    {
        $payment = Payment::query()
            ->with(['order.customer', 'order.address', 'order.items.product'])
            ->where('id', $paymentId)
            ->first();

        if (!$payment) {
            return redirect()
                ->route('admin.payments.index')
                ->with('error', 'Payment not found.');
        }

        return view('admin.payments.show', compact('payment'));
    }
}
