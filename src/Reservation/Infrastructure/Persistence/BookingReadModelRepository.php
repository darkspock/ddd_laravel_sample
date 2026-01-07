<?php

declare(strict_types=1);

namespace Src\Reservation\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Src\Reservation\Domain\ReadModels\BookingListItemRM;
use Src\Reservation\Domain\Repositories\BookingReadModelRepositoryInterface;
use Src\Reservation\Domain\ValueObjects\ClientId;
use Src\Reservation\Domain\ValueObjects\RestaurantId;
use Src\Shared\Framework\Application\Queries\PaginatedCollection;

/**
 * ReadModel Repository - ARCHITECTURAL EXCEPTION
 *
 * This repository is an intentional exception to some DDD rules for READ operations.
 * Unlike regular repositories that work with domain entities, this one:
 *
 * 1. CROSSES BOUNDED CONTEXT BOUNDARIES
 *    - JOINs bookings (Reservation BC) with clients (Client BC)
 *    - This is acceptable ONLY for read operations (queries)
 *    - Write operations MUST still respect BC boundaries
 *
 * 2. CONTAINS COMPUTED/DERIVED DATA
 *    - Calculates total_price via SQL aggregation (SUM of booking_products)
 *    - Alternative: Calculate in domain (Booking::getTotalPrice())
 *
 * TRADEOFFS TO CONSIDER:
 *
 * ┌─────────────────────────────────────────────────────────────────────────────┐
 * │ Approach              │ Pros                    │ Cons                      │
 * ├─────────────────────────────────────────────────────────────────────────────┤
 * │ SQL Aggregation       │ - Single query          │ - Business logic in SQL   │
 * │ (current approach)    │ - Fast for large lists  │ - Harder to test          │
 * │                       │ - Less memory usage     │ - Duplicated logic        │
 * ├─────────────────────────────────────────────────────────────────────────────┤
 * │ Domain Calculation    │ - Logic in one place    │ - N+1 queries risk        │
 * │ (Booking entity)      │ - Easy to test          │ - More memory usage       │
 * │                       │ - Type-safe             │ - Slower for large lists  │
 * ├─────────────────────────────────────────────────────────────────────────────┤
 * │ Denormalized Column   │ - Fastest reads         │ - Must sync on writes     │
 * │ (total_price in DB)   │ - Simple queries        │ - Eventual consistency    │
 * │                       │ - No duplication        │ - More complex writes     │
 * └─────────────────────────────────────────────────────────────────────────────┘
 *
 * WHEN TO USE EACH:
 * - SQL Aggregation: List views with many items, when business rules are simple
 * - Domain Calculation: Detail views, complex business rules, strong consistency
 * - Denormalized Column: High-traffic reads, simple aggregations, can tolerate sync
 *
 * IMPORTANT: If the price calculation logic changes in Booking::getTotalPrice(),
 * this SQL must be updated too. Consider adding a test that verifies both
 * approaches return the same result.
 *
 * @see ai_docs/infrastructure.md - ReadModels section
 * @see ai_docs/architecture.md - ReadModels Between BC
 */
final readonly class BookingReadModelRepository implements BookingReadModelRepositoryInterface
{
    /**
     * Fetches a paginated list of bookings with cross-domain data.
     *
     * NOTE: The total_price is calculated via SQL aggregation here.
     * This duplicates the logic from Booking::getTotalPrice() for performance.
     * If pricing logic changes, both places must be updated.
     *
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
    ): PaginatedCollection {
        $baseQuery = DB::table('bookings')
            ->leftJoin('clients', 'bookings.client_id', '=', 'clients.id')
            ->leftJoin(
                DB::raw('(SELECT booking_id, SUM(total_price) as total FROM booking_products GROUP BY booking_id) as bp'),
                'bookings.id',
                '=',
                'bp.booking_id'
            )
            ->select([
                'bookings.id',
                'bookings.client_id',
                'clients.name as client_name',
                'bookings.restaurant_id',
                'bookings.date',
                'bookings.time',
                'bookings.party_size',
                'bookings.status',
                DB::raw('COALESCE(bp.total, 0) as total_price'),
                'bookings.created_at',
            ]);

        // Apply filters
        if ($restaurantId !== null) {
            $baseQuery->where('bookings.restaurant_id', $restaurantId->getValue());
        }

        if ($clientId !== null) {
            $baseQuery->where('bookings.client_id', $clientId->getValue());
        }

        // Filter by client name - THIS JUSTIFIES THE CROSS-DOMAIN JOIN
        // We're filtering bookings by a field that belongs to the Client bounded context
        if ($clientName !== null) {
            $baseQuery->where('clients.name', 'LIKE', '%' . $clientName . '%');
        }

        if ($status !== null) {
            $baseQuery->where('bookings.status', $status);
        }

        if ($dateFrom !== null) {
            $baseQuery->where('bookings.date', '>=', $dateFrom);
        }

        if ($dateTo !== null) {
            $baseQuery->where('bookings.date', '<=', $dateTo);
        }

        // Get total count before pagination
        $total = $baseQuery->count('bookings.id');

        // Calculate page from offset/limit
        $page = $limit > 0 ? (int) floor($offset / $limit) + 1 : 1;

        // Apply pagination and ordering
        $results = $baseQuery
            ->orderBy('bookings.date', 'desc')
            ->orderBy('bookings.time', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        // Map to ReadModel
        /** @var array<int, BookingListItemRM> $items */
        $items = [];
        foreach ($results as $row) {
            /** @var array{id: string, client_id: string, client_name: ?string, restaurant_id: string, date: string, time: string, party_size: int|string, status: string, total_price: int|string, created_at: string} $data */
            $data = (array) $row;

            $items[] = new BookingListItemRM(
                id: $data['id'],
                clientId: $data['client_id'],
                clientName: $data['client_name'] ?? 'Unknown',
                restaurantId: $data['restaurant_id'],
                date: $data['date'],
                time: $data['time'],
                partySize: (int) $data['party_size'],
                status: $data['status'],
                totalPriceCents: (int) $data['total_price'],
                createdAt: $data['created_at'],
            );
        }

        /** @var PaginatedCollection<BookingListItemRM> */
        return new PaginatedCollection(
            items: $items,
            pageSize: $limit,
            page: $page,
            totalCount: $total,
        );
    }
}
