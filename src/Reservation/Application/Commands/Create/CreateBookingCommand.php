<?php

declare(strict_types=1);

namespace Src\Reservation\Application\Commands\Create;

use Src\Reservation\Domain\Enums\ProductType;
use Src\Reservation\Domain\ValueObjects\BookingId;
use Src\Reservation\Domain\ValueObjects\ClientId;
use Src\Reservation\Domain\ValueObjects\PartySize;
use Src\Reservation\Domain\ValueObjects\RestaurantId;
use Src\Reservation\Domain\ValueObjects\TimeSlot;
use Src\Shared\Framework\Application\Commands\CommandInterface;

/**
 * @see CreateBookingHandler
 */
final readonly class CreateBookingCommand implements CommandInterface
{
    /**
     * @param array<array{type: ProductType, quantity: int}> $products
     */
    public function __construct(
        public BookingId $id,
        public ClientId $clientId,
        public RestaurantId $restaurantId,
        public TimeSlot $timeSlot,
        public PartySize $partySize,
        public ?string $specialRequests = null,
        public array $products = [],
    ) {
    }
}
