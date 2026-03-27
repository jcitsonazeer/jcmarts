@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <style>
                .order-status-actions {
                    display: flex;
                    flex-wrap: nowrap;
                    align-items: flex-start;
                    gap: 10px;
                    overflow-x: auto;
                    padding-bottom: 4px;
                }

                .order-status-item {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    flex: 0 0 auto;
                    gap: 6px;
                }

                .current-stage-label {
                    min-height: 30px;
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    font-size: 12px;
                    font-weight: 700;
                    color: #28a745;
                    white-space: nowrap;
                }

                .current-stage-label.placeholder {
                    visibility: hidden;
                }

                .order-status-btn {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    min-width: max-content;
                    min-height: 42px;
                    height: 42px;
                    white-space: nowrap;
                    flex: 0 0 auto;
                    padding: 0 14px;
                    text-align: center;
                    line-height: 1.2;
                }

                .order-status-btn.is-current {
                    box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.18);
                }
            </style>

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
                            <div class="col-md-12">
                                <h5>Update Order Status</h5>

                                @if (empty($nextAllowedStatuses))
                                    <div class="alert alert-info">
                                        This order has already reached the final stage.
                                    </div>
                                @else
                                    <form method="POST"
                                          action="{{ route('admin.orders.process.update', $order->id) }}"
                                          onsubmit="return confirm('Are you sure you want to update this order status?');">
                                        @csrf

                                        <div class="order-status-actions">
                                            @foreach($statusFlow as $status)
                                                <div class="order-status-item">
                                                    <span class="current-stage-label {{ $status === $currentStatus ? '' : 'placeholder' }}">
                                                        <span aria-hidden="true">&darr;</span>
                                                        Current Stage
                                                    </span>

                                                    <button
                                                        type="submit"
                                                        name="order_status"
                                                        value="{{ $status }}"
                                                        class="btn btn-cstm order-status-btn
                                                            {{ $status === $currentStatus
                                                                ? 'btn-success'
                                                                : (in_array($status, $nextAllowedStatuses, true)
                                                                    ? 'btn-primary'
                                                                    : 'btn-secondary') }}
                                                            {{ $status === $currentStatus ? 'is-current' : '' }}"
                                                        {{ in_array($status, $nextAllowedStatuses, true) ? '' : 'disabled' }}
                                                    >
                                                        {{ \Illuminate\Support\Str::headline($status) }}
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    </form>
                                @endif
                            </div>
                        </div>

                        <div class="row mt-4">
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
                                        <td>
                                            <span class="btn btn-success btn-sm">
                                                {{ $currentStatusLabel }}
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <div class="col-md-6">
                                <div class="mt-4">
                                    <h5>Process Order</h5>

                                    <ol class="pl-3">
                                        @foreach($timeline as $step)
                                            <li class="mb-2">
                                                <strong>{{ $step['label'] }}</strong>

                                                @if ($step['is_completed'])
                                                    <div>
                                                        Done at:
                                                        {{ $step['action_time'] ? $step['action_time']->format('d-m-Y H:i') : '-' }}
                                                    </div>

                                                    <div>
                                                        Done by: {{ $step['actor_name'] ?? '-' }}
                                                    </div>
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
