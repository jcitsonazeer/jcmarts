@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title">Expired Pending Reservations</h4>
                            <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">Back to Orders</a>
                        </div>

                        <p class="text-muted">
                            These orders reserved stock for payment but crossed the expiry time without being paid.
                            Releasing them will add the reserved stock back so customers can purchase again.
                        </p>

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
                                        <th>Mobile</th>
                                        <th>Reserved At</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th width="180">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($orders as $order)
                                        <tr>
                                            <td>{{ $order->id }}</td>
                                            <td>{{ $order->customer?->name ?? '-' }}</td>
                                            <td>{{ $order->customer?->mobile_number ?? '-' }}</td>
                                            <td>{{ $order->created_date ? date('d-m-Y H:i', strtotime($order->created_date)) : '-' }}</td>
                                            <td>
                                                @foreach($order->items as $item)
                                                    <div>
                                                        {{ $item->product?->product_name ?? 'Product' }}
                                                        (Qty: {{ $item->quantity }})
                                                    </div>
                                                @endforeach
                                            </td>
                                            <td>{{ $order->currency }} {{ number_format((float) $order->total_amount, 2) }}</td>
                                            <td>
                                                <form method="POST" action="{{ route('admin.orders.release-reservation', $order->id) }}" onsubmit="return confirm('Release reserved stock for this expired pending order?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-warning btn-sm">
                                                        Release Stock
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">No expired pending reservations found</td>
                                        </tr>
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
