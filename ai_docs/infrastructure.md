# Infrastructure Layer

Complete guide to Repositories, Tables, Entities, and Hydrators.

## Repositories

### Basic Rules

1. **Repositories are stateless**
   - No state properties
   - Only readonly properties in exceptional cases
   - All dependencies injected via constructor

2. **Public methods return ONLY:**
   - Entities
   - ReadModels
   - Collections
   - Arrays (of entities or readmodels)
   - Scalar values (int, string, bool, float)

3. **Never return raw database query data directly**
   - ❌ Don't return: Raw query builder results
   - ✅ Return: Hydrated entities or ReadModels

## ReadModels

**CRITICAL:** Repository methods that return query data MUST use ReadModels instead of `array<mixed>`.

### What is a ReadModel?

A ReadModel is a **read-only DTO** optimized for queries:
- Located in `Domain/ReadModels/`
- Name ends with `RM` or `ReadModel`
- Always `readonly` with public properties
- NO business logic
- Used for complex queries and data projections

### When to Use ReadModels

Use ReadModels when:
1. Repository method returns query data that's not a full entity
2. Aggregating data from multiple tables
3. Projecting a subset of entity properties
4. Returning computed/derived data
5. Any method that would return `array<array{...}>`

### ❌ INCORRECT - Returning mixed arrays

```php
/**
 * @return array<array{client_id: string|null, name: string, email: string|null}>
 */
public function getListContacts(ListId $listId): array
{
    $results = $this->db->query(/* ... */);

    return $results->map(fn($r) => [
        'client_id' => $r->client_id,
        'name' => $r->name,
        'email' => $r->email,
    ])->toArray();
}
```

### ✅ CORRECT - Using ReadModel

**Step 1:** Create ReadModel in `Domain/ReadModels/`

```php
// src/Marketing/List/Domain/ReadModels/ListContactRM.php
namespace CoverManager\Marketing\List\Domain\ReadModels;

final readonly class ListContactRM
{
    public function __construct(
        public ?string $clientId,
        public string $name,
        public ?string $email,
        public ?string $phone,
        public ?string $countryCode,
        public bool $acceptsMarketing,
    ) {}
}
```

**Step 2:** Update Repository Interface

```php
// src/Marketing/List/Domain/Repositories/MarketingListRepositoryInterface.php
use CoverManager\Marketing\List\Domain\ReadModels\ListContactRM;

interface MarketingListRepositoryInterface
{
    /**
     * @return array<ListContactRM>
     */
    public function getListContacts(ListId $listId, AppEnum $app): array;
}
```

**Step 3:** Implement in Repository

```php
// src/Marketing/List/Infrastructure/Persistence/MarketingListRepository.php
use CoverManager\Marketing\List\Domain\ReadModels\ListContactRM;

public function getListContacts(ListId $listId, AppEnum $app): array
{
    $results = MarketingListClientModel::query()
        ->where('list_id', $listId->value())
        ->get();

    return $results->map(function ($result) {
        return new ListContactRM(
            clientId: $result->client_id,
            name: $result->name,
            email: $result->email,
            phone: $result->phone,
            countryCode: $result->country_code,
            acceptsMarketing: (bool) $result->accepts_marketing,
        );
    })->toArray();
}
```

**Step 4:** Use in QueryHandler

```php
// src/Marketing/List/Application/Queries/GetListContactsHandler.php
public function __invoke(GetListContactsQuery $query): array
{
    // Returns array<ListContactRM> with full type safety
    $contacts = $this->repository->getListContacts($query->listId, $query->app);

    // Access properties with autocomplete and type checking
    foreach ($contacts as $contact) {
        $email = $contact->email;      // ✅ Type-safe property access
        $name = $contact->name;         // ✅ IDE autocomplete works
        $accepts = $contact->acceptsMarketing;
    }

    return $contacts;
}
```

### ReadModel Naming Convention

- **Suffix with `RM`** for ReadModel: `ListContactRM`, `CampaignStatsRM`
- **Or use full `ReadModel`**: `ListContactReadModel`, `CampaignStatsReadModel`
- Name describes the **data contents**, not the usage

### ReadModel Benefits

