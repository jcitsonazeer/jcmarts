<button type="button"
        class="wishlist wishlist-toggle {{ $isInWishlist ? 'active' : '' }}"
        wire:click="toggle"
        aria-label="Wishlist">
  <i class="fa fa-heart" data-aos="flip-left"    data-aos-offset="150" data-aos-duration="3000"></i>
</button>
