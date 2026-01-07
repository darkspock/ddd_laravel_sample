<?php

declare(strict_types=1);

namespace Apps\Api\Booking\Index;

use Apps\Api\Booking\Shared\BookingListRes;
use Src\Reservation\Application\Queries\Index\IndexBookingsQuery;
use Src\Shared\Framework\Infrastructure\Bus\QueryBus\QueryBusInterface;

final readonly class IndexBookingsAction
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {
    }

    public function __invoke(IndexBookingsDto $dto): BookingListRes
    {
        $collection = $this->queryBus->query(new IndexBookingsQuery(
            restaurantId: $dto->restaurantId,
            clientId: $dto->clientId,
            clientName: $dto->clientName,
            status: $dto->status,
            dateFrom: $dto->dateFrom,
            dateTo: $dto->dateTo,
            limit: $dto->limit,
            offset: $dto->offset,
        ));

        return BookingListRes::fromCollection($collection);
    }
}
