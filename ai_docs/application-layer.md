# Application Layer (CQRS)

Complete guide to Queries, Commands, Events, and Process Managers in the Application layer.

## Queries (Read Operations)

### Important Rules

1. **All queries must have proper PHPDoc**
   ```php
   /**
    * @see GetClientByIdHandler
    * @implements QueryInterface<ClientDTO>
    */
   final class GetClientByIdQuery implements QueryInterface
   ```

2. **Handlers are stateless**
   - All properties are `readonly`
   - For caching, use cache system, not properties

   ```php
   final class GetClientByIdHandler
   {
       public function __construct(
           private readonly ClientRepository $repository
       ) {}
   }
   ```

### Query Return Types (Allowed)

✅ Allowed returns:
- ReadModel directly
- DTO with simple ValueObjects and Enums
- Primitives (int, string, bool, float)
- Collections/arrays

❌ Not allowed:
- Entities (use DTOs instead)
- Complex mutable objects

### DTOs Can Have Methods

#### ✅ Allowed - Decorating Data:
```php
class BookingDTO {
    public function getAgeFormatted(): string {
        return "3 days and 2 hours";
    }

    public function getDateFormatted(): string {
        return $this->date->format('Y-m-d H:i');
    }
}
```

#### ❌ Not Allowed - Business Rules:
```php
class ClientDTO {
    public function isSpecialClient(): bool {
        // WRONG: Business rule belongs in Entity or ReadModel
        return $this->type === 'VIP';
    }
}
```

**Rule:** DTOs can format/decorate data, but cannot contain business logic.

### Query Naming Conventions

#### Verbs:

**Get** - Always expects to find, throws error if not found
```php
GetClientByIdQuery          // Throws ClientNotFoundException if not found
GetRestaurantByIdQuery      // Throws RestaurantNotFoundException
```

**Find** - May or may not find element
```php
FindClientsByStatusQuery    // Returns empty array if none found
FindBookingByDateQuery      // Returns null if not found
```

**Search** - For filtered searches, ElasticSearch, etc.
```php
SearchClientsQuery          // With filters, pagination
SearchBookingsQuery         // Complex search criteria
```

#### Adjectives:

**Ref** - Returns DTO with minimum data
```php
GetClientRefQuery
// Returns: id, name, surname, phone, email (minimal fields)
```

**Detail** - Returns DTO with all entity info (entity only, no relations)
```php
GetClientDetailQuery
// Returns: All client fields, but NO bookings, NO group
```

**Full** - Returns DTO with all entity info and all possible relations
```php
GetClientFullQuery
// Returns: All client fields + bookings + group + preferences
```

**List** - Returns paginated, denormalized data
```php
SearchClientListQuery
// Returns: Paginated results with denormalized data
// Example: Includes status description, not just status ID
```

### Adding Fields to Existing Queries

#### Scenario 1: Useful for any consumer + no extra cost
→ **Add to existing query**

```php
class GetClientDetailQuery {
    // Add field that's useful for everyone
    public string $preferredLanguage;  // No extra query needed
}
```

#### Scenario 2: Only needed for specific use + high cost
→ **Choose one option:**

**Option A:** Add parameter
```php
class GetClientDetailQuery {
    public function __construct(
        public ClientId $id,
        public bool $withMarketingInfo = false  // Optional, expensive
    ) {}
}

class ClientDetailDTO {
    public ?MarketingInfoDTO $marketingInfo = null;
}
```

**Option B:** Create new query (if very useful)
```php
GetClientMarketingRefQuery  // Specific business purpose
```

### Performance Rules

#### ❌ Forbidden: Queries in Loops

**Bad Example:**
```php
foreach ($clients as $client) {
    $bookings = $this->queryBus->query(
        new GetBookingsByClientIdQuery($client->id)
    );
    // WRONG: N queries
}
```

**Good Example:**
```php
// 1. Get all client bookings in ONE query
$clientIds = array_column($clients, 'id');
$allBookings = $this->queryBus->query(
    new GetBookingsByClientIdsQuery($clientIds)  // Single query with IN clause
);

// 2. Join in PHP
foreach ($clients as $client) {
    $client->bookings = array_filter(
        $allBookings,
        fn($b) => $b->clientId === $client->id
    );
}
```

#### ✅ Required: Minimize SQL Queries

**Example:** Get client with all bookings and reviews
```php
// Maximum 2 queries:

// 1. Get all client bookings
$bookings = $this->getBookings($clientId);

// 2. Get all reviews in ONE query
$bookingIds = array_column($bookings, 'id');
$reviews = $this->getReviewsByBookingIds($bookingIds);

// 3. Join in PHP
foreach ($bookings as $booking) {
    $booking->review = $reviews[$booking->id] ?? null;
}
```

## Commands (Write Operations)

### Command Structure

```php
use CoverManager\Shared\Framework\Application\Commands\CommandInterface;

/**
 * @see CreateClientHandler
 */
final class CreateClientCommand implements CommandInterface
{
    public function __construct(
        public readonly ClientId $id,        // ID is PASSED IN
        public readonly string $name,
        public readonly string $email,
        public readonly RestaurantId $restaurantId
    ) {}
}
```

