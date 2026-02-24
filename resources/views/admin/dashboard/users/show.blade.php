@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">View User</h4>
                            <div>
                                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                                    <i class="fa fa-arrow-left"></i> Back to Users
                                </a>
                                @if (session('admin_user_type') === 'MASTER')
                                    <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-warning">
                                        <i class="fa fa-edit"></i> Edit
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
                                @endif
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

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th width="200">ID</th>
                                        <td>{{ $user->id }}</td>
                                    </tr>
                                    <tr>
                                        <th>Username</th>
                                        <td>{{ $user->admin_username }}</td>
                                    </tr>
                                    <tr>
                                        <th>User Type</th>
                                        <td>{{ $user->user_type }}</td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            @if($user->cur_status === 'ACTIVE')
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <span class="badge badge-secondary">Inactive</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Created Date</th>
                                        <td>{{ $user->created_date ? date('d-m-Y H:i', strtotime($user->created_date)) : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Last Login</th>
                                        <td>{{ $user->last_login_at ? date('d-m-Y H:i', strtotime($user->last_login_at)) : '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
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
