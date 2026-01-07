<?php

declare(strict_types=1);

namespace Src\Reservation\Application\Queries\Index;

use Src\Reservation\Domain\ReadModels\BookingListItemRM;
use Src\Reservation\Domain\Repositories\BookingReadModelRepositoryInterface;
use Src\Shared\Framework\Application\Queries\PaginatedCollection;
use Src\Shared\Framework\Infrastructure\Bus\QueryBus\QueryHandlerInterface;

/**
 * Handler that demonstrates cross-domain queries using a ReadModel.
 *
 * This is the "pragmatic DDD" approach: we JOIN bookings with clients
 * to get client names for display. This is acceptable for READ operations
 * because we're not modifying domain state.
 *
 * The ReadModel pattern ensures:
 * 1. Domain boundaries are respected for writes
 * 2. Queries can be optimized without domain constraints
 * 3. Denormalized data is clearly marked as read-only
 */
final readonly class IndexBookingsHandler implements QueryHandlerInterface
{
    public function __construct(
        private BookingReadModelRepositoryInterface $repository,
    ) {
    }

    /**
     * @return PaginatedCollection<BookingListItemRM>
     */
    public function __invoke(IndexBookingsQuery $query): PaginatedCollection
    {
        return $this->repository->findPaginated(
            restaurantId: $query->restaurantId,
            clientId: $query->clientId,
            clientName: $query->clientName,
            status: $query->status,
            dateFrom: $query->dateFrom,
            dateTo: $query->dateTo,
            limit: $query->limit,
            offset: $query->offset,
        );
    }
}
