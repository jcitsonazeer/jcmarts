@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <h4 class="card-title">Payments</h4>

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
                                        <th>Gateway</th>
                                        <th>Gateway Payment ID</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Paid At</th>
                                        <th width="100">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($payments as $payment)
                                        <tr>
                                            <td>{{ $payment->id }}</td>
                                            <td>{{ $payment->order_id }}</td>
                                            <td>{{ $payment->order?->customer?->name ?? '-' }}</td>
                                            <td>{{ $payment->gateway }}</td>
                                            <td>{{ $payment->gateway_payment_id ?? '-' }}</td>
                                            <td>{{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}</td>
                                            <td>{{ ucwords(str_replace('_', ' ', $payment->status)) }}</td>
                                            <td>{{ $payment->paid_at ? $payment->paid_at->format('d-m-Y H:i') : '-' }}</td>
                                            <td>
                                                <a href="{{ route('admin.payments.show', $payment->id) }}" class="btn btn-info btn-sm">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center">No payments found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($payments->hasPages())
                            <div class="mt-3 d-flex justify-content-center">
                                {{ $payments->links('pagination::bootstrap-4') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
