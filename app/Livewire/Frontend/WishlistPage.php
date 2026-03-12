<?php

namespace App\Livewire\Frontend;

use App\Services\WishlistService;
use Illuminate\Support\Collection;
use Livewire\Component;

class WishlistPage extends Component
{
    public Collection $items;

    public function mount(WishlistService $wishlistService): void
    {
        $this->loadItems($wishlistService);
    }

    public function removeItem(int $wishlistId, WishlistService $wishlistService): void
    {
        $customerId = $wishlistService->getCustomerId();
        if (!$customerId) {
            return;
        }

        $item = $this->items->firstWhere('id', $wishlistId);
        if (!$item) {
            return;
        }

        $wishlistService->remove($customerId, (int) $item->product_id);
        $this->loadItems($wishlistService);
        $this->dispatch('wishlist-updated');
    }

    public function render()
    {
        return view('livewire.frontend.wishlist-page', [
            'items' => $this->items,
        ]);
    }

    private function loadItems(WishlistService $wishlistService): void
    {
        $customerId = $wishlistService->getCustomerId();
        $this->items = $customerId
            ? $wishlistService->getActiveItems($customerId)
            : collect();
    }
}
