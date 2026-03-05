@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">View Offer</h4>
                            <div>
                                <a href="{{ route('admin.offer-details.index') }}" class="btn btn-secondary">
                                    <i class="fa fa-arrow-left"></i> Back to Offers
                                </a>
                                <a href="{{ route('admin.offer-details.edit', $offer->id) }}" class="btn btn-warning">
                                    <i class="fa fa-edit"></i> Edit
                                </a>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th width="200">ID</th>
                                        <td>{{ $offer->id }}</td>
                                    </tr>
                                    <tr>
                                        <th>Offer Name</th>
                                        <td>{{ $offer->offer_name }}</td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>{{ $offer->is_active ? 'Active' : 'Inactive' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Created By</th>
                                        <td>{{ $offer->createdBy ? $offer->createdBy->admin_username : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Created Date</th>
                                        <td>{{ $offer->created_date ? date('d-m-Y H:i', strtotime($offer->created_date)) : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Updated By</th>
                                        <td>{{ $offer->updatedBy ? $offer->updatedBy->admin_username : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Updated Date</th>
                                        <td>{{ $offer->updated_date ? date('d-m-Y H:i', strtotime($offer->updated_date)) : '-' }}</td>
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
