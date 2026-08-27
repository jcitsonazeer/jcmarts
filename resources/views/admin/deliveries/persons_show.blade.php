@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title">Delivery Person Details</h4>
                            <div>
                                <a href="{{ route('admin.deliveries.persons.edit', $deliveryPerson->id) }}" class="btn btn-warning">
                                    <i class="fa fa-edit"></i> Edit
                                </a>
                                <a href="{{ route('admin.deliveries.persons.index') }}" class="btn btn-secondary">Back</a>
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <h5>Basic Information</h5>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>ID</th>
                                        <td>{{ $deliveryPerson->id }}</td>
                                    </tr>
                                    <tr>
                                        <th>Name</th>
                                        <td>{{ $deliveryPerson->name }}</td>
                                    </tr>
                                    <tr>
                                        <th>Mobile</th>
                                        <td>{{ $deliveryPerson->mobile }}</td>
                                    </tr>
                                    <tr>
                                        <th>Email</th>
                                        <td>{{ $deliveryPerson->email ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Vehicle Type</th>
                                        <td>{{ $deliveryPerson->vehicle_type ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Vehicle Number</th>
                                        <td>{{ $deliveryPerson->vehicle_number ?? '-' }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5>Status Information</h5>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            @if($deliveryPerson->status === 'active')
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <span class="badge badge-secondary">Inactive</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Availability</th>
                                        <td>
                                            @if($deliveryPerson->availability_status === 'available')
                                                <span class="badge badge-success">Available</span>
                                            @elseif($deliveryPerson->availability_status === 'busy')
                                                <span class="badge badge-warning">Busy</span>
                                            @else
                                                <span class="badge badge-secondary">Offline</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Active Deliveries</th>
                                        <td>{{ $deliveryPerson->deliveries_count ?? 0 }}</td>
                                    </tr>
                                    <tr>
                                        <th>Completed Deliveries</th>
                                        <td>{{ $deliveryPerson->completed_deliveries_count ?? 0 }}</td>
                                    </tr>
                                    <tr>
                                        <th>Created By</th>
                                        <td>{{ $deliveryPerson->createdBy ? $deliveryPerson->createdBy->admin_username : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Created Date</th>
                                        <td>{{ $deliveryPerson->created_date ? date('d-m-Y H:i', strtotime($deliveryPerson->created_date)) : '-' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
