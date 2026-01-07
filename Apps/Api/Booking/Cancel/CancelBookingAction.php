<?php

declare(strict_types=1);

namespace Apps\Api\Booking\Cancel;

use Apps\Api\Booking\Shared\BookingActionRes;
use Src\Reservation\Application\Commands\Cancel\CancelBookingCommand;
use Src\Shared\Framework\Infrastructure\Bus\CommandBus\CommandBusInterface;

final readonly class CancelBookingAction
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(CancelBookingDto $dto): BookingActionRes
    {
        $this->commandBus->dispatch(new CancelBookingCommand(
            bookingId: $dto->bookingId,
            reason: $dto->reason,
        ));

        return new BookingActionRes(message: 'Booking cancelled successfully');
    }
}
