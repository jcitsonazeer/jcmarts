@include('frontend.header')
<div class="content-top-breadcum"></div>

<div id="checkout-cart" class="container">
  <ul class="breadcrumb">
    <h1>Shopping Cart &nbsp;({{ $itemCount ?? 0 }} items)</h1>
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
          <div class="table-responsive">
            <table class="table table-bordered">
              <thead>
                <tr>
                  <td class="text-center">Image</td>
                  <td class="text-left">Product Name</td>
                  <td class="text-left">Model</td>
                  <td class="text-left">Quantity</td>
                  <td class="text-right">Unit Price</td>
                  <td class="text-right">Total</td>
                </tr>
              </thead>
              <tbody>
                @forelse(($cartItems ?? collect()) as $cartItem)
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
                  <tr>
                    <td class="text-center">
                      <a href="{{ route('frontend.single_product', ['product_id' => $product?->id]) }}">
                        <img src="{{ $productImage }}" alt="{{ $product?->product_name }}" title="{{ $product?->product_name }}" class="img-thumbnail" style="max-width:70px;" onerror="this.onerror=null;this.src='{{ $defaultImage }}';">
                      </a>
                    </td>
                    <td class="text-left">
                      <a href="{{ route('frontend.single_product', ['product_id' => $product?->id]) }}">{{ $product?->product_name }}</a><br>
                      <small>Packet Size: {{ $packetSize !== '' ? $packetSize : 'N/A' }}</small>
                    </td>
                    <td class="text-left">Product {{ $product?->id }}</td>
                    <td class="text-left">
                      <div class="input-group btn-block" style="max-width: 220px;">
                        <form action="{{ route('frontend.cart.update', $cartItem->id) }}" method="POST" style="display:flex; width:100%;">
                          @csrf
                          <input type="number" name="quantity" value="{{ (int) $cartItem->quantity }}" min="1" class="form-control">
                          <span class="input-group-btn" style="display:flex;">
                            <button type="submit" class="btn btn-primary refresh"><i class="fa fa-refresh"></i></button>
                          </span>
                        </form>
                        <form action="{{ route('frontend.cart.remove', $cartItem->id) }}" method="POST" style="margin-left: 4px;">
                          @csrf
                          <button type="submit" class="btn btn-danger delete"><i class="fa fa-times-circle"></i></button>
                        </form>
                      </div>
                    </td>
                    <td class="text-right">&#8377;{{ number_format($unitPrice, 2) }}</td>
                    <td class="text-right">&#8377;{{ number_format($lineTotal, 2) }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6" class="text-center">Your cart is empty.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        <div class="col-xs-12 col-sm-4">
          <table class="table table-bordered grand-total">
            <tbody>
              <tr>
                <td class="text-right"><strong>Sub-Total:</strong></td>
                <td class="text-right">&#8377;{{ number_format((float) ($subTotal ?? 0), 2) }}</td>
              </tr>
              <tr>
                <td class="text-right"><strong>GST (10%):</strong></td>
                <td class="text-right">&#8377;{{ number_format((float) ($gst ?? 0), 2) }}</td>
              </tr>
              <tr>
                <td class="text-right"><strong>Total:</strong></td>
                <td class="text-right">&#8377;{{ number_format((float) ($total ?? 0), 2) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="buttons clearfix">
        <div class="pull-left"><a href="{{ route('frontend.products') }}" class="btn btn-default">Continue Shopping</a></div>
        <div class="pull-right"><a href="{{ route('frontend.checkout') }}" class="btn btn-primary">Checkout</a></div>
      </div>
    </div>
  </div>
</div>

@include('frontend.footer')
