@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">Edit Product Images</h4>
                            <a href="{{ route('admin.product-images.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back to Product Images
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

                        @php
                            $defaultImage = asset('assets/admin/images/no_image.png');
                            $image1 = $product->single_image_1 ? asset('storage/product/single/' . $product->single_image_1) : $defaultImage;
                            $image2 = $product->single_image_2 ? asset('storage/product/single/' . $product->single_image_2) : $defaultImage;
                            $image3 = $product->single_image_3 ? asset('storage/product/single/' . $product->single_image_3) : $defaultImage;
                            $image4 = $product->single_image_4 ? asset('storage/product/single/' . $product->single_image_4) : $defaultImage;
                        @endphp

                        <form method="POST" action="{{ route('admin.product-images.update', $product->id) }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Product</label>
                                    <input type="text" class="form-control" value="{{ $product->product_name }}" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label>Sub Category</label>
                                    <input type="text" class="form-control" value="{{ $product->subCategory ? $product->subCategory->sub_category_name : '-' }}" disabled>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Single Image 1</label>
                                    <input type="file" id="single_image_1_input" name="single_image_1" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/*">
                                    <small class="text-muted">Upload to replace current image.</small>
                                    <div class="mt-2">
                                        <img id="single_image_1_preview" src="{{ $image1 }}" alt="Single Image 1"
                                             style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px;"
                                             onerror="this.onerror=null;this.src='{{ $defaultImage }}';">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label>Single Image 2</label>
                                    <input type="file" id="single_image_2_input" name="single_image_2" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/*">
                                    <small class="text-muted">Upload to replace current image.</small>
                                    <div class="mt-2">
                                        <img id="single_image_2_preview" src="{{ $image2 }}" alt="Single Image 2"
                                             style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px;"
                                             onerror="this.onerror=null;this.src='{{ $defaultImage }}';">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Single Image 3</label>
                                    <input type="file" id="single_image_3_input" name="single_image_3" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/*">
                                    <small class="text-muted">Upload to replace current image.</small>
                                    <div class="mt-2">
                                        <img id="single_image_3_preview" src="{{ $image3 }}" alt="Single Image 3"
                                             style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px;"
                                             onerror="this.onerror=null;this.src='{{ $defaultImage }}';">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label>Single Image 4</label>
                                    <input type="file" id="single_image_4_input" name="single_image_4" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/*">
                                    <small class="text-muted">Upload to replace current image.</small>
                                    <div class="mt-2">
                                        <img id="single_image_4_preview" src="{{ $image4 }}" alt="Single Image 4"
                                             style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px;"
                                             onerror="this.onerror=null;this.src='{{ $defaultImage }}';">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary mr-2"><i class="fa fa-save"></i> Update Images</button>
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
