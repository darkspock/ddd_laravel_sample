<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Infrastructure\Bus\ProjectionBus;

use Src\Shared\Framework\Application\Projections\ProjectionInterface;

interface ProjectionBusInterface
{
    public function project(ProjectionInterface $projection): mixed;
}
