@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">Add Product</h4>
                            <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back to Products
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

                        <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
                            @csrf

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Sub Category <span class="text-danger">*</span></label>
                                    <input
                                        type="text"
                                        name="sub_category_name"
                                        class="form-control"
                                        list="sub-category-options"
                                        value="{{ old('sub_category_name') }}"
                                        placeholder="Type or select sub category"
                                        autocomplete="off"
                                        required
                                    >
                                    <datalist id="sub-category-options">
                                        @foreach($subCategories as $subCategory)
                                            <option value="{{ $subCategory->sub_category_name }}"></option>
                                        @endforeach
                                    </datalist>
                                    @error('sub_category_name')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label>Brand</label>
                                    <input
                                        type="text"
                                        name="brand_name"
                                        class="form-control"
                                        list="brand-options"
                                        value="{{ old('brand_name') }}"
                                        placeholder="Type or select brand"
                                        autocomplete="off"
                                    >
                                    <datalist id="brand-options">
                                        @foreach($brands as $brand)
                                            <option value="{{ $brand->brand_name }}"></option>
                                        @endforeach
                                    </datalist>
                                    @error('brand_name')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Product Name <span class="text-danger">*</span></label>
                                    <input type="text" name="product_name" class="form-control" value="{{ old('product_name') }}" required>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Product Image</label>
                                    <input type="file" id="product_image_input" name="product_image" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/*">
                                    <small class="text-muted">Allowed: JPG, JPEG, PNG, WEBP (max 2MB)</small>
                                    @php($defaultImage = asset('assets/admin/images/no_image.png'))
                                    <div class="mt-2">
                                        <img id="product_image_preview"
                                             src="{{ $defaultImage }}"
                                             alt="Product Preview"
                                             style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px;"
                                             onerror="this.onerror=null;this.src='{{ $defaultImage }}';">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label>Status <span class="text-danger">*</span></label>
                                    <select name="is_active" class="form-control" required>
                                        <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Description</label>
                                    <textarea name="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label>Warranty Info</label>
                                    <textarea name="warranty_info" class="form-control" rows="4">{{ old('warranty_info') }}</textarea>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary mr-2"><i class="fa fa-save"></i> Submit</button>
                                    <button type="reset" class="btn btn-warning mr-2"><i class="fa fa-refresh"></i> Reset</button>
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
    document.getElementById('product_image_preview').src = URL.createObjectURL(file);
});
</script>
@endpush
@endsection
