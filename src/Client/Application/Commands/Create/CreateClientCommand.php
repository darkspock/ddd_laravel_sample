<?php

declare(strict_types=1);

namespace Src\Client\Application\Commands\Create;

use Src\Client\Domain\ValueObjects\ClientId;
use Src\Shared\Framework\Application\Commands\CommandInterface;

final readonly class CreateClientCommand implements CommandInterface
{
    public function __construct(
        public ClientId $id,
        public string $name,
        public ?string $email,
        public ?string $phone,
    ) {
    }
}
