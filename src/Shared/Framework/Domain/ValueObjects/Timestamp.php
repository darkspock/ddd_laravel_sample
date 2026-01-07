<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Domain\ValueObjects;

final readonly class Timestamp
{
    public function __construct(
        public ?string $createdBy,
        public ?string $updatedBy,
        public ?int $createdAt,
        public ?int $updatedAt,
    ) {
    }
}
