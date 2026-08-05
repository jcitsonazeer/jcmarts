<div class="row">
    <div class="col-md-6">
        <h5>Return Info</h5>
        <table class="table table-bordered">
            <tr>
                <th>Status</th>
                <td>{{ ucwords(str_replace('_', ' ', $return->status)) }}</td>
            </tr>
            <tr>
                <th>Order ID</th>
                <td>
                    <a href="{{ route('admin.orders.show', $return->order_id) }}">
                        {{ $return->order_id }}
                    </a>
                </td>
            </tr>
            <tr>
                <th>Reason</th>
                <td>{{ $return->reason }}</td>
            </tr>
            <tr>
                <th>Customer Note</th>
                <td>{{ $return->customer_note ?? '-' }}</td>
            </tr>
            <tr>
                <th>Refund Amount</th>
                <td>{{ $return->order?->currency ?? 'INR' }} {{ number_format((float) $return->refund_amount, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="col-md-6">
        <h5>Customer & Timeline</h5>
        <table class="table table-bordered">
            <tr>
                <th>Customer</th>
                <td>{{ $return->customer?->name ?? $return->order?->customer?->name ?? '-' }}</td>
            </tr>
            <tr>
                <th>Mobile</th>
                <td>{{ $return->customer?->mobile_number ?? $return->order?->customer?->mobile_number ?? '-' }}</td>
            </tr>
            <tr>
                <th>Requested At</th>
                <td>{{ $return->requested_at ? $return->requested_at->format('d-m-Y H:i') : '-' }}</td>
            </tr>
            <tr>
                <th>Approved At</th>
                <td>{{ $return->approved_at ? $return->approved_at->format('d-m-Y H:i') : '-' }}</td>
            </tr>
            <tr>
                <th>Received At</th>
                <td>{{ $return->received_at ? $return->received_at->format('d-m-Y H:i') : '-' }}</td>
            </tr>
            <tr>
                <th>Admin Note</th>
                <td>{{ $return->admin_note ?? '-' }}</td>
            </tr>
        </table>
    </div>
</div>

<h5>Returned Items</h5>
<div class="table-responsive mb-4">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Product</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Refund Line Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($return->items as $item)
                <tr>
                    <td>{{ $item->product?->product_name ?? 'Product' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $return->order?->currency ?? 'INR' }} {{ number_format((float) $item->unit_price, 2) }}</td>
                    <td>{{ $return->order?->currency ?? 'INR' }} {{ number_format((float) $item->line_total, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">No return items found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@php($latestRefund = $return->refunds->sortByDesc('id')->first())
@if($latestRefund)
    <h5>Refund</h5>
    <table class="table table-bordered mb-4">
        <tr>
            <th>Status</th>
            <td>{{ $latestRefund->status }}</td>
        </tr>
        <tr>
            <th>Razorpay Refund ID</th>
            <td>
                <a href="{{ route('admin.refunds.show', $latestRefund->id) }}">
                    {{ $latestRefund->gateway_refund_id ?? ('Refund #' . $latestRefund->id) }}
                </a>
            </td>
        </tr>
        <tr>
            <th>Amount</th>
            <td>{{ $latestRefund->currency }} {{ number_format((float) $latestRefund->amount, 2) }}</td>
        </tr>
    </table>
@endif
