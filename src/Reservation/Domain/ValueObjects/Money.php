<?php

declare(strict_types=1);

namespace Src\Reservation\Domain\ValueObjects;

final class Money
{
    public function __construct(
        public readonly int $cents,
        public readonly string $currency = 'EUR',
    ) {
        if ($cents < 0) {
            throw new \InvalidArgumentException('Money amount cannot be negative');
        }
    }

    public static function fromCents(int $cents, string $currency = 'EUR'): self
    {
        return new self($cents, $currency);
    }

    public static function zero(string $currency = 'EUR'): self
    {
        return new self(0, $currency);
    }

    public function add(Money $other): self
    {
        $this->ensureSameCurrency($other);

        return new self($this->cents + $other->cents, $this->currency);
    }

    public function multiply(int $factor): self
    {
        return new self($this->cents * $factor, $this->currency);
    }

    public function getValue(): int
    {
        return $this->cents;
    }

    public function toDecimal(): float
    {
        return $this->cents / 100;
    }

    public function format(): string
    {
        return sprintf('%.2f %s', $this->toDecimal(), $this->currency);
    }

    private function ensureSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException(
                sprintf('Cannot operate on different currencies: %s and %s', $this->currency, $other->currency)
            );
        }
    }
}
