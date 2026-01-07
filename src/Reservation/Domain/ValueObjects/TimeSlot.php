<?php

declare(strict_types=1);

namespace Src\Reservation\Domain\ValueObjects;

use DateTimeImmutable;
use Src\Shared\Framework\Domain\ValueObjects\ValueObjectInterface;

final readonly class TimeSlot implements ValueObjectInterface
{
    public function __construct(
        private DateTimeImmutable $date,
        private DateTimeImmutable $time
    ) {
    }

    public static function fromStrings(string $date, string $time): self
    {
        return new self(
            date: new DateTimeImmutable($date),
            time: new DateTimeImmutable($time)
        );
    }

    public static function fromDateTime(DateTimeImmutable $dateTime): self
    {
        return new self(
            date: $dateTime,
            time: $dateTime
        );
    }

    public function date(): DateTimeImmutable
    {
        return $this->date;
    }

    public function time(): DateTimeImmutable
    {
        return $this->time;
    }

    public function dateString(): string
    {
        return $this->date->format('Y-m-d');
    }

    public function timeString(): string
    {
        return $this->time->format('H:i');
    }

    public function equals(ValueObjectInterface $other): bool
    {
        return $other instanceof self
            && $this->dateString() === $other->dateString()
            && $this->timeString() === $other->timeString();
    }
}
