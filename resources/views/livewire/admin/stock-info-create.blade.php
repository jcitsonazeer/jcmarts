<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">Add Stock</h4>
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

                        <form wire:submit.prevent="save">

                            <div class="form-group">
                                <label>Product &amp; Weight <span class="text-danger">*</span></label>
                                <div class="position-relative" wire:click.outside="closeRateDropdown">
                                    <input
                                        type="text"
                                        class="form-control"
                                        placeholder="Search product & weight..."
                                        autocomplete="off"
                                        wire:model.live="rate_search"
                                        wire:input="clearRateSelection"
                                        wire:focus="openRateDropdown"
                                        wire:keydown.escape="closeRateDropdown"
                                        wire:blur="resolveRateSelection"
                                    >
                                    <input type="hidden" id="rate-master-id" name="rate_master_id" wire:model.live="rate_master_id" required>
                                    <small class="form-text text-muted">Select a product from the dropdown list.</small>

                                    @if($rate_dropdown_open)
                                        <div class="list-group position-absolute w-100 mt-1" style="z-index: 1000; max-height: 240px; overflow: auto;">
                                            @forelse($rate_results as $rateOption)
                                                <button
                                                    type="button"
                                                    class="list-group-item list-group-item-action"
                                                    wire:click="selectRate({{ $rateOption['id'] }}, @js($rateOption['label']))"
                                                >
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span>{{ $rateOption['label'] }}</span>
                                                        <small class="text-muted">#{{ $rateOption['id'] }}</small>
                                                    </div>
                                                </button>
                                            @empty
                                                <div class="list-group-item text-muted">No matching products found.</div>
                                            @endforelse
                                        </div>
                                    @endif
                                </div>
                            </div>

                            @if(!empty($rateDetails))
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h6 class="mb-3">Rate Details</h6>
                                        <div class="row">
                                            <div class="col-md-6 mb-2"><strong>Product:</strong> {{ $rateDetails['product_name'] }}</div>
                                            <div class="col-md-6 mb-2"><strong>UOM:</strong> {{ $rateDetails['uom_label'] }}</div>
                                            <div class="col-md-6 mb-2"><strong>Cost Price:</strong> {{ $rateDetails['cost_price'] }}</div>
                                            <div class="col-md-6 mb-2"><strong>Selling Price:</strong> {{ $rateDetails['selling_price'] }}</div>
                                            <div class="col-md-6 mb-2"><strong>Offer %:</strong> {{ $rateDetails['offer_percentage'] }}</div>
                                            <div class="col-md-6 mb-2"><strong>Final Price:</strong> {{ $rateDetails['final_price'] }}</div>
                                        </div>
                                        <div class="mt-2">
                                            <strong>Current Stock:</strong> {{ $current_stock }}
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="form-group">
                                <label>Add Stock <span class="text-danger">*</span></label>
                                <input type="number" min="1" name="stock_in_count" class="form-control" wire:model.live="stock_in_count" required>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary mr-2"><i class="fa fa-save"></i> Submit</button>
                                <button type="reset" class="btn btn-warning mr-2"><i class="fa fa-refresh"></i> Reset</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
