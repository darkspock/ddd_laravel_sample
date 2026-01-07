<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Infrastructure\Bus\ProjectionBus;

use Src\Shared\Framework\Application\Projections\ProjectionInterface;
use Src\Shared\Framework\Helpers\Container;

use function get_class;

use RuntimeException;

final class SimpleProjectionBus implements ProjectionBusInterface
{
    /** @var array <string> */
    public array $routes = [];

    public function project(ProjectionInterface $projection): mixed
    {
        $projectionClass = get_class($projection);
        /** @var class-string<ProjectionHandlerInterface>|null $projectionHandlerName */
        $projectionHandlerName = $this->routes[$projectionClass] ?? preg_replace('/Projection$/', 'Handler', $projectionClass);
        if ($projectionHandlerName === null) {
            throw new RuntimeException('Handler not found for ' . $projectionClass);
        }

        /** @var ProjectionHandlerInterface $projectionHandler */
        $projectionHandler = Container::getObjectInstance($projectionHandlerName);

        return $projectionHandler->__invoke($projection);
    }
}
