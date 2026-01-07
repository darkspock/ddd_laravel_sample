# Pragmatic DDD with Laravel - Sample Project

This is the companion code repository for the book **"Pragmatic DDD with Laravel"**.

## About the Project

A sample restaurant booking system demonstrating Domain-Driven Design patterns with Laravel. The system manages **Bookings** and **Clients** as separate bounded contexts, with bookings containing products (table reservations, menus, wine bottles, events).

### Key Concepts Demonstrated

- **Domain-Driven Design** - Entities, Value Objects, Aggregates, Domain Events
- **CQRS** - Separate Commands and Queries with dedicated handlers
- **Hexagonal Architecture** - Clear separation between Domain, Application, and Infrastructure
- **Type-based validation** - No Laravel `rules()`, validation through Value Objects and DTOs
- **Read Models** - Pragmatic approach for cross-domain queries (booking index with client data)

## Project Structure

```
src/
├── Client/                      # Client bounded context
│   ├── Application/
│   │   ├── Commands/Create/     # CreateClientCommand + Handler
│   │   └── Queries/GetById/     # GetClientByIdQuery + Handler
│   ├── Domain/
│   │   ├── Entities/            # Client entity
│   │   ├── ValueObjects/        # ClientId
│   │   ├── Events/              # ClientCreatedEvent
│   │   ├── Exceptions/          # ClientNotFoundException
│   │   └── Repositories/        # Repository interface
│   └── Infrastructure/
│       └── Persistence/         # Eloquent repository, mapper, model
│
├── Reservation/                 # Booking bounded context
│   ├── Application/
│   │   ├── Commands/
│   │   │   ├── Create/          # CreateBookingCommand + Handler
│   │   │   ├── Confirm/         # ConfirmBookingCommand + Handler
│   │   │   └── Cancel/          # CancelBookingCommand + Handler
│   │   └── Queries/
│   │       ├── GetById/         # GetBookingByIdQuery + Handler
│   │       └── Index/           # IndexBookingsQuery + Handler (uses ReadModel)
│   ├── Domain/
│   │   ├── Entities/            # Booking (aggregate root), BookingProduct
│   │   ├── ValueObjects/        # BookingId, RestaurantId, PartySize, etc.
│   │   ├── Events/              # BookingCreated, BookingConfirmed, etc.
│   │   ├── Enums/               # BookingStatus, ProductType
│   │   ├── Exceptions/          # Domain exceptions
│   │   ├── ReadModels/          # BookingListItemRM (for index query)
│   │   └── Repositories/        # Repository interfaces
│   └── Infrastructure/
│       └── Persistence/         # Eloquent repositories, mappers, models
│
└── Shared/
    └── Framework/               # Base classes (BaseEntity, Ulid, Bus interfaces)

Apps/
├── Api/
│   ├── Client/                  # HTTP layer for client API
│   │   ├── Create/              # POST /api/clients
│   │   ├── Show/                # GET /api/clients/{id}
│   │   └── Shared/              # ClientRes, ClientResService
│   └── Booking/                 # HTTP layer for booking API
│       ├── Create/              # POST /api/bookings
│       ├── Show/                # GET /api/bookings/{id}
│       ├── Index/               # GET /api/bookings
│       ├── Confirm/             # POST /api/bookings/{id}/confirm
│       ├── Cancel/              # POST /api/bookings/{id}/cancel
│       └── Shared/              # BookingRes, BookingListRes, services
└── Shared/
    └── Http/                    # AbstractFormRequest, FormRequestHelper, BaseRes

tests/
├── Integration/                 # API integration tests
│   ├── IntegrationTestCase.php  # Base class with auto-cleanup
│   └── Api/
│       ├── Client/              # Client endpoint tests
│       └── Booking/             # Booking endpoint tests
├── Unit/                        # Unit tests
└── Feature/                     # Feature tests
```

## Domain Model

### Product Types (Enum)

- `table_reservation` - Table reservation fee
- `menu` - Menu/meal
- `bottle_of_wine` - Wine bottle
- `event` - Special event (concert, etc.)

### Booking States

- `pending` - Initial state
- `confirmed` - Confirmed by restaurant
- `cancelled` - Cancelled (with optional reason)

## Key Patterns Demonstrated

### 1. Entity Construction Pattern

```php
final class Booking extends BaseEntity
{
    // Private constructor - enforces use of factory methods
    private function __construct(...) {}

    // For creating NEW entities - enforces business rules, records events
    public static function create(...): self
    {
        $booking = new self(...);
        $booking->recordLast(BookingCreatedEvent::fromEntity($booking));
        return $booking;
    }

    // For hydration from DB - allows any state, no events
    public static function reconstitute(...): self
    {
        return new self(...);
    }
}
```

### 2. Value Objects for Type Safety

