<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Domain\ValueObjects;

use JsonSerializable;
use RuntimeException;

class StringIdentifier implements IdentifierInterface, JsonSerializable
{
    private string $value;

    /**
     * @return static
     */
    public static function create(int|string $value): self
    {
        if ($value === '') {
            throw new RuntimeException('Error Empty String Id');
        }

        return new static((string) $value);
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    /**
     * @return static|null
     */
    public static function createOrNull(int|string|null $value): ?self
    {
        if ($value === '' || $value === null) {
            return null;
        }

        return new static((string) $value);
    }

    final public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
