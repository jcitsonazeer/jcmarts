@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">View Offer Product</h4>
                            <div>
                                <a href="{{ route('admin.offer-products.index') }}" class="btn btn-secondary">
                                    <i class="fa fa-arrow-left"></i> Back to Offer Products
                                </a>
                                <a href="{{ route('admin.offer-products.edit', $offerProduct->id) }}" class="btn btn-warning">
                                    <i class="fa fa-edit"></i> Edit
                                </a>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th width="200">ID</th>
                                        <td>{{ $offerProduct->id }}</td>
                                    </tr>
                                    <tr>
                                        <th>Offer Name</th>
                                        <td>{{ $offerProduct->offer ? $offerProduct->offer->offer_name : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Product</th>
                                        <td>{{ $offerProduct->product ? $offerProduct->product->product_name : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>{{ $offerProduct->is_active ? 'Active' : 'Inactive' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Created By</th>
                                        <td>{{ $offerProduct->createdBy ? $offerProduct->createdBy->admin_username : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Created Date</th>
                                        <td>{{ $offerProduct->created_date ? date('d-m-Y H:i', strtotime($offerProduct->created_date)) : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Updated By</th>
                                        <td>{{ $offerProduct->updatedBy ? $offerProduct->updatedBy->admin_username : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Updated Date</th>
                                        <td>{{ $offerProduct->updated_date ? date('d-m-Y H:i', strtotime($offerProduct->updated_date)) : '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
