@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">Edit UOM</h4>
                            <a href="{{ route('admin.uom-masters.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back to UOM
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

                        <form method="POST" action="{{ route('admin.uom-masters.update', $uom->id) }}">
                            @csrf
                            @method('PUT')

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Primary UOM <span class="text-danger">*</span></label>
                                    <input type="text"
                                           id="primary_uom"
                                           name="primary_uom"
                                           class="form-control"
                                           list="primary_uom_options"
                                           value="{{ old('primary_uom', $uom->primary_uom) }}"
                                           placeholder="Type or select primary UOM"
                                           required>
                                    <datalist id="primary_uom_options">
                                        @foreach($primaryUomOptions as $option)
                                            <option value="{{ $option }}"></option>
                                        @endforeach
                                    </datalist>
                                    <small class="text-muted">Type new value or pick existing value from input dropdown.</small>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Secondary UOM <span class="text-danger">*</span></label>
                                    <input type="text" name="secondary_uom" class="form-control" value="{{ old('secondary_uom', $uom->secondary_uom) }}" required>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Created By</label>
                                    <input type="text" class="form-control" value="{{ $uom->createdBy ? $uom->createdBy->admin_username : '-' }}" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label>Created Date</label>
                                    <input type="text" class="form-control" value="{{ $uom->created_date ? date('d-m-Y H:i', strtotime($uom->created_date)) : '-' }}" disabled>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary mr-2"><i class="fa fa-save"></i> Update</button>
                                    <a href="{{ route('admin.uom-masters.index') }}" class="btn btn-secondary"><i class="fa fa-times"></i> Cancel</a>
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
