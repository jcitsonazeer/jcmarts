@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <h4 class="card-title">Refunds</h4>

                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Order</th>
                                        <th>Return</th>
                                        <th>Payment</th>
                                        <th>Customer</th>
                                        <th>Refund ID</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Requested</th>
                                        <th width="100">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($refunds as $refund)
                                        <tr>
                                            <td>{{ $refund->id }}</td>
                                            <td>{{ $refund->order_id }}</td>
                                            <td>{{ $refund->return_id ?? '-' }}</td>
                                            <td>{{ $refund->payment_id }}</td>
                                            <td>{{ $refund->order?->customer?->name ?? '-' }}</td>
                                            <td>{{ $refund->gateway_refund_id ?? '-' }}</td>
                                            <td>{{ $refund->currency }} {{ number_format((float) $refund->amount, 2) }}</td>
                                            <td>{{ ucwords(str_replace('_', ' ', $refund->status)) }}</td>
                                            <td>{{ $refund->requested_at ? $refund->requested_at->format('d-m-Y H:i') : '-' }}</td>
                                            <td>
                                                <a href="{{ route('admin.refunds.show', $refund->id) }}" class="btn btn-info btn-sm">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center">No refunds found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($refunds->hasPages())
                            <div class="mt-3 d-flex justify-content-center">
                                {{ $refunds->links('pagination::bootstrap-4') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
