@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">Add UOM</h4>
                            <a href="{{ route('admin.uom-masters.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back to UOM
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

                        <form method="POST" action="{{ route('admin.uom-masters.store') }}">
                            @csrf

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Primary UOM <span class="text-danger">*</span></label>
                                    <input type="text"
                                           id="primary_uom"
                                           name="primary_uom"
                                           class="form-control"
                                           list="primary_uom_options"
                                           value="{{ old('primary_uom') }}"
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
                                    <input type="text" name="secondary_uom" class="form-control" value="{{ old('secondary_uom') }}" required>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary mr-2"><i class="fa fa-save"></i> Submit</button>
                                    <button type="reset" class="btn btn-warning mr-2"><i class="fa fa-refresh"></i> Reset</button>
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
