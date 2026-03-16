@include('frontend.header')

<style>
    :root {
        --green: #28a745;
        --red: #dc3545;
        --yellow: #ffc107;
        --green-soft: #5faf76;
        --yellow-soft: #5faf76;
        --red-soft: #fde8ea;
        --ink: #1f2a37;
        --muted: #5f6b7a;
    }

    .order-page-wrapper { padding: 30px 0; }

    .order-sidebar {
        background: linear-gradient(160deg, var(--yellow-soft), #f6f2da);
        border: 1px solid #f1d98a;
        border-radius: 14px;
        padding: 18px;
        display: flex;
        flex-direction: column;
        height: 720px;
    }

    .order-sidebar h3 { margin: 0 0 14px; font-weight: 700; color: var(--ink); }

    .order-search { display: flex; gap: 8px; margin-bottom: 14px; }
    .order-search input { border-radius: 10px; border: 1px solid #f1d98a; background: #fff7d9; }
    .order-search button { border-radius: 10px; background: var(--green); color: #fff; border: 0; }

    .order-list { flex: 1; overflow-y: auto; padding-right: 6px; }

    .order-card {
        background: #fef6d9;
        border: 1px solid #f1d98a;
        border-radius: 12px;
        padding: 14px 14px;
        margin-bottom: 12px;
        transition: transform .12s ease, box-shadow .12s ease;
    }
    .order-card:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(0,0,0,0.08); }
    .order-card.active { border-color: var(--green); box-shadow: 0 8px 18px rgba(40, 167, 69, 0.2); }

    .order-card .order-row { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
    .order-card .order-left { display: flex; flex-direction: column; }
    .order-card .order-right { display: flex; flex-direction: column; align-items: flex-end; }
    .order-card .order-id { font-size: 16px; font-weight: 700; color: var(--ink); }
    .order-card .order-meta { font-size: 12px; color: var(--muted); margin-top: 4px; }
    .order-card .order-total { font-size: 13px; font-weight: 700; color: var(--ink); }
    .order-card .status-pill {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 11px;
        margin-top: 6px;
        border: 1px solid #e9c86b;
        background: #fff0b4;
        color: #7a5b00;
    }

    .order-details {
        background: linear-gradient(160deg, var(--green-soft), #f2f7ef);
        border: 1px solid #cfe6d7;
        border-radius: 14px;
        padding: 18px;
        display: flex;
        flex-direction: column;
        height: 720px;
    }

    .order-details .top-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .order-details h3 { margin: 0; font-weight: 800; color: var(--ink); }
    .order-date { color: #000; margin-top: 8px; }

    .badge-status {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
    }
    .badge-paid { background: #e6f7ec; color: #1e7e34; border: 1px solid #bfe9cd; }
    .badge-unpaid { background: var(--red-soft); color: var(--red); border: 1px solid #f3b5bd; }

    .section-title {
        margin: 12px 0 8px;
        font-weight: 700;
        color: var(--ink);
        font-size: 14px;
    }

    .info-card {
        background: #f6fff6;
        border: 1px solid #cfe6d7;
        border-radius: 12px;
        padding: 10px;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 4px 0;
        border-bottom: 1px dashed #cfe6d7;
    }
    .info-row:last-child { border-bottom: 0; }
    .info-row .label { font-size: 11px; color: var(--muted); }
    .info-row .value { font-size: 13px; font-weight: 700; color: var(--ink); }

    .product-list {
        display: grid;
        gap: 10px;
        max-height: 380px;
        overflow-y: auto;
        padding-right: 6px;
    }

    .product-card {
        background: #f6fff6;
        border: 1px solid #cfe6d7;
        border-radius: 12px;
        padding: 10px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .product-card .title { font-weight: 700; color: var(--ink); }
    .product-card .meta { font-size: 11px; color: var(--muted); margin-top: 4px; }
    .product-card .price { font-weight: 800; color: var(--ink); }

    .amount-card {
        background: #fff0b4;
        border: 1px solid #e9c86b;
        border-radius: 12px;
        padding: 10px;
    }
    .amount-row { display: flex; justify-content: space-between; padding: 4px 0; border-bottom: 1px dashed #e9c86b; }
    .amount-row:last-child { border-bottom: 0; }
    .amount-row .label { color: #6b5200; font-size: 11px; }
    .amount-row .value { font-weight: 800; color: #3c2f00; font-size: 13px; }

    .address-card {
        background: #f6fff6;
        border: 1px solid #cfe6d7;
        border-radius: 12px;
        padding: 10px;
        line-height: 1.5;
        color: var(--ink);
        font-size: 13px;
    }

    .two-card-row {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    @media (max-width: 991px) {
        .order-sidebar,
        .order-details { min-height: auto; }
        .two-card-row { grid-template-columns: 1fr; }
    }
</style>

<div class="content-top-breadcum"></div>

<div class="container order-page-wrapper">
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
            <div class="order-sidebar">
                <h3>Order History</h3>

                <form class="order-search" method="GET" action="{{ route('frontend.orders.index') }}">
                    <input type="text" name="q" value="{{ $search ?? '' }}" class="form-control" placeholder="Search (order id or status)">
                    <button type="submit" class="btn">Search</button>
                </form>

                <div class="order-list">
                    @forelse($orders as $order)
                        @php($orderDate = $order->created_date ?: $order->paid_at)
                        <a href="{{ route('frontend.orders.index', ['order_id' => $order->id, 'q' => $search]) }}" style="text-decoration:none;">
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
            <div class="order-details">
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
