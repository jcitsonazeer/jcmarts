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
                        @php($latestReturn = $order->returnRequests->sortByDesc('id')->first())
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

                        @if($latestReturn)
                            <div class="card mb-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0">Return Request</h5>
                                        <span class="badge badge-warning">
                                            {{ ucwords(str_replace('_', ' ', $latestReturn->status)) }}
                                        </span>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <table class="table table-bordered">
                                                <tr>
                                                    <th>Reason</th>
                                                    <td>{{ $latestReturn->reason }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Customer Note</th>
                                                    <td>{{ $latestReturn->customer_note ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Refund Amount</th>
                                                    <td>{{ $order->currency }} {{ number_format((float) $latestReturn->refund_amount, 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Requested At</th>
                                                    <td>{{ $latestReturn->requested_at ? $latestReturn->requested_at->format('d-m-Y H:i') : '-' }}</td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <table class="table table-bordered">
                                                <tr>
                                                    <th>Approved At</th>
                                                    <td>{{ $latestReturn->approved_at ? $latestReturn->approved_at->format('d-m-Y H:i') : '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Pickup Scheduled</th>
                                                    <td>{{ $latestReturn->pickup_scheduled_at ? $latestReturn->pickup_scheduled_at->format('d-m-Y H:i') : '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Received At</th>
                                                    <td>{{ $latestReturn->received_at ? $latestReturn->received_at->format('d-m-Y H:i') : '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Admin Note</th>
                                                    <td>{{ $latestReturn->admin_note ?? '-' }}</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>

                                    <h5>Return Items</h5>
                                    <div class="table-responsive mb-3">
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
                                                @foreach($latestReturn->items as $returnItem)
                                                    <tr>
                                                        <td>{{ $returnItem->product?->product_name ?? 'Product' }}</td>
                                                        <td>{{ $returnItem->quantity }}</td>
                                                        <td>{{ $order->currency }} {{ number_format((float) $returnItem->unit_price, 2) }}</td>
                                                        <td>{{ $order->currency }} {{ number_format((float) $returnItem->line_total, 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    @if($latestReturn->status === 'return_requested')
                                        <form method="POST" action="{{ route('admin.returns.update', $latestReturn->id) }}" class="mb-2">
                                            @csrf
                                            <textarea name="admin_note" class="form-control mb-2" rows="2" placeholder="Admin note"></textarea>
                                            <button type="submit" name="action" value="approve" class="btn btn-success">Approve Return</button>
                                            <button type="submit" name="action" value="reject" class="btn btn-danger" onclick="return confirm('Reject this return request?');">Reject Return</button>
                                        </form>
                                    @elseif($latestReturn->status === 'return_approved')
                                        <form method="POST" action="{{ route('admin.returns.update', $latestReturn->id) }}">
                                            @csrf
                                            <button type="submit" name="action" value="schedule_pickup" class="btn btn-primary">Schedule Pickup</button>
                                        </form>
                                    @elseif($latestReturn->status === 'pickup_scheduled')
                                        <form method="POST" action="{{ route('admin.returns.update', $latestReturn->id) }}">
                                            @csrf
                                            <button type="submit" name="action" value="mark_received" class="btn btn-primary">Mark Product Received</button>
                                        </form>
                                    @elseif($latestReturn->status === 'product_received')
                                        <form method="POST" action="{{ route('admin.returns.update', $latestReturn->id) }}">
                                            @csrf
                                            <textarea name="admin_note" class="form-control mb-2" rows="2" placeholder="Inspection note"></textarea>
                                            <label class="d-block mb-2">
                                                <input type="checkbox" name="sellable_stock" value="1">
                                                Product is sellable and can be added back to stock
                                            </label>
                                            <button type="submit" name="action" value="inspection_passed" class="btn btn-success" onclick="return confirm('Pass inspection and start Razorpay refund?');">
                                                Inspection Passed & Refund
                                            </button>
                                            <button type="submit" name="action" value="inspection_failed" class="btn btn-danger" onclick="return confirm('Fail inspection and close return?');">
                                                Inspection Failed
                                            </button>
                                        </form>
                                    @endif
                                    <div class="mt-3">
                                        <a href="{{ route('admin.returns.show', $latestReturn->id) }}" class="btn btn-info btn-sm">View Return</a>
                                        <a href="{{ route('admin.returns.process', $latestReturn->id) }}" class="btn btn-primary btn-sm">Process Return</a>
                                    </div>
                                </div>
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
