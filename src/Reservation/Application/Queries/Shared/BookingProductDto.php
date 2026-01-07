<?php

declare(strict_types=1);

namespace Src\Reservation\Application\Queries\Shared;

use Src\Reservation\Domain\Entities\BookingProduct;

final readonly class BookingProductDto
{
    public function __construct(
        public string $id,
        public string $productType,
        public string $productLabel,
        public int $quantity,
        public int $unitPriceCents,
        public int $totalPriceCents,
    ) {
    }

    public static function fromEntity(BookingProduct $product): self
    {
        return new self(
            id: $product->id->getValue(),
            productType: $product->productType->value,
            productLabel: $product->productType->getLabel(),
            quantity: $product->quantity,
            unitPriceCents: $product->unitPrice->cents,
            totalPriceCents: $product->getTotalPrice()->cents,
        );
    }
}
