<?php

declare(strict_types=1);

namespace Src\Reservation\Application\Commands\Confirm;

use Src\Reservation\Domain\ValueObjects\BookingId;
use Src\Shared\Framework\Application\Commands\CommandInterface;

/**
 * @see ConfirmBookingHandler
 */
final readonly class ConfirmBookingCommand implements CommandInterface
{
    public function __construct(
        public BookingId $bookingId,
    ) {
    }
}
