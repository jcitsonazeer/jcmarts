@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <h4 class="card-title">Unassigned Orders</h4>

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
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Order Date</th>
                                        <th>Amount</th>
                                        <th>Order Status</th>
                                        <th>Delivery Address</th>
                                        <th width="200">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($orders as $order)
                                        @php($orderStatus = $order->statuses->sortByDesc('action_time')->first())
                                        @php($address = $order->address)
                                        @php($addressText = $address ? implode(', ', array_filter([$address->address_line_1 ?? null, $address->address_line_2 ?? null, $address->city ?? null, $address->state ?? null, $address->pincode ?? null])) : '-')
                                        <tr>
                                            <td>{{ $order->id }}</td>
                                            <td>{{ $order->customer?->name ?? '-' }}</td>
                                            <td>{{ $order->created_date ? date('d-m-Y H:i', strtotime($order->created_date)) : '-' }}</td>
                                            <td>{{ $order->currency ?? 'INR' }} {{ number_format((float) ($order->total_amount ?? 0), 2) }}</td>
                                            <td>
                                                @if($orderStatus)
                                                    <span class="badge badge-info">{{ ucwords(str_replace('_', ' ', $orderStatus->order_status)) }}</span>
                                                @else
                                                    <span class="badge badge-secondary">Not Started</span>
                                                @endif
                                            </td>
                                            <td>{{ Str::limit($addressText, 50) }}</td>
                                            <td>
                                                <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-info btn-sm" title="View Order">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.deliveries.assign', $order->id) }}" class="btn btn-primary btn-sm" title="Assign Delivery">
                                                    <i class="fa fa-truck"></i> Assign
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">No unassigned orders found</td>
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
