<?php

declare(strict_types=1);

namespace Apps\Api\Booking\Shared;

use Apps\Shared\Http\BaseRes;

final readonly class BookingActionRes extends BaseRes
{
    public function __construct(
        public string $message,
    ) {
    }
}