1. **Type Safety** - PHPStan validates all property access
2. **IDE Support** - Full autocomplete for properties
3. **Refactoring** - Easy to find all usages
4. **Documentation** - Clear contract of returned data
5. **Immutability** - `readonly` prevents accidental mutations

### Repository Structure

```php
final class ClientRepository implements ClientRepositoryInterface
{
    public function __construct(
        private readonly DB $db,
        private readonly ClientHydrator $hydrator
    ) {}

    public function findById(ClientId $id): ?Client
    {
        $row = $this->db->table('clients')
            ->where('id', $id->getValue())
            ->first();

        if ($row === null) {
            return null;
        }

        return $this->hydrator->hydrate($row);
    }

    public function save(Client $client): void
    {
        $data = $this->hydrator->dehydrate($client);

        if ($client->id()->getValue() === 0) {
            $this->db->table('clients')->insert($data);
        } else {
            $this->db->table('clients')
                ->where('id', $client->id()->getValue())
                ->update($data);
        }
    }
}
```

### Complex Repositories

#### Problem: Repository Growing Too Large

**Solution:** Extract methods to separate file

```php
// Main repository
class ClientRepository implements ClientRepositoryInterface
{
    public function findById(ClientId $id): ?Client { }
    public function save(Client $client): void { }
}

// Separate file for complex queries
class ClientRMQueryRepository
{
    public function __construct(
        private readonly DB $db
    ) {}

    public function findClientWithStatsRM(ClientId $id): ?ClientStatsRM
    {
        // Complex query logic here
    }
}
```

**Note:** No interface needed for now (too verbose for current needs)

### Method Naming

#### ❌ Bad - Describes usage:
```php
findClientRMAutocomplete()  // What is it used for
```

#### ✅ Good - Describes content:
```php
findClientWithStatsRM()     // What it contains
```

**Rule:** Name should describe WHAT it returns, not WHERE it's used.

---

## Tables

### Table Objects

For each table, create a `*Table` class with:
- `TABLE_NAME` constant
- PHPDoc with all properties and types

#### Example:
```php
/**
 * @property int $id
 * @property string $name
 * @property string $surname
 * @property string $email
 * @property ?string $phone
 * @property ?string $mobile
 * @property int $restaurant_id
 * @property string $created_at
 * @property string $updated_at
 * @property int $type
 * @property bool $is_special
 */
class ClientTable
{
    public const TABLE_NAME = 'clients';
}
```

### Rules:

1. **TABLE_NAME constant**
   - This is the ONLY constant used in code to reference table
   - Never use string literals like `'clients'` in queries

2. **Define all properties with types in PHPDoc**
   - Use `?` for nullable fields
   - Use proper types: int, string, bool, float

3. **Benefits:**
   - IDE autocomplete
   - PHPStan type checking
   - Documentation

### Using Table Objects

```php
// ✅ CORRECT
$this->db->table(ClientTable::TABLE_NAME);
$this->db->table(ClientTable::TABLE_NAME)->where('id', $id);

// ❌ WRONG
$this->db->table('clients');  // String literal
```

---

## Entities

### Entity Construction Pattern (create vs reconstitute)

**CRITICAL:** Entities must protect their invariants. The constructor should be private.

#### The Problem

If `__construct` is public, anyone can create an entity in an invalid state:

```php
// ❌ WRONG - Public constructor allows invalid state
class Booking
{
    public function __construct(
        public BookingId $id,
        public BookingStatus $status,  // Could pass CONFIRMED directly!
        // ... products not included, incomplete entity
    ) {}
}

// This bypasses business rules!
$booking = new Booking(
    id: BookingId::random(),
    status: BookingStatus::CONFIRMED,  // Should go through confirm() method!
);
```

#### The Solution: Two Factory Methods

