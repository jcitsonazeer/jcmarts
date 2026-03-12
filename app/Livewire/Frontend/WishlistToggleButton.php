<?php

namespace App\Livewire\Frontend;

use App\Services\WishlistService;
use Livewire\Component;

class WishlistToggleButton extends Component
{
    public int $productId;
    public bool $isInWishlist = false;

    public function mount(int $productId, WishlistService $wishlistService): void
    {
        $this->productId = $productId;

        $customerId = $wishlistService->getCustomerId();
        $this->isInWishlist = $customerId
            ? $wishlistService->isInWishlist($customerId, $this->productId)
            : false;
    }

    public function toggle(WishlistService $wishlistService)
    {
        $customerId = $wishlistService->getCustomerId();
        if (!$customerId) {
            return redirect()->route('frontend.login');
        }

        $this->isInWishlist = $wishlistService->toggle($customerId, $this->productId);
        $this->dispatch('wishlist-updated');
    }

    public function render()
    {
        return view('livewire.frontend.wishlist-toggle-button');
    }
}
