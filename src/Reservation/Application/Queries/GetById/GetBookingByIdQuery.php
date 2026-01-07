<?php

declare(strict_types=1);

namespace Src\Reservation\Application\Queries\GetById;

use Src\Reservation\Domain\ValueObjects\BookingId;
use Src\Shared\Framework\Infrastructure\Bus\QueryBus\QueryInterface;

/**
 * @see GetBookingByIdHandler
 */
final readonly class GetBookingByIdQuery implements QueryInterface
{
    public function __construct(
        public BookingId $bookingId,
    ) {
    }
}