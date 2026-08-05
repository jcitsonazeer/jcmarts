<?php

namespace App\Http\Controllers;

use App\Models\Refund;

class AdminRefundController extends Controller
{
    public function index()
    {
        $refunds = Refund::query()
            ->with(['order.customer', 'payment', 'returnRequest.items.product'])
            ->orderByDesc('requested_at')
            ->orderByDesc('created_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.refunds.index', compact('refunds'));
    }

    public function show(int $refundId)
    {
        $refund = Refund::query()
            ->with([
                'order.customer',
                'order.address',
                'payment',
                'returnRequest.items.product',
            ])
            ->where('id', $refundId)
            ->first();

        if (!$refund) {
            return redirect()
                ->route('admin.refunds.index')
                ->with('error', 'Refund not found.');
        }

        return view('admin.refunds.show', compact('refund'));
    }
}
