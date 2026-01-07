<?php

declare(strict_types=1);

namespace Apps\Api\Booking\Show;

use Apps\Shared\Http\AbstractFormRequest;
use Src\Reservation\Domain\ValueObjects\BookingId;

final class ShowBookingRequest extends AbstractFormRequest
{
    public function getDto(): ShowBookingDto
    {
        return new ShowBookingDto(
            bookingId: BookingId::fromString($this->getHelper()->routeString('id')),
        );
    }
}
