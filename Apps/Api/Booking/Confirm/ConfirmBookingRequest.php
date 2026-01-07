<?php

declare(strict_types=1);

namespace Apps\Api\Booking\Confirm;

use Apps\Shared\Http\AbstractFormRequest;
use Src\Reservation\Domain\ValueObjects\BookingId;

final class ConfirmBookingRequest extends AbstractFormRequest
{
    public function getDto(): ConfirmBookingDto
    {
        return new ConfirmBookingDto(
            bookingId: BookingId::fromString($this->getHelper()->routeString('id')),
        );
    }
}
