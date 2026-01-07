<?php

declare(strict_types=1);

namespace Apps\Api\Booking\Confirm;

use Apps\Api\Booking\Shared\BookingActionRes;
use Src\Reservation\Application\Commands\Confirm\ConfirmBookingCommand;
use Src\Shared\Framework\Infrastructure\Bus\CommandBus\CommandBusInterface;

final readonly class ConfirmBookingAction
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(ConfirmBookingDto $dto): BookingActionRes
    {
        $this->commandBus->dispatch(new ConfirmBookingCommand(
            bookingId: $dto->bookingId,
        ));

        return new BookingActionRes(message: 'Booking confirmed successfully');
    }
}
