<?php

declare(strict_types=1);

namespace Apps\Api\Booking\Shared;

use Apps\Shared\Http\BaseRes;
use Src\Reservation\Domain\ReadModels\BookingListItemRM;
use Src\Shared\Framework\Application\Queries\PaginatedCollection;

final readonly class BookingListRes extends BaseRes
{
    /**
     * @param array<BookingListItemRes> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $pageSize,
        public int $page,
    ) {
    }

    /**
     * @param PaginatedCollection<BookingListItemRM> $collection
     */
    public static function fromCollection(PaginatedCollection $collection): self
    {
        $items = array_map(
            fn (BookingListItemRM $item) => BookingListItemRes::fromReadModel($item),
            $collection->items
        );

        return new self(
            items: $items,
            total: $collection->totalCount,
            pageSize: $collection->pageSize,
            page: $collection->page,
        );
    }
}
