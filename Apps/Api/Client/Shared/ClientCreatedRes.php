<?php

declare(strict_types=1);

namespace Apps\Api\Client\Shared;

use Apps\Shared\Http\BaseRes;

final readonly class ClientCreatedRes extends BaseRes
{
    public function __construct(
        public string $id,
        public string $message = 'Client created successfully',
    ) {
    }
}
