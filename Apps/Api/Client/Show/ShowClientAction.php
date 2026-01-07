<?php

declare(strict_types=1);

namespace Apps\Api\Client\Show;

use Apps\Api\Client\Shared\ClientRes;
use Apps\Api\Client\Shared\Services\ClientResService;

final readonly class ShowClientAction
{
    public function __construct(
        private ClientResService $resService,
    ) {
    }

    public function __invoke(ShowClientDto $dto): ClientRes
    {
        return $this->resService->getClientResource($dto->clientId);
    }
}
