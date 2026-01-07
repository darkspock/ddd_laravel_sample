<?php

declare(strict_types=1);

namespace Src\Reservation\Application\Commands\Cancel;

use Src\Reservation\Domain\Repositories\BookingRepositoryInterface;
use Src\Shared\Framework\Infrastructure\Bus\CommandBus\CommandHandlerInterface;
use Src\Shared\Framework\Infrastructure\Bus\EventBus\EventBusInterface;

final readonly class CancelBookingHandler implements CommandHandlerInterface
{
    public function __construct(
        private BookingRepositoryInterface $repository,
        private EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(CancelBookingCommand $command): void
    {
        $booking = $this->repository->getById($command->bookingId);

        $booking->cancel($command->reason);

        $this->repository->store($booking);
        $this->eventBus->publishEvents($booking->releaseEvents());
    }
}