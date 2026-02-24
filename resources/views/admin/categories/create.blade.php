@extends('admin.dashboard.headerfooter')


@section('content')
<div class="main-panel">
    <div class="content-wrapper">

        <!-- Basic Form area Start -->
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">

                        <!-- Header -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">Add Category</h4>
                            <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back to Categories
                            </a>
                        </div>

                        <!-- Validation Errors -->
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <!-- Form -->
                        <div class="row">
                            <div class="col-sm-12 col-xs-12">
                                <form method="POST" action="{{ route('admin.categories.store') }}">
                                    @csrf

                                    <div class="form-group row">
                                        <div class="col-md-6">
                                            <label>
                                                Category Name <span class="text-danger">*</span>
                                            </label>
                                            <input type="text"
                                                   name="category_name"
                                                   class="form-control {{ $errors->has('category_name') ? 'is-invalid' : '' }}"
                                                   placeholder="Enter Category Name"
                                                   value="{{ old('category_name') }}"
                                                   required>

                                            @error('category_name')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label>Created By</label>
                                            <input type="text"
                                                   class="form-control"
                                                   value="{{ session('admin_username') ?? 'Unknown' }}"
                                                   disabled>
                                        </div>
                                    </div>

                                    <!-- Buttons -->
                                    <div class="form-group row">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary mr-2">
                                                <i class="fa fa-save"></i> Submit
                                            </button>

                                            <button type="reset" class="btn btn-warning mr-2">
                                                <i class="fa fa-refresh"></i> Reset
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
        <!-- Basic Form area End -->

    </div>
</div>

@push('scripts')
<script>
    // Auto-hide alerts after 5 seconds
    setTimeout(function () {
        $('.alert').fadeOut('slow');
    }, 5000);

</script>
@endpush
@endsection
