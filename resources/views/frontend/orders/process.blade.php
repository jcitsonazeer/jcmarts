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

    @if ($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    @php($orderDate = $order->created_date ?: $order->paid_at)

    <div class="process-grid">
        <div class="process-card">
            <div class="top-row">
                <h3>Proceed Order - {{ $order->id }}</h3>
                <span class="badge-status {{ $currentStatus === 'order_delivered' ? 'badge-paid' : 'badge-unpaid' }}">
                    {{ $currentStatusLabel }}
                </span>
            </div>

            <div class="order-date">Order date: {{ $orderDate ? date('d-m-Y H:i', strtotime($orderDate)) : '-' }}</div>

            <div class="section-title">Basic Order Details</div>
            <div class="info-card">
                <div class="info-row">
                    <div class="label">Customer</div>
                    <div class="value">{{ $order->customer?->name ?? '-' }}</div>
                </div>
                <div class="info-row">
                    <div class="label">Mobile</div>
                    <div class="value">{{ $order->customer?->mobile_number ?? '-' }}</div>
                </div>
                <div class="info-row">
                    <div class="label">Total</div>
                    <div class="value">{{ $order->currency }} {{ number_format((float) $order->total_amount, 2) }}</div>
                </div>
                <div class="info-row">
                    <div class="label">Payment Status</div>
                    <div class="value">{{ $order->payment_status ?? '-' }}</div>
                </div>
                <div class="info-row">
                    <div class="label">Current Process Status</div>
                    <div class="value">{{ $currentStatusLabel }}</div>
                </div>
            </div>

            <div class="section-title">Change Order Status</div>
            @if (empty($nextAllowedStatuses))
                <div class="alert alert-success process-finished-message">
                    This order has reached the final stage.
                </div>
            @else
                <form method="POST" action="{{ route('frontend.orders.process.update', $order->id) }}" onsubmit="return confirm('Are you sure you want to update this order status?');">
                    @csrf
                    <div style="display:flex;flex-wrap:wrap;gap:10px;">
                        @foreach($statusFlow as $status)
                            <button
                                type="submit"
                                name="order_status"
                                value="{{ $status }}"
                                class="btn {{ in_array($status, $nextAllowedStatuses, true) ? 'btn-primary' : 'btn-default' }}"
                                {{ in_array($status, $nextAllowedStatuses, true) ? '' : 'disabled' }}
                            >
                                {{ $status }}
                            </button>
                        @endforeach
                    </div>
                </form>
            @endif

            <div class="process-actions">
                <a href="{{ route('frontend.orders.index', ['order_id' => $order->id]) }}" class="btn btn-default">Back to My Orders</a>
            </div>
        </div>

        <div class="process-card">
            <div class="section-title">Order Progress</div>
            <div class="order-worm-graph horizontal-worm-graph">
                @foreach($timeline as $step)
                    <div class="worm-step {{ $step['is_completed'] ? 'completed' : '' }} {{ $step['is_current'] ? 'current' : '' }} {{ $step['is_pending'] ? 'pending' : '' }}">
                        <div class="worm-marker"></div>
                        <div class="worm-content">
                            <div class="worm-title">{{ $step['label'] }}</div>
                            <div class="worm-meta">
                                @if ($step['action_time'])
                                    {{ $step['action_time']->format('d-m-Y H:i') }}
                                @else
                                    Pending
                                @endif
                            </div>
                            <div class="worm-meta">
                                @if (!empty($step['actor_name']))
                                    Updated by {{ $step['actor_name'] }}
                                @else
                                    Waiting for update
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@include('frontend.footer')
