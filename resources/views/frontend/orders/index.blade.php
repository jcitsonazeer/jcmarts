@include('frontend.header')

<div class="content-top-breadcum"></div>

<div class="container" style="padding:30px 0;">
    <h2>My Orders</h2>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Payment Status</th>
                    <th>Items</th>
                    <th width="120">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    @php($orderDate = $order->created_date ?: $order->paid_at)
                    <tr>
                        <td>{{ $order->id }}</td>
                        <td>{{ $orderDate ? date('d-m-Y H:i', strtotime($orderDate)) : '-' }}</td>
                        <td>{{ $order->currency }} {{ number_format((float) $order->total_amount, 2) }}</td>
                        <td>{{ $order->payment_status ?? '-' }}</td>
                        <td>{{ $order->items_count ?? 0 }}</td>
                        <td>
                            <a href="{{ route('frontend.orders.show', $order->id) }}" class="btn btn-primary btn-sm">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">No orders found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@include('frontend.footer')
