@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title">Process Return - {{ $return->id }}</h4>
                            <a href="{{ route('admin.returns.index') }}" class="btn btn-secondary">Back</a>
                        </div>

                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        @include('admin.returns.partials.details', ['return' => $return])

                        <h5>Return Processing</h5>
                        @if($return->status === 'return_requested')
                            <form method="POST" action="{{ route('admin.returns.update', $return->id) }}">
                                @csrf
                                <textarea name="admin_note" class="form-control mb-2" rows="2" placeholder="Admin note"></textarea>
                                <button type="submit" name="action" value="approve" class="btn btn-success">Approve Return</button>
                                <button type="submit" name="action" value="reject" class="btn btn-danger" onclick="return confirm('Reject this return request?');">Reject Return</button>
                            </form>
                        @elseif($return->status === 'return_approved')
                            <form method="POST" action="{{ route('admin.returns.update', $return->id) }}">
                                @csrf
                                <button type="submit" name="action" value="schedule_pickup" class="btn btn-primary">Schedule Pickup</button>
                            </form>
                        @elseif($return->status === 'pickup_scheduled')
                            <form method="POST" action="{{ route('admin.returns.update', $return->id) }}">
                                @csrf
                                <button type="submit" name="action" value="mark_received" class="btn btn-primary">Mark Product Received</button>
                            </form>
                        @elseif($return->status === 'product_received')
                            <form method="POST" action="{{ route('admin.returns.update', $return->id) }}">
                                @csrf
                                <textarea name="admin_note" class="form-control mb-2" rows="2" placeholder="Inspection note"></textarea>
                                <label class="d-block mb-2">
                                    <input type="checkbox" name="sellable_stock" value="1">
                                    Returned items are sellable and can be added back to stock
                                </label>
                                <button type="submit" name="action" value="inspection_passed" class="btn btn-success" onclick="return confirm('Pass inspection and start Razorpay refund?');">
                                    Inspection Passed & Refund
                                </button>
                                <button type="submit" name="action" value="inspection_failed" class="btn btn-danger" onclick="return confirm('Fail inspection and close return?');">
                                    Inspection Failed
                                </button>
                            </form>
                        @else
                            <div class="alert alert-info">
                                No processing action is available for this return status.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
