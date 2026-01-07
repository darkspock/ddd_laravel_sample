<?php

declare(strict_types=1);

namespace Src\Client\Domain\Repositories;

use Src\Client\Domain\Entities\Client;
use Src\Client\Domain\ValueObjects\ClientId;

interface ClientRepositoryInterface
{
    public function store(Client $client): void;

    /**
     * @throws \Src\Client\Domain\Exceptions\ClientNotFoundException
     */
    public function getById(ClientId $id): Client;

    public function findById(ClientId $id): ?Client;
}
