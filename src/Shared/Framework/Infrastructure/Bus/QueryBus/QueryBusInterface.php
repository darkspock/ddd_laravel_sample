<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Infrastructure\Bus\QueryBus;

interface QueryBusInterface
{
    /**
     * @template T
     *
     * @param  QueryInterface<T>  $query
     * @return T
     *
     * @throws QueryBusException
     */
    public function query(QueryInterface $query);

    /**
     * @return array<mixed>
     * @throws QueryBusException
     */
    public function queryIndexed(QueryInterface $query, string $index): array;

    /**
     * @template T
     *
     * @param  QueryInterface<T>  $query
     * @return T
     *
     * @throws QueryBusException
     */
    public function queryCached(QueryInterface $query, ?int $ttl = null);

    /**
     * @template T
     *
     * @param  QueryInterface<T>  $query
     * @return T
     *
     * @throws QueryBusException
     */
    public function queryOrFail(QueryInterface $query);
}
