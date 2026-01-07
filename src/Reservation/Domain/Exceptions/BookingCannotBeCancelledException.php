<?php

declare(strict_types=1);

namespace Src\Reservation\Domain\Exceptions;

use Src\Shared\Framework\Domain\Exceptions\VisibleExceptionInterface;
use RuntimeException;

final class BookingCannotBeCancelledException extends RuntimeException implements VisibleExceptionInterface
{
    public static function alreadyCompleted(): self
    {
        return new self("Booking cannot be cancelled. It has already been completed.");
    }

    public static function alreadyCancelled(): self
    {
        return new self("Booking is already cancelled.");
    }
}
