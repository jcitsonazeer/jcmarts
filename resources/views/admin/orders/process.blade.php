@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title">Proceed Order - {{ $order->id }}</h4>
                            <div>
                                <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-info">View Order</a>
                                <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">Back</a>
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger">{{ $errors->first() }}</div>
                        @endif

                        @php($orderDate = $order->created_date ?: $order->paid_at)

                        <div class="row">
                            <div class="col-md-6">
                                <h5>Basic Order Details</h5>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Order ID</th>
                                        <td>{{ $order->id }}</td>
                                    </tr>
                                    <tr>
                                        <th>Customer</th>
                                        <td>{{ $order->customer?->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Mobile</th>
                                        <td>{{ $order->customer?->mobile_number ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Order Date</th>
                                        <td>{{ $orderDate ? date('d-m-Y H:i', strtotime($orderDate)) : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Total</th>
                                        <td>{{ $order->currency }} {{ number_format((float) $order->total_amount, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Current Process Status</th>
                                        <td>{{ $currentStatusLabel }}</td>
                                    </tr>
                                </table>
                            </div>

                            <div class="col-md-6">
                                <h5>Update Order Status</h5>
                                @if (empty($nextAllowedStatuses))
                                    <div class="alert alert-info">This order has already reached the final stage.</div>
                                @else
                                    <form method="POST" action="{{ route('admin.orders.process.update', $order->id) }}" onsubmit="return confirm('Are you sure you want to update this order status?');">
                                        @csrf
                                        <div style="display:flex;flex-wrap:wrap;gap:10px;">
                                            @foreach($statusFlow as $status)
                                                <button
                                                    type="submit"
                                                    name="order_status"
                                                    value="{{ $status }}"
                                                    class="btn {{ in_array($status, $nextAllowedStatuses, true) ? 'btn-primary' : 'btn-secondary' }}"
                                                    {{ in_array($status, $nextAllowedStatuses, true) ? '' : 'disabled' }}
                                                >
                                                    {{ $status }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </form>
                                @endif

                                <div class="mt-4">
                                    <h5>Process Order</h5>
                                    <ol class="pl-3">
                                        @foreach($timeline as $step)
                                            <li class="mb-2">
                                                <strong>{{ $step['label'] }}</strong>
                                                @if ($step['is_completed'])
                                                    <div>Done at: {{ $step['action_time'] ? $step['action_time']->format('d-m-Y H:i') : '-' }}</div>
                                                    <div>Done by: {{ $step['actor_name'] ?? '-' }}</div>
                                                @elseif ($step['is_reachable'])
                                                    <div>Pending</div>
                                                @else
                                                    <div>Locked until previous step is completed</div>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
