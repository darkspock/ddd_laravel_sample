<?php

declare(strict_types=1);

namespace Src\Reservation\Infrastructure\Persistence;

use Src\Reservation\Domain\Entities\Booking;
use Src\Reservation\Domain\Exceptions\BookingNotFoundException;
use Src\Reservation\Domain\Repositories\BookingRepositoryInterface;
use Src\Reservation\Domain\ValueObjects\BookingId;
use Src\Reservation\Domain\ValueObjects\RestaurantId;

final class BookingRepository implements BookingRepositoryInterface
{
    public function store(Booking $booking): void
    {
        BookingModel::updateOrCreate(
            ['id' => $booking->id->getValue()],
            BookingMapper::toArray($booking)
        );

        // Store products (delete and recreate for simplicity)
        BookingProductModel::where('booking_id', $booking->id->getValue())->delete();

        foreach ($booking->getProducts() as $product) {
            BookingProductModel::create(BookingMapper::productToArray($product));
        }
    }

    public function findById(BookingId $id): ?Booking
    {
        $model = BookingModel::with('products')->find($id->getValue());

        if ($model === null) {
            return null;
        }

        return BookingMapper::toDomain($model);
    }

    public function getById(BookingId $id): Booking
    {
        $booking = $this->findById($id);

        if ($booking === null) {
            throw BookingNotFoundException::withId($id);
        }

        return $booking;
    }

    /**
     * @return Booking[]
     */
    public function findByRestaurantId(RestaurantId $restaurantId): array
    {
        $models = BookingModel::with('products')
            ->where('restaurant_id', $restaurantId->getValue())
            ->get();

        /** @var Booking[] */
        return $models->map(fn (BookingModel $model): Booking => BookingMapper::toDomain($model))->toArray();
    }
}
