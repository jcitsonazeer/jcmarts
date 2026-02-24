@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">Edit Index Banner</h4>
                            <a href="{{ route('admin.index-banners.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back to Banners
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

                        <div class="row">
                            <div class="col-sm-12 col-xs-12">
                                <form method="POST" action="{{ route('admin.index-banners.update', $banner->id) }}" enctype="multipart/form-data">
                                    @csrf
                                    @method('PUT')
                                    @php
                                        $defaultImage = asset('assets/admin/images/no_image.png');
                                        $bannerImage = $banner->banner_image
                                            ? asset('storage/index_banner/' . $banner->banner_image)
                                            : $defaultImage;
                                    @endphp

                                    <div class="form-group row">
                                        <div class="col-md-6">
                                            <label>Banner Image</label>
                                            <input type="file"
                                                   id="banner_image_input"
                                                   name="banner_image"
                                                   class="form-control {{ $errors->has('banner_image') ? 'is-invalid' : '' }}"
                                                   accept=".jpg,.jpeg,.png,.webp,image/*">
                                            <small class="text-muted">Allowed: JPG, JPEG, PNG, WEBP (max 4MB)</small>
                                            @error('banner_image')
                                                <span class="text-danger d-block">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label>Current Image</label>
                                            <div>
                                                <img id="banner_current_image"
                                                     src="{{ $bannerImage }}"
                                                     alt="Current Banner Image"
                                                     style="width: 240px; height: 100px; object-fit: cover; border-radius: 6px;"
                                                     onerror="this.onerror=null;this.src='{{ $defaultImage }}';">
                                            </div>
                                            <label class="mt-2 mb-1 d-block">Selected Image</label>
                                            <div>
                                                <img id="banner_selected_image"
                                                     src="{{ $defaultImage }}"
                                                     alt="Selected Banner Image"
                                                     style="width: 240px; height: 100px; object-fit: cover; border-radius: 6px; display: none;"
                                                     onerror="this.onerror=null;this.src='{{ $defaultImage }}';">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-md-6">
                                            <label>Created By</label>
                                            <input type="text"
                                                   class="form-control"
                                                   value="{{ $banner->createdBy ? $banner->createdBy->admin_username : '-' }}"
                                                   disabled>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Created Date</label>
                                            <input type="text"
                                                   class="form-control"
                                                   value="{{ $banner->created_date ? date('d-m-Y H:i', strtotime($banner->created_date)) : '-' }}"
                                                   disabled>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-md-6">
                                            <label>Updated By</label>
                                            <input type="text"
                                                   class="form-control"
                                                   value="{{ $banner->updatedBy ? $banner->updatedBy->admin_username : '-' }}"
                                                   disabled>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Updated Date</label>
                                            <input type="text"
                                                   class="form-control"
                                                   value="{{ $banner->updated_date ? date('d-m-Y H:i', strtotime($banner->updated_date)) : '-' }}"
                                                   disabled>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary mr-2">
                                                <i class="fa fa-save"></i> Update
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
    setTimeout(function () {
        $('.alert').fadeOut('slow');
    }, 5000);

    document.getElementById('banner_image_input')?.addEventListener('change', function (event) {
        const file = event.target.files && event.target.files[0];
        if (!file) {
            return;
        }
        const selectedImage = document.getElementById('banner_selected_image');
        selectedImage.src = URL.createObjectURL(file);
        selectedImage.style.display = 'block';
    });
</script>
@endpush
@endsection
