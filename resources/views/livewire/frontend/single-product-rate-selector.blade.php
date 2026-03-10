@php
    $selectedRate = $this->selectedRate;
    $displayPrice = (float) ($selectedRate['display_price'] ?? 0);
    $sellingPrice = (float) ($selectedRate['selling_price'] ?? 0);
    $isSoldOut = $this->isSoldOut;
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
            <input
                type="number"
                min="1"
                step="1"
                wire:model.live="quantity"
                id="input-quantity"
                class="form-control"
            >
            @if($isSoldOut)
                <div class="text-danger" style="margin-top: 10px;">Sold out</div>
            @else
                <button
                    type="button"
                    id="button-cart"
                    wire:click="addToCart"
                    class="btn btn-primary btn-lg btn-block addtocart"
                >
                    Add
                </button>
            @endif
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
