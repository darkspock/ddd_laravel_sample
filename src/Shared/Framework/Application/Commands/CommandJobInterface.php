<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Application\Commands;

interface CommandJobInterface
{
    public function getQueue(): QueueEnum;

    public function shouldQueue(): bool;

    public function getDelay(): int;

}
