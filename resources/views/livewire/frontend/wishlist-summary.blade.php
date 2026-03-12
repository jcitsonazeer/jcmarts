<div class="header-wishlist">
  <a href="{{ $isLoggedIn ? route('frontend.wishlist') : route('frontend.login') }}" class="wishlist-btn" aria-label="Wishlist">
    <span class="wishlist-count">{{ $itemCount }}</span>
    <i class="fa fa-heart" aria-hidden="true"></i>
  </a>
</div>
