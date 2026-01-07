<?php

declare(strict_types=1);

namespace Apps\Api\Booking\Shared;

use Apps\Shared\Http\BaseRes;
use Src\Reservation\Application\Queries\Shared\BookingProductDto;

final readonly class BookingProductRes extends BaseRes
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

    public static function fromDto(BookingProductDto $dto): self
    {
        return new self(
            id: $dto->id,
            productType: $dto->productType,
            productLabel: $dto->productLabel,
            quantity: $dto->quantity,
            unitPriceCents: $dto->unitPriceCents,
            totalPriceCents: $dto->totalPriceCents,
        );
    }
}
