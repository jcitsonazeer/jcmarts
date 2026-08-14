@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">Add Customer</h4>
                            <a href="{{ route('admin.customers.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back to Customers
                            </a>
                        </div>

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('admin.customers.store') }}">
                            @csrf

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label>Mobile Number <span class="text-danger">*</span></label>
                                    <input type="text" name="mobile_number" class="form-control" value="{{ old('mobile_number') }}" required>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Verified Status <span class="text-danger">*</span></label>
                                    <select name="verified_status" class="form-control" required>
                                        <option value="pending" {{ old('verified_status', 'pending') === 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="verified" {{ old('verified_status') === 'verified' ? 'selected' : '' }}>Verified</option>
                                        <option value="rejected" {{ old('verified_status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label>Status <span class="text-danger">*</span></label>
                                    <select name="is_active" class="form-control" required>
                                        <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary mr-2"><i class="fa fa-save"></i> Submit</button>
                                    <button type="reset" class="btn btn-warning mr-2"><i class="fa fa-refresh"></i> Reset</button>
                                    <a href="{{ route('admin.customers.index') }}" class="btn btn-secondary"><i class="fa fa-times"></i> Cancel</a>
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
