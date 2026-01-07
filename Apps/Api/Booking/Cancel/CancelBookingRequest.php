<?php

declare(strict_types=1);

namespace Apps\Api\Booking\Cancel;

use Apps\Shared\Http\AbstractFormRequest;
use Src\Reservation\Domain\ValueObjects\BookingId;

final class CancelBookingRequest extends AbstractFormRequest
{
    public function getDto(): CancelBookingDto
    {
        return new CancelBookingDto(
            bookingId: BookingId::fromString($this->getHelper()->routeString('id')),
            reason: $this->getHelper()->getStringOrNull('reason'),
        );
    }
}
