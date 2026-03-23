@php
  $defaultImage = asset('assets/frontend/images/no_image.png');
@endphp

<div class="wishlist page_wishlist orders-theme order-page-wrapper container">
  <div class="order-details" style="width:50%;margin:auto">
    <div class="top-row">
      <h3>My Wishlist</h3>
    </div>

    @if(($items ?? collect())->isEmpty())
      <div class="alert alert-info">No items in your wishlist.</div>
    @else
      <div class="product-list">
        @foreach($items as $item)
          @php
            $product = $item->product;
            $productImage = $product && !empty($product->product_image)
              ? asset('storage/product/' . $product->product_image)
              : $defaultImage;
            $rate = $product?->rates?->first();
            $selling = (float) ($rate->selling_price ?? 0);
            $final = (float) ($rate->final_price ?? 0);
            $shown = $final > 0 ? $final : $selling;
          @endphp
          <div class="product-card">
            <div class="cart-left">
              <a href="{{ route('frontend.single_product', ['product_id' => $product?->id]) }}">
                <img src="{{ $productImage }}"
                     alt="{{ $product?->product_name }}"
                     title="{{ $product?->product_name }}"
                     class="cart-thumb"
                     onerror="this.onerror=null;this.src='{{ $defaultImage }}';">
              </a>
              <div>
                <div class="title">
                  <a href="{{ route('frontend.single_product', ['product_id' => $product?->id]) }}">
                    {{ $product?->product_name ?? 'Product' }}
                  </a>
                </div>
                <div class="meta">Price</div>
              </div>
            </div>
            <div class="cart-right">
              <div class="price">&#8377;{{ number_format($shown, 2) }}</div>
              <button type="button" class="btn btn-danger btn-xs" wire:click="removeItem({{ $item->id }})">
                Remove
              </button>
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </div>
</div>
