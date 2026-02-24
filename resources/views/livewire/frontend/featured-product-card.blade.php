@php
  $defaultImage = asset('assets/frontend/images/no_image.png');
  $productImage = !empty($product->product_image) ? asset('storage/product/' . $product->product_image) : $defaultImage;
  $rate = $this->selectedRate;
  $sellingPrice = (float) ($rate['selling_price'] ?? 0);
  $finalPrice = (float) ($rate['final_price'] ?? 0);
  $offer = (float) ($rate['offer_percentage'] ?? 0);
  $shown = (float) $this->shownPrice;
@endphp

<div class=" product-items ">
  <div class="product-thumb transition">
    @if($offer > 0)
      <p class="tag">{{ (int) round($offer) }}<br> % <br> <i>off</i></p>
    @endif

    <div class="image">
      <div class="first_image">
        <a href="{{ route('frontend.single_product', ['product_id' => $product->id]) }}">
          <img src="{{ $productImage }}" alt="{{ $product->product_name }}" title="{{ $product->product_name }}" class="img-responsive" onerror="this.onerror=null;this.src='{{ $defaultImage }}';">
        </a>
      </div>
    </div>

    <div class="product-details">
      <div class="caption">
        <h4><a href="{{ route('frontend.single_product', ['product_id' => $product->id]) }}">{{ $product->product_name }}</a></h4>
        <div class="model">Product {{ $product->id }}</div>

        <p class="price">
          <span class="price-new">&#8377;{{ number_format($shown, 2) }}</span>
          @if($sellingPrice > $finalPrice && $finalPrice > 0)
            <span class="price-old">&#8377;{{ number_format($sellingPrice, 2) }}</span>
          @endif
          <span class="price-tax">Ex Tax: &#8377;{{ number_format($shown, 2) }}</span>
        </p>

        <div id="product-{{ $product->id }}" class="product_option">
          <div class="form-group">
            <select class="form-control" wire:model.live="selectedRateId">
              @foreach($rates as $rateOption)
                <option value="{{ $rateOption['id'] }}">{{ $rateOption['label'] }}</option>
              @endforeach
            </select>
          </div>

          <div class="input-group col-xs-12 col-sm-12 button-group">
            <label class="control-label col-sm-2 col-xs-2">Qty</label>
            <input type="number" name="quantity" min="1" value="1" size="1" step="1" class="qty form-control col-sm-2 col-xs-9">
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <button type="button" class="addtocart col-sm-4 pull-right">Add</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
