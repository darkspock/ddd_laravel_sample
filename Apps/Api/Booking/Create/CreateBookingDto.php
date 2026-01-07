<?php

declare(strict_types=1);

namespace Apps\Api\Booking\Create;

use Src\Reservation\Domain\ValueObjects\BookingId;
use Src\Reservation\Domain\ValueObjects\ClientId;
use Src\Reservation\Domain\ValueObjects\PartySize;
use Src\Reservation\Domain\ValueObjects\RestaurantId;
use Src\Reservation\Domain\ValueObjects\TimeSlot;

final readonly class CreateBookingDto
{
    /**
     * @param array<ProductInputDto> $products
     */
    public function __construct(
        public BookingId $id,
        public ClientId $clientId,
        public RestaurantId $restaurantId,
        public TimeSlot $timeSlot,
        public PartySize $partySize,
        public ?string $specialRequests,
        public array $products = [],
    ) {
    }
}
