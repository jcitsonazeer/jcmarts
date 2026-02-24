@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title">Sub Category Management</h4>
                            <a href="{{ route('admin.sub-categories.create') }}" class="btn btn-primary">
                                <i class="fa fa-plus"></i> Add Sub Category
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

                        <div class="row">
                            <div class="col-sm-12 col-xs-12">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>ID</th>
                                                <th>Category</th>
                                                <th>Sub Category</th>
                                                <th>Image</th>
                                                <th>Created By</th>
                                                <th>Created Date</th>
                                                <th>Updated By</th>
                                                <th>Updated Date</th>
                                                <th width="150">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($subCategories as $sub)
                                                <tr>
                                                    <td>{{ $sub->id }}</td>
                                                    <td>{{ $sub->category ? $sub->category->category_name : '-' }}</td>
                                                    <td>{{ $sub->sub_category_name }}</td>
                                                    @php
                                                        $defaultImage = asset('assets/admin/images/no_image.png');
                                                        $subCategoryImage = $sub->sub_category_image
                                                            ? asset('storage/sub_category/' . $sub->sub_category_image)
                                                            : $defaultImage;
                                                    @endphp
                                                    <td>
                                                        <img src="{{ $subCategoryImage }}"
                                                             alt="Sub Category Image"
                                                             style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;"
                                                             onerror="this.onerror=null;this.src='{{ $defaultImage }}';">
                                                    </td>
                                                    <td>{{ $sub->createdBy ? $sub->createdBy->admin_username : '-' }}</td>
                                                    <td>{{ $sub->created_date ? date('d-m-Y', strtotime($sub->created_date)) : '-' }}</td>
                                                    <td>{{ $sub->updatedBy ? $sub->updatedBy->admin_username : '-' }}</td>
                                                    <td>{{ $sub->updated_date ? date('d-m-Y H:i', strtotime($sub->updated_date)) : '-' }}</td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="{{ route('admin.sub-categories.show', $sub->id) }}"
                                                               class="btn btn-info btn-sm" title="View">
                                                                <i class="fa fa-eye"></i>
                                                            </a>

                                                            <a href="{{ route('admin.sub-categories.edit', $sub->id) }}"
                                                               class="btn btn-warning btn-sm" title="Edit">
                                                                <i class="fa fa-edit"></i>
                                                            </a>

                                                            <form action="{{ route('admin.sub-categories.destroy', $sub->id) }}"
                                                                  method="POST" class="d-inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit"
                                                                        class="btn btn-danger btn-sm"
                                                                        title="Delete"
                                                                        onclick="return confirm('Are you sure you want to delete this sub category?')">
                                                                    <i class="fa fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="9" class="text-center">
                                                        No sub categories found
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
