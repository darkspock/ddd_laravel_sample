<?php

declare(strict_types=1);

namespace Apps\Api\Booking\Shared;

use Apps\Shared\Http\BaseRes;
use Src\Reservation\Application\Queries\Shared\BookingDto;

final readonly class BookingRes extends BaseRes
{
    /**
     * @param array<BookingProductRes> $products
     */
    public function __construct(
        public string $id,
        public string $clientId,
        public string $restaurantId,
        public string $date,
        public string $time,
        public int $partySize,
        public string $status,
        public ?string $specialRequests,
        public ?string $confirmedAt,
        public ?string $cancelledAt,
        public ?string $cancellationReason,
        public array $products,
        public int $totalPriceCents,
    ) {
    }

    public static function fromDto(BookingDto $dto): self
    {
        $products = array_map(
            fn ($productDto) => BookingProductRes::fromDto($productDto),
            $dto->products
        );

        return new self(
            id: $dto->id,
            clientId: $dto->clientId,
            restaurantId: $dto->restaurantId,
            date: $dto->date,
            time: $dto->time,
            partySize: $dto->partySize,
            status: $dto->status,
            specialRequests: $dto->specialRequests,
            confirmedAt: $dto->confirmedAt,
            cancelledAt: $dto->cancelledAt,
            cancellationReason: $dto->cancellationReason,
            products: $products,
            totalPriceCents: $dto->totalPriceCents,
        );
    }
}
