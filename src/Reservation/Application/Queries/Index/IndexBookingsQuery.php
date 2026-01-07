<?php

declare(strict_types=1);

namespace Src\Reservation\Application\Queries\Index;

use Src\Reservation\Domain\ValueObjects\ClientId;
use Src\Reservation\Domain\ValueObjects\RestaurantId;
use Src\Shared\Framework\Application\Queries\PaginatedCollection;
use Src\Shared\Framework\Infrastructure\Bus\QueryBus\QueryInterface;

/**
 * @implements QueryInterface<PaginatedCollection>
 */
final readonly class IndexBookingsQuery implements QueryInterface
{
    public function __construct(
        public ?RestaurantId $restaurantId = null,
        public ?ClientId $clientId = null,
        public ?string $clientName = null,
        public ?string $status = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public int $limit = 20,
        public int $offset = 0,
    ) {
    }
}
