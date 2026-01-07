<?php

declare(strict_types=1);

namespace Src\Reservation\Domain\Entities;

use Src\Reservation\Domain\Enums\ProductType;
use Src\Reservation\Domain\ValueObjects\BookingId;
use Src\Reservation\Domain\ValueObjects\BookingProductId;
use Src\Reservation\Domain\ValueObjects\Money;

/**
 * BookingProduct is a child entity of the Booking aggregate.
 * It cannot exist independently and is always accessed through the Booking aggregate root.
 */
final class BookingProduct
{
    /**
     * Private constructor - use create() for new products or reconstitute() for hydration.
     */
    private function __construct(
        public readonly BookingProductId $id,
        public readonly BookingId $bookingId,
        public readonly ProductType $productType,
        public readonly int $quantity,
        public readonly Money $unitPrice,
    ) {
    }

    /**
     * Reconstitute a BookingProduct from persistence (hydration).
     *
     * This method allows setting ANY state without validation.
     * Use this ONLY for hydrating from database, never for creating new products.
     */
    public static function reconstitute(
        BookingProductId $id,
        BookingId $bookingId,
        ProductType $productType,
        int $quantity,
        Money $unitPrice,
    ): self {
        return new self(
            id: $id,
            bookingId: $bookingId,
            productType: $productType,
            quantity: $quantity,
            unitPrice: $unitPrice,
        );
    }

    public static function create(
        BookingId $bookingId,
        ProductType $productType,
        int $quantity,
    ): self {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Quantity must be at least 1');
        }

        return new self(
            id: BookingProductId::random(),
            bookingId: $bookingId,
            productType: $productType,
            quantity: $quantity,
            unitPrice: Money::fromCents($productType->getPriceInCents()),
        );
    }

    public function getTotalPrice(): Money
    {
        return $this->unitPrice->multiply($this->quantity);
    }
}
