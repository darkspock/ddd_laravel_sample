<?php

declare(strict_types=1);

namespace Src\Reservation\Domain\ReadModels;

/**
 * ReadModel for booking list.
 *
 * This demonstrates the "pragmatic DDD" approach where we allow cross-domain
 * data (client name from Client bounded context) in read models.
 *
 * WHY WE NEED THE JOIN:
 * - We want to filter bookings by client name (e.g., "GET /bookings?client_name=John")
 * - Client name belongs to the Client bounded context, not Reservation
 * - Without the JOIN, we'd need to:
 *   1. First query Clients to get IDs matching the name
 *   2. Then query Bookings with those IDs
 *   This is inefficient and doesn't scale well.
 *
 * THE PRAGMATIC SOLUTION:
 * - For READ operations (queries), we allow cross-domain JOINs
 * - The result is a ReadModel (denormalized DTO), not a domain entity
 * - Domain boundaries are still respected for WRITE operations (commands)
 */
final readonly class BookingListItemRM
{
    public function __construct(
        public string $id,
        public string $clientId,
        public string $clientName,
        public string $restaurantId,
        public string $date,
        public string $time,
        public int $partySize,
        public string $status,
        public int $totalPriceCents,
        public string $createdAt,
    ) {
    }
}
