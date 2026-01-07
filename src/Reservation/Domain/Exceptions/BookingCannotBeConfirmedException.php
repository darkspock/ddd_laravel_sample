<?php

declare(strict_types=1);

namespace Src\Reservation\Domain\Exceptions;

use Src\Shared\Framework\Domain\Exceptions\VisibleExceptionInterface;
use RuntimeException;

final class BookingCannotBeConfirmedException extends RuntimeException implements VisibleExceptionInterface
{
    public static function notPending(string $currentStatus): self
    {
        return new self("Booking cannot be confirmed. Current status is '{$currentStatus}'. Only pending bookings can be confirmed.");
    }
}
