<?php

namespace App\Livewire\Frontend;

use App\Services\WishlistService;
use Livewire\Attributes\On;
use Livewire\Component;

class WishlistSummary extends Component
{
    public int $itemCount = 0;
    public bool $isLoggedIn = false;

    public function mount(WishlistService $wishlistService): void
    {
        $this->isLoggedIn = !empty(session('customer_id'));
        $this->itemCount = $wishlistService->getItemCount();
    }

    #[On('wishlist-updated')]
    public function refreshCount(WishlistService $wishlistService): void
    {
        $this->isLoggedIn = !empty(session('customer_id'));
        $this->itemCount = $wishlistService->getItemCount();
    }

    public function render()
    {
        return view('livewire.frontend.wishlist-summary');
    }
}
