@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">Add User</h4>
                            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back to Users
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

                        <div class="row">
                            <div class="col-sm-12 col-xs-12">
                                <form method="POST" action="{{ route('admin.users.store') }}">
                                    @csrf

                                    <div class="form-group row">
                                        <div class="col-md-6">
                                            <label>Username <span class="text-danger">*</span></label>
                                            <input type="text"
                                                   name="admin_username"
                                                   class="form-control {{ $errors->has('admin_username') ? 'is-invalid' : '' }}"
                                                   placeholder="Enter Username"
                                                   value="{{ old('admin_username') }}"
                                                   required>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Password <span class="text-danger">*</span></label>
                                            <input type="password"
                                                   name="admin_password"
                                                   class="form-control {{ $errors->has('admin_password') ? 'is-invalid' : '' }}"
                                                   placeholder="Enter Password"
                                                   required>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-md-6">
                                            <label>User Type <span class="text-danger">*</span></label>
                                            <select name="user_type" class="form-control" required>
                                                <option value="">Select</option>
                                                <option value="MASTER" {{ old('user_type') === 'MASTER' ? 'selected' : '' }}>MASTER</option>
                                                <option value="ENTRYSTAFF" {{ old('user_type') === 'ENTRYSTAFF' ? 'selected' : '' }}>ENTRYSTAFF</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Status <span class="text-danger">*</span></label>
                                            <select name="cur_status" class="form-control" required>
                                                <option value="ACTIVE" {{ old('cur_status', 'ACTIVE') === 'ACTIVE' ? 'selected' : '' }}>ACTIVE</option>
                                                <option value="INACTIVE" {{ old('cur_status') === 'INACTIVE' ? 'selected' : '' }}>INACTIVE</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary mr-2">
                                                <i class="fa fa-save"></i> Submit
                                            </button>

                                            <button type="reset" class="btn btn-warning mr-2">
                                                <i class="fa fa-refresh"></i> Reset
                                            </button>

                                            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                                                <i class="fa fa-times"></i> Cancel
                                            </a>
                                        </div>
                                    </div>

                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    setTimeout(function () {
        $('.alert').fadeOut('slow');
    }, 5000);
</script>
@endpush
@endsection
