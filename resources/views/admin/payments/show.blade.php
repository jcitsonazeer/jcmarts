@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title">Payment Details - {{ $payment->id }}</h4>
                            <a href="{{ route('admin.payments.index') }}" class="btn btn-secondary">Back</a>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <h5>Payment Info</h5>
                                <table class="table table-bordered">
                                    <tr><th>Gateway</th><td>{{ $payment->gateway }}</td></tr>
                                    <tr><th>Gateway Order ID</th><td>{{ $payment->gateway_order_id ?? '-' }}</td></tr>
                                    <tr><th>Gateway Payment ID</th><td>{{ $payment->gateway_payment_id ?? '-' }}</td></tr>
                                    <tr><th>Amount</th><td>{{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}</td></tr>
                                    <tr><th>Status</th><td>{{ ucwords(str_replace('_', ' ', $payment->status)) }}</td></tr>
                                    <tr><th>Paid At</th><td>{{ $payment->paid_at ? $payment->paid_at->format('d-m-Y H:i') : '-' }}</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5>Order & Customer</h5>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Order ID</th>
                                        <td><a href="{{ route('admin.orders.show', $payment->order_id) }}">{{ $payment->order_id }}</a></td>
                                    </tr>
                                    <tr><th>Customer</th><td>{{ $payment->order?->customer?->name ?? '-' }}</td></tr>
                                    <tr><th>Mobile</th><td>{{ $payment->order?->customer?->mobile_number ?? '-' }}</td></tr>
                                    <tr><th>Order Total</th><td>{{ $payment->order?->currency ?? $payment->currency }} {{ number_format((float) ($payment->order?->total_amount ?? 0), 2) }}</td></tr>
                                </table>
                            </div>
                        </div>

                        <h5>Order Items</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Line Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($payment->order?->items ?? [] as $item)
                                        <tr>
                                            <td>{{ $item->product?->product_name ?? 'Product' }}</td>
                                            <td>{{ $item->quantity }}</td>
                                            <td>{{ $payment->order?->currency ?? $payment->currency }} {{ number_format((float) $item->unit_price, 2) }}</td>
                                            <td>{{ $payment->order?->currency ?? $payment->currency }} {{ number_format((float) $item->line_total, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center">No items found</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
