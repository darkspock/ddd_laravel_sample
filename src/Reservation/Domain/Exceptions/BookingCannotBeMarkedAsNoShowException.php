<?php

declare(strict_types=1);

namespace Src\Reservation\Domain\Exceptions;

use RuntimeException;
use Src\Shared\Framework\Domain\Exceptions\VisibleExceptionInterface;

final class BookingCannotBeMarkedAsNoShowException extends RuntimeException implements VisibleExceptionInterface
{
    public static function notConfirmed(string $currentStatus): self
    {
        return new self("Booking cannot be marked as no-show. Current status is '{$currentStatus}'. Only confirmed bookings can be marked as no-show.");
    }
}
