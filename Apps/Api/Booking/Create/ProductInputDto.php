<?php

declare(strict_types=1);

namespace Apps\Api\Booking\Create;

use Src\Reservation\Domain\Enums\ProductType;

final readonly class ProductInputDto
{
    public function __construct(
        public ProductType $type,
        public int $quantity,
    ) {
    }
}
