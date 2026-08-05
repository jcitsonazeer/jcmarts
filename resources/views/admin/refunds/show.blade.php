@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title">Refund Details - {{ $refund->id }}</h4>
                            <a href="{{ route('admin.refunds.index') }}" class="btn btn-secondary">Back</a>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <h5>Refund Info</h5>
                                <table class="table table-bordered">
                                    <tr><th>Gateway</th><td>{{ $refund->gateway }}</td></tr>
                                    <tr><th>Razorpay Refund ID</th><td>{{ $refund->gateway_refund_id ?? '-' }}</td></tr>
                                    <tr><th>Amount</th><td>{{ $refund->currency }} {{ number_format((float) $refund->amount, 2) }}</td></tr>
                                    <tr><th>Status</th><td>{{ ucwords(str_replace('_', ' ', $refund->status)) }}</td></tr>
                                    <tr><th>Reason</th><td>{{ $refund->reason ?? '-' }}</td></tr>
                                    <tr><th>Failed Reason</th><td>{{ $refund->failed_reason ?? '-' }}</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5>Process Dates</h5>
                                <table class="table table-bordered">
                                    <tr><th>Requested At</th><td>{{ $refund->requested_at ? $refund->requested_at->format('d-m-Y H:i') : '-' }}</td></tr>
                                    <tr><th>Approved At</th><td>{{ $refund->approved_at ? $refund->approved_at->format('d-m-Y H:i') : '-' }}</td></tr>
                                    <tr><th>Processed At</th><td>{{ $refund->processed_at ? $refund->processed_at->format('d-m-Y H:i') : '-' }}</td></tr>
                                    <tr>
                                        <th>Order</th>
                                        <td><a href="{{ route('admin.orders.show', $refund->order_id) }}">{{ $refund->order_id }}</a></td>
                                    </tr>
                                    <tr>
                                        <th>Payment</th>
                                        <td><a href="{{ route('admin.payments.show', $refund->payment_id) }}">{{ $refund->payment_id }}</a></td>
                                    </tr>
                                    <tr>
                                        <th>Return</th>
                                        <td>
                                            @if($refund->return_id)
                                                <a href="{{ route('admin.returns.show', $refund->return_id) }}">{{ $refund->return_id }}</a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        @if($refund->returnRequest)
                            <h5>Returned Items</h5>
                            <div class="table-responsive">
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
                                        @foreach($refund->returnRequest->items as $item)
                                            <tr>
                                                <td>{{ $item->product?->product_name ?? 'Product' }}</td>
                                                <td>{{ $item->quantity }}</td>
                                                <td>{{ $refund->currency }} {{ number_format((float) $item->unit_price, 2) }}</td>
                                                <td>{{ $refund->currency }} {{ number_format((float) $item->line_total, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
