@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title">Promo Tile Products - {{ $promoTile->promo_title }}</h4>
                            <a href="{{ route('admin.promo-tiles.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back to Promo Tiles
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

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <h5 class="mt-3 mb-2">Add Product to This Promo Tile</h5>
                        <form method="POST" action="{{ route('admin.promo-tiles.products.store', $promoTile->id) }}">
                            @csrf
                            <div class="form-group row">
                                <div class="col-md-6 col-lg-4">
                                    <label>Product <span class="text-danger">*</span></label>
                                    <input
                                        type="text"
                                        name="product_name"
                                        class="form-control"
                                        list="product-options"
                                        value="{{ old('product_name') }}"
                                        placeholder="Type or select product"
                                        autocomplete="off"
                                        required
                                    >
                                    <datalist id="product-options">
                                        @foreach(($availableProducts ?? collect()) as $product)
                                            <option value="{{ $product->product_name }}"></option>
                                        @endforeach
                                    </datalist>
                                    @error('product_name')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-6 col-lg-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> Add Product</button>
                                </div>
                            </div>
                        </form>

                        <hr>

                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Product</th>
                                        <th>Added Date</th>
                                        <th width="120">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($products as $product)
                                        <tr>
                                            <td>{{ $products->firstItem() + $loop->index }}</td>
                                            <td>{{ $product->product ? $product->product->product_name : '-' }}</td>
                                            <td>{{ $product->created_date ? date('d-m-Y H:i', strtotime($product->created_date)) : '-' }}</td>
                                            <td>
                                                <form action="{{ route('admin.promo-tiles.products.destroy', [$promoTile->id, $product->product_id]) }}"
                                                      method="POST"
                                                      class="d-inline"
                                                      onsubmit="return confirm('Remove this product from the promo tile?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Remove">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center">
                                                No products linked to this promo tile yet.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($products->hasPages())
                            <div class="mt-3 d-flex justify-content-center">
                                {{ $products->links('pagination::bootstrap-4') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection