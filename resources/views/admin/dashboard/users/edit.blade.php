@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">Edit User</h4>
                            <div>
                                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                                    <i class="fa fa-arrow-left"></i> Back to Users
                                </a>
                                <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-info">
                                    <i class="fa fa-eye"></i> View
                                </a>
                                <form action="{{ route('admin.users.status', $user->id) }}"
                                      method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="btn {{ $user->cur_status === 'ACTIVE' ? 'btn-secondary' : 'btn-success' }}"
                                            onclick="return confirm('Change status for this user?')">
                                        @if($user->cur_status === 'ACTIVE')
                                            <i class="fa fa-ban"></i> Set Inactive
                                        @else
                                            <i class="fa fa-check"></i> Set Active
                                        @endif
                                    </button>
                                </form>
                            </div>
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

                        <div class="row">
                            <div class="col-sm-12 col-xs-12">
                                <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
                                    @csrf
                                    @method('PUT')

                                    <div class="form-group row">
                                        <div class="col-md-6">
                                            <label>Username <span class="text-danger">*</span></label>
                                            <input type="text"
                                                   name="admin_username"
                                                   class="form-control {{ $errors->has('admin_username') ? 'is-invalid' : '' }}"
                                                   value="{{ old('admin_username', $user->admin_username) }}"
                                                   required>
                                        </div>
                                        <div class="col-md-6">
                                            <label>New Password</label>
                                            <input type="password"
                                                   name="admin_password"
                                                   class="form-control"
                                                   placeholder="Leave blank to keep current password">
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-md-6">
                                            <label>User Type <span class="text-danger">*</span></label>
                                            <select name="user_type" class="form-control" required>
                                                <option value="MASTER" {{ old('user_type', $user->user_type) === 'MASTER' ? 'selected' : '' }}>MASTER</option>
                                                <option value="ENTRYSTAFF" {{ old('user_type', $user->user_type) === 'ENTRYSTAFF' ? 'selected' : '' }}>ENTRYSTAFF</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Status</label>
                                            <input type="text" class="form-control" value="{{ $user->cur_status }}" disabled>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary mr-2">
                                                <i class="fa fa-save"></i> Update
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
