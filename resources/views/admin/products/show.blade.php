@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">View Product</h4>
                            <div>
                                <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">
                                    <i class="fa fa-arrow-left"></i> Back
                                </a>
                                <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-warning">
                                    <i class="fa fa-edit"></i> Edit
                                </a>
                            </div>
                        </div>

                        @php
                            $defaultImage = asset('assets/admin/images/no_image.png');
                            $productImage = $product->product_image
                                ? asset('storage/product/' . $product->product_image)
                                : $defaultImage;
                        @endphp

                        <table class="table table-bordered">
                            <tbody>
                                <tr><th width="220">ID</th><td>{{ $product->id }}</td></tr>
                                <tr><th>Sub Category</th><td>{{ $product->subCategory ? $product->subCategory->sub_category_name : '-' }}</td></tr>
                                <tr><th>Product Name</th><td>{{ $product->product_name }}</td></tr>
                                <tr>
                                    <th>Image</th>
                                    <td>
                                        <img src="{{ $productImage }}"
                                             alt="Product Image"
                                             style="width: 90px; height: 90px; object-fit: cover; border-radius: 6px;"
                                             onerror="this.onerror=null;this.src='{{ $defaultImage }}';">
                                    </td>
                                </tr>
                                <tr><th>Description</th><td>{{ $product->description ?: '-' }}</td></tr>
                                <tr><th>Warranty Info</th><td>{{ $product->warranty_info ?: '-' }}</td></tr>
                                <tr><th>Status</th><td>{{ $product->is_active ? 'Active' : 'Inactive' }}</td></tr>
                                <tr><th>Created By</th><td>{{ $product->createdBy ? $product->createdBy->admin_username : '-' }}</td></tr>
                                <tr><th>Created Date</th><td>{{ $product->created_date ? date('d-m-Y H:i', strtotime($product->created_date)) : '-' }}</td></tr>
                                <tr><th>Updated By</th><td>{{ $product->updatedBy ? $product->updatedBy->admin_username : '-' }}</td></tr>
                                <tr><th>Updated Date</th><td>{{ $product->updated_date ? date('d-m-Y H:i', strtotime($product->updated_date)) : '-' }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

