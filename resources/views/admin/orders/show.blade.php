@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title">Order Details</h4>
                            <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">Back</a>
                        </div>

                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        @php($orderDate = $order->created_date ?: $order->current_payment_paid_at)
                        @php($latestRefund = $order->refunds->sortByDesc('id')->first())
                        @php($hasCancellationRequest = $order->current_order_status === \App\Services\OrderStatusService::STATUS_CANCELLATION_REQUESTED)

                        @if($hasCancellationRequest && $latestRefund && in_array($latestRefund->status, ['requested', 'failed'], true))
                            <div class="alert alert-warning d-flex justify-content-between align-items-center">
                                <div>
                                    Customer requested cancellation. Refund amount:
                                    <strong>{{ $latestRefund->currency }} {{ number_format((float) $latestRefund->amount, 2) }}</strong>
                                </div>
                                <form method="POST"
                                      action="{{ route('admin.orders.approve-cancellation', $order->id) }}"
                                      onsubmit="return confirm('Approve cancellation and start Razorpay refund?');">
                                    @csrf
                                    <button type="submit" class="btn btn-danger">Approve & Refund</button>
                                </form>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <h5>Order Info</h5>
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
                                        <td>{{ $order->current_payment_method ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Payment Status</th>
                                        <td>{{ $order->current_payment_status ?? '-' }}</td>
                                    </tr>
                                    @if($latestRefund)
                                        <tr>
                                            <th>Refund Status</th>
                                            <td>{{ $latestRefund->status }}</td>
                                        </tr>
                                        <tr>
                                            <th>Razorpay Refund ID</th>
                                            <td>{{ $latestRefund->gateway_refund_id ?? '-' }}</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <th>Active</th>
                                        <td>{{ $order->is_active ? 'Yes' : 'No' }}</td>
                                    </tr>
                                </table>
                            </div>

                            <div class="col-md-6">
                                <h5>Customer Info</h5>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Name</th>
                                        <td>{{ $order->customer?->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Mobile</th>
                                        <td>{{ $order->customer?->mobile_number ?? '-' }}</td>
                                    </tr>
                                </table>

                                <h5>Delivery Address</h5>
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

                        <h5>Items</h5>
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
                                    @forelse($order->items as $item)
                                        <tr>
                                            <td>{{ $item->product?->product_name ?? 'Product' }}</td>
                                            <td>{{ $item->quantity }}</td>
                                            <td>{{ $order->currency }} {{ number_format((float) $item->unit_price, 2) }}</td>
                                            <td>{{ $order->currency }} {{ number_format((float) $item->line_total, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center">No items found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <h5>Order Summary</h5>
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
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
