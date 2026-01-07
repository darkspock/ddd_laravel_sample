<?php

declare(strict_types=1);

namespace Src\Reservation\Domain\ValueObjects;

use Src\Reservation\Domain\Exceptions\InvalidPartySizeException;
use Src\Shared\Framework\Domain\ValueObjects\ValueObjectInterface;

final readonly class PartySize implements ValueObjectInterface
{
    private const MIN_SIZE = 1;
    private const MAX_SIZE = 20;

    public function __construct(
        public int $value
    ) {
        if ($value < self::MIN_SIZE) {
            throw InvalidPartySizeException::tooSmall($value, self::MIN_SIZE);
        }
        if ($value > self::MAX_SIZE) {
            throw InvalidPartySizeException::tooLarge($value, self::MAX_SIZE);
        }
    }

    public function equals(ValueObjectInterface $other): bool
    {
        return $other instanceof self && $this->value === $other->value;
    }
}