### Handler Structure

```php
final class CreateClientHandler
{
    public function __construct(
        private readonly ClientRepository $repository,
        private readonly EventBus $eventBus
    ) {}

    public function __invoke(CreateClientCommand $command): void  // Returns VOID
    {
        // 1. Create entity (with ID from command)
        $client = Client::create(
            id: $command->id,
            name: $command->name,
            email: $command->email,
            restaurantId: $command->restaurantId
        );

        // 2. Persist
        $this->repository->save($client);

        // 3. Publish events
        $this->eventBus->publishEvents($client->pullDomainEvents());

        // NO RETURN - Commands return void, only throw exceptions
    }
}
```

### Command Rules

**CRITICAL:**
- Commands **NEVER return anything** - return type is `void`
- Commands can **ONLY throw exceptions** for errors
- **ID is generated BEFORE the command** and passed in
- Commands should be **imperative** (CreateClient, UpdateBooking)
- Handlers should be **stateless**
- Commands should contain **only data** (no logic)
- Business logic belongs in **Entity** or **Domain Service**

### Usage Pattern

```php
// CORRECT: Generate ID first, pass to command, command returns void
$clientId = ClientId::random();
$this->commandBus->dispatch(
    new CreateClientCommand(
        id: $clientId,
        name: 'John Doe',
        email: 'john@example.com',
        restaurantId: $restaurantId
    )
);
// $clientId already available, no return needed

// WRONG: Expecting return value from command
$clientId = $this->commandBus->dispatch(
    new CreateClientCommand(...)  // Commands return void!
);
```

## Process Managers (Saga/Cron)

### When to Use

- Long processes requiring many commands
- Cron jobs
- Complex workflows

### Location
`Application/ProcessManagers`

### Naming Convention

- Command suffix: `Process`
- Handler suffix: `ProcessHandler`

### Example

```php
use CoverManager\Shared\Framework\Application\Commands\CommandInterface;

/**
 * @see SendDailyReportProcessHandler
 */
final class SendDailyReportProcess implements CommandInterface
{
    public function __construct(
        public readonly RestaurantId $restaurantId,
        public readonly DateTimeImmutable $date
    ) {}
}

final class SendDailyReportProcessHandler
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus
    ) {}

    public function __invoke(SendDailyReportProcess $process): void
    {
        // 1. Get data
        $bookings = $this->queryBus->query(
            new GetBookingsByDateQuery($process->date)
        );

        // 2. Generate report
        $report = $this->generateReport($bookings);

        // 3. Send email
        $this->commandBus->dispatch(
            new SendEmailCommand($report)
        );

        // No output - Process Managers have no return value
    }
}
```

### Important Notes

**Process Managers have NO output**
- They orchestrate other commands/queries
- No return value
- Side effects only (emails, events, etc.)

**Advantages:**
- ✅ PHPStan support
- ✅ Easier to test
- ✅ More reusable
- ✅ Less coupling
- ✅ Correct architectural location

**Disadvantages:**
- ❌ More code to write (trade-off for better architecture)

## Event Listeners

### Event Content Best Practices

#### ❌ Wrong Approach 1: Only store ID
```php
class ClientUpdatedEvent {
    public function __construct(public readonly int $clientId) {}
}
// WRONG: Consumer has to figure everything out
```

#### ❌ Wrong Approach 2: Store everything
```php
class ClientUpdatedEvent {
    public function __construct(
        public readonly Client $client,
        public readonly array $oldData,
        public readonly array $newData
    ) {}
}
// WRONG: Too much data, coupling
```

#### ✅ Correct Approach 3: Store minimal relevant data
```php
class ClientUpdatedEvent {
    public function __construct(
        public readonly ClientId $clientId,
        public readonly ?string $oldEmail = null,
        public readonly ?string $newEmail = null
    ) {}
}
// CORRECT: Only event-relevant data
```

### Consistency Problem

```php
// ClientUpdatedEvent contains new name
class OnClientUpdateNameUpdateGroupClient
{
    public function __invoke(ClientUpdatedEvent $event): void
    {
        // PROBLEM: Is this the current name?
        // Another process might have changed client data

        // SOLUTION: Always read from DB
        $client = $this->clientRepository->findById($event->clientId);
        // Now we have the latest data
    }
}
```

**Rule:** With eventual consistency, always read latest data from DB, don't trust event data.

### Best Practices

- ✅ Analyze what information is relevant for each event
- ✅ Include change log information
- ✅ Keep event payload small
- ✅ For specific field logic:
  - Option 1: Create separate event for basic data changes
  - Option 2: Add subtype indicator in event

### Event Types

#### Business Events
Location: `Application/Listeners`
- React to domain events
- Business logic listeners

#### Infrastructure Events
Location: `Infrastructure/Listeners`
- React to external systems (RabbitMQ, webhooks)
- Logging, change tracking
- Not business logic

---

**See also:**
- [Architecture](architecture.md) - BC communication
- [Infrastructure](infrastructure.md) - Repositories
- [Critical Rules](critical-rules.md) - Performance and CQRS rules
- [Code Quality](code-quality.md) - SOLID principles
