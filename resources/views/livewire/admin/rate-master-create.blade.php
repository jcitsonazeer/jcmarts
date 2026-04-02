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

                        <div
                            id="rate-master-client-error"
                            class="alert alert-danger d-none"
                            role="alert"
                        ></div>

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('admin.rate-masters.store') }}" id="rate-master-create-form">
                            @csrf

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Product <span class="text-danger">*</span></label>
                                    <div class="position-relative" wire:click.outside="closeProductDropdown">
                                        <input
                                            type="text"
                                            class="form-control"
                                            placeholder="Search product..."
                                            autocomplete="off"
                                            name="product_search"
                                            value="{{ $product_search }}"
                                            wire:model.live="product_search"
                                            wire:input="clearProductSelection"
                                            wire:focus="openProductDropdown"
                                            wire:keydown.escape="closeProductDropdown"
                                            wire:blur="resolveProductSelection"
                                            required
                                        >
                                        <input type="hidden" name="product_id" value="{{ $product_id }}" wire:model.live="product_id" required>

                                        @if($product_dropdown_open)
                                            <div class="list-group position-absolute w-100 mt-1" style="z-index: 1000; max-height: 240px; overflow: auto;">
                                                @forelse($product_results as $productOption)
                                                    <button
                                                        type="button"
                                                        class="list-group-item list-group-item-action"
                                                        wire:click="selectProduct({{ $productOption['id'] }}, @js($productOption['label']))"
                                                    >
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span>{{ $productOption['label'] }}</span>
                                                            <small class="text-muted">#{{ $productOption['id'] }}</small>
                                                        </div>
                                                    </button>
                                                @empty
                                                    <div class="list-group-item text-muted">No matching products found.</div>
                                                @endforelse
                                            </div>
                                        @endif
                                    </div>
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
                                                <th>Sold Out <span class="text-danger">*</span></th>
                                                <th>Stock Dependent <span class="text-danger">*</span></th>
                                                <th>Status <span class="text-danger">*</span></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if (empty($primary_uom))
                                                <tr>
                                                    <td colspan="9" class="text-center text-muted">Select Primary UOM to load secondary rows.</td>
                                                </tr>
                                            @elseif (empty($rate_rows))
                                                <tr>
                                                    <td colspan="9" class="text-center text-muted">No secondary UOM found for selected primary UOM.</td>
                                                </tr>
                                            @else
                                                @foreach($rate_rows as $index => $row)
                                                    <tr data-existing-rate="{{ !empty($row['already_exists']) ? '1' : '0' }}">
                                                        <td>
                                                            <strong>{{ $row['secondary_uom'] }}</strong>
                                                            @if(!empty($row['already_exists']))
                                                                <div class="text-info small mt-1">Existing rate already saved for this product and UOM.</div>
                                                            @endif
                                                            @if(empty($row['already_exists']))
                                                                <input type="hidden" name="rate_rows[{{ $index }}][uom_id]" value="{{ $row['uom_id'] }}">
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if(!empty($row['already_exists']))
                                                                <div class="form-control bg-light">{{ $row['cost_price'] }}</div>
                                                            @else
                                                                <input
                                                                    type="number"
                                                                    step="0.01"
                                                                    min="0"
                                                                    class="form-control"
                                                                    wire:model.live="rate_rows.{{ $index }}.cost_price"
                                                                    name="rate_rows[{{ $index }}][cost_price]"
                                                                >
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if(!empty($row['already_exists']))
                                                                <div class="form-control bg-light">{{ $row['selling_price'] }}</div>
                                                            @else
                                                                <input
                                                                    type="number"
                                                                    step="0.01"
                                                                    min="0"
                                                                    class="form-control"
                                                                    wire:model.live="rate_rows.{{ $index }}.selling_price"
                                                                    name="rate_rows[{{ $index }}][selling_price]"
                                                                >
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if(!empty($row['already_exists']))
                                                                <div class="form-control bg-light">{{ $row['offer_percentage'] }}</div>
                                                            @else
                                                                <input
                                                                    type="number"
                                                                    step="0.01"
                                                                    min="0"
                                                                    max="100"
                                                                    class="form-control"
                                                                    wire:model.live="rate_rows.{{ $index }}.offer_percentage"
                                                                    name="rate_rows[{{ $index }}][offer_percentage]"
                                                                >
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if(!empty($row['already_exists']))
                                                                <div class="form-control bg-light">{{ $row['offer_price'] }}</div>
                                                            @else
                                                                <input
                                                                    type="number"
                                                                    step="0.01"
                                                                    min="0"
                                                                    class="form-control"
                                                                    wire:model.live="rate_rows.{{ $index }}.offer_price"
                                                                    name="rate_rows[{{ $index }}][offer_price]"
                                                                >
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if(!empty($row['already_exists']))
                                                                <div class="form-control bg-light">{{ $row['final_price'] }}</div>
                                                            @else
                                                                <input
                                                                    type="number"
                                                                    step="0.01"
                                                                    min="0"
                                                                    class="form-control"
                                                                    value="{{ $row['final_price'] }}"
                                                                    name="rate_rows[{{ $index }}][final_price]"
                                                                    readonly
                                                                >
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if(!empty($row['already_exists']))
                                                                <div class="form-control bg-light">{{ $row['soldout_status'] }}</div>
                                                            @else
                                                                <select
                                                                    class="form-control"
                                                                    wire:model.live="rate_rows.{{ $index }}.soldout_status"
                                                                    name="rate_rows[{{ $index }}][soldout_status]"
                                                                    required
                                                                >
                                                                    <option value="NO">NO</option>
                                                                    <option value="YES">YES</option>
                                                                </select>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if(!empty($row['already_exists']))
                                                                <div class="form-control bg-light">{{ $row['stock_dependent'] }}</div>
                                                            @else
                                                                <select
                                                                    class="form-control"
                                                                    wire:model.live="rate_rows.{{ $index }}.stock_dependent"
                                                                    name="rate_rows[{{ $index }}][stock_dependent]"
                                                                    required
                                                                >
                                                                    <option value="NO">NO</option>
                                                                    <option value="YES">YES</option>
                                                                </select>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if(!empty($row['already_exists']))
                                                                <div class="form-control bg-light">{{ (string) $row['is_active'] === '1' ? 'Active' : 'Inactive' }}</div>
                                                            @else
                                                                <select
                                                                    class="form-control"
                                                                    wire:model.live="rate_rows.{{ $index }}.is_active"
                                                                    name="rate_rows[{{ $index }}][is_active]"
                                                                    required
                                                                >
                                                                    <option value="1">Active</option>
                                                                    <option value="0">Inactive</option>
                                                                </select>
                                                            @endif
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('rate-master-create-form');
    const errorBox = document.getElementById('rate-master-client-error');
    const errorDisplayDuration = 5000;
    let errorHideTimeoutId = null;
    let errorLockedUntil = 0;

    if (!form || !errorBox) {
        return;
    }

    const hideError = function (force = false) {
        if (!force && Date.now() < errorLockedUntil) {
            return;
        }

        window.clearTimeout(errorHideTimeoutId);
        errorHideTimeoutId = null;
        errorBox.classList.add('d-none');
        errorBox.textContent = '';
    };

    const showError = function (message) {
        errorLockedUntil = Date.now() + errorDisplayDuration;
        errorBox.textContent = message;
        errorBox.classList.remove('d-none');

        if (window.jQuery) {
            window.jQuery(errorBox).stop(true, true).show();
        }

        window.clearTimeout(errorHideTimeoutId);
        errorHideTimeoutId = window.setTimeout(function () {
            hideError(true);
        }, errorDisplayDuration);
    };

    const observer = new MutationObserver(function () {
        if (!errorBox.textContent || Date.now() >= errorLockedUntil) {
            return;
        }

        const isHidden =
            errorBox.classList.contains('d-none') ||
            errorBox.style.display === 'none';

        if (isHidden) {
            errorBox.classList.remove('d-none');

            if (window.jQuery) {
                window.jQuery(errorBox).stop(true, true).show();
            } else {
                errorBox.style.display = '';
            }
        }
    });

    observer.observe(errorBox, {
        attributes: true,
        attributeFilter: ['class', 'style'],
    });

    form.addEventListener('submit', function (event) {
        hideError(true);

        const rows = form.querySelectorAll('tbody tr');

        for (const row of rows) {
            const costInput = row.querySelector('input[name*="[cost_price]"]');
            const sellingInput = row.querySelector('input[name*="[selling_price]"]');
            const isExistingRate = row.getAttribute('data-existing-rate') === '1';

            if (!costInput || !sellingInput) {
                continue;
            }

            const costValue = costInput.value.trim();
            const sellingValue = sellingInput.value.trim();

            if (costValue === '' && sellingValue === '') {
                continue;
            }

            if (isExistingRate) {
                continue;
            }

            const costPrice = parseFloat(costValue || '0');
            const sellingPrice = parseFloat(sellingValue || '0');

            if (!Number.isNaN(costPrice) && !Number.isNaN(sellingPrice) && sellingPrice < costPrice) {
                event.preventDefault();
                showError('Selling price should be greater than or equal to cost price.');
                errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                sellingInput.focus();
                return;
            }
        }
    });
});
</script>
