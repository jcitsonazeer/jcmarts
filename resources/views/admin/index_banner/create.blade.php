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
                                                   accept=".jpg,.jpeg,.png,.webp,image/*"
                                                   required>
                                            <small class="text-muted">Allowed: JPG, JPEG, PNG, WEBP (max 4MB)</small>
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
    setTimeout(function () {
        $('.alert').fadeOut('slow');
    }, 5000);

    document.getElementById('banner_image_input')?.addEventListener('change', function (event) {
        const file = event.target.files && event.target.files[0];
        if (!file) {
            return;
        }
        document.getElementById('banner_image_preview').src = URL.createObjectURL(file);
    });
</script>
@endpush
@endsection
