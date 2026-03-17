@include('frontend.header')

<div class="content-top-breadcum"></div>

<div class="container order-page-wrapper orders-theme">
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

    <div class="row">
        <div class="col-md-4">
            <div class="order-sidebar order-fixed">
                <h3>Order History</h3>

                <form class="order-search" method="GET" action="{{ route('frontend.orders.index') }}">
                    <input type="text" name="q" value="{{ $search ?? '' }}" class="form-control" placeholder="Search (order id or status)">
                    <button type="submit" class="btn">Search</button>
                </form>

                <div class="order-list">
                    @forelse($orders as $order)
                        @php($orderDate = $order->created_date ?: $order->paid_at)
                        <a href="{{ route('frontend.orders.index', ['order_id' => $order->id, 'q' => $search]) }}" class="order-link">
                            <div class="order-card {{ ($selectedOrderId ?? 0) === (int) $order->id ? 'active' : '' }}">
                                <div class="order-row">
                                    <div class="order-left">
                                        <div class="order-id">Order : {{ $order->id }}</div>
                                        <div class="order-meta">{{ $orderDate ? date('d-m-Y H:i', strtotime($orderDate)) : '-' }}</div>
                                    </div>
                                    <div class="order-right">
                                        <div class="order-total">{{ $order->currency }} {{ number_format((float) $order->total_amount, 2) }}</div>
                                        <div class="status-pill">{{ $order->payment_status ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="text-center">No orders found.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="order-details order-fixed">
                @if($selectedOrder)
                    @php($orderDate = $selectedOrder->created_date ?: $selectedOrder->paid_at)
                    <div class="top-row">
                        <h3>Order - {{ $selectedOrder->id }}</h3>
                        @php($isPaid = strtolower((string) $selectedOrder->payment_status) === 'paid')
                        <span class="badge-status {{ $isPaid ? 'badge-paid' : 'badge-unpaid' }}">
                            {{ $selectedOrder->payment_status ?? 'Status' }}
                        </span>
                    </div>

                    <div class="order-date">Order date: {{ $orderDate ? date('d-m-Y H:i', strtotime($orderDate)) : '-' }}</div>

                    <div class="section-title">Order Info</div>
                    <div class="info-card">
                        <div class="info-row">
                            <div class="label">Order ID</div>
                            <div class="value">{{ $selectedOrder->id }}</div>
                        </div>
                        <div class="info-row">
                            <div class="label">Payment Method</div>
                            <div class="value">{{ $selectedOrder->payment_method ?? '-' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="label">Payment Status</div>
                            <div class="value">{{ $selectedOrder->payment_status ?? '-' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="label">Active</div>
                            <div class="value">{{ $selectedOrder->is_active ? 'Yes' : 'No' }}</div>
                        </div>
                    </div>

                    <div class="section-title">Products ({{ $selectedOrder->items->count() }})</div>
                    <div class="product-list">
                        @forelse($selectedOrder->items as $item)
<div class="product-card d-flex justify-content-between align-items-center">
    
    <div class="d-flex gap-3 align-items-center">

        <span class="title">
            {{ $item->product?->product_name ?? 'Product' }}
        </span>

        <span class="meta">
            Qty: {{ $item->quantity }}
        </span>

        <span class="meta">
            Unit: {{ $selectedOrder->currency }} {{ number_format((float) $item->unit_price, 2) }}
        </span>

    </div>

    <div class="price">
        {{ $selectedOrder->currency }} {{ number_format((float) $item->line_total, 2) }}
    </div>

</div>
                        @empty
                            <div class="text-center">No items found.</div>
                        @endforelse
                    </div>

                    <div class="section-title">Summary</div>
                    <div class="two-card-row">


                        <div class="address-card">
                            <div><strong>Address:</strong> {{ $selectedOrder->address?->address_line_1 ?? '-' }}</div>
                            <div>{{ $selectedOrder->address?->address_line_2 ?? '' }}</div>
                            <div><strong>Location:</strong> {{ $selectedOrder->address?->location ?? '-' }}</div>
                            <div><strong>Pincode:</strong> {{ $selectedOrder->address?->pincode ?? '-' }}</div>
                            <div><strong>Landmark:</strong> {{ $selectedOrder->address?->landmark ?? '-' }}</div>
                        </div>
						
						  <div class="amount-card">
                            <div class="amount-row">
                                <div class="label">Sub Total</div>
                                <div class="value">{{ $selectedOrder->currency }} {{ number_format((float) $selectedOrder->sub_total, 2) }}</div>
                            </div>
                            <div class="amount-row">
                                <div class="label">Delivery Charge</div>
                                <div class="value">{{ $selectedOrder->currency }} {{ number_format((float) $selectedOrder->delivery_charge, 2) }}</div>
                            </div>
                            <div class="amount-row">
                                <div class="label">Packing Charge</div>
                                <div class="value">{{ $selectedOrder->currency }} {{ number_format((float) $selectedOrder->packing_charge, 2) }}</div>
                            </div>
                            <div class="amount-row">
                                <div class="label">Other Charge</div>
                                <div class="value">{{ $selectedOrder->currency }} {{ number_format((float) $selectedOrder->other_charge, 2) }}</div>
                            </div>
                            <div class="amount-row">
                                <div class="label">Total</div>
                                <div class="value">{{ $selectedOrder->currency }} {{ number_format((float) $selectedOrder->total_amount, 2) }}</div>
                            </div>
                        </div>
						
                    </div>
                @else
                    <div class="text-center">Select an order to view details.</div>
                @endif
            </div>
        </div>
    </div>
</div>

@include('frontend.footer')
