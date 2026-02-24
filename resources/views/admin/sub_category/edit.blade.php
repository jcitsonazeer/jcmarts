@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">

        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">Edit Sub Category</h4>
                            <a href="{{ route('admin.sub-categories.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back to Sub Categories
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
                                <form method="POST" action="{{ route('admin.sub-categories.update', $subCategory->id) }}" enctype="multipart/form-data">
                                    @csrf
                                    @method('PUT')

                                    <div class="form-group row">
                                        <div class="col-md-6">
                                            <label>Category <span class="text-danger">*</span></label>
                                            <select name="category_id" class="form-control" required>
                                                <option value="">Select Category</option>
                                                @foreach($categories as $cat)
                                                    <option value="{{ $cat->id }}" {{ old('category_id', $subCategory->category_id) == $cat->id ? 'selected' : '' }}>
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
                                                   value="{{ old('sub_category_name', $subCategory->sub_category_name) }}"
                                                   required>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-md-6">
                                            <label>Sub Category Image</label>
                                            <input type="file"
                                                   id="sub_category_image_input"
                                                   name="sub_category_image"
                                                   class="form-control {{ $errors->has('sub_category_image') ? 'is-invalid' : '' }}"
                                                   accept=".jpg,.jpeg,.png,.webp,image/*">
                                            <small class="text-muted">Allowed: JPG, JPEG, PNG, WEBP (max 2MB)</small>
                                            @error('sub_category_image')
                                                <span class="text-danger d-block">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label>Current Image</label>
                                            @php
                                                $defaultImage = asset('assets/admin/images/no_image.png');
                                                $subCategoryImage = $subCategory->sub_category_image
                                                    ? asset('storage/sub_category/' . $subCategory->sub_category_image)
                                                    : $defaultImage;
                                            @endphp
                                            <div>
                                                <img id="sub_category_current_image"
                                                     src="{{ $subCategoryImage }}"
                                                     alt="Current Sub Category Image"
                                                     style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px;"
                                                     onerror="this.onerror=null;this.src='{{ $defaultImage }}';">
                                            </div>
                                            <label class="mt-2 mb-1 d-block">Selected Image</label>
                                            <div>
                                                <img id="sub_category_selected_image"
                                                     src="{{ $defaultImage }}"
                                                     alt="Selected Sub Category Image"
                                                     style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px; display: none;"
                                                     onerror="this.onerror=null;this.src='{{ $defaultImage }}';">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-md-6">
                                            <label>Created By</label>
                                            <input type="text"
                                                   class="form-control"
                                                   value="{{ $subCategory->createdBy ? $subCategory->createdBy->admin_username : '-' }}"
                                                   disabled>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Created Date</label>
                                            <input type="text"
                                                   class="form-control"
                                                   value="{{ $subCategory->created_date ? date('d-m-Y H:i', strtotime($subCategory->created_date)) : '-' }}"
                                                   disabled>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-md-6">
                                            <label>Updated By</label>
                                            <input type="text"
                                                   class="form-control"
                                                   value="{{ $subCategory->updatedBy ? $subCategory->updatedBy->admin_username : '-' }}"
                                                   disabled>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Updated Date</label>
                                            <input type="text"
                                                   class="form-control"
                                                   value="{{ $subCategory->updated_date ? date('d-m-Y H:i', strtotime($subCategory->updated_date)) : '-' }}"
                                                   disabled>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary mr-2">
                                                <i class="fa fa-save"></i> Update
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

    document.getElementById('sub_category_image_input')?.addEventListener('change', function (event) {
        const file = event.target.files && event.target.files[0];
        if (!file) {
            return;
        }
        const selectedImage = document.getElementById('sub_category_selected_image');
        selectedImage.src = URL.createObjectURL(file);
        selectedImage.style.display = 'block';
    });
</script>
@endpush
@endsection
