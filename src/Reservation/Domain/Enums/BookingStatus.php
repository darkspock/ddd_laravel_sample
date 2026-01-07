<?php

declare(strict_types=1);

namespace Src\Reservation\Domain\Enums;

enum BookingStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';
    case NO_SHOW = 'no_show';

    public static function getDefaultValue(): self
    {
        return self::PENDING;
    }
}
