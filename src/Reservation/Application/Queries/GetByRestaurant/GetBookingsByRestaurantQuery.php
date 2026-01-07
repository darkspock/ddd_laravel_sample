<?php

declare(strict_types=1);

namespace Src\Reservation\Application\Queries\GetByRestaurant;

use Src\Reservation\Domain\ValueObjects\RestaurantId;
use Src\Shared\Framework\Infrastructure\Bus\QueryBus\QueryInterface;

/**
 * @see GetBookingsByRestaurantHandler
 */
final readonly class GetBookingsByRestaurantQuery implements QueryInterface
{
    public function __construct(
        public RestaurantId $restaurantId,
    ) {
    }
}
