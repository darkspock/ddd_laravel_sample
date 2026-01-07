<?php

declare(strict_types=1);

namespace Src\Reservation\Application\Queries\GetById;

use Src\Reservation\Application\Queries\Shared\BookingDto;
use Src\Reservation\Domain\Repositories\BookingRepositoryInterface;
use Src\Shared\Framework\Infrastructure\Bus\QueryBus\QueryHandlerInterface;

final readonly class GetBookingByIdHandler implements QueryHandlerInterface
{
    public function __construct(
        private BookingRepositoryInterface $repository,
    ) {
    }

    public function __invoke(GetBookingByIdQuery $query): BookingDto
    {
        $booking = $this->repository->getById($query->bookingId);

        return BookingDto::fromEntity($booking);
    }
}
