<?php

declare(strict_types=1);

namespace Apps\Api\Booking\Show;

use Apps\Api\Booking\Shared\BookingRes;
use Apps\Api\Booking\Shared\Services\BookingResService;

final readonly class ShowBookingAction
{
    public function __construct(
        private BookingResService $resService,
    ) {
    }

    public function __invoke(ShowBookingDto $dto): BookingRes
    {
        return $this->resService->getBookingResource($dto->bookingId);
    }
}
