<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Domain\ValueObjects;

interface IdentifierInterface
{
    /**
     * @return static
     */
    public static function create(string|int $value): self;

    public function getValue(): int|string;
}
