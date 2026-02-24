@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <!-- Basic Table area Start -->
        <div class="container-fluid">
            <!-- Table row -->
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title">Category Management</h4>
                            <a href="{{  route('admin.categories.create') }}" class="btn btn-primary">
                                <i class="fa fa-plus"></i> Add Category
                            </a>
                        </div>

                        <!-- Success/Error Messages -->
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
                                                <th>Category Name</th>
                                                <th>Created By</th>
                                                <th>Created Date</th>
                                                <th>Updated By</th>
                                                <th>Updated Date</th>
                                                <th width="150">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($categories as $cat)
                                                <tr>
                                                    <td>{{ $cat->id }}</td>
                                                    <td>{{ $cat->category_name }}</td>
                                                    <td>{{ $cat->createdBy ? $cat->createdBy->admin_username : '-' }}</td>
                                                    <td>{{ date('d-m-Y', strtotime($cat->created_date)) }}</td>
                                                    <td>{{ $cat->updatedBy ? $cat->updatedBy->admin_username : '-' }}</td>
                                                    <td>{{ $cat->updated_date ? date('d-m-Y H:i', strtotime($cat->updated_date)) : '-' }}</td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <!-- Edit -->
                                                            <a href="{{ route('admin.categories.edit', $cat->id) }}"
                                                               class="btn btn-warning btn-sm"
                                                               title="Edit">
                                                                <i class="fa fa-edit"></i>
                                                            </a>

                                                            <!-- Delete -->
                                                            <form action="{{ route('admin.categories.destroy', $cat->id) }}"
                                                                  method="POST"
                                                                  class="d-inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit"
                                                                        class="btn btn-danger btn-sm"
                                                                        title="Delete"
                                                                        onclick="return confirm('Are you sure you want to delete this category?')">
                                                                    <i class="fa fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-center">
                                                        No categories found
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
    // Auto-hide alerts after 5 seconds
    setTimeout(function () {
        $('.alert').fadeOut('slow');
    }, 5000);
</script>
@endpush
@endsection
