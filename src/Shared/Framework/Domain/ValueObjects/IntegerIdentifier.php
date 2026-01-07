<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Domain\ValueObjects;

use Src\Shared\Framework\Domain\Exceptions\InvalidIdentifierException;
use JsonSerializable;

class IntegerIdentifier implements IdentifierInterface, JsonSerializable
{
    protected int $value = -1;

    /**
     * @return static
     */
    public static function create(int|string $value): self
    {
        $intValue = (int) $value;
        if ($intValue === 0) {
            throw new InvalidIdentifierException(static::class, $intValue);
        }

        return new static($intValue);
    }

    /**
     * @return static|null
     */
    public static function createOrNull(int|string|null $value): ?self
    {
        if ($value === 0) {
            return null;
        }
        if ($value === null) {
            return null;
        }
        $intValue = (int) $value;
        if ($intValue === 0) {
            throw new InvalidIdentifierException(static::class, $intValue);
        }

        return new static($intValue);
    }

    final public function __construct(int $value)
    {
        $this->value = $value;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * For Compatibility with Copilot
     */
    public function value(): int
    {
        return $this->getValue();
    }

    public function equals(IdentifierInterface $other): bool
    {
        return $this->getValue() === $other->getValue();
    }

    public function __toString()
    {
        return (string) $this->value;
    }

    public function jsonSerialize(): int
    {
        return $this->value;
    }
}
