<?php

declare(strict_types=1);

namespace Src\Reservation\Domain\Events;

use Src\Reservation\Domain\Entities\Booking;
use Src\Shared\Framework\Domain\Events\DomainEvent;

final class BookingMarkedAsNoShowEvent extends DomainEvent
{
    public function __construct(
        public readonly string $bookingId,
        public readonly string $markedAt,
    ) {
        parent::__construct();
    }

    public static function fromEntity(Booking $booking): self
    {
        return new self(
            bookingId: $booking->id->getValue(),
            markedAt: $booking->noShowAt?->format('Y-m-d H:i:s') ?? '',
        );
    }

    public function getName(): string
    {
        return 'booking.marked_as_no_show';
    }
}
