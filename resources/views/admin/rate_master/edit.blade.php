@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">Edit Rate</h4>
                            <a href="{{ route('admin.rate-masters.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back to Rates
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

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('admin.rate-masters.update', $rate->id) }}">
                            @csrf
                            @method('PUT')

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Product <span class="text-danger">*</span></label>
                                    <select name="product_id" class="form-control" required>
                                        <option value="">Select Product</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}" {{ old('product_id', $rate->product_id) == $product->id ? 'selected' : '' }}>
                                                {{ $product->product_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label>Primary UOM</label>
                                    <input type="text" class="form-control" value="{{ $rate->uom ? $rate->uom->primary_uom : '-' }}" readonly>
                                </div>
                            </div>

                            <input type="hidden" name="uom_id" value="{{ $rate->uom_id }}">

                            <div class="form-group">
                                <label class="mb-2">Secondary UOM Rates</label>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Secondary UOM</th>
                                                <th>Cost Price <span class="text-danger">*</span></th>
                                                <th>Selling Price <span class="text-danger">*</span></th>
                                                <th>Offer Percentage</th>
                                                <th>Offer Price</th>
                                                <th>Final Price</th>
                                                <th>Stock Qty <span class="text-danger">*</span></th>
                                                <th>Status <span class="text-danger">*</span></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><strong>{{ $rate->uom ? $rate->uom->secondary_uom : '-' }}</strong></td>
                                                <td><input type="number" step="0.01" min="0" id="cost_price" name="cost_price" class="form-control" value="{{ old('cost_price', $rate->cost_price) }}" required></td>
                                                <td><input type="number" step="0.01" min="0" id="selling_price" name="selling_price" class="form-control" value="{{ old('selling_price', $rate->selling_price) }}" required></td>
                                                <td><input type="number" step="0.01" min="0" max="100" id="offer_percentage" name="offer_percentage" class="form-control" value="{{ old('offer_percentage', $rate->offer_percentage) }}"></td>
                                                <td><input type="number" step="0.01" min="0" id="offer_price" name="offer_price" class="form-control" value="{{ old('offer_price', $rate->offer_price) }}"></td>
                                                <td><input type="number" step="0.01" min="0" id="final_price" name="final_price" class="form-control" value="{{ old('final_price', $rate->final_price) }}" readonly></td>
                                                <td><input type="number" min="0" id="stock_qty" name="stock_qty" class="form-control" value="{{ old('stock_qty', $rate->stock_qty) }}" required></td>
                                                <td>
                                                    <select name="is_active" class="form-control" required>
                                                        <option value="1" {{ old('is_active', $rate->is_active) == 1 ? 'selected' : '' }}>Active</option>
                                                        <option value="0" {{ old('is_active', $rate->is_active) == 0 ? 'selected' : '' }}>Inactive</option>
                                                    </select>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary mr-2"><i class="fa fa-save"></i> Update</button>
                                    <a href="{{ route('admin.rate-masters.index') }}" class="btn btn-secondary"><i class="fa fa-times"></i> Cancel</a>
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

@push('scripts')
<script>
    (function () {
        var sellingPriceInput = document.getElementById('selling_price');
        var offerPercentageInput = document.getElementById('offer_percentage');
        var offerPriceInput = document.getElementById('offer_price');
        var finalPriceInput = document.getElementById('final_price');
        var lastEditedField = 'offer_percentage';

        function toNumber(value) {
            var num = parseFloat(value);
            return isNaN(num) ? 0 : num;
        }

        function fixed2(value) {
            return (Math.round(value * 100) / 100).toFixed(2);
        }

        function recalculateFromPercentage(normalizeInput) {
            var sellingPrice = toNumber(sellingPriceInput.value);
            var offerPercentage = toNumber(offerPercentageInput.value);

            if (offerPercentage < 0) offerPercentage = 0;
            if (offerPercentage > 100) offerPercentage = 100;

            var offerPrice = (sellingPrice * offerPercentage) / 100;
            var finalPrice = sellingPrice - offerPrice;

            if (normalizeInput) {
                offerPercentageInput.value = fixed2(offerPercentage);
            }
            offerPriceInput.value = fixed2(offerPrice);
            finalPriceInput.value = fixed2(finalPrice < 0 ? 0 : finalPrice);
        }

        function recalculateFromOfferPrice(normalizeInput) {
            var sellingPrice = toNumber(sellingPriceInput.value);
            var offerPrice = toNumber(offerPriceInput.value);

            if (offerPrice < 0) offerPrice = 0;
            if (offerPrice > sellingPrice) offerPrice = sellingPrice;

            var offerPercentage = sellingPrice > 0 ? (offerPrice / sellingPrice) * 100 : 0;
            var finalPrice = sellingPrice - offerPrice;

            if (normalizeInput) {
                offerPriceInput.value = fixed2(offerPrice);
            }
            offerPercentageInput.value = fixed2(offerPercentage);
            finalPriceInput.value = fixed2(finalPrice < 0 ? 0 : finalPrice);
        }

        sellingPriceInput.addEventListener('input', function () {
            if (lastEditedField === 'offer_price') {
                recalculateFromOfferPrice(false);
            } else {
                recalculateFromPercentage(false);
            }
        });

        offerPercentageInput.addEventListener('input', function () {
            lastEditedField = 'offer_percentage';
            recalculateFromPercentage(false);
        });

        offerPriceInput.addEventListener('input', function () {
            lastEditedField = 'offer_price';
            recalculateFromOfferPrice(false);
        });

        offerPercentageInput.addEventListener('blur', function () {
            recalculateFromPercentage(true);
        });

        offerPriceInput.addEventListener('blur', function () {
            recalculateFromOfferPrice(true);
        });

        recalculateFromPercentage(true);
    })();
</script>
@endpush
