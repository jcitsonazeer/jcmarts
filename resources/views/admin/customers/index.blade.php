@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title">Customer Management</h4>
                            <a href="{{ route('admin.customers.create') }}" class="btn btn-primary">
                                <i class="fa fa-plus"></i> Add Customer
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

                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        <form method="GET" action="{{ route('admin.customers.index') }}" class="mb-3 js-auto-search-form">
                            <div class="form-group row align-items-end mb-0">
                                <div class="col-md-6">
                                    <label>Filter Customer</label>
                                    <input type="text" name="search" class="form-control js-auto-search-input" value="{{ $search ?? '' }}" placeholder="Search name, mobile, or status" autocomplete="off">
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Mobile</th>
                                        <th>Verified</th>
                                        <th>Status</th>
                                        <th>Orders</th>
                                        <th>Created Date</th>
                                        <th width="160">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($customers as $customer)
                                        <tr>
                                            <td>{{ $customers->firstItem() + $loop->index }}</td>
                                            <td>{{ $customer->name }}</td>
                                            <td>{{ $customer->mobile_number }}</td>
                                            <td>
                                                @if($customer->verified_status === 'verified')
                                                    <span class="badge badge-success">Verified</span>
                                                @elseif($customer->verified_status === 'rejected')
                                                    <span class="badge badge-danger">Rejected</span>
                                                @else
                                                    <span class="badge badge-warning">Pending</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($customer->is_active)
                                                    <span class="badge badge-success">Active</span>
                                                @else
                                                    <span class="badge badge-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td>{{ $customer->orders_count }}</td>
                                            <td>{{ $customer->created_date ? date('d-m-Y H:i', strtotime($customer->created_date)) : '-' }}</td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.customers.show', $customer->id) }}" class="btn btn-info btn-sm" title="View">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('admin.customers.edit', $customer->id) }}" class="btn btn-warning btn-sm" title="Edit">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('admin.customers.destroy', $customer->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm" title="Delete"
                                                                onclick="return confirm('Are you sure you want to delete this customer?')">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center">No customers found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($customers->hasPages())
                            <div class="mt-3 d-flex justify-content-center">
                                {{ $customers->links('pagination::bootstrap-4') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
