<?php

namespace App\Exceptions;

use RuntimeException;

class PriceChangedException extends RuntimeException
{
    protected array $changes;

    public $oldPrice = null;
    public $currentPrice = null;
    public $old_price = null;
    public $current_price = null;
    public $changes_public = null;

    public function __construct(string $message, array $changes = [], int $code = 422, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->changes = $changes;
        if (isset($changes[0])) {
            $this->oldPrice = isset($changes[0]['old_price']) ? (float) $changes[0]['old_price'] : null;
            $this->currentPrice = isset($changes[0]['current_price']) ? (float) $changes[0]['current_price'] : null;
            $this->old_price = $this->oldPrice;
            $this->current_price = $this->currentPrice;
        }
        $this->changes_public = $changes;
    }

    public function getChanges(): array
    {
        return $this->changes;
    }

    public function getOldPrice(): ?float
    {
        return isset($this->changes[0]['old_price']) ? (float) $this->changes[0]['old_price'] : null;
    }

    public function getCurrentPrice(): ?float
    {
        return isset($this->changes[0]['current_price']) ? (float) $this->changes[0]['current_price'] : null;
    }
}
