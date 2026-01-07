<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Client\Domain\Repositories\ClientRepositoryInterface;
use Src\Client\Infrastructure\Persistence\ClientRepository;
use Src\Reservation\Domain\Repositories\BookingReadModelRepositoryInterface;
use Src\Reservation\Domain\Repositories\BookingRepositoryInterface;
use Src\Reservation\Infrastructure\Persistence\BookingReadModelRepository;
use Src\Reservation\Infrastructure\Persistence\BookingRepository;
use Src\Shared\Framework\Infrastructure\Bus\CommandBus\CommandBusInterface;
use Src\Shared\Framework\Infrastructure\Bus\CommandBus\SimpleCommandBus;
use Src\Shared\Framework\Infrastructure\Bus\EventBus\EventBusInterface;
use Src\Shared\Framework\Infrastructure\Bus\EventBus\SimpleEventBus;
use Src\Shared\Framework\Infrastructure\Bus\QueryBus\QueryBus;
use Src\Shared\Framework\Infrastructure\Bus\QueryBus\QueryBusInterface;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bus bindings
        $this->app->bind(QueryBusInterface::class, QueryBus::class);
        $this->app->bind(CommandBusInterface::class, SimpleCommandBus::class);
        $this->app->bind(EventBusInterface::class, SimpleEventBus::class);

        // Reservation repositories
        $this->app->bind(BookingRepositoryInterface::class, BookingRepository::class);
        $this->app->bind(BookingReadModelRepositoryInterface::class, BookingReadModelRepository::class);

        // Client repository
        $this->app->bind(ClientRepositoryInterface::class, ClientRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
