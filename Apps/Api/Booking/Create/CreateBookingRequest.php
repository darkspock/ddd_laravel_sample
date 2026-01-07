<?php

declare(strict_types=1);

namespace Apps\Api\Booking\Create;

use Apps\Shared\Http\AbstractFormRequest;
use Src\Reservation\Domain\Enums\ProductType;
use Src\Reservation\Domain\ValueObjects\BookingId;
use Src\Reservation\Domain\ValueObjects\ClientId;
use Src\Reservation\Domain\ValueObjects\PartySize;
use Src\Reservation\Domain\ValueObjects\RestaurantId;
use Src\Reservation\Domain\ValueObjects\TimeSlot;

final class CreateBookingRequest extends AbstractFormRequest
{
    public function getDto(): CreateBookingDto
    {
        /** @var array<int, array{type: string, quantity: int|string}> $productsInput */
        $productsInput = $this->getHelper()->getArrayOrNull('products') ?? [];

        $products = [];
        foreach ($productsInput as $item) {
            $products[] = new ProductInputDto(
                type: ProductType::from((string) $item['type']),
                quantity: (int) $item['quantity'],
            );
        }

        return new CreateBookingDto(
            id: BookingId::random(),
            clientId: ClientId::fromString($this->getHelper()->getString('client_id')),
            restaurantId: RestaurantId::fromString($this->getHelper()->getString('restaurant_id')),
            timeSlot: TimeSlot::fromStrings(
                $this->getHelper()->getString('date'),
                $this->getHelper()->getString('time')
            ),
            partySize: new PartySize($this->getHelper()->getInt('party_size')),
            specialRequests: $this->getHelper()->getStringOrNull('special_requests'),
            products: $products,
        );
    }
}
