# CLAUDE.md

This file provides guidance to Claude Code when working with code in this repository.

## Project Overview

This is a **sample project** demonstrating Domain-Driven Design (DDD) with Laravel. It accompanies the book "Pragmatic DDD with Laravel".

The project implements a simple restaurant booking system using:
- DDD patterns (Entities, Value Objects, Aggregates, Repositories)
- CQRS (Command Query Responsibility Segregation)
- Hexagonal Architecture

## Folder Structure

```
/ddd_laravel_sample/
├── Apps/                    # HTTP Layer
│   ├── Api/                 # API endpoints
│   │   └── Booking/         # Booking endpoints
│   │       ├── Create/      # POST /api/bookings
│   │       ├── Show/        # GET /api/bookings/{id}
│   │       ├── Confirm/     # POST /api/bookings/{id}/confirm
│   │       └── Cancel/      # POST /api/bookings/{id}/cancel
│   └── Shared/              # Shared HTTP components
│       └── Http/            # AbstractFormRequest, FormRequestHelper
├── src/                     # Domain code
│   ├── Reservation/         # Booking bounded context
│   │   ├── Application/     # Commands, Queries, Handlers
│   │   ├── Domain/          # Entities, Value Objects, Events
│   │   └── Infrastructure/  # Repository implementations
│   └── Shared/              # Shared framework
│       └── Framework/       # Base classes for DDD
├── ai_docs/                 # Architecture documentation
├── app/                     # Laravel standard folder
├── config/                  # Laravel configuration
├── database/                # Migrations, seeders
└── routes/                  # API routes
```

## Architecture Rules

### Critical Rules (NEVER break these)

1. **Requests NEVER have `rules()` method** - Only `getDto()` for type-based validation
2. **Actions are thin** - Only verify access, dispatch command/query, return response
3. **Handlers NEVER use `DB::` directly** - Always use repositories
4. **Commands return void** - Never return values, only throw exceptions
5. **IDs are Value Objects** - Never use strings for domain IDs

### Layer Boundaries

```
HTTP Layer (Apps/)
    ↓ dispatches
Application Layer (src/*/Application/)
    ↓ uses
Domain Layer (src/*/Domain/)
    ↓ implemented by
Infrastructure Layer (src/*/Infrastructure/)
```

### Namespace Conventions

- `Apps\Api\{BoundedContext}\{UseCase}` - HTTP layer
- `Src\{BoundedContext}\Application\Commands\{UseCase}` - Commands
- `Src\{BoundedContext}\Application\Queries\{UseCase}` - Queries
- `Src\{BoundedContext}\Domain\Entities` - Entities
- `Src\{BoundedContext}\Domain\ValueObjects` - Value Objects
- `Src\{BoundedContext}\Domain\Repositories` - Repository interfaces
- `Src\{BoundedContext}\Infrastructure\Persistence` - Repository implementations

## Code Patterns

### Request → Action → Command flow

```php
// 1. Request extracts and validates data
final class CreateBookingRequest extends AbstractFormRequest
{
    public function getDto(): CreateBookingDto
    {
        return new CreateBookingDto(
            id: BookingId::random(),
            clientId: ClientId::fromString($this->getHelper()->getString('client_id')),
            // ...
        );
    }
}

// 2. Action dispatches command
final class CreateBookingAction
{
    public function __invoke(CreateBookingDto $dto): JsonResponse
    {
        $this->commandBus->dispatch(new CreateBookingCommand(...));
        return new JsonResponse(['id' => $dto->id->getValue()], 201);
    }
}

// 3. Handler executes business logic
final class CreateBookingHandler
{
    public function __invoke(CreateBookingCommand $command): void
    {
        $booking = Booking::create(...);
        $this->repository->store($booking);
        $this->eventBus->publishEvents($booking->releaseEvents());
    }
}
```

### Entity with Domain Events

```php
final class Booking extends BaseEntity
{
    public static function create(...): self
    {
        $booking = new self(...);
        $booking->recordLast(BookingCreatedEvent::fromEntity($booking));
        return $booking;
    }

    public function confirm(): void
    {
        // Business rule validation
        if ($this->status !== BookingStatus::PENDING) {
            throw BookingCannotBeConfirmedException::notPending($this->status->value);
        }

        $this->status = BookingStatus::CONFIRMED;
        $this->recordLast(BookingConfirmedEvent::fromEntity($this));
    }
}
```

## Common Commands

```bash
# Run tests
php artisan test

# Run PHPStan
./vendor/bin/phpstan analyse

# Run migrations
php artisan migrate
```

## See Also

- `ai_docs/` - Detailed architecture documentation
- `ai_docs/critical-rules.md` - Rules that must never be broken
- `ai_docs/architecture.md` - Full architecture overview
