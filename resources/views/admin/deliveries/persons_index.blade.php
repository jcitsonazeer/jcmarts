@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title">Delivery Persons</h4>
                            <a href="{{ route('admin.deliveries.persons.create') }}" class="btn btn-primary">
                                <i class="fa fa-plus"></i> Add Delivery Person
                            </a>
                        </div>

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

                        <form method="GET" action="{{ route('admin.deliveries.persons.index') }}" class="mb-3 js-auto-search-form">
                            <div class="form-group row align-items-end mb-0">
                                <div class="col-md-6">
                                    <label>Search</label>
                                    <input type="text" name="search" class="form-control js-auto-search-input" value="{{ $search ?? '' }}" placeholder="Search name, mobile, email, or vehicle number" autocomplete="off">
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Mobile</th>
                                        <th>Vehicle</th>
                                        <th>Status</th>
                                        <th>Availability</th>
                                        <th>Active Deliveries</th>
                                        <th width="160">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($deliveryPersons as $person)
                                        <tr>
                                            <td>{{ $deliveryPersons->firstItem() + $loop->index }}</td>
                                            <td>{{ $person->name }}</td>
                                            <td>{{ $person->mobile }}</td>
                                            <td>{{ $person->vehicle_type ? $person->vehicle_type . ' - ' . $person->vehicle_number : '-' }}</td>
                                            <td>
                                                @if($person->status === 'active')
                                                    <span class="badge badge-success">Active</span>
                                                @else
                                                    <span class="badge badge-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($person->availability_status === 'available')
                                                    <span class="badge badge-success">Available</span>
                                                @elseif($person->availability_status === 'busy')
                                                    <span class="badge badge-warning">Busy</span>
                                                @else
                                                    <span class="badge badge-secondary">Offline</span>
                                                @endif
                                            </td>
                                            <td>{{ $person->deliveries_count ?? 0 }}</td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.deliveries.persons.show', $person->id) }}" class="btn btn-info btn-sm" title="View">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('admin.deliveries.persons.edit', $person->id) }}" class="btn btn-warning btn-sm" title="Edit">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center">No delivery persons found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($deliveryPersons->hasPages())
                            <div class="mt-3 d-flex justify-content-center">
                                {{ $deliveryPersons->links('pagination::bootstrap-4') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
