<?php

declare(strict_types=1);

namespace Apps\Api\Booking\Shared;

use Apps\Shared\Http\BaseRes;
use Src\Reservation\Domain\ReadModels\BookingListItemRM;

final readonly class BookingListItemRes extends BaseRes
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

    public static function fromReadModel(BookingListItemRM $item): self
    {
        return new self(
            id: $item->id,
            clientId: $item->clientId,
            clientName: $item->clientName,
            restaurantId: $item->restaurantId,
            date: $item->date,
            time: $item->time,
            partySize: $item->partySize,
            status: $item->status,
            totalPriceCents: $item->totalPriceCents,
            createdAt: $item->createdAt,
        );
    }
}
