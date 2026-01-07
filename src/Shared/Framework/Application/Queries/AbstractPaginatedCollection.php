<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Application\Queries;

/**
 * @template T
 */
abstract class AbstractPaginatedCollection
{
    public ?int $pageSize = null;

    public ?int $page = null;

    public ?int $totalCount = null;

    /** @var array<int,T> */
    public array $items = [];

    public function setPagination(int $offset, int $limit): void
    {
        $this->pageSize = $limit;
        $this->page = (int) ($offset / $limit) + 1;
        if ($this->totalCount !== null) {
            $this->page = min($this->page, (int) ceil($this->totalCount / $limit));
        }
    }
}