```php
final class Booking extends BaseEntity
{
    /** @var array<BookingProduct> */
    private array $products = [];

    /**
     * Private constructor - enforces use of factory methods.
     */
    private function __construct(
        public readonly BookingId $id,
        public readonly ClientId $clientId,
        public BookingStatus $status,
        // ...
    ) {}

    /**
     * Factory for NEW entities - enforces business rules.
     *
     * @param array<array{type: ProductType, quantity: int}> $products
     */
    public static function create(
        BookingId $id,
        ClientId $clientId,
        array $products,
        // ...
    ): self {
        $booking = new self(
            id: $id,
            clientId: $clientId,
            status: BookingStatus::PENDING,  // ✅ Always starts as PENDING
            // ...
        );

        foreach ($products as $product) {
            $booking->addProduct($product['type'], $product['quantity']);
        }

        $booking->recordLast(BookingCreatedEvent::fromEntity($booking));

        return $booking;
    }

    /**
     * Factory for HYDRATION from database - allows any state.
     *
     * Use this ONLY in Mappers/Repositories, never in application code.
     *
     * @param array<BookingProduct> $products
     */
    public static function reconstitute(
        BookingId $id,
        ClientId $clientId,
        BookingStatus $status,  // ✅ Can be any status from DB
        array $products,
        // ...
    ): self {
        $booking = new self(
            id: $id,
            clientId: $clientId,
            status: $status,
            // ...
        );

        $booking->products = $products;

        return $booking;
    }
}
```

#### Using in Mapper/Hydrator

```php
final class BookingMapper
{
    public static function toDomain(BookingModel $model): Booking
    {
        $bookingId = BookingId::fromString($model->id);

        // Map child entities first
        $products = [];
        if ($model->relationLoaded('products')) {
            foreach ($model->products as $productModel) {
                $products[] = self::productToDomain($productModel, $bookingId);
            }
        }

        // Use reconstitute() for hydration
        $booking = Booking::reconstitute(
            id: $bookingId,
            clientId: ClientId::fromString($model->client_id),
            status: BookingStatus::from($model->status),
            products: $products,
            // ...
        );

        $booking->setIsNew(false);

        return $booking;
    }
}
```

#### Rules Summary

| Method | Purpose | When to Use | Invariants |
|--------|---------|-------------|------------|
| `create()` | New entities | Application code | ✅ Enforced |
| `reconstitute()` | Hydration from DB | Mappers only | ❌ Bypassed |
| `__construct` | Internal only | Never directly | N/A |

#### Additional Guidelines

1. **Never expose setters for child collections** - Use `reconstitute()` instead of `setProducts()`
2. **Child entities should follow the same pattern** - Private constructor + `create()` + `reconstitute()`
3. **reconstitute() should NOT emit domain events** - Events were already emitted when entity was created

---

### Entities on Legacy Tables

#### Rule 1: Entity is Not Guilty of Obsolete DB Design

Don't transfer infrastructure problems to entities.

**Problem Example:**

Table `payments` has fields:
- `booking_id` (always -1 for eCommerce)
- `order_id` (always -1 for eCommerce)
- `type` (always 2 for eCommerce)

#### ❌ Wrong - Legacy fields leak into entity:
```php
class ECommerce
{
    private int $bookingId;  // Legacy cruft
    private int $orderId;    // Legacy cruft
    private int $type;       // Legacy cruft
    private float $amount;   // Business field
}
```

#### ✅ Correct - Entity only has business fields:
```php
class ECommerce
{
    private ECommerceId $id;
    private Money $amount;
    private PaymentStatus $status;
    // Only business fields, no legacy cruft
}

// Handle legacy in Hydrator/Repository
class ECommerceHydrator
{
    public function hydrate(stdClass $row): ECommerce
    {
        // Map only business fields
        return new ECommerce(
            id: new ECommerceId($row->id),
            amount: new Money($row->amount),
            status: PaymentStatus::from($row->status)
        );
    }

    public function dehydrate(ECommerce $ecommerce): array
    {
        return [
            'id' => $ecommerce->id()->getValue(),
            'amount' => $ecommerce->amount()->getValue(),
            'status' => $ecommerce->status()->value,
            // Set legacy fields here
            'booking_id' => -1,
            'order_id' => -1,
            'type' => 2
        ];
    }
}
```

### Rule 2: No Orphan Code

- Only create code you will use **NOW**
- Don't create Entity if not using it yet
- Don't commit unused code

#### ❌ Bad:
```php
// Creating complete entity not used in current task
class Payment {
    // ... 200 lines of code
    // But not using it yet!
}
```

#### ✅ Good:
```php
// Only create what you need for current task
class BookingService {
    public function createBooking() {
        // Using this NOW in current task
    }
}
```

### Anemic Entities

