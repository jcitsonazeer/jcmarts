@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">Edit Product</h4>
                            <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back to Products
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

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('admin.products.update', $product->id) }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Sub Category <span class="text-danger">*</span></label>
                                    <select name="sub_category_id" class="form-control" required>
                                        <option value="">Select Sub Category</option>
                                        @foreach($subCategories as $subCategory)
                                            <option value="{{ $subCategory->id }}" {{ old('sub_category_id', $product->sub_category_id) == $subCategory->id ? 'selected' : '' }}>
                                                {{ $subCategory->sub_category_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label>Product Name <span class="text-danger">*</span></label>
                                    <input type="text" name="product_name" class="form-control" value="{{ old('product_name', $product->product_name) }}" required>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Product Image</label>
                                    <input type="file" id="product_image_input" name="product_image" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/*">
                                    <small class="text-muted">Allowed: JPG, JPEG, PNG, WEBP (max 2MB)</small>
                                </div>
                                <div class="col-md-6">
                                    <label>Status <span class="text-danger">*</span></label>
                                    <select name="is_active" class="form-control" required>
                                        <option value="1" {{ old('is_active', $product->is_active) == 1 ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ old('is_active', $product->is_active) == 0 ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                @php
                                    $defaultImage = asset('assets/admin/images/no_image.png');
                                    $productImage = $product->product_image
                                        ? asset('storage/product/' . $product->product_image)
                                        : $defaultImage;
                                @endphp
                                <div class="col-md-6">
                                    <label>Current Image</label>
                                    <div>
                                        <img id="product_current_image"
                                             src="{{ $productImage }}"
                                             alt="Current Product Image"
                                             style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px;"
                                             onerror="this.onerror=null;this.src='{{ $defaultImage }}';">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label>Selected Image</label>
                                    <div>
                                        <img id="product_selected_image"
                                             src="{{ $defaultImage }}"
                                             alt="Selected Product Image"
                                             style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px; display: none;"
                                             onerror="this.onerror=null;this.src='{{ $defaultImage }}';">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Description</label>
                                    <textarea name="description" class="form-control" rows="4">{{ old('description', $product->description) }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label>Warranty Info</label>
                                    <textarea name="warranty_info" class="form-control" rows="4">{{ old('warranty_info', $product->warranty_info) }}</textarea>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Created By</label>
                                    <input type="text" class="form-control" value="{{ $product->createdBy ? $product->createdBy->admin_username : '-' }}" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label>Created Date</label>
                                    <input type="text" class="form-control" value="{{ $product->created_date ? date('d-m-Y H:i', strtotime($product->created_date)) : '-' }}" disabled>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary mr-2"><i class="fa fa-save"></i> Update</button>
                                    <a href="{{ route('admin.products.index') }}" class="btn btn-secondary"><i class="fa fa-times"></i> Cancel</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.getElementById('product_image_input')?.addEventListener('change', function (event) {
    const file = event.target.files && event.target.files[0];
    if (!file) {
        return;
    }
    const selectedImage = document.getElementById('product_selected_image');
    selectedImage.src = URL.createObjectURL(file);
    selectedImage.style.display = 'block';
});
</script>
@endpush
@endsection
