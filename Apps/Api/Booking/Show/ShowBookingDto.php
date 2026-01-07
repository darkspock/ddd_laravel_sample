<?php

declare(strict_types=1);

namespace Apps\Api\Booking\Show;

use Src\Reservation\Domain\ValueObjects\BookingId;

final readonly class ShowBookingDto
{
    public function __construct(
        public BookingId $bookingId,
    ) {
    }
}
