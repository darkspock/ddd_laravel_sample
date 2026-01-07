<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Application\Commands;

enum QueueEnum: string
{
    case default = 'default';
    case marketing = 'marketing';
}
