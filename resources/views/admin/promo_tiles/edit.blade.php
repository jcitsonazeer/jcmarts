@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">Edit Promo Tile</h4>
                            <a href="{{ route('admin.promo-tiles.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back to Promo Tiles
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
                                <form method="POST" action="{{ route('admin.promo-tiles.update', $promoTile->id) }}"
                                      enctype="multipart/form-data">
                                    @csrf
                                    @method('PUT')
                                    @php
                                        $defaultImage = asset('assets/admin/images/no_image.png');
                                        $currentImage = $promoTile->promo_image
                                            ? asset('storage/promo_tile/' . $promoTile->promo_image)
                                            : $defaultImage;
                                    @endphp

                                    <div class="form-group row">
                                        <div class="col-md-6">
                                            <label>Promo Image (Leave empty to keep current)</label>
                                            <input type="file"
                                                   id="promo_image_input"
                                                   name="promo_image"
                                                   class="form-control {{ $errors->has('promo_image') ? 'is-invalid' : '' }}"
                                                   accept=".jpg,.jpeg,.png,.gif,image/jpeg,image/png,image/gif">
                                            <small class="text-muted">Allowed: JPG, JPEG, PNG, GIF image (max 5 MB, GIF up to 16 MB)</small>
                                            @error('promo_image')
                                                <span class="text-danger d-block">{{ $message }}</span>
                                            @enderror
                                            <div class="mt-2">
                                                <img id="promo_image_preview"
                                                     src="{{ $currentImage }}"
                                                     alt="Promo Image Preview"
                                                     style="width: 240px; height: 120px; object-fit: cover; border-radius: 6px;"
                                                     onerror="this.onerror=null;this.src='{{ $defaultImage }}';">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Updated By</label>
                                            <input type="text"
                                                   class="form-control"
                                                   value="{{ session('admin_username') ?? 'Unknown' }}"
                                                   disabled>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-md-6">
                                            <label>Promo Title <span class="text-danger">*</span></label>
                                            <input type="text"
                                                   name="promo_title"
                                                   class="form-control"
                                                   value="{{ old('promo_title', $promoTile->promo_title) }}"
                                                   maxlength="150"
                                                   required>
                                            @error('promo_title')
                                                <span class="text-danger d-block">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label>Sort Order <span class="text-danger">*</span></label>
                                            <input type="number"
                                                   name="sort_order"
                                                   class="form-control"
                                                   value="{{ old('sort_order', $promoTile->sort_order) }}"
                                                   min="0"
                                                   required>
                                            <small class="text-muted">Lower numbers appear first on the home page.</small>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-md-6">
                                            <label>Status <span class="text-danger">*</span></label>
                                            <select name="is_active" class="form-control" required>
                                                <option value="1" {{ old('is_active', (string) $promoTile->is_active) == '1' ? 'selected' : '' }}>Active</option>
                                                <option value="0" {{ old('is_active', (string) $promoTile->is_active) == '0' ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary mr-2">
                                                <i class="fa fa-save"></i> Update
                                            </button>
                                            <a href="{{ route('admin.promo-tiles.index') }}" class="btn btn-secondary">
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
    document.getElementById('promo_image_input')?.addEventListener('change', function (event) {
        const file = event.target.files && event.target.files[0];
        if (!file) {
            return;
        }

        const imagePreview = document.getElementById('promo_image_preview');
        const fileName = (file.name || '').toLowerCase();
        const isGif = fileName.endsWith('.gif');
        const maxFileSize = isGif ? 16 * 1024 * 1024 : 5 * 1024 * 1024; // 16 MB for GIF, 5 MB for others

        if (file.size > maxFileSize) {
            alert(isGif ? 'Promo image must not exceed 16 MB.' : 'Promo image must not exceed 5 MB.');
            event.target.value = '';
            return;
        }

        const isImage = ['image/jpeg', 'image/png', 'image/gif'].includes(file.type)
            && (fileName.endsWith('.jpg') || fileName.endsWith('.jpeg') || fileName.endsWith('.png') || fileName.endsWith('.gif'));

        if (!isImage) {
            alert('Please select only a JPG, JPEG, PNG, or GIF image.');
            event.target.value = '';
            return;
        }

        imagePreview.src = URL.createObjectURL(file);
    });
</script>
@endpush
@endsection