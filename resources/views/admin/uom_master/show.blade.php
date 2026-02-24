@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">View UOM</h4>
                            <div>
                                <a href="{{ route('admin.uom-masters.index') }}" class="btn btn-secondary">
                                    <i class="fa fa-arrow-left"></i> Back
                                </a>
                                <a href="{{ route('admin.uom-masters.edit', $uom->id) }}" class="btn btn-warning">
                                    <i class="fa fa-edit"></i> Edit
                                </a>
                            </div>
                        </div>

                        <table class="table table-bordered">
                            <tbody>
                                <tr><th width="220">ID</th><td>{{ $uom->id }}</td></tr>
                                <tr><th>Primary UOM</th><td>{{ $uom->primary_uom }}</td></tr>
                                <tr><th>Secondary UOM</th><td>{{ $uom->secondary_uom ?: '-' }}</td></tr>
                                <tr><th>Created By</th><td>{{ $uom->createdBy ? $uom->createdBy->admin_username : '-' }}</td></tr>
                                <tr><th>Created Date</th><td>{{ $uom->created_date ? date('d-m-Y H:i', strtotime($uom->created_date)) : '-' }}</td></tr>
                                <tr><th>Updated By</th><td>{{ $uom->updatedBy ? $uom->updatedBy->admin_username : '-' }}</td></tr>
                                <tr><th>Updated Date</th><td>{{ $uom->updated_date ? date('d-m-Y H:i', strtotime($uom->updated_date)) : '-' }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

