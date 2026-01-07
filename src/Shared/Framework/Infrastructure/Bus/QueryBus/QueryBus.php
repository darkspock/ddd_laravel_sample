<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Infrastructure\Bus\QueryBus;

use Src\Shared\Framework\Helpers\CacheHelper;

use function get_class;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\App;

use function is_callable;

use RuntimeException;

final class QueryBus implements QueryBusInterface
{
    /** @var array<string> */
    public static array $routes = [];

    public function query(QueryInterface $query)
    {
        $key = get_class($query);
        $queryHandlerName = self::$routes[$key] ?? preg_replace('/Query$/', 'Handler', $key);
        if ($queryHandlerName === null) {
            throw new RuntimeException('Handler not found for query: ' . $key);
        }
        if (is_callable($queryHandlerName)) {
            return $queryHandlerName($query);
        }
        /** @var QueryHandlerInterface $queryHandler */
        $queryHandler = App::make($queryHandlerName);

        return $queryHandler->__invoke($query);
    }

    public function queryOrFail(QueryInterface $query)
    {
        $res = $this->query($query);
        if ($res === null || (is_array($res) && count($res) === 0)) {
            throw new ModelNotFoundException((string) json_encode($query, JSON_THROW_ON_ERROR));
        }

        return $res;
    }

    public function queryCached(QueryInterface $query, ?int $ttl = null)
    {
        $class = get_class($query);
        $key = md5($class . serialize($query));

        return CacheHelper::onceByKey($key, function () use ($query) {
            return $this->query($query);
        }, $ttl ?? 0);
    }

    /**
     * @return array<int,mixed>
     *
     * @throws QueryBusException
     */
    public function queryIndexed(QueryInterface $query, string $index): array
    {
        /** @var array<mixed> $items */
        $items = $this->query($query);
        /** @var array<int,mixed> $res */
        $res = collect($items)->keyBy($index)->toArray();

        return $res;
    }
}
