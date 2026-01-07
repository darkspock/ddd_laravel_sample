<?php

declare(strict_types=1);

namespace Apps\Api\Booking\Index;

use Src\Reservation\Domain\ValueObjects\ClientId;
use Src\Reservation\Domain\ValueObjects\RestaurantId;

final readonly class IndexBookingsDto
{
    public function __construct(
        public ?RestaurantId $restaurantId,
        public ?ClientId $clientId,
        public ?string $clientName,
        public ?string $status,
        public ?string $dateFrom,
        public ?string $dateTo,
        public int $limit,
        public int $offset,
    ) {
    }
}
