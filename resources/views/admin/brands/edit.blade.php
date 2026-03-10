@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">Edit Brand</h4>
                            <a href="{{ route('admin.brands.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back to Brands
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

                        <form method="POST" action="{{ route('admin.brands.update', $brand->id) }}">
                            @csrf
                            @method('PUT')

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Brand Name <span class="text-danger">*</span></label>
                                    <input type="text" name="brand_name" class="form-control" value="{{ old('brand_name', $brand->brand_name) }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label>Status <span class="text-danger">*</span></label>
                                    <select name="is_active" class="form-control" required>
                                        <option value="1" {{ old('is_active', $brand->is_active) == 1 ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ old('is_active', $brand->is_active) == 0 ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Created By</label>
                                    <input type="text" class="form-control" value="{{ $brand->createdBy ? $brand->createdBy->admin_username : '-' }}" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label>Created Date</label>
                                    <input type="text" class="form-control" value="{{ $brand->created_date ? date('d-m-Y H:i', strtotime($brand->created_date)) : '-' }}" disabled>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary mr-2"><i class="fa fa-save"></i> Update</button>
                                    <a href="{{ route('admin.brands.index') }}" class="btn btn-secondary"><i class="fa fa-times"></i> Cancel</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
