<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Domain\ValueObjects;

interface JsonPersistenceInterface
{
    /**
     * @return static
     */
    public static function fromJson(string $json): self;

    public function toJson(): string;
}
