@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title">Order Management</h4>
                            <a href="{{ route('admin.orders.pending-reservations') }}" class="btn btn-warning">
                                Expired Pending Reservations
                            </a>
                        </div>

                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Customer</th>
                                        <th>Mobile</th>
                                        <th>Total</th>
                                        <th>Payment Status</th>
                                        <th>Order Process</th>
                                        <th>Items</th>
                                        <th>Created Date</th>
                                        <th width="250">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($orders as $order)
                                        @php($orderDate = $order->created_date ?: $order->paid_at)
                                        <tr>
                                            <td>{{ $order->id }}</td>
                                            <td>{{ $order->customer?->name ?? '-' }}</td>
                                            <td>{{ $order->customer?->mobile_number ?? '-' }}</td>
                                            <td>{{ $order->currency }} {{ number_format((float) $order->total_amount, 2) }}</td>
                                            <td>{{ $order->payment_status ?? '-' }}</td>
                                            <td>{{ $order->current_order_status ? ucwords(str_replace('_', ' ', $order->current_order_status)) : 'Not Started' }}</td>
                                            <td>{{ $order->items_count ?? 0 }}</td>
                                            <td>{{ $orderDate ? date('d-m-Y H:i', strtotime($orderDate)) : '-' }}</td>
                                            <td>
                                                <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-info btn-sm">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.orders.process.show', $order->id) }}" class="btn btn-primary btn-sm">
                                                    Proceed Order
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center">No orders found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($orders->hasPages())
                            <div class="mt-3 d-flex justify-content-center">
                                {{ $orders->links('pagination::bootstrap-4') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
