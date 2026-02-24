@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title">User Management</h4>
                            @if (session('admin_user_type') === 'MASTER')
                                <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                                    <i class="fa fa-plus"></i> Add User
                                </a>
                            @endif
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

                        <div class="row">
                            <div class="col-sm-12 col-xs-12">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>ID</th>
                                                <th>Username</th>
                                                <th>User Type</th>
                                                <th>Status</th>
                                                <th>Created</th>
                                                <th width="180">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($users as $user)
                                                <tr>
                                                    <td>{{ $user->id }}</td>
                                                    <td>{{ $user->admin_username }}</td>
                                                    <td>{{ $user->user_type }}</td>
                                                    <td>
                                                        @if($user->cur_status === 'ACTIVE')
                                                            <span class="badge badge-success">Active</span>
                                                        @else
                                                            <span class="badge badge-secondary">Inactive</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $user->created_date ? date('d-m-Y', strtotime($user->created_date)) : '-' }}</td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="{{ route('admin.users.show', $user->id) }}"
                                                               class="btn btn-info btn-sm" title="View">
                                                                <i class="fa fa-eye"></i>
                                                            </a>

                                                            @if (session('admin_user_type') === 'MASTER')
                                                                <a href="{{ route('admin.users.edit', $user->id) }}"
                                                                   class="btn btn-warning btn-sm" title="Edit">
                                                                    <i class="fa fa-edit"></i>
                                                                </a>

                                                                <form action="{{ route('admin.users.status', $user->id) }}"
                                                                      method="POST" class="d-inline">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <button type="submit"
                                                                            class="btn btn-sm {{ $user->cur_status === 'ACTIVE' ? 'btn-secondary' : 'btn-success' }}"
                                                                            title="Toggle Status"
                                                                            onclick="return confirm('Change status for this user?')">
                                                                        @if($user->cur_status === 'ACTIVE')
                                                                            <i class="fa fa-ban"></i>
                                                                        @else
                                                                            <i class="fa fa-check"></i>
                                                                        @endif
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center">
                                                        No users found
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.btn-group .btn {
    margin-right: 2px;
}
.table td {
    vertical-align: middle;
}
</style>

@push('scripts')
<script>
    setTimeout(function () {
        $('.alert').fadeOut('slow');
    }, 5000);
</script>
@endpush
@endsection
