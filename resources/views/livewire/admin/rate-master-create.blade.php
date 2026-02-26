<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">Add Rate</h4>
                            <a href="{{ route('admin.rate-masters.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back to Rates
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

                        <form method="POST" action="{{ route('admin.rate-masters.store') }}">
                            @csrf

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Product <span class="text-danger">*</span></label>
                                    <select name="product_id" class="form-control" wire:model.live="product_id" required>
                                        <option value="">Select Product</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product['id'] }}">{{ $product['product_name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label>Primary UOM <span class="text-danger">*</span></label>
                                    <select name="primary_uom" class="form-control" wire:model.live="primary_uom" required>
                                        <option value="">Select Primary UOM</option>
                                        @foreach($primaryUoms as $primaryUom)
                                            <option value="{{ $primaryUom }}">{{ $primaryUom }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

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
                                            @if (empty($primary_uom))
                                                <tr>
                                                    <td colspan="8" class="text-center text-muted">Select Primary UOM to load secondary rows.</td>
                                                </tr>
                                            @elseif (empty($rate_rows))
                                                <tr>
                                                    <td colspan="8" class="text-center text-muted">No secondary UOM found for selected primary UOM.</td>
                                                </tr>
                                            @else
                                                @foreach($rate_rows as $index => $row)
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $row['secondary_uom'] }}</strong>
                                                            <input type="hidden" name="rate_rows[{{ $index }}][uom_id]" value="{{ $row['uom_id'] }}">
                                                        </td>
                                                        <td>
                                                            <input
                                                                type="number"
                                                                step="0.01"
                                                                min="0"
                                                                class="form-control"
                                                                wire:model.live="rate_rows.{{ $index }}.cost_price"
                                                                name="rate_rows[{{ $index }}][cost_price]"
                                                            >
                                                        </td>
                                                        <td>
                                                            <input
                                                                type="number"
                                                                step="0.01"
                                                                min="0"
                                                                class="form-control"
                                                                wire:model.live="rate_rows.{{ $index }}.selling_price"
                                                                name="rate_rows[{{ $index }}][selling_price]"
                                                            >
                                                        </td>
                                                        <td>
                                                            <input
                                                                type="number"
                                                                step="0.01"
                                                                min="0"
                                                                max="100"
                                                                class="form-control"
                                                                wire:model.live="rate_rows.{{ $index }}.offer_percentage"
                                                                name="rate_rows[{{ $index }}][offer_percentage]"
                                                            >
                                                        </td>
                                                        <td>
                                                            <input
                                                                type="number"
                                                                step="0.01"
                                                                min="0"
                                                                class="form-control"
                                                                wire:model.live="rate_rows.{{ $index }}.offer_price"
                                                                name="rate_rows[{{ $index }}][offer_price]"
                                                            >
                                                        </td>
                                                        <td>
                                                            <input
                                                                type="number"
                                                                step="0.01"
                                                                min="0"
                                                                class="form-control"
                                                                value="{{ $row['final_price'] }}"
                                                                name="rate_rows[{{ $index }}][final_price]"
                                                                readonly
                                                            >
                                                        </td>
                                                        <td>
                                                            <input
                                                                type="number"
                                                                min="0"
                                                                class="form-control"
                                                                wire:model.live="rate_rows.{{ $index }}.stock_qty"
                                                                name="rate_rows[{{ $index }}][stock_qty]"
                                                            >
                                                        </td>
                                                        <td>
                                                            <select
                                                                class="form-control"
                                                                wire:model.live="rate_rows.{{ $index }}.is_active"
                                                                name="rate_rows[{{ $index }}][is_active]"
                                                                required
                                                            >
                                                                <option value="1">Active</option>
                                                                <option value="0">Inactive</option>
                                                            </select>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary mr-2"><i class="fa fa-save"></i> Submit</button>
                                    <button type="reset" class="btn btn-warning mr-2"><i class="fa fa-refresh"></i> Reset</button>
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
