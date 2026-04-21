@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">

        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">Add Sub Category</h4>
                            <a href="{{ route('admin.sub-categories.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back to Sub Categories
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

                        <div class="row">
                            <div class="col-sm-12 col-xs-12">
                                <form method="POST" action="{{ route('admin.sub-categories.store') }}" enctype="multipart/form-data">
                                    @csrf

                                    <div class="form-group row">
                                        <div class="col-md-6">
                                            <label>Category <span class="text-danger">*</span></label>
                                            <select name="category_id" class="form-control" required>
                                                <option value="">Select Category</option>
                                                @foreach($categories as $cat)
                                                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                                        {{ $cat->category_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Sub Category Name <span class="text-danger">*</span></label>
                                            <input type="text"
                                                   name="sub_category_name"
                                                   class="form-control {{ $errors->has('sub_category_name') ? 'is-invalid' : '' }}"
                                                   placeholder="Enter Sub Category Name"
                                                   value="{{ old('sub_category_name') }}"
                                                   required>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-md-6">
                                            <label>Sub Category Image</label>
                                            @php
                                                $defaultImage = asset('assets/admin/images/no_image.png');
                                            @endphp
                                            <input type="file"
                                                   id="sub_category_image_input"
                                                   name="sub_category_image"
                                                   class="form-control {{ $errors->has('sub_category_image') ? 'is-invalid' : '' }}"
                                                   accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                                            <small class="text-muted d-block">Allowed: JPG, JPEG, PNG (max 2MB)</small>
                                            <small class="text-muted">Preview and upload size: 180 x 135 px</small>
                                            @error('sub_category_image')
                                                <span class="text-danger d-block">{{ $message }}</span>
                                            @enderror
                                            <div class="mt-2">
                                                <img id="sub_category_image_preview"
                                                     src="{{ $defaultImage }}"
                                                     alt="Sub Category Preview"
                                                     style="width: 180px; height: 135px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd;"
                                                     onerror="this.onerror=null;this.src='{{ $defaultImage }}';">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Created By</label>
                                            <input type="text"
                                                   class="form-control"
                                                   value="{{ session('admin_username') ?? 'Unknown' }}"
                                                   disabled>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary mr-2">
                                                <i class="fa fa-save"></i> Submit
                                            </button>

                                            <button type="reset" class="btn btn-warning mr-2">
                                                <i class="fa fa-refresh"></i> Reset
                                            </button>

                                            <a href="{{ route('admin.sub-categories.index') }}" class="btn btn-secondary">
                                                <i class="fa fa-times"></i> Cancel
                                            </a>
                                        </div>
                                    </div>

                                </form>
                            </div>
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

    const subCategoryImageInput = document.getElementById('sub_category_image_input');
    const subCategoryPreviewImage = document.getElementById('sub_category_image_preview');
    const previewWidth = 180;
    const previewHeight = 135;
    const defaultPreviewImage = @json($defaultImage);

    subCategoryImageInput?.addEventListener('change', function (event) {
        const file = event.target.files && event.target.files[0];
        if (!file) {
            return;
        }

        const allowedTypes = ['image/jpeg', 'image/png'];
        const fileName = (file.name || '').toLowerCase();
        const isAllowedExtension = fileName.endsWith('.jpg') || fileName.endsWith('.jpeg') || fileName.endsWith('.png');

        if (!allowedTypes.includes(file.type) || !isAllowedExtension) {
            alert('Please select only JPG, JPEG, or PNG image.');
            subCategoryImageInput.value = '';
            subCategoryPreviewImage.src = defaultPreviewImage;
            return;
        }

        const reader = new FileReader();

        reader.onload = function (loadEvent) {
            const image = new Image();

            image.onload = function () {
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');

                canvas.width = previewWidth;
                canvas.height = previewHeight;

                const sourceRatio = image.width / image.height;
                const targetRatio = previewWidth / previewHeight;

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
                    previewWidth,
                    previewHeight
                );

                subCategoryPreviewImage.src = canvas.toDataURL('image/jpeg', 0.9);

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
                    subCategoryImageInput.files = dataTransfer.files;
                }, 'image/jpeg', 0.9);
            };

            image.src = loadEvent.target.result;
        };

        reader.readAsDataURL(file);
    });
</script>
@endpush
@endsection
