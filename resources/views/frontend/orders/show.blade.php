@include('frontend.header')

<div class="content-top-breadcum"></div>

<div class="container" style="padding:30px 0;">
    <div class="d-flex justify-content-between align-items-center" style="margin-bottom:15px;">
        <h2>Order Details</h2>
        <a href="{{ route('frontend.orders.index') }}" class="btn btn-default">Back to Orders</a>
    </div>

    @php($orderDate = $order->created_date ?: $order->paid_at)

    <div class="row">
        <div class="col-md-6">
            <h4>Order Info</h4>
            <table class="table table-bordered">
                <tr>
                    <th>Order ID</th>
                    <td>{{ $order->id }}</td>
                </tr>
                <tr>
                    <th>Date</th>
                    <td>{{ $orderDate ? date('d-m-Y H:i', strtotime($orderDate)) : '-' }}</td>
                </tr>
                <tr>
                    <th>Payment Method</th>
                    <td>{{ $order->payment_method ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Payment Status</th>
                    <td>{{ $order->payment_status ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Active</th>
                    <td>{{ $order->is_active ? 'Yes' : 'No' }}</td>
                </tr>
            </table>
        </div>

        <div class="col-md-6">
            <h4>Delivery Address</h4>
            <table class="table table-bordered">
                <tr>
                    <th>Address</th>
                    <td>
                        {{ $order->address?->address_line_1 ?? '-' }}<br>
                        {{ $order->address?->address_line_2 ?? '' }}
                    </td>
                </tr>
                <tr>
                    <th>Location</th>
                    <td>{{ $order->address?->location ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Pincode</th>
                    <td>{{ $order->address?->pincode ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Landmark</th>
                    <td>{{ $order->address?->landmark ?? '-' }}</td>
                </tr>
            </table>
        </div>
    </div>

    <h4>Items</h4>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Line Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->items as $item)
                    <tr>
                        <td>{{ $item->product?->product_name ?? 'Product' }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ $order->currency }} {{ number_format((float) $item->unit_price, 2) }}</td>
                        <td>{{ $order->currency }} {{ number_format((float) $item->line_total, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">No items found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h4>Order Summary</h4>
    <table class="table table-bordered">
        <tr>
            <th>Sub Total</th>
            <td>{{ $order->currency }} {{ number_format((float) $order->sub_total, 2) }}</td>
        </tr>
        <tr>
            <th>Delivery Charge</th>
            <td>{{ $order->currency }} {{ number_format((float) $order->delivery_charge, 2) }}</td>
        </tr>
        <tr>
            <th>Packing Charge</th>
            <td>{{ $order->currency }} {{ number_format((float) $order->packing_charge, 2) }}</td>
        </tr>
        <tr>
            <th>Other Charge</th>
            <td>{{ $order->currency }} {{ number_format((float) $order->other_charge, 2) }}</td>
        </tr>
        <tr>
            <th>Total</th>
            <td><strong>{{ $order->currency }} {{ number_format((float) $order->total_amount, 2) }}</strong></td>
        </tr>
    </table>
</div>

@include('frontend.footer')
