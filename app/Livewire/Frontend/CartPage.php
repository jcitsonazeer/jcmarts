<?php

namespace App\Livewire\Frontend;

use App\Services\CartService;
use Illuminate\Support\Collection;
use Livewire\Component;

class CartPage extends Component
{
    public Collection $cartItems;
    public array $quantities = [];

    public function mount(CartService $cartService): void
    {
        $this->loadCart($cartService);
    }

    public function updateQuantity(int $cartId, CartService $cartService): void
    {
        $quantity = max(1, (int) ($this->quantities[$cartId] ?? 1));
        $this->quantities[$cartId] = $quantity;

        $cartService->updateQuantity($cartId, $quantity);
        $this->loadCart($cartService);
        $this->dispatch('cart-updated');
    }

    public function removeItem(int $cartId, CartService $cartService): void
    {
        $cartService->removeItem($cartId);
        $this->loadCart($cartService);
        $this->dispatch('cart-updated');
    }

    public function getItemCountProperty(): int
    {
        return (int) collect($this->cartItems)->sum('quantity');
    }

    public function getSubTotalProperty(): float
    {
        return (float) collect($this->cartItems)->sum(function ($item) {
            return ((float) $item->unit_price) * ((int) $item->quantity);
        });
    }

    public function getTotalProperty(): int
    {
        return (int) round($this->subTotal);
    }

    public function render()
    {
        return view('livewire.frontend.cart-page', [
            'cartItems' => $this->cartItems,
            'itemCount' => $this->itemCount,
            'subTotal' => $this->subTotal,
            'total' => $this->total,
        ]);
    }

    private function loadCart(CartService $cartService): void
    {
        $this->cartItems = $cartService->getActiveItems();
        $this->quantities = [];

        foreach ($this->cartItems as $item) {
            $this->quantities[$item->id] = (int) $item->quantity;
        }
    }
}
