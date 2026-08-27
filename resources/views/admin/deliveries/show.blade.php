@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title">Delivery Details - #{{ $delivery->id }}</h4>
                            <div>
                                <a href="{{ route('admin.orders.show', $delivery->order_id) }}" class="btn btn-info">View Order</a>
                                <a href="{{ route('admin.deliveries.active') }}" class="btn btn-secondary">Back</a>
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <h5>Order Information</h5>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Order ID</th>
                                        <td>
                                            <a href="{{ route('admin.orders.show', $delivery->order_id) }}">
                                                #{{ $delivery->order_id }}
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Customer</th>
                                        <td>{{ $delivery->order?->customer?->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Mobile</th>
                                        <td>{{ $delivery->order?->customer?->mobile_number ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Order Amount</th>
                                        <td>{{ $delivery->order?->currency ?? 'INR' }} {{ number_format((float) ($delivery->order?->total_amount ?? 0), 2) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Order Status</th>
                                        <td>
                                            @php($orderStatus = $delivery->order?->statuses?->sortByDesc('action_time')->first())
                                            @if($orderStatus)
                                                <span class="badge badge-info">{{ ucwords(str_replace('_', ' ', $orderStatus->order_status)) }}</span>
                                            @else
                                                <span class="badge badge-secondary">Not Started</span>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <div class="col-md-6">
                                <h5>Delivery Information</h5>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Delivery Status</th>
                                        <td>
                                            @if($delivery->status === 'not_assigned')
                                                <span class="badge badge-secondary">Not Assigned</span>
                                            @elseif($delivery->status === 'assigned')
                                                <span class="badge badge-primary">Assigned</span>
                                            @elseif($delivery->status === 'accepted')
                                                <span class="badge badge-info">Accepted</span>
                                            @elseif($delivery->status === 'picked_up')
                                                <span class="badge badge-warning">Picked Up</span>
                                            @elseif($delivery->status === 'out_for_delivery')
                                                <span class="badge badge-dark">Out for Delivery</span>
                                            @elseif($delivery->status === 'delivered')
                                                <span class="badge badge-success">Delivered</span>
                                            @elseif($delivery->status === 'cancelled')
                                                <span class="badge badge-danger">Cancelled</span>
                                            @elseif($delivery->status === 'failed')
                                                <span class="badge badge-danger">Failed</span>
                                            @elseif($delivery->status === 'rejected')
                                                <span class="badge badge-danger">Rejected</span>
                                            @else
                                                <span class="badge badge-secondary">{{ ucwords(str_replace('_', ' ', $delivery->status)) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Delivery Person</th>
                                        <td>{{ $delivery->deliveryPerson?->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Assigned At</th>
                                        <td>{{ $delivery->assigned_at ? date('d-m-Y H:i', strtotime($delivery->assigned_at)) : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Accepted At</th>
                                        <td>{{ $delivery->accepted_at ? date('d-m-Y H:i', strtotime($delivery->accepted_at)) : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Picked Up At</th>
                                        <td>{{ $delivery->picked_up_at ? date('d-m-Y H:i', strtotime($delivery->picked_up_at)) : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Out for Delivery At</th>
                                        <td>{{ $delivery->out_for_delivery_at ? date('d-m-Y H:i', strtotime($delivery->out_for_delivery_at)) : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Delivered At</th>
                                        <td>{{ $delivery->delivered_at ? date('d-m-Y H:i', strtotime($delivery->delivered_at)) : '-' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h5>Delivery Address</h5>
                                <p>{{ $delivery->delivery_address ?? '-' }}</p>
                            </div>
                            <div class="col-md-6">
                                @if(!empty($allowedStatuses))
                                    <h5>Update Status</h5>
                                    <form method="POST" action="{{ route('admin.deliveries.update-status', $delivery->id) }}" onsubmit="return confirm('Are you sure you want to update the delivery status?');">
                                        @csrf
                                        <div class="form-group">
                                            <select name="delivery_status" class="form-control" required>
                                                <option value="">-- Select Status --</option>
                                                @foreach($allowedStatuses as $status)
                                                    <option value="{{ $status }}">{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $status)) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-save"></i> Update Status
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-12">
                                <h5>Status Timeline</h5>
                                <div class="row">
                                    @foreach($timeline as $step)
                                        <div class="col-md-2 text-center mb-3">
                                            <div class="p-2 rounded {{ $step['is_completed'] ? 'bg-success text-white' : ($step['is_current'] ? 'bg-primary text-white' : 'bg-light') }}">
                                                <strong>{{ $step['label'] }}</strong>
                                                @if($step['timestamp'])
                                                    <div><small>{{ $step['timestamp']->format('d-m-Y H:i') }}</small></div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        @if($delivery->statusHistories && $delivery->statusHistories->count())
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <h5>Status History</h5>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered">
                                            <thead class="thead-dark">
                                                <tr>
                                                    <th>Time</th>
                                                    <th>Old Status</th>
                                                    <th>New Status</th>
                                                    <th>Changed By</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($delivery->statusHistories->sortByDesc('changed_at') as $history)
                                                    <tr>
                                                        <td>{{ $history->changed_at ? date('d-m-Y H:i', strtotime($history->changed_at)) : '-' }}</td>
                                                        <td>{{ $history->old_status ? ucwords(str_replace('_', ' ', $history->old_status)) : '-' }}</td>
                                                        <td>{{ ucwords(str_replace('_', ' ', $history->new_status)) }}</td>
                                                        <td>{{ $history->changed_by_id ?? '-' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
