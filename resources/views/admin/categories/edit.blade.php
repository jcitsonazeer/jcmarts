@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">

        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">Edit Category</h4>
                            <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back to Categories
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
                                <form method="POST" action="{{ route('admin.categories.update', $category->id) }}">
                                    @csrf
                                    @method('PUT')

                                    <div class="form-group row">
                                        <div class="col-md-6">
                                            <label>
                                                Category Name <span class="text-danger">*</span>
                                            </label>
                                            <input type="text"
                                                   name="category_name"
                                                   class="form-control {{ $errors->has('category_name') ? 'is-invalid' : '' }}"
                                                   value="{{ old('category_name', $category->category_name) }}"
                                                   required>

                                            @error('category_name')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label>Created By</label>
                                            <input type="text"
                                                   class="form-control"
                                                   value="{{ $category->createdBy ? $category->createdBy->admin_username : '-' }}"
                                                   disabled>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-md-6">
                                            <label>Created Date</label>
                                            <input type="text"
                                                   class="form-control"
                                                   value="{{ $category->created_date ? date('d-m-Y H:i', strtotime($category->created_date)) : '-' }}"
                                                   disabled>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Updated By</label>
                                            <input type="text"
                                                   class="form-control"
                                                   value="{{ $category->updatedBy ? $category->updatedBy->admin_username : '-' }}"
                                                   disabled>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-md-6">
                                            <label>Updated Date</label>
                                            <input type="text"
                                                   class="form-control"
                                                   value="{{ $category->updated_date ? date('d-m-Y H:i', strtotime($category->updated_date)) : '-' }}"
                                                   disabled>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary mr-2">
                                                <i class="fa fa-save"></i> Update
                                            </button>

                                            <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">
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

</script>
@endpush
@endsection
