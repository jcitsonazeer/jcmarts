<div id="checkout-cart" class="container order-page-wrapper orders-theme">
  <ul class="breadcrumb">
    <h1>Shopping Cart &nbsp;({{ $itemCount }} items)</h1>
    <li><a href="{{ route('frontend.home') }}"><i class="fa fa-home"></i></a></li>
    <li><a href="{{ route('frontend.cart') }}">Shopping Cart</a></li>
  </ul>

  <div class="row">
    <div id="content" class="col-sm-12 checkout">
      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif

      <div class="row">
        <div class="col-xs-12 col-sm-8">
          <div class="order-sidebar">
            <h3>Cart Items</h3>
            <div class="product-list cart-list">
              @forelse($cartItems as $cartItem)
                @php
                  $product = $cartItem->product;
                  $rate = $cartItem->rate;
                  $uom = $rate?->uom;
                  $packetSize = trim(($uom->secondary_uom ?? '') . ' ' . ($uom->primary_uom ?? ''));
                  $defaultImage = asset('assets/frontend/images/no_image.png');
                  $productImage = !empty($product?->product_image) ? asset('storage/product/' . $product->product_image) : $defaultImage;
                  $unitPrice = (float) $cartItem->unit_price;
                  $lineTotal = $unitPrice * (int) $cartItem->quantity;
                @endphp
                <div class="product-card" wire:key="cart-row-{{ $cartItem->id }}">
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
                          {{ $product?->product_name }}
                        </a>
                      </div>
                      <div class="meta">Packet Size: {{ $packetSize !== '' ? $packetSize : 'N/A' }}</div>
                      <div class="meta">Unit Price: &#8377;{{ number_format($unitPrice, 2) }}</div>
                    </div>
                  </div>
                  <div class="cart-right">
                    <div class="qty-control">
                      <input
                        type="number"
                        min="1"
                        class="form-control"
                        wire:model.change="quantities.{{ $cartItem->id }}"
                        wire:change="updateQuantity({{ $cartItem->id }})"
                        onkeydown="return event.key === 'ArrowUp' || event.key === 'ArrowDown'"
                        onpaste="return false"
                        ondrop="return false"
                        onwheel="this.blur()"
                      >
                      <button
                        type="button"
                        class="btn btn-danger delete"
                        wire:click="removeItem({{ $cartItem->id }})"
                      >
                        <i class="fa fa-times-circle"></i>
                      </button>
                    </div>
                    <div class="price">&#8377;{{ number_format($lineTotal, 2) }}</div>
                  </div>
                </div>
              @empty
                <div class="text-center">Your cart is empty.</div>
              @endforelse
            </div>
          </div>
        </div>

        <div class="col-xs-12 col-sm-4">
          <div class="order-details">
            <h3>Price Details</h3>
            <div class="amount-card">
              <div class="amount-row">
                <div class="label">Sub-Total</div>
                <div class="value">&#8377;{{ number_format($subTotal, 2) }}</div>
              </div>
              <div class="amount-row">
                <div class="label">Total</div>
                <div class="value">&#8377;{{ number_format($total, 0) }}</div>
              </div>
            </div>

            <div class="cart-actions">
              <a href="{{ route('frontend.products') }}" class="btn btn-default">Continue Shopping</a>
              @if($itemCount > 0)
                <a href="{{ route('frontend.checkout') }}" class="btn btn-primary">Checkout</a>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
