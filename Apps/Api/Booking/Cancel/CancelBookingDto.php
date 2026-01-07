<?php

declare(strict_types=1);

namespace Apps\Api\Booking\Cancel;

use Src\Reservation\Domain\ValueObjects\BookingId;

final readonly class CancelBookingDto
{
    public function __construct(
        public BookingId $bookingId,
        public ?string $reason,
    ) {
    }
}
