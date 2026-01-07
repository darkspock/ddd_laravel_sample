<?php

declare(strict_types=1);

namespace Src\Reservation\Domain\Entities;

use DateTimeImmutable;
use Src\Reservation\Domain\Enums\BookingStatus;
use Src\Reservation\Domain\Enums\ProductType;
use Src\Reservation\Domain\Events\BookingCancelledEvent;
use Src\Reservation\Domain\Events\BookingConfirmedEvent;
use Src\Reservation\Domain\Events\BookingCreatedEvent;
use Src\Reservation\Domain\Exceptions\BookingCannotBeCancelledException;
use Src\Reservation\Domain\Exceptions\BookingCannotBeConfirmedException;
use Src\Reservation\Domain\ValueObjects\BookingId;
use Src\Reservation\Domain\ValueObjects\ClientId;
use Src\Reservation\Domain\ValueObjects\Money;
use Src\Reservation\Domain\ValueObjects\PartySize;
use Src\Reservation\Domain\ValueObjects\RestaurantId;
use Src\Reservation\Domain\ValueObjects\TimeSlot;
use Src\Shared\Framework\Domain\Entities\BaseEntity;

final class Booking extends BaseEntity
{
    /** @var array<BookingProduct> */
    private array $products = [];

    /**
     * Private constructor - use create() for new bookings or reconstitute() for hydration.
     */
    private function __construct(
        public readonly BookingId $id,
        public readonly ClientId $clientId,
        public readonly RestaurantId $restaurantId,
        public TimeSlot $timeSlot,
        public PartySize $partySize,
        public BookingStatus $status,
        public ?string $specialRequests = null,
        public ?DateTimeImmutable $confirmedAt = null,
        public ?DateTimeImmutable $cancelledAt = null,
        public ?string $cancellationReason = null,
    ) {
    }

    /**
     * @param array<array{type: ProductType, quantity: int}> $products
     */
    public static function create(
        BookingId $id,
        ClientId $clientId,
        RestaurantId $restaurantId,
        TimeSlot $timeSlot,
        PartySize $partySize,
        ?string $specialRequests = null,
        array $products = [],
    ): self {
        $booking = new self(
            id: $id,
            clientId: $clientId,
            restaurantId: $restaurantId,
            timeSlot: $timeSlot,
            partySize: $partySize,
            status: BookingStatus::getDefaultValue(),
            specialRequests: $specialRequests,
        );

        foreach ($products as $product) {
            $booking->addProduct($product['type'], $product['quantity']);
        }

        $booking->recordLast(BookingCreatedEvent::fromEntity($booking));

        return $booking;
    }

    /**
     * Reconstitute a Booking from persistence (hydration).
     *
     * This method allows setting ANY state, including products.
     * Use this ONLY for hydrating from database, never for creating new bookings.
     *
     * @param array<BookingProduct> $products
     */
    public static function reconstitute(
        BookingId $id,
        ClientId $clientId,
        RestaurantId $restaurantId,
        TimeSlot $timeSlot,
        PartySize $partySize,
        BookingStatus $status,
        array $products = [],
        ?string $specialRequests = null,
        ?DateTimeImmutable $confirmedAt = null,
        ?DateTimeImmutable $cancelledAt = null,
        ?string $cancellationReason = null,
    ): self {
        $booking = new self(
            id: $id,
            clientId: $clientId,
            restaurantId: $restaurantId,
            timeSlot: $timeSlot,
            partySize: $partySize,
            status: $status,
            specialRequests: $specialRequests,
            confirmedAt: $confirmedAt,
            cancelledAt: $cancelledAt,
            cancellationReason: $cancellationReason,
        );

        $booking->products = $products;

        return $booking;
    }

    public function addProduct(ProductType $type, int $quantity): void
    {
        $this->products[] = BookingProduct::create(
            bookingId: $this->id,
            productType: $type,
            quantity: $quantity,
        );
    }


    /**
     * @return array<BookingProduct>
     */
    public function getProducts(): array
    {
        return $this->products;
    }

    public function getTotalPrice(): Money
    {
        $total = Money::zero();

        foreach ($this->products as $product) {
            $total = $total->add($product->getTotalPrice());
        }

        return $total;
    }

    public function confirm(): void
    {
        if ($this->status !== BookingStatus::PENDING) {
            throw BookingCannotBeConfirmedException::notPending($this->status->value);
        }

        $this->status = BookingStatus::CONFIRMED;
        $this->confirmedAt = new DateTimeImmutable();

        $this->recordLast(BookingConfirmedEvent::fromEntity($this));
    }

    public function cancel(?string $reason = null): void
    {
        if ($this->status === BookingStatus::COMPLETED) {
            throw BookingCannotBeCancelledException::alreadyCompleted();
        }

        if ($this->status === BookingStatus::CANCELLED) {
            throw BookingCannotBeCancelledException::alreadyCancelled();
        }

        $this->status = BookingStatus::CANCELLED;
        $this->cancelledAt = new DateTimeImmutable();
        $this->cancellationReason = $reason;

        $this->recordLast(BookingCancelledEvent::fromEntity($this));
    }

    public function complete(): void
    {
        $this->status = BookingStatus::COMPLETED;
    }

    public function markAsNoShow(): void
    {
        $this->status = BookingStatus::NO_SHOW;
    }

    public function updatePartySize(PartySize $partySize): void
    {
        $this->partySize = $partySize;
    }

    public function updateTimeSlot(TimeSlot $timeSlot): void
    {
        $this->timeSlot = $timeSlot;
    }
}
