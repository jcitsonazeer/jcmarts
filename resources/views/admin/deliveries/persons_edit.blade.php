@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">Edit Delivery Person</h4>
                            <a href="{{ route('admin.deliveries.persons.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back to Delivery Persons
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

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('admin.deliveries.persons.update', $deliveryPerson->id) }}">
                            @csrf
                            @method('PUT')

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" value="{{ old('name', $deliveryPerson->name) }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label>Mobile Number <span class="text-danger">*</span></label>
                                    <input type="text" name="mobile" class="form-control" value="{{ old('mobile', $deliveryPerson->mobile) }}" required>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control" value="{{ old('email', $deliveryPerson->email) }}">
                                </div>
                                <div class="col-md-6">
                                    <label>Password <small class="text-muted">(leave blank to keep current)</small></label>
                                    <input type="password" name="password" class="form-control">
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Vehicle Type</label>
                                    <input type="text" name="vehicle_type" class="form-control" value="{{ old('vehicle_type', $deliveryPerson->vehicle_type) }}">
                                </div>
                                <div class="col-md-6">
                                    <label>Vehicle Number</label>
                                    <input type="text" name="vehicle_number" class="form-control" value="{{ old('vehicle_number', $deliveryPerson->vehicle_number) }}">
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Status <span class="text-danger">*</span></label>
                                    <select name="status" class="form-control" required>
                                        <option value="active" {{ old('status', $deliveryPerson->status) === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ old('status', $deliveryPerson->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label>Availability Status <span class="text-danger">*</span></label>
                                    <select name="availability_status" class="form-control" required>
                                        <option value="available" {{ old('availability_status', $deliveryPerson->availability_status) === 'available' ? 'selected' : '' }}>Available</option>
                                        <option value="busy" {{ old('availability_status', $deliveryPerson->availability_status) === 'busy' ? 'selected' : '' }}>Busy</option>
                                        <option value="offline" {{ old('availability_status', $deliveryPerson->availability_status) === 'offline' ? 'selected' : '' }}>Offline</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Created By</label>
                                    <input type="text" class="form-control" value="{{ $deliveryPerson->createdBy ? $deliveryPerson->createdBy->admin_username : '-' }}" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label>Created Date</label>
                                    <input type="text" class="form-control" value="{{ $deliveryPerson->created_date ? date('d-m-Y H:i', strtotime($deliveryPerson->created_date)) : '-' }}" disabled>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary mr-2"><i class="fa fa-save"></i> Update</button>
                                    <a href="{{ route('admin.deliveries.persons.index') }}" class="btn btn-secondary"><i class="fa fa-times"></i> Cancel</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
