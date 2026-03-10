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

                        <form method="POST" action="{{ route('admin.stock-infos.store') }}">
                            @csrf

                            <div class="form-group">
                                <label>Product &amp; Weight <span class="text-danger">*</span></label>
                                <input
                                    type="text"
                                    id="stock-rate-input"
                                    class="form-control"
                                    list="stock-rate-list"
                                    placeholder="Select or type to search..."
                                >
                                <datalist id="stock-rate-list">
                                    @foreach($rateOptions as $rateOption)
                                        <option value="{{ $rateOption['label'] }}" data-id="{{ $rateOption['id'] }}"></option>
                                    @endforeach
                                </datalist>
                                <input type="hidden" id="stock-rate-id" name="rate_master_id" wire:model.live="rate_master_id" required>
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
                                <input type="number" min="1" name="stock_in_count" class="form-control" value="{{ old('stock_in_count') }}" required>
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

@push('scripts')
<script>
    (function () {
        function bindStockRateInput() {
            var input = document.getElementById('stock-rate-input');
            var hidden = document.getElementById('stock-rate-id');
            var list = document.getElementById('stock-rate-list');
            if (!input || !hidden || !list) return;

            if (input.dataset.bound === '1') return;
            input.dataset.bound = '1';

            input.addEventListener('input', function () {
                var value = (input.value || '').trim();
                var match = list.querySelector('option[value="' + value.replace(/"/g, '\\"') + '"]');
                if (match) {
                    hidden.value = match.dataset.id || '';
                    hidden.dispatchEvent(new Event('input', { bubbles: true }));
                } else {
                    hidden.value = '';
                    hidden.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });
        }

        document.addEventListener('livewire:initialized', function () {
            bindStockRateInput();
            if (window.Livewire && typeof window.Livewire.hook === 'function') {
                try {
                    window.Livewire.hook('message.processed', function () {
                        bindStockRateInput();
                    });
                } catch (e) {}
            }
        });
    })();
</script>
@endpush
