@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">View Brand</h4>
                            <div>
                                <a href="{{ route('admin.brands.index') }}" class="btn btn-secondary">
                                    <i class="fa fa-arrow-left"></i> Back
                                </a>
                                <a href="{{ route('admin.brands.edit', $brand->id) }}" class="btn btn-warning">
                                    <i class="fa fa-edit"></i> Edit
                                </a>
                            </div>
                        </div>

                        <table class="table table-bordered">
                            <tbody>
                                <tr><th width="220">ID</th><td>{{ $brand->id }}</td></tr>
                                <tr><th>Brand Name</th><td>{{ $brand->brand_name }}</td></tr>
                                <tr><th>Status</th><td>{{ $brand->is_active ? 'Active' : 'Inactive' }}</td></tr>
                                <tr><th>Created By</th><td>{{ $brand->createdBy ? $brand->createdBy->admin_username : '-' }}</td></tr>
                                <tr><th>Created Date</th><td>{{ $brand->created_date ? date('d-m-Y H:i', strtotime($brand->created_date)) : '-' }}</td></tr>
                                <tr><th>Updated By</th><td>{{ $brand->updatedBy ? $brand->updatedBy->admin_username : '-' }}</td></tr>
                                <tr><th>Updated Date</th><td>{{ $brand->updated_date ? date('d-m-Y H:i', strtotime($brand->updated_date)) : '-' }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
