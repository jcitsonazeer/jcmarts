@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title">Select Display Rate</h4>
                            <a href="{{ route('admin.selected-display.index') }}" class="btn btn-secondary">
                                Back
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

                        @php($productImage = $product->product_image ? asset('storage/product/' . $product->product_image) : asset('assets/admin/images/no_image.png'))
                        <div class="d-flex align-items-center mb-4">
                            <img src="{{ $productImage }}" alt="{{ $product->product_name }}" style="height: 70px; width: 70px; object-fit: cover;" class="mr-3">
                            <h5 class="mb-0">{{ $product->product_name }}</h5>
                        </div>

                        <form method="POST" action="{{ route('admin.selected-display.update', $product->id) }}">
                            @csrf

                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>Select</th>
                                            <th>ID</th>
                                            <th>UOM</th>
                                            <th>Cost Price</th>
                                            <th>Selling Price</th>
                                            <th>Offer %</th>
                                            <th>Final Price</th>
                                            <th>Stock</th>
                                            <th>Current</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($rates as $rate)
                                            <tr>
                                                <td>
                                                    <input type="radio"
                                                           name="selected_rate_id"
                                                           value="{{ $rate->id }}"
                                                           {{ old('selected_rate_id', $rates->firstWhere('selected_display', 1)?->id) == $rate->id ? 'checked' : '' }}>
                                                </td>
                                                <td>{{ $rate->id }}</td>
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
                                                    @if((int) $rate->selected_display === 1)
                                                        <span class="badge badge-success">Selected</span>
                                                    @else
                                                        <span class="badge badge-secondary">No</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @error('selected_rate_id')
                                <div class="text-danger mb-3">{{ $message }}</div>
                            @enderror

                            <button type="submit" class="btn btn-primary">Submit</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
