<?php

declare(strict_types=1);

namespace Src\Reservation\Application\Queries\GetByRestaurant;

use Src\Reservation\Application\Queries\Shared\BookingDto;
use Src\Reservation\Domain\Repositories\BookingRepositoryInterface;
use Src\Shared\Framework\Infrastructure\Bus\QueryBus\QueryHandlerInterface;

final readonly class GetBookingsByRestaurantHandler implements QueryHandlerInterface
{
    public function __construct(
        private BookingRepositoryInterface $repository,
    ) {
    }

    /**
     * @return BookingDto[]
     */
    public function __invoke(GetBookingsByRestaurantQuery $query): array
    {
        $bookings = $this->repository->findByRestaurantId($query->restaurantId);

        return array_map(
            fn ($booking) => BookingDto::fromEntity($booking),
            $bookings
        );
    }
}
