<?php

declare(strict_types=1);

namespace Src\Reservation\Infrastructure\Persistence;

use DateTimeImmutable;
use Src\Reservation\Domain\Entities\Booking;
use Src\Reservation\Domain\Entities\BookingProduct;
use Src\Reservation\Domain\Enums\BookingStatus;
use Src\Reservation\Domain\Enums\ProductType;
use Src\Reservation\Domain\ValueObjects\BookingId;
use Src\Reservation\Domain\ValueObjects\BookingProductId;
use Src\Reservation\Domain\ValueObjects\ClientId;
use Src\Reservation\Domain\ValueObjects\Money;
use Src\Reservation\Domain\ValueObjects\PartySize;
use Src\Reservation\Domain\ValueObjects\RestaurantId;
use Src\Reservation\Domain\ValueObjects\TimeSlot;

final class BookingMapper
{
    public static function toDomain(BookingModel $model): Booking
    {
        $bookingId = BookingId::fromString($model->id);

        // Map products if loaded
        $products = [];
        if ($model->relationLoaded('products')) {
            foreach ($model->products as $productModel) {
                $products[] = self::productToDomain($productModel, $bookingId);
            }
        }

        $booking = Booking::reconstitute(
            id: $bookingId,
            clientId: ClientId::fromString($model->client_id),
            restaurantId: RestaurantId::fromString($model->restaurant_id),
            timeSlot: TimeSlot::fromStrings($model->date, $model->time),
            partySize: new PartySize($model->party_size),
            status: BookingStatus::from($model->status),
            products: $products,
            specialRequests: $model->special_requests,
            confirmedAt: $model->confirmed_at ? new DateTimeImmutable($model->confirmed_at) : null,
            cancelledAt: $model->cancelled_at ? new DateTimeImmutable($model->cancelled_at) : null,
            cancellationReason: $model->cancellation_reason,
        );

        $booking->setIsNew(false);

        return $booking;
    }

    private static function productToDomain(BookingProductModel $model, BookingId $bookingId): BookingProduct
    {
        return BookingProduct::reconstitute(
            id: BookingProductId::fromString($model->id),
            bookingId: $bookingId,
            productType: ProductType::from($model->product_type),
            quantity: $model->quantity,
            unitPrice: Money::fromCents($model->unit_price),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function toArray(Booking $booking): array
    {
        return [
            'id' => $booking->id->getValue(),
            'client_id' => $booking->clientId->getValue(),
            'restaurant_id' => $booking->restaurantId->getValue(),
            'date' => $booking->timeSlot->dateString(),
            'time' => $booking->timeSlot->timeString(),
            'party_size' => $booking->partySize->value,
            'status' => $booking->status->value,
            'special_requests' => $booking->specialRequests,
            'confirmed_at' => $booking->confirmedAt?->format('Y-m-d H:i:s'),
            'cancelled_at' => $booking->cancelledAt?->format('Y-m-d H:i:s'),
            'cancellation_reason' => $booking->cancellationReason,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function productToArray(BookingProduct $product): array
    {
        return [
            'id' => $product->id->getValue(),
            'booking_id' => $product->bookingId->getValue(),
            'product_type' => $product->productType->value,
            'quantity' => $product->quantity,
            'unit_price' => $product->unitPrice->cents,
            'total_price' => $product->getTotalPrice()->cents,
        ];
    }
}