#### Business Logic in Entities:
✅ Should be in entity:
- Validate data and maintain invariants
- Business relationships within the domain
- Calculations and business rules specific to this entity

```php
class Client
{
    public function changeEmail(string $newEmail): void
    {
        // Validation (invariant)
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException($newEmail);
        }

        $this->email = $newEmail;
        $this->recordEvent(new ClientEmailChangedEvent($this->id, $newEmail));
    }

    public function isSpecial(): bool
    {
        // Business rule specific to client
        return $this->type === ClientType::VIP;
    }
}
```

#### Business Logic in Domain Services:
✅ Should be in domain service:
- Rules involving external entities
- Rules between different instances of entities
- Complex validations requiring repository access

```php
class ValidateBookingService
{
    public function __construct(
        private readonly BookingRepository $repository
    ) {}

    public function canCreateBooking(
        RestaurantId $restaurantId,
        DateTimeImmutable $datetime,
        int $partySize
    ): bool {
        // Needs repository - can't be in entity
        $existingBooking = $this->repository->findByRestaurantAndDatetime(
            $restaurantId,
            $datetime
        );

        return $existingBooking === null;
    }
}
```

---

## Hydrators

### Purpose

Transform between:
- Database rows (stdClass/array) → Entities/ReadModels
- Entities → Database arrays

### Hydrator Structure

```php
final class ClientHydrator
{
    public function hydrate(stdClass|array $row): Client
    {
        // Handle both stdClass and array
        $data = (object) $row;

        return new Client(
            id: new ClientId($data->id),
            name: $data->name,
            surname: $data->surname,
            email: $data->email,
            phone: $data->phone ?? null,
            restaurantId: new RestaurantId($data->restaurant_id),
            type: ClientType::from($data->type),
            isSpecial: (bool)$data->is_special
        );
    }

    public function dehydrate(Client $client): array
    {
        return [
            'id' => $client->id()->getValue(),
            'name' => $client->name(),
            'surname' => $client->surname(),
            'email' => $client->email(),
            'phone' => $client->phone(),
            'restaurant_id' => $client->restaurantId()->getValue(),
            'type' => $client->type()->value,
            'is_special' => $client->isSpecial() ? 1 : 0
        ];
    }
}
```

### Hydrating Collections

```php
final class ClientHydrator
{
    public function hydrateMany(array $rows): array
    {
        return array_map(
            fn($row) => $this->hydrate($row),
            $rows
        );
    }
}
```

---

## Aggregate/Entity Design

Before writing entity code, use this canvas:
https://github.com/ddd-crew/aggregate-design-canvas

### Brainstorming Topics:

1. **Properties**
   - What data does the entity hold?

2. **Validation Rules** (invariants)
   - What rules must ALWAYS be true?
   - Example: Email must be valid format

3. **Exceptions to Rules**
   - How to handle edge cases?
   - Example: Rounding cents causes total mismatch

4. **Events**
   - Which domain events trigger?
   - Example: ClientCreated, ClientEmailChanged

5. **States and Transitions**
   - What states can entity be in?
   - How does it transition between states?
   - Example: Booking: Pending → Confirmed → Cancelled

6. **Performance:**
   - Aggregate or multiple entities?
   - Data volume expectations
   - Query limits and pagination

7. **Commands and Main Queries**
   - What operations will be performed?
   - What queries will be common?

8. **Concurrency**
   - Will there be concurrency conflicts?
   - Example: Two processes updating same booking

9. **Entity Relations**
   - How does it relate to other entities?
   - Example: Client has many Bookings

10. **Infrastructure**
    - Which tables?
    - How to manage data?
    - Legacy considerations?

### Important:

**ONLY start coding after completing this design**

Don't skip this step for complex domains!

---

## Migrations

### Rules:
- Using Laravel migrations
- Write clear, reversible migrations

### Example:
```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->foreignId('restaurant_id')->constrained();
            $table->integer('type');
            $table->boolean('is_special')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
```

---

**See also:**
- [Application Layer](application-layer.md) - Queries and Commands using repositories
- [Code Quality](code-quality.md) - SOLID principles for entities
- [Architecture](architecture.md) - DDD structure
- [Critical Rules](critical-rules.md) - Performance and best practices
