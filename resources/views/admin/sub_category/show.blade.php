@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">

        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">View Sub Category</h4>
                            <div>
                                <a href="{{ route('admin.sub-categories.index') }}" class="btn btn-secondary">
                                    <i class="fa fa-arrow-left"></i> Back to Sub Categories
                                </a>
                                <a href="{{ route('admin.sub-categories.edit', $subCategory->id) }}" class="btn btn-warning">
                                    <i class="fa fa-edit"></i> Edit
                                </a>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th width="200">ID</th>
                                        <td>{{ $subCategory->id }}</td>
                                    </tr>
                                    <tr>
                                        <th>Category</th>
                                        <td>{{ $subCategory->category ? $subCategory->category->category_name : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Sub Category</th>
                                        <td>{{ $subCategory->sub_category_name }}</td>
                                    </tr>
                                    <tr>
                                        <th>Image</th>
                                        @php
                                            $defaultImage = asset('assets/frontend/images/p1.jpg');
                                            $subCategoryImage = $subCategory->sub_category_image
                                                ? asset('storage/sub_category/' . $subCategory->sub_category_image)
                                                : $defaultImage;
                                        @endphp
                                        <td>
                                            <img src="{{ $subCategoryImage }}"
                                                 alt="Sub Category Image"
                                                 style="width: 90px; height: 90px; object-fit: cover; border-radius: 6px;"
                                                 onerror="this.onerror=null;this.src='{{ $defaultImage }}';">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Created By</th>
                                        <td>{{ $subCategory->createdBy ? $subCategory->createdBy->admin_username : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Created Date</th>
                                        <td>{{ $subCategory->created_date ? date('d-m-Y H:i', strtotime($subCategory->created_date)) : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Updated By</th>
                                        <td>{{ $subCategory->updatedBy ? $subCategory->updatedBy->admin_username : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Updated Date</th>
                                        <td>{{ $subCategory->updated_date ? date('d-m-Y H:i', strtotime($subCategory->updated_date)) : '-' }}</td>
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
