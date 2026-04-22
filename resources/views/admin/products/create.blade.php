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
                                    <input type="file" id="product_image_input" name="product_image" class="form-control" accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                                    <small class="text-muted d-block">Allowed: JPG, JPEG, PNG (max 2MB)</small>
                                    <small class="text-muted">Preview and upload size: 233 x 215 px</small>
                                    @error('product_image')
                                        <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                    @php($defaultImage = asset('assets/admin/images/no_image.png'))
                                    <div class="mt-2">
                                        <img id="product_image_preview"
                                             src="{{ $defaultImage }}"
                                             alt="Product Preview"
                                             style="width: 233px; height: 215px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd;"
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
const productImageInput = document.getElementById('product_image_input');
const productImagePreview = document.getElementById('product_image_preview');
const productPreviewWidth = 233;
const productPreviewHeight = 215;
const defaultProductImage = @json($defaultImage);

productImageInput?.addEventListener('change', function (event) {
    const file = event.target.files && event.target.files[0];
    if (!file) {
        return;
    }

    const allowedTypes = ['image/jpeg', 'image/png'];
    const fileName = (file.name || '').toLowerCase();
    const isAllowedExtension = fileName.endsWith('.jpg') || fileName.endsWith('.jpeg') || fileName.endsWith('.png');

    if (!allowedTypes.includes(file.type) || !isAllowedExtension) {
        alert('Please select only JPG, JPEG, or PNG image.');
        productImageInput.value = '';
        productImagePreview.src = defaultProductImage;
        return;
    }

    const reader = new FileReader();

    reader.onload = function (loadEvent) {
        const image = new Image();

        image.onload = function () {
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');

            canvas.width = productPreviewWidth;
            canvas.height = productPreviewHeight;

            const sourceRatio = image.width / image.height;
            const targetRatio = productPreviewWidth / productPreviewHeight;

            let sourceX = 0;
            let sourceY = 0;
            let sourceWidth = image.width;
            let sourceHeight = image.height;

            if (sourceRatio > targetRatio) {
                sourceWidth = image.height * targetRatio;
                sourceX = (image.width - sourceWidth) / 2;
            } else {
                sourceHeight = image.width / targetRatio;
                sourceY = (image.height - sourceHeight) / 2;
            }

            context.drawImage(
                image,
                sourceX,
                sourceY,
                sourceWidth,
                sourceHeight,
                0,
                0,
                productPreviewWidth,
                productPreviewHeight
            );

            productImagePreview.src = canvas.toDataURL('image/jpeg', 0.9);

            canvas.toBlob(function (blob) {
                if (!blob) {
                    return;
                }

                const resizedFile = new File(
                    [blob],
                    file.name.replace(/\.[^.]+$/, '') + '.jpg',
                    { type: 'image/jpeg' }
                );

                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(resizedFile);
                productImageInput.files = dataTransfer.files;
            }, 'image/jpeg', 0.9);
        };

        image.src = loadEvent.target.result;
    };

    reader.readAsDataURL(file);
});
</script>
@endpush
@endsection
