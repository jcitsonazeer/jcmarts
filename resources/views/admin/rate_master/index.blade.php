@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title">Rate Master</h4>
                            <a href="{{ route('admin.rate-masters.create') }}" class="btn btn-primary">
                                <i class="fa fa-plus"></i> Add Rate
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

                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Product</th>
                                        <th>UOM</th>
                                        <th>Cost Price</th>
                                        <th>Selling Price</th>
                                        <th>Offer %</th>
                                        <th>Final Price</th>
                                        <th>Stock</th>
                                        <th width="160">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($rates as $rate)
                                        <tr>
                                            <td>{{ $rate->id }}</td>
                                            <td>{{ $rate->product ? $rate->product->product_name : '-' }}</td>
                                            <td>
                                                @if($rate->uom)
                                                    {{ $rate->uom->primary_uom }} - {{ $rate->uom->secondary_uom }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>{{ $rate->cost_price }}</td>
                                            <td>{{ $rate->selling_price }}</td>
                                            <td>{{ $rate->offer_percentage }}</td>
                                            <td>{{ $rate->final_price }}</td>
                                            <td>{{ $rate->stock_qty }}</td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.rate-masters.show', $rate->id) }}" class="btn btn-info btn-sm" title="View">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('admin.rate-masters.edit', $rate->id) }}" class="btn btn-warning btn-sm" title="Edit">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('admin.rate-masters.destroy', $rate->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm" title="Delete"
                                                                onclick="return confirm('Are you sure you want to delete this rate?')">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center">No rates found</td>
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
