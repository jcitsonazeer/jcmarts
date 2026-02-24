@php
    $selectedRate = $this->selectedRate;
    $displayPrice = (float) ($selectedRate['display_price'] ?? 0);
    $sellingPrice = (float) ($selectedRate['selling_price'] ?? 0);
@endphp

<div>
    <ul class="list-unstyled price" style="margin-bottom: 15px;">
        <li class="oldprice" style="{{ $sellingPrice > $displayPrice && $displayPrice > 0 ? '' : 'display:none;' }}">
            <span class="old-price" style="text-decoration: line-through;">&#8377;{{ number_format($sellingPrice, 2) }}</span>
        </li>
        <li>
            <h2 class="special-price" style="margin: 0;">
                <span class="price-new-live">&#8377;{{ number_format($displayPrice, 2) }}</span>
            </h2>
        </li>
    </ul>

    <div id="product">
        <div class="form-group">
            <div>
                @forelse($rates as $rate)
                    <div class="single-rate-item">
                        <label class="single-rate-row">
                            <span class="single-rate-left">
                                <input type="radio"
                                       wire:model.live="selectedRateId"
                                       value="{{ $rate['id'] }}">
                                {{ $rate['label'] }}
                            </span>
                            <span class="single-rate-price">&#8377;{{ number_format((float) $rate['display_price'], 2) }}</span>
                        </label>
                    </div>
                @empty
                    <p>No rates available.</p>
                @endforelse
            </div>
        </div>

        <div class="form-group">
            <label class="control-label" for="input-quantity">Qty</label>
            <input type="text" name="quantity" value="1" size="2" id="input-quantity" class="form-control">
            <input type="hidden">
            <button type="button" id="button-cart" class="btn btn-primary btn-lg btn-block addtocart">Add</button>
        </div>
    </div>
    <style>
        .single-rate-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            margin: 0;
        }

        .single-rate-left {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .single-rate-item {
            margin-bottom: 8px;
        }

        .single-rate-price {
            margin-left: 12px;
            white-space: nowrap;
            min-width: 90px;
            text-align: right;
        }
    </style>
</div>
