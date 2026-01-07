<?php

declare(strict_types=1);

namespace Src\Reservation\Domain\Events;

use Src\Reservation\Domain\Entities\Booking;
use Src\Shared\Framework\Domain\Events\DomainEvent;

final class BookingCreatedEvent extends DomainEvent
{
    public function __construct(
        public readonly string $bookingId,
        public readonly string $clientId,
        public readonly string $restaurantId,
        public readonly string $date,
        public readonly string $time,
        public readonly int $partySize,
        public readonly string $status,
    ) {
        parent::__construct();
    }

    public static function fromEntity(Booking $booking): self
    {
        return new self(
            bookingId: $booking->id->getValue(),
            clientId: $booking->clientId->getValue(),
            restaurantId: $booking->restaurantId->getValue(),
            date: $booking->timeSlot->dateString(),
            time: $booking->timeSlot->timeString(),
            partySize: $booking->partySize->value,
            status: $booking->status->value,
        );
    }

    public function getName(): string
    {
        return 'booking.created';
    }
}
