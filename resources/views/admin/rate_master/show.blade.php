@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">View Rate</h4>
                            <div>
                                <a href="{{ route('admin.rate-masters.index') }}" class="btn btn-secondary">
                                    <i class="fa fa-arrow-left"></i> Back
                                </a>
                                <a href="{{ route('admin.rate-masters.edit', $rate->id) }}" class="btn btn-warning">
                                    <i class="fa fa-edit"></i> Edit
                                </a>
                            </div>
                        </div>

                        <table class="table table-bordered">
                            <tbody>
                                <tr><th width="220">ID</th><td>{{ $rate->id }}</td></tr>
                                <tr><th>Product</th><td>{{ $rate->product ? $rate->product->product_name : '-' }}</td></tr>
                                <tr><th>UOM</th><td>{{ $rate->uom ? $rate->uom->primary_uom : '-' }}</td></tr>
                                <tr><th>Cost Price</th><td>{{ $rate->cost_price }}</td></tr>
                                <tr><th>Selling Price</th><td>{{ $rate->selling_price }}</td></tr>
                                <tr><th>Offer Percentage</th><td>{{ $rate->offer_percentage }}</td></tr>
                                <tr><th>Offer Price</th><td>{{ $rate->offer_price }}</td></tr>
                                <tr><th>Final Price</th><td>{{ $rate->final_price }}</td></tr>
                                <tr><th>Stock Qty</th><td>{{ $rate->stock_qty }}</td></tr>
                                <tr><th>Status</th><td>{{ $rate->is_active ? 'Active' : 'Inactive' }}</td></tr>
                                <tr><th>Created By</th><td>{{ $rate->createdBy ? $rate->createdBy->admin_username : '-' }}</td></tr>
                                <tr><th>Created Date</th><td>{{ $rate->created_date ? date('d-m-Y H:i', strtotime($rate->created_date)) : '-' }}</td></tr>
                                <tr><th>Updated By</th><td>{{ $rate->updatedBy ? $rate->updatedBy->admin_username : '-' }}</td></tr>
                                <tr><th>Updated Date</th><td>{{ $rate->updated_date ? date('d-m-Y H:i', strtotime($rate->updated_date)) : '-' }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
