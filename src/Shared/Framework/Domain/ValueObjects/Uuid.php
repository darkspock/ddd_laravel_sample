<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Domain\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;
use Ramsey\Uuid\Uuid as RamseyUuid;

class Uuid implements IdentifierInterface, JsonSerializable
{
    protected string $value;

    final public function __construct(string $value)
    {
        $this->ensureIsValidUuid($value);
        $this->value = $value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    /**
     * @return static
     */
    public static function random(): static
    {
        return static::createInstance(RamseyUuid::uuid4()->toString());
    }

    /**
     * @param  string  $value
     * @return static
     */
    protected static function createInstance(string $value): static
    {
        return new static($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->getValue() === $other->getValue();
    }

    public function __toString(): string
    {
        return $this->getValue();
    }

    private function ensureIsValidUuid(string $id): void
    {
        if (RamseyUuid::isValid($id) === false) {
            throw new InvalidArgumentException(sprintf('<%s> does not allow the value <%s>.', static::class, $id));
        }
    }

    /**
     * @return static
     */
    public static function create(string|int $value): static
    {
        return static::createInstance((string) $value);
    }

    /**
     * @return static|null
     */
    public static function createOrNull(null|string|int $value): ?static
    {
        if ($value === null) {
            return null;
        }

        return static::createInstance((string) $value);
    }

    /**
     * @return static
     */
    public static function fromString(string $value): static
    {
        return static::createInstance($value);
    }
}
