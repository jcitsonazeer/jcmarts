<div id="cart" class="btn-group btn-block cart_block">
    <button
        type="button"
        class="btn btn-inverse btn-block btn-lg"
        onclick="window.location.href='{{ route('frontend.cart') }}';">

        <span class="cart-total"> {{ $itemCount }}</span>
        <span class="cart-total-res">{{ $itemCount }}</span>
    </button>

    <a href="{{ route('frontend.cart') }}" class="addtocart btn">
        <span class="cart-text"></span>
        <span class="cart-total-res">{{ $itemCount }}</span>
    </a>
</div>
