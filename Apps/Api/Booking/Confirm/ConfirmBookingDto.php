<?php

declare(strict_types=1);

namespace Apps\Api\Booking\Confirm;

use Src\Reservation\Domain\ValueObjects\BookingId;

final readonly class ConfirmBookingDto
{
    public function __construct(
        public BookingId $bookingId,
    ) {
    }
}
