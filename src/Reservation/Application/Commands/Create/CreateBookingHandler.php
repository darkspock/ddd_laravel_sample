<?php

declare(strict_types=1);

namespace Src\Reservation\Application\Commands\Create;

use Src\Reservation\Domain\Entities\Booking;
use Src\Reservation\Domain\Repositories\BookingRepositoryInterface;
use Src\Shared\Framework\Infrastructure\Bus\CommandBus\CommandHandlerInterface;
use Src\Shared\Framework\Infrastructure\Bus\EventBus\EventBusInterface;

final readonly class CreateBookingHandler implements CommandHandlerInterface
{
    public function __construct(
        private BookingRepositoryInterface $repository,
        private EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(CreateBookingCommand $command): void
    {
        $booking = Booking::create(
            id: $command->id,
            clientId: $command->clientId,
            restaurantId: $command->restaurantId,
            timeSlot: $command->timeSlot,
            partySize: $command->partySize,
            specialRequests: $command->specialRequests,
            products: $command->products,
        );

        $this->repository->store($booking);
        $this->eventBus->publishEvents($booking->releaseEvents());
    }
}