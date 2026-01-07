<?php

declare(strict_types=1);

namespace Src\Reservation\Application\Queries\Shared;

use Src\Reservation\Domain\Entities\Booking;

final readonly class BookingDto
{
    /**
     * @param array<BookingProductDto> $products
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

    public static function fromEntity(Booking $booking): self
    {
        $products = array_map(
            fn ($product) => BookingProductDto::fromEntity($product),
            $booking->getProducts()
        );

        return new self(
            id: $booking->id->getValue(),
            clientId: $booking->clientId->getValue(),
            restaurantId: $booking->restaurantId->getValue(),
            date: $booking->timeSlot->dateString(),
            time: $booking->timeSlot->timeString(),
            partySize: $booking->partySize->value,
            status: $booking->status->value,
            specialRequests: $booking->specialRequests,
            confirmedAt: $booking->confirmedAt?->format('Y-m-d H:i:s'),
            cancelledAt: $booking->cancelledAt?->format('Y-m-d H:i:s'),
            cancellationReason: $booking->cancellationReason,
            products: $products,
            totalPriceCents: $booking->getTotalPrice()->cents,
        );
    }
}
