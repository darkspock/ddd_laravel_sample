<?php

declare(strict_types=1);

namespace Src\Reservation\Domain\Repositories;

use Src\Reservation\Domain\ReadModels\BookingListItemRM;
use Src\Reservation\Domain\ValueObjects\ClientId;
use Src\Reservation\Domain\ValueObjects\RestaurantId;
use Src\Shared\Framework\Application\Queries\PaginatedCollection;

interface BookingReadModelRepositoryInterface
{
    /**
     * @return PaginatedCollection<BookingListItemRM>
     */
    public function findPaginated(
        ?RestaurantId $restaurantId,
        ?ClientId $clientId,
        ?string $clientName,
        ?string $status,
        ?string $dateFrom,
        ?string $dateTo,
        int $limit,
        int $offset,
    ): PaginatedCollection;
}
