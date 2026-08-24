@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">Add Index Banner</h4>
                            <a href="{{ route('admin.index-banners.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back to Banners
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
                                <form method="POST" action="{{ route('admin.index-banners.store') }}" enctype="multipart/form-data">
                                    @csrf
                                    @php
                                        $defaultImage = asset('assets/admin/images/no_image.png');
                                    @endphp

                                    <div class="form-group row">
                                        <div class="col-md-6">
                                            <label>Banner Image <span class="text-danger">*</span></label>
                                            <input type="file"
                                                   id="banner_image_input"
                                                   name="banner_image"
                                                   class="form-control {{ $errors->has('banner_image') ? 'is-invalid' : '' }}"
                                                   accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                                                   required>
                                            <small class="text-muted">Allowed: JPG, JPEG, PNG (max 5 MB)</small>
                                            @error('banner_image')
                                                <span class="text-danger d-block">{{ $message }}</span>
                                            @enderror
                                            <div class="mt-2">
                                                <img id="banner_image_preview"
                                                     src="{{ $defaultImage }}"
                                                     alt="Banner Preview"
                                                     style="width: 240px; height: 100px; object-fit: cover; border-radius: 6px;"
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
                                        <div class="col-md-6">
                                            <label>Offer Name (Optional)</label>
                                            <select name="offer_details_id" class="form-control">
                                                <option value="">Select Offer</option>
                                                @foreach(($offers ?? collect()) as $offer)
                                                    <option value="{{ $offer->id }}" {{ (string) old('offer_details_id') === (string) $offer->id ? 'selected' : '' }}>
                                                        {{ $offer->offer_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Sub Category (Optional)</label>
                                            <input
                                                type="text"
                                                name="sub_category_name"
                                                class="form-control"
                                                list="sub-category-options"
                                                value="{{ old('sub_category_name') }}"
                                                placeholder="Type or select sub category"
                                                autocomplete="off"
                                            >
                                            <datalist id="sub-category-options">
                                                @foreach(($subCategories ?? collect()) as $subCategory)
                                                    <option value="{{ $subCategory->sub_category_name }}"></option>
                                                @endforeach
                                            </datalist>
                                            @error('sub_category_name')
                                                <span class="text-danger d-block">{{ $message }}</span>
                                            @enderror
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
                                            <a href="{{ route('admin.index-banners.index') }}" class="btn btn-secondary">
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
    document.getElementById('banner_image_input')?.addEventListener('change', function (event) {
        const file = event.target.files && event.target.files[0];
        if (!file) {
            return;
        }

        const maxFileSize = 5 * 1024 * 1024; // 5 MB

        if (file.size > maxFileSize) {
            alert('Banner image must not exceed 5 MB.');
            event.target.value = '';
            document.getElementById('banner_image_preview').src = @json($defaultImage);
            return;
        }

        const allowedTypes = ['image/jpeg', 'image/png'];
        const fileName = (file.name || '').toLowerCase();
        const isAllowedExtension = fileName.endsWith('.jpg') || fileName.endsWith('.jpeg') || fileName.endsWith('.png');

        if (!allowedTypes.includes(file.type) || !isAllowedExtension) {
            alert('Please select only JPG, JPEG, or PNG image.');
            event.target.value = '';
            document.getElementById('banner_image_preview').src = @json($defaultImage);
            return;
        }

        document.getElementById('banner_image_preview').src = URL.createObjectURL(file);
    });
</script>
@endpush
@endsection
