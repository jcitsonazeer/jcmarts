@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title">Assign Delivery Person - Order #{{ $order->id }}</h4>
                            <a href="{{ route('admin.deliveries.unassigned') }}" class="btn btn-secondary">Back</a>
                        </div>

                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <h5>Order Information</h5>
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
                                        <th>Order Amount</th>
                                        <td>{{ $order->currency ?? 'INR' }} {{ number_format((float) $order->total_amount, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Order Status</th>
                                        <td>
                                            @php($latestStatus = $order->statuses->sortByDesc('action_time')->first())
                                            @if($latestStatus)
                                                <span class="badge badge-info">{{ ucwords(str_replace('_', ' ', $latestStatus->order_status)) }}</span>
                                            @else
                                                <span class="badge badge-secondary">Not Started</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Delivery Address</th>
                                        <td>{{ $existingDelivery ? $existingDelivery->delivery_address : '-' }}</td>
                                    </tr>
                                </table>
                            </div>

                            <div class="col-md-6">
                                <h5>Assign Delivery Person</h5>

                                @if($existingDelivery && $existingDelivery->status !== 'not_assigned')
                                    <div class="alert alert-warning">
                                        This order already has an assigned delivery person.
                                    </div>
                                @else
                                    <form method="POST" action="{{ route('admin.deliveries.assign.store', $order->id) }}" onsubmit="return confirm('Are you sure you want to assign this delivery person?');">
                                        @csrf

                                        <div class="form-group">
                                            <label>Select Delivery Person <span class="text-danger">*</span></label>
                                            <select name="delivery_person_id" class="form-control" required>
                                                <option value="">-- Select Delivery Person --</option>
                                                @forelse($availablePersons as $person)
                                                    <option value="{{ $person->id }}" {{ old('delivery_person_id') == $person->id ? 'selected' : '' }}>
                                                        {{ $person->name }} ({{ $person->mobile }}) - {{ $person->vehicle_type ?? 'N/A' }} {{ $person->vehicle_number ?? '' }}
                                                    </option>
                                                @empty
                                                    <option value="" disabled>No available delivery persons</option>
                                                @endforelse
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <button type="submit" class="btn btn-primary" {{ $availablePersons->isEmpty() ? 'disabled' : '' }}>
                                                <i class="fa fa-truck"></i> Assign Delivery
                                            </button>
                                        </div>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
