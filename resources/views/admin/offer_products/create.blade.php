@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">Add Offer Product</h4>
                            <a href="{{ route('admin.offer-products.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back to Offer Products
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

                        @if (session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('admin.offer-products.store') }}">
                            @csrf

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Offer Name <span class="text-danger">*</span></label>
                                    <select name="offer_id" class="form-control" required>
                                        <option value="">Select Offer</option>
                                        @foreach(($offers ?? collect()) as $offer)
                                            <option value="{{ $offer->id }}" {{ (string) old('offer_id') === (string) $offer->id ? 'selected' : '' }}>
                                                {{ $offer->offer_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
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
                                        @foreach(($products ?? collect()) as $product)
                                            <option value="{{ $product->product_name }}"></option>
                                        @endforeach
                                    </datalist>
                                    @error('product_name')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Status <span class="text-danger">*</span></label>
                                    <select name="is_active" class="form-control" required>
                                        <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary mr-2"><i class="fa fa-save"></i> Submit</button>
                                    <button type="reset" class="btn btn-warning mr-2"><i class="fa fa-refresh"></i> Reset</button>
                                    <a href="{{ route('admin.offer-products.index') }}" class="btn btn-secondary"><i class="fa fa-times"></i> Cancel</a>
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