```php
// IDs are Value Objects (ULIDs), not strings
final class BookingId extends Ulid {}
final class ClientId extends Ulid {}

// Business rules in Value Objects
final readonly class PartySize
{
    public function __construct(public int $value)
    {
        if ($value < 1) throw InvalidPartySizeException::tooSmall($value, 1);
        if ($value > 20) throw InvalidPartySizeException::tooLarge($value, 20);
    }
}
```

### 3. Request → DTO → Command Flow

```php
// Request creates typed DTO (NO rules() method!)
final class CreateBookingRequest extends AbstractFormRequest
{
    public function getDto(): CreateBookingDto
    {
        return new CreateBookingDto(
            id: BookingId::random(),
            clientId: ClientId::fromString($this->getHelper()->getString('client_id')),
            partySize: new PartySize($this->getHelper()->getInt('party_size')),
            // ...
        );
    }
}
```

### 4. Thin Actions (Return Resources, not JsonResponse)

```php
final class CreateBookingAction
{
    public function __invoke(CreateBookingDto $dto): BookingCreatedRes
    {
        $this->commandBus->dispatch(new CreateBookingCommand(...));
        return new BookingCreatedRes(id: $dto->id->getValue());
    }
}
```

### 5. Command Handlers Return Void

```php
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

### 6. Read Models for Cross-Domain Queries

```php
// BookingReadModelRepository - pragmatic SQL join for listing
// Documented exception to strict DDD boundaries
final class BookingReadModelRepository
{
    public function findAll(IndexBookingsQuery $query): PaginatedCollection
    {
        // Uses SQL join between bookings and clients tables
        // This is a READ-ONLY operation, acceptable for queries
    }
}
```

## Installation

```bash
# Clone the repository
git clone https://github.com/darkspock/ddd_laravel_sample.git
cd ddd_laravel_sample

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Start Laravel Sail
./vendor/bin/sail up -d

# Generate application key
./vendor/bin/sail artisan key:generate

# Run migrations
./vendor/bin/sail artisan migrate
```

## Commands

```bash
# Run all tests
make test

# Run integration tests only
./vendor/bin/sail artisan test --testsuite=Integration

# Run PHPStan
./vendor/bin/phpstan analyse

# Or via make
make phpstan
```

## API Endpoints

### Clients

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/clients` | Create a new client |
| GET | `/api/clients/{id}` | Get client by ID |

### Bookings

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/bookings` | Create a new booking |
| GET | `/api/bookings` | List bookings (with filters) |
| GET | `/api/bookings/{id}` | Get booking by ID |
| POST | `/api/bookings/{id}/confirm` | Confirm a booking |
| POST | `/api/bookings/{id}/cancel` | Cancel a booking |

### Example: Create Client

```bash
curl -X POST http://localhost/api/clients \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+34612345678"
  }'
```

### Example: Create Booking

```bash
curl -X POST http://localhost/api/bookings \
  -H "Content-Type: application/json" \
  -d '{
    "client_id": "01HQ3K...",
    "restaurant_id": "01HQ3K...",
    "date": "2025-02-15",
    "time": "19:00",
    "party_size": 4,
    "special_requests": "Window seat please",
    "products": [
      {"type": "menu", "quantity": 4},
      {"type": "bottle_of_wine", "quantity": 1}
    ]
  }'
```

### Example: List Bookings with Filters

```bash
# Filter by client
curl "http://localhost/api/bookings?client_id=01HQ3K..."

# Filter by status and date range
curl "http://localhost/api/bookings?status=confirmed&date_from=2025-01-01&date_to=2025-12-31"

# Pagination
curl "http://localhost/api/bookings?limit=10&offset=0"
```

## Documentation

See the `ai_docs/` folder for detailed architecture documentation:

- `architecture.md` - Overall DDD and Hexagonal architecture
- `critical-rules.md` - Rules that must never be broken
- `application-layer.md` - CQRS patterns (Commands, Queries, Handlers)
- `http-layer-actions.md` - Action patterns
- `http-requests-pattern.md` - Request/DTO patterns
- `infrastructure.md` - Entity construction and persistence patterns

## Testing

The project includes integration tests that run against the real database:

```
tests/Integration/Api/
├── Client/
│   ├── CreateClientTest.php    # 5 tests
│   └── ShowClientTest.php      # 3 tests
└── Booking/
    ├── CreateBookingTest.php   # 11 tests
    ├── ShowBookingTest.php     # 4 tests
    ├── IndexBookingsTest.php   # 7 tests
    ├── ConfirmBookingTest.php  # 4 tests
    └── CancelBookingTest.php   # 5 tests
```

Total: **39 tests**, **117 assertions**

## License

MIT License - See LICENSE file for details.

## Book

This sample accompanies **"Pragmatic DDD with Laravel"** by John Macias.

**Book website:** [pragmaticddd.com](https://pragmaticddd.com)

**Purchase the book:** [pragmaticddd.com/get/p3k7m9x2](https://pragmaticddd.com/get/p3k7m9x2)
