@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <h4 class="card-title">Active Deliveries</h4>

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
                                        <th>Delivery ID</th>
                                        <th>Order</th>
                                        <th>Customer</th>
                                        <th>Delivery Person</th>
                                        <th>Status</th>
                                        <th>Assigned At</th>
                                        <th width="100">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($deliveries as $delivery)
                                        <tr>
                                            <td>{{ $delivery->id }}</td>
                                            <td>
                                                <a href="{{ route('admin.orders.show', $delivery->order_id) }}">
                                                    #{{ $delivery->order_id }}
                                                </a>
                                            </td>
                                            <td>{{ $delivery->order?->customer?->name ?? '-' }}</td>
                                            <td>{{ $delivery->deliveryPerson?->name ?? '-' }}</td>
                                            <td>
                                                @if($delivery->status === 'assigned')
                                                    <span class="badge badge-primary">Assigned</span>
                                                @elseif($delivery->status === 'accepted')
                                                    <span class="badge badge-info">Accepted</span>
                                                @elseif($delivery->status === 'picked_up')
                                                    <span class="badge badge-warning">Picked Up</span>
                                                @elseif($delivery->status === 'out_for_delivery')
                                                    <span class="badge badge-dark">Out for Delivery</span>
                                                @else
                                                    <span class="badge badge-secondary">{{ ucwords(str_replace('_', ' ', $delivery->status)) }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $delivery->assigned_at ? date('d-m-Y H:i', strtotime($delivery->assigned_at)) : '-' }}</td>
                                            <td>
                                                <a href="{{ route('admin.deliveries.show', $delivery->id) }}" class="btn btn-info btn-sm" title="View">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">No active deliveries found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($deliveries->hasPages())
                            <div class="mt-3 d-flex justify-content-center">
                                {{ $deliveries->links('pagination::bootstrap-4') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
