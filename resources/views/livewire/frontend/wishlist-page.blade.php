@php
  $defaultImage = asset('assets/frontend/images/no_image.png');
@endphp

<div class="wishlist page_wishlist">
  <h2>My Wishlist</h2>

  @if(($items ?? collect())->isEmpty())
    <div class="alert alert-info">No items in your wishlist.</div>
  @else
    <div class="table-responsive">
      <table class="table table-bordered">
        <thead>
        <tr>
          <td>Image</td>
          <td>Product</td>
          <td>Price</td>
          <td>Action</td>
        </tr>
        </thead>
        <tbody>
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
          <tr>
            <td style="width:120px;">
              <a href="{{ route('frontend.single_product', ['product_id' => $product?->id]) }}">
                <img src="{{ $productImage }}"
                     alt="{{ $product?->product_name }}"
                     title="{{ $product?->product_name }}"
                     class="img-responsive"
                     onerror="this.onerror=null;this.src='{{ $defaultImage }}';">
              </a>
            </td>
            <td>
              <a href="{{ route('frontend.single_product', ['product_id' => $product?->id]) }}">
                {{ $product?->product_name ?? 'Product' }}
              </a>
            </td>
            <td>
              &#8377;{{ number_format($shown, 2) }}
            </td>
            <td>
              <button type="button" class="btn btn-danger btn-xs" wire:click="removeItem({{ $item->id }})">
                Remove
              </button>
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
