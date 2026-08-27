@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <h4 class="card-title">Delivery Dashboard</h4>

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

                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card border-left-primary">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-1">Unassigned</h6>
                                        <h3 class="mb-0">{{ $stats['deliveries']['not_assigned'] ?? 0 }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-left-warning">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-1">Assigned</h6>
                                        <h3 class="mb-0">{{ $stats['deliveries']['assigned'] ?? 0 }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-left-info">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-1">Out for Delivery</h6>
                                        <h3 class="mb-0">{{ ($stats['deliveries']['out_for_delivery'] ?? 0) + ($stats['deliveries']['picked_up'] ?? 0) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-left-success">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-1">Delivered</h6>
                                        <h3 class="mb-0">{{ $stats['deliveries']['delivered'] ?? 0 }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card border-left-danger">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-1">Failed / Cancelled / Rejected</h6>
                                        <h3 class="mb-0">{{ ($stats['deliveries']['failed'] ?? 0) + ($stats['deliveries']['cancelled'] ?? 0) + ($stats['deliveries']['rejected'] ?? 0) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-left-success">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-1">DP Available</h6>
                                        <h3 class="mb-0">{{ $stats['delivery_persons']['available'] ?? 0 }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-left-warning">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-1">DP Busy</h6>
                                        <h3 class="mb-0">{{ $stats['delivery_persons']['busy'] ?? 0 }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-left-secondary">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-1">DP Offline / Inactive</h6>
                                        <h3 class="mb-0">{{ ($stats['delivery_persons']['offline'] ?? 0) + ($stats['delivery_persons']['inactive'] ?? 0) }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <a href="{{ route('admin.deliveries.persons.index') }}" class="btn btn-primary btn-block mb-2">
                                    <i class="fa fa-users"></i> Delivery Persons
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="{{ route('admin.deliveries.unassigned') }}" class="btn btn-warning btn-block mb-2">
                                    <i class="fa fa-exclamation-triangle"></i> Unassigned Orders
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="{{ route('admin.deliveries.active') }}" class="btn btn-info btn-block mb-2">
                                    <i class="fa fa-truck"></i> Active Deliveries
                                </a>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
