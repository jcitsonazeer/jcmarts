<button type="button"
        class="wishlist wishlist-toggle {{ $isInWishlist ? 'active' : '' }}"
        wire:click="toggle"
        aria-label="Wishlist">
  <i class="fa fa-heart"></i>
</button>
