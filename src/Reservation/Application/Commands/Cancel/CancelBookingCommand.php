<?php

declare(strict_types=1);

namespace Src\Reservation\Application\Commands\Cancel;

use Src\Reservation\Domain\ValueObjects\BookingId;
use Src\Shared\Framework\Application\Commands\CommandInterface;

/**
 * @see CancelBookingHandler
 */
final readonly class CancelBookingCommand implements CommandInterface
{
    public function __construct(
        public BookingId $bookingId,
        public ?string $reason = null,
    ) {
    }
}