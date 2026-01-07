<?php

declare(strict_types=1);

namespace Apps\Api\Booking\Shared\Services;

use Apps\Api\Booking\Shared\BookingRes;
use Src\Reservation\Application\Queries\GetById\GetBookingByIdQuery;
use Src\Reservation\Application\Queries\Shared\BookingDto;
use Src\Reservation\Domain\ValueObjects\BookingId;
use Src\Shared\Framework\Infrastructure\Bus\QueryBus\QueryBusInterface;

final readonly class BookingResService
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {
    }

    public function getBookingResource(BookingId $bookingId): BookingRes
    {
        /** @var BookingDto $dto */
        $dto = $this->queryBus->query(new GetBookingByIdQuery($bookingId));

        return BookingRes::fromDto($dto);
    }
}
