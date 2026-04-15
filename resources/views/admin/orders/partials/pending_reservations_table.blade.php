<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead class="thead-dark">
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Mobile</th>
                <th>Status</th>
                <th>Reserved At</th>
                <th>Expires At</th>
                <th>Released At</th>
                <th>Release Reason</th>
                <th>Items</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr>
                    <td>{{ $order->id }}</td>
                    <td>{{ $order->customer?->name ?? '-' }}</td>
                    <td>{{ $order->customer?->mobile_number ?? '-' }}</td>
                    <td>{{ ucfirst($order->payment_status ?? '-') }}</td>
                    <td>{{ $order->created_date ? date('d-m-Y H:i:s', strtotime($order->created_date)) : '-' }}</td>
                    <td>{{ $order->reservation_expires_at ? date('d-m-Y H:i:s', strtotime($order->reservation_expires_at)) : '-' }}</td>
                    <td>{{ $order->reservation_released_at ? date('d-m-Y H:i:s', strtotime($order->reservation_released_at)) : '-' }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $order->reservation_release_reason ?? '-')) }}</td>
                    <td>
                        @foreach($order->items as $item)
                            <div>
                                {{ $item->product?->product_name ?? 'Product' }}
                                (Qty: {{ $item->quantity }})
                            </div>
                        @endforeach
                    </td>
                    <td>{{ $order->currency }} {{ number_format((float) $order->total_amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">No expired reservation history found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($orders->hasPages())
    <div class="mt-3 d-flex justify-content-center">
        {{ $orders->links('pagination::bootstrap-4') }}
    </div>
@endif
