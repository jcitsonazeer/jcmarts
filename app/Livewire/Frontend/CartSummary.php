<?php

namespace App\Livewire\Frontend;

use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

class CartSummary extends Component
{
    public int $itemCount = 0;

    public function mount(CartService $cartService): void
    {
        $this->itemCount = $cartService->getItemCount();
    }

    #[On('cart-updated')]
    public function refreshCount(CartService $cartService): void
    {
        $this->itemCount = $cartService->getItemCount();
    }

    public function render()
    {
        return view('livewire.frontend.cart-summary');
    }
}
