<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">Stock History</h4>
                            <a href="{{ route('admin.stock-infos.create') }}" class="btn btn-primary">
                                <i class="fa fa-plus"></i> Add Stock
                            </a>
                        </div>

                        <div class="form-group">
                            <label>Product &amp; Weight <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                id="stock-history-input"
                                class="form-control"
                                list="stock-history-list"
                                placeholder="Select or type to search..."
                            >
                            <datalist id="stock-history-list">
                                @foreach($rateOptions as $rateOption)
                                    <option value="{{ $rateOption['label'] }}" data-id="{{ $rateOption['id'] }}"></option>
                                @endforeach
                            </datalist>
                            <input type="hidden" id="stock-history-id" wire:model.live="rate_master_id">
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Product</th>
                                        <th>UOM</th>
                                        <th>Stock In</th>
                                        <th>Sale Qty</th>
                                        <th>Current Stock</th>
                                        <th>Sale Order</th>
                                        <th>Created Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($history as $row)
                                        <tr>
                                            <td>{{ $row['id'] }}</td>
                                            <td>{{ $row['rate']['product']['product_name'] ?? '-' }}</td>
                                            <td>
                                                @if(!empty($row['rate']['uom']))
                                                    {{ $row['rate']['uom']['primary_uom'] ?? '-' }} - {{ $row['rate']['uom']['secondary_uom'] ?? '-' }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>{{ $row['stock_in_count'] }}</td>
                                            <td>{{ $row['sale_quantity'] }}</td>
                                            <td>{{ $row['current_stock'] }}</td>
                                            <td>{{ $row['sale_order_id'] ?? '-' }}</td>
                                            <td>{{ !empty($row['created_date']) ? date('d-m-Y H:i', strtotime($row['created_date'])) : '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center">No stock history found</td>
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

@push('scripts')
<script>
    (function () {
        function bindStockHistoryInput() {
            var input = document.getElementById('stock-history-input');
            var hidden = document.getElementById('stock-history-id');
            var list = document.getElementById('stock-history-list');
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
            bindStockHistoryInput();
            if (window.Livewire && typeof window.Livewire.hook === 'function') {
                try {
                    window.Livewire.hook('message.processed', function () {
                        bindStockHistoryInput();
                    });
                } catch (e) {}
            }
        });
    })();
</script>
@endpush
