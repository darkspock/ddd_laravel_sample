<?php

declare(strict_types=1);

namespace Src\Reservation\Domain\Exceptions;

use Src\Reservation\Domain\ValueObjects\BookingId;
use Src\Shared\Framework\Domain\Exceptions\VisibleExceptionInterface;
use RuntimeException;

final class BookingNotFoundException extends RuntimeException implements VisibleExceptionInterface
{
    public static function withId(BookingId $id): self
    {
        return new self("Booking with ID '{$id->getValue()}' not found.");
    }
}
