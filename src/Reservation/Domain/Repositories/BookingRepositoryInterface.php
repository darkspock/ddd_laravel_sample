<?php

declare(strict_types=1);

namespace Src\Reservation\Domain\Repositories;

use Src\Reservation\Domain\Entities\Booking;
use Src\Reservation\Domain\ValueObjects\BookingId;
use Src\Reservation\Domain\ValueObjects\RestaurantId;

interface BookingRepositoryInterface
{
    public function store(Booking $booking): void;

    public function findById(BookingId $id): ?Booking;

    public function getById(BookingId $id): Booking;

    /**
     * @return Booking[]
     */
    public function findByRestaurantId(RestaurantId $restaurantId): array;
}
