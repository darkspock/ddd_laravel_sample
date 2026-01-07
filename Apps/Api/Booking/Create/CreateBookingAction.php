<?php

declare(strict_types=1);

namespace Apps\Api\Booking\Create;

use Apps\Api\Booking\Shared\BookingCreatedRes;
use Src\Reservation\Application\Commands\Create\CreateBookingCommand;
use Src\Shared\Framework\Infrastructure\Bus\CommandBus\CommandBusInterface;

final readonly class CreateBookingAction
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(CreateBookingDto $dto): BookingCreatedRes
    {
        // Convert HTTP DTOs to Command format
        $products = array_map(
            fn (ProductInputDto $product): array => [
                'type' => $product->type,
                'quantity' => $product->quantity,
            ],
            $dto->products
        );

        $this->commandBus->dispatch(new CreateBookingCommand(
            id: $dto->id,
            clientId: $dto->clientId,
            restaurantId: $dto->restaurantId,
            timeSlot: $dto->timeSlot,
            partySize: $dto->partySize,
            specialRequests: $dto->specialRequests,
            products: $products,
        ));

        return new BookingCreatedRes(id: $dto->id->getValue());
    }
}
