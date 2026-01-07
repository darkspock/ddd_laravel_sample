<?php

declare(strict_types=1);

namespace Apps\Api\Booking\Index;

use Apps\Shared\Http\AbstractFormRequest;
use Src\Reservation\Domain\ValueObjects\ClientId;
use Src\Reservation\Domain\ValueObjects\RestaurantId;

final class IndexBookingsRequest extends AbstractFormRequest
{
    public function getDto(): IndexBookingsDto
    {
        $restaurantId = $this->getHelper()->getStringOrNull('restaurant_id');
        $clientId = $this->getHelper()->getStringOrNull('client_id');

        return new IndexBookingsDto(
            restaurantId: $restaurantId !== null ? RestaurantId::fromString($restaurantId) : null,
            clientId: $clientId !== null ? ClientId::fromString($clientId) : null,
            clientName: $this->getHelper()->getStringOrNull('client_name'),
            status: $this->getHelper()->getStringOrNull('status'),
            dateFrom: $this->getHelper()->getStringOrNull('date_from'),
            dateTo: $this->getHelper()->getStringOrNull('date_to'),
            limit: $this->getHelper()->getIntOrNull('limit') ?? 20,
            offset: $this->getHelper()->getIntOrNull('offset') ?? 0,
        );
    }
}
