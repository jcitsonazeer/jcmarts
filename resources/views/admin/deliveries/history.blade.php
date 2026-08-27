@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <h4 class="card-title">Delivery History</h4>

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

                        <form method="GET" action="{{ route('admin.deliveries.history') }}" class="mb-3">
                            <div class="form-group row align-items-end mb-0">
                                <div class="col-md-2">
                                    <label>Search</label>
                                    <input type="text" name="search" class="form-control" value="{{ $search ?? '' }}" placeholder="Order ID or Name">
                                </div>
                                <div class="col-md-2">
                                    <label>Delivery Person</label>
                                    <select name="delivery_person_id" class="form-control">
                                        <option value="">All</option>
                                        @foreach($deliveryPersons as $person)
                                            <option value="{{ $person->id }}" {{ ($deliveryPersonId ?? '') == $person->id ? 'selected' : '' }}>
                                                {{ $person->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>From Date</label>
                                    <input type="date" name="date_from" class="form-control" value="{{ $dateFrom ?? '' }}">
                                </div>
                                <div class="col-md-2">
                                    <label>To Date</label>
                                    <input type="date" name="date_to" class="form-control" value="{{ $dateTo ?? '' }}">
                                </div>
                                <div class="col-md-2">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fa fa-filter"></i> Filter
                                    </button>
                                </div>
                                <div class="col-md-2">
                                    <label>&nbsp;</label>
                                    <a href="{{ route('admin.deliveries.history') }}" class="btn btn-secondary btn-block">
                                        Clear
                                    </a>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Delivery ID</th>
                                        <th>Order</th>
                                        <th>Customer</th>
                                        <th>Delivery Person</th>
                                        <th>Status</th>
                                        <th>Delivered At</th>
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
                                                @if($delivery->status === 'delivered')
                                                    <span class="badge badge-success">Delivered</span>
                                                @else
                                                    <span class="badge badge-secondary">{{ ucwords(str_replace('_', ' ', $delivery->status)) }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $delivery->delivered_at ? date('d-m-Y H:i', strtotime($delivery->delivered_at)) : '-' }}</td>
                                            <td>
                                                <a href="{{ route('admin.deliveries.show', $delivery->id) }}" class="btn btn-info btn-sm" title="View">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">No delivery history found</td>
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
