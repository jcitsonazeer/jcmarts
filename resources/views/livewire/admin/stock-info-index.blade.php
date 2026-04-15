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
                                >
                                <input type="hidden" wire:model.live="rate_master_id">

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
                                    @forelse(($history ?? collect()) as $row)
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
                        @if($history && $history->hasPages())
                            <div class="mt-3 d-flex justify-content-center">
                                {{ $history->links('pagination::bootstrap-4') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
