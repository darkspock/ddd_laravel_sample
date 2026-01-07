<?php

declare(strict_types=1);

namespace Apps\Api\Booking\Shared;

use Apps\Shared\Http\BaseRes;

final readonly class BookingCreatedRes extends BaseRes
{
    public function __construct(
        public string $id,
        public string $message = 'Booking created successfully',
    ) {
    }
}
