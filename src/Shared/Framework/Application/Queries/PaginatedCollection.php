<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Application\Queries;

/**
 * @template T
 */
final readonly class PaginatedCollection
{
    /**
     * @param  array<int,T>  $items
     */
    public function __construct(
        public array $items = [],
        public int $pageSize = 200,
        public int $page = 1,
        public int $totalCount = 0,
    ) {
    }
}
