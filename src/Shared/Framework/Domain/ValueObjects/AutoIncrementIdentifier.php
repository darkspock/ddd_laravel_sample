<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Domain\ValueObjects;

use Illuminate\Support\Str;

class AutoIncrementIdentifier extends IntegerIdentifier
{
    /** @var array<string|int,int> */
    public static array $lastAutoincrement = [];

    public string $random = 'INITIAL_VALUE'; //needed for serialization

    /**
     * @return static
     */
    public static function autoincrement(): self
    {
        $random = (string) Str::uuid();
        static::$lastAutoincrement[$random] = -1;
        $instance = new static(-1);
        $instance->random = $random;

        return $instance;
    }

    public function getValue(): int
    {
        if (parent::getValue() === -1) {
            return static::$lastAutoincrement[$this->random] ?? parent::getValue();
        }

        return parent::getValue();
    }

    public function value(): int
    {
        if (parent::getValue() === -1) {
            return static::$lastAutoincrement[$this->random] ?? parent::getValue();
        }

        return parent::getValue();
    }

    public function setAutoIncrement(int $value): void
    {
        static::$lastAutoincrement[$this->random] = $value;
        $this->value = $value;
    }

    public function __toString()
    {
        return (string) $this->getValue();
    }

    /**
     * @return static
     */
    public static function fromInt(int $value): self
    {
        return self::create($value);
    }
}
