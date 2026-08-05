@include('frontend.header')

<div class="content-top-breadcum"></div>

<div class="container order-page-wrapper orders-theme">
    @if ($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="order-details order-fixed">
        <div class="top-row">
            <h3>Return Order - {{ $order->id }}</h3>
            <span class="badge-status badge-paid">
                Return available until {{ $order->return_allowed_until ? $order->return_allowed_until->format('d-m-Y') : '-' }}
            </span>
        </div>

        <div class="section-title">Select Return Reason</div>
        <form method="POST" action="{{ route('frontend.orders.return.store', $order->id) }}">
            @csrf

            <div class="info-card">
                @foreach($reasons as $reason)
                    <label class="info-row" style="cursor:pointer;">
                        <div class="label">
                            <input type="radio" name="reason" value="{{ $reason }}" {{ old('reason') === $reason ? 'checked' : '' }} required>
                        </div>
                        <div class="value">{{ $reason }}</div>
                    </label>
                @endforeach

                <div class="info-row" style="display:block;">
                    <div class="label">Additional Note</div>
                    <textarea name="customer_note" class="form-control" rows="4" placeholder="Describe the issue">{{ old('customer_note') }}</textarea>
                </div>
            </div>

            <div class="section-title">Select Items To Return</div>
            <div class="product-list">
                @foreach($order->returnable_items as $item)
                    <div class="product-card d-flex justify-content-between align-items-center" style="gap:15px;flex-wrap:wrap;">
                        <div>
                            <span class="title">{{ $item->product?->product_name ?? 'Product' }}</span>
                            <span class="meta">Returnable Qty: {{ $item->returnable_quantity }}</span>
                            <span class="meta">Unit: {{ $order->currency }} {{ number_format((float) $item->unit_price, 2) }}</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <label for="return_item_{{ $item->id }}" style="margin:0;">Qty</label>
                            <input
                                id="return_item_{{ $item->id }}"
                                type="number"
                                name="items[{{ $item->id }}][quantity]"
                                class="form-control"
                                value="{{ old('items.' . $item->id . '.quantity', 0) }}"
                                min="0"
                                max="{{ $item->returnable_quantity }}"
                                style="width:90px;"
                            >
                        </div>
                    </div>
                @endforeach
            </div>

            <div style="margin-top:20px;display:flex;gap:10px;">
                <button type="submit" class="btn btn-warning">Submit Return Request</button>
                <a href="{{ route('frontend.orders.index', ['order_id' => $order->id]) }}" class="btn btn-default">Back</a>
            </div>
        </form>
    </div>
</div>

@include('frontend.footer')
