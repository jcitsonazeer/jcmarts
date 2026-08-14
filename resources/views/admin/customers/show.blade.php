@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">View Customer</h4>
                            <div>
                                <a href="{{ route('admin.customers.index') }}" class="btn btn-secondary">
                                    <i class="fa fa-arrow-left"></i> Back
                                </a>
                                <a href="{{ route('admin.customers.edit', $customer->id) }}" class="btn btn-warning">
                                    <i class="fa fa-edit"></i> Edit
                                </a>
                            </div>
                        </div>

                        <table class="table table-bordered mb-4">
                            <tbody>
                                <tr><th width="220">ID</th><td>{{ $customer->id }}</td></tr>
                                <tr><th>Name</th><td>{{ $customer->name }}</td></tr>
                                <tr><th>Mobile Number</th><td>{{ $customer->mobile_number }}</td></tr>
                                <tr><th>Verified Status</th><td>{{ ucfirst($customer->verified_status) }}</td></tr>
                                <tr><th>Status</th><td>{{ $customer->is_active ? 'Active' : 'Inactive' }}</td></tr>
                                <tr><th>Addresses</th><td>{{ $customer->addresses_count }}</td></tr>
                                <tr><th>Orders</th><td>{{ $customer->orders_count }}</td></tr>
                                <tr><th>Wishlist Items</th><td>{{ $customer->wishlists_count }}</td></tr>
                                <tr><th>Created By</th><td>{{ $customer->createdBy ? $customer->createdBy->admin_username : '-' }}</td></tr>
                                <tr><th>Created Date</th><td>{{ $customer->created_date ? date('d-m-Y H:i', strtotime($customer->created_date)) : '-' }}</td></tr>
                                <tr><th>Updated By</th><td>{{ $customer->updatedBy ? $customer->updatedBy->admin_username : '-' }}</td></tr>
                                <tr><th>Updated Date</th><td>{{ $customer->updated_date ? date('d-m-Y H:i', strtotime($customer->updated_date)) : '-' }}</td></tr>
                            </tbody>
                        </table>

                        <h5 class="mb-3">Addresses</h5>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Address</th>
                                        <th>Location</th>
                                        <th>Pincode</th>
                                        <th>Landmark</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($customer->addresses as $address)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                {{ $address->address_line_1 }}
                                                @if($address->address_line_2)
                                                    <br>{{ $address->address_line_2 }}
                                                @endif
                                            </td>
                                            <td>{{ $address->location }}</td>
                                            <td>{{ $address->pincode }}</td>
                                            <td>{{ $address->landmark ?? '-' }}</td>
                                            <td>{{ $address->is_active ? 'Active' : 'Inactive' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center">No addresses found</td>
                                        </tr>
                                    @endforelse
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
