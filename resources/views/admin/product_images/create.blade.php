@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">Add Product Images</h4>
                            <a href="{{ route('admin.product-images.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back to Product Images
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

                        @php($defaultImage = asset('assets/admin/images/no_image.png'))

                        <form method="POST" action="{{ route('admin.product-images.store') }}" enctype="multipart/form-data">
                            @csrf

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Product <span class="text-danger">*</span></label>
                                    <select name="product_id" class="form-control" required>
                                        <option value="">Select Product</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}"
                                                {{ old('product_id', request('product_id')) == $product->id ? 'selected' : '' }}>
                                                {{ $product->product_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Choose product and upload any one or more images.</small>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Single Image 1</label>
                                    <input type="file" id="single_image_1_input" name="single_image_1" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/*">
                                    <small class="text-muted">Allowed: JPG, JPEG, PNG, WEBP (max 2MB)</small>
                                    <div class="mt-2">
                                        <img id="single_image_1_preview" src="{{ $defaultImage }}" alt="Single Image 1"
                                             style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px;"
                                             onerror="this.onerror=null;this.src='{{ $defaultImage }}';">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label>Single Image 2</label>
                                    <input type="file" id="single_image_2_input" name="single_image_2" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/*">
                                    <small class="text-muted">Allowed: JPG, JPEG, PNG, WEBP (max 2MB)</small>
                                    <div class="mt-2">
                                        <img id="single_image_2_preview" src="{{ $defaultImage }}" alt="Single Image 2"
                                             style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px;"
                                             onerror="this.onerror=null;this.src='{{ $defaultImage }}';">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Single Image 3</label>
                                    <input type="file" id="single_image_3_input" name="single_image_3" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/*">
                                    <small class="text-muted">Allowed: JPG, JPEG, PNG, WEBP (max 2MB)</small>
                                    <div class="mt-2">
                                        <img id="single_image_3_preview" src="{{ $defaultImage }}" alt="Single Image 3"
                                             style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px;"
                                             onerror="this.onerror=null;this.src='{{ $defaultImage }}';">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label>Single Image 4</label>
                                    <input type="file" id="single_image_4_input" name="single_image_4" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/*">
                                    <small class="text-muted">Allowed: JPG, JPEG, PNG, WEBP (max 2MB)</small>
                                    <div class="mt-2">
                                        <img id="single_image_4_preview" src="{{ $defaultImage }}" alt="Single Image 4"
                                             style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px;"
                                             onerror="this.onerror=null;this.src='{{ $defaultImage }}';">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary mr-2"><i class="fa fa-save"></i> Save Images</button>
                                    <button type="reset" class="btn btn-warning mr-2"><i class="fa fa-refresh"></i> Reset</button>
                                    <a href="{{ route('admin.product-images.index') }}" class="btn btn-secondary"><i class="fa fa-times"></i> Cancel</a>
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
['1', '2', '3', '4'].forEach(function (key) {
    const input = document.getElementById('single_image_' + key + '_input');
    const preview = document.getElementById('single_image_' + key + '_preview');

    input?.addEventListener('change', function (event) {
        const file = event.target.files && event.target.files[0];
        if (!file) {
            return;
        }
        preview.src = URL.createObjectURL(file);
    });
});
</script>
@endpush
@endsection
