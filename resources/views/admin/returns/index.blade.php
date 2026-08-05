@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <h4 class="card-title">Return Requests</h4>

                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Order</th>
                                        <th>Customer</th>
                                        <th>Items</th>
                                        <th>Refund Amount</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th>Requested At</th>
                                        <th width="180">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($returns as $return)
                                        <tr>
                                            <td>{{ $return->id }}</td>
                                            <td>{{ $return->order_id }}</td>
                                            <td>{{ $return->customer?->name ?? '-' }}</td>
                                            <td>{{ $return->items->sum('quantity') }}</td>
                                            <td>{{ $return->order?->currency ?? 'INR' }} {{ number_format((float) $return->refund_amount, 2) }}</td>
                                            <td>{{ $return->reason }}</td>
                                            <td>{{ ucwords(str_replace('_', ' ', $return->status)) }}</td>
                                            <td>{{ $return->requested_at ? $return->requested_at->format('d-m-Y H:i') : '-' }}</td>
                                            <td>
                                                <a href="{{ route('admin.returns.show', $return->id) }}" class="btn btn-info btn-sm">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.returns.process', $return->id) }}" class="btn btn-primary btn-sm">
                                                    Process
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center">No return requests found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($returns->hasPages())
                            <div class="mt-3 d-flex justify-content-center">
                                {{ $returns->links('pagination::bootstrap-4') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
