# Critical Rules - MUST READ

⚠️ **READ THIS FIRST BEFORE ANY TASK**

These are non-negotiable rules that prevent critical errors in production.

---

## 🚨 MOST CRITICAL RULES (TOP 3)

### 🚨 #1: Requests NEVER Use Laravel Validation

**❌ FORBIDDEN:**
```php
public function rules(): array { return [...]; }  // NEVER
public function after(): array { return [...]; }  // NEVER
$this->validated();  // NEVER
```

**✅ CORRECT:**
```php
public function getDto(): XxxDto {
    // Only map to strongly-typed DTOs
    return new XxxDto(...);
}
```

**📖 Read before creating/modifying Requests:** [HTTP Requests Pattern](http-requests-pattern.md)

---

### 🚨 #2: Actions NEVER Contain Business Logic and NEVER Return JsonResponse

**❌ FORBIDDEN in Actions:**
- `DB::table()` or `Model::find()`
- `foreach`, `array_map` for business logic or data transformations
- Validations or calculations
- Business data transformations
- **❌ CRITICAL: Return `JsonResponse`** - Actions must be decoupled from HTTP
- **❌ CRITICAL: Return DTOs** - DTOs are internal objects

**✅ CORRECT in Actions:**
- `verifyAccess()` - Security only
- `$this->commandBus->dispatch()` - Delegate commands
- `$this->resService->getXxxResource()` - Get Resource via ResService
- **✅ Return single Resource (`XxxRes`)** - Allows testing without HTTP context
- **✅ Return array of Resources (`XxxRes[]`)** - For simple lists without pagination
- **✅ Return ResourceCollection** - For lists with pagination metadata
- **✅ Use `array_map` to transform DTO → Resource** - This is HTTP layer concern, not business logic

**Resource Return Types:**
```php
// Single resource
public function __invoke(...): ClientRes
{
    $dto = $this->queryBus->query(new GetClientQuery($id));
    return new ClientRes($dto);
}

// Array of resources (no pagination)
public function __invoke(...): array
{
    $dtos = $this->queryBus->query(new FindClientsQuery($filters));
    return array_map(
        static fn($dto) => new ClientRes($dto),
        $dtos
    );
}

// ResourceCollection (with pagination)
public function __invoke(...): ClientCollectionRes
{
    $dtos = $this->queryBus->query(new FindClientsQuery($filters));

    $resources = array_map(
        static fn($dto) => new ClientRes($dto),
        $dtos
    );

    return new ClientCollectionRes(
        items: $resources,
        total: count($dtos),
        limit: $dto->limit,
        offset: $dto->offset
    );
}

// ResourceCollection with cursor pagination
public function __invoke(...): ClientCollectionRes
{
    $result = $this->queryBus->query(new FindClientsQuery($filters));

    $resources = array_map(
        static fn($dto) => new ClientRes($dto),
        $result->items
    );

    return new ClientCollectionRes(
        items: $resources,
        nextCursor: $result->nextCursor,
        hasMore: $result->hasMore
    );
}
```

**Pagination Strategies:**
- **Offset/Limit:** Use for small-to-medium datasets where absolute page numbers are needed
- **Cursor:** Use for large datasets or infinite scroll, better performance and consistency

**✅ CORRECT in Controller:**
- `response()->json($resource)` - Controller converts Res to JsonResponse (only HTTP-specific code)

**📖 Read before creating/modifying Actions:** [HTTP Layer Actions](http-layer-actions.md)

---

### 🚨 #3: Handlers NEVER Use `DB::`

**❌ FORBIDDEN in Handlers:**
```php
DB::table('users')->where(...)->get();  // NEVER

DB::table('crm_client_tags')
    ->where('client_id', $clientId)
    ->get();  // NEVER
```

**✅ CORRECT in Handlers:**
```php
// 1. Create Repository Interface in Domain
interface ClientTagRepositoryInterface {
    public function getClientTagNames(string $clientId, string $clientType): array;
}

// 2. Implement in Infrastructure
class ClientTagRepository implements ClientTagRepositoryInterface {
    public function getClientTagNames(string $clientId, string $clientType): array {
        return DB::table('crm_client_tags')->...;  // DB only in Repository
    }
}

// 3. Inject and use in Handler
class XxxHandler {
    public function __construct(
        private ClientTagRepositoryInterface $clientTagRepository,
    ) {}

    public function __invoke() {
        $tags = $this->clientTagRepository->getClientTagNames($clientId, $clientType);
    }
}

// 4. Register in AppServiceProvider
$this->app->bind(ClientTagRepositoryInterface::class, ClientTagRepository::class);
```

**Rule:**
- ❌ NEVER access DB directly in Handlers
- ✅ ALWAYS create Repository if it doesn't exist
- ✅ DB:: only allowed in Infrastructure layer (Repositories)

---

### 🚨 #4: IDs Are Value Objects, NOT Strings

**❌ FORBIDDEN:**
```php
interface TagRepositoryInterface {
    public function getClientIdsByTag(string $tagId): array;  // NEVER string
}

class XxxHandler {
    private function applyFilter(mixed $tagId): void {
        $clients = $this->repository->getClientIdsByTag($tagId);  // NEVER mixed/string
    }
}
```

**✅ CORRECT:**
```php
// 1. Use ValueObject in Repository Interface
interface TagRepositoryInterface {
    public function getClientIdsByTag(TagId $tagId): array;  // ValueObject
}

// 2. Convert mixed to ValueObject in Handler
class XxxHandler {
    private function applyFilter(mixed $tagId): void {
        $tagIdVO = new TagId(MixedHelper::getString($tagId));  // Convert first
        $clients = $this->repository->getClientIdsByTag($tagIdVO);  // Pass ValueObject
    }
}

// 3. In Repository implementation: use ->value()
class TagRepository implements TagRepositoryInterface {
    public function getClientIdsByTag(TagId $tagId): array {
        return DB::table('crm_client_tags')
            ->where('tag_id', $tagId->value())  // Convert to string here
            ->pluck('client_id')
            ->all();
    }
}
```

**Rule:**
- ❌ NEVER use `string` for IDs in interfaces/parameters
- ✅ ALWAYS use ValueObjects (`TagId`, `ClientId`, etc.)
- ✅ If doesn't exist: create `final readonly class XxxId extends Ulid {}`
- ✅ Convert `mixed` with `MixedHelper::getString()`
- ✅ In Repository implementation: use `->value()` to get string

---

## 0. Identifier Pattern (Dual ID Pattern)

### CRITICAL: Dual Identification System

**This CRM microservice uses two types of IDs for each entity:**

#### Internal ID (ULID)
- **Purpose:** Unique identifier within the microservice
- **Format:** ULID (26 characters)
- **Examples:** `RestaurantId`, `GroupClientId`, `RestaurantClientId`
- **Usage:** Internal relationships, foreign keys, references within CRM

```php
final class GroupClientId extends Ulid
{
}
```

#### External ID (AppComposedId)
- **Purpose:** Composite identifier for entities imported from external applications
- **Components:**
  - `AppEnum $app` - Source application (coverManager, premiumGuest, guestOnline, zenChef)
  - `string $id` - ID in external application
- **Examples:** `AppRestaurantId`, `AppGroupClientId`, `AppRestaurantClientId`

```php
abstract readonly class AppComposedId
{
    public function __construct(
        public AppEnum $app,
        public string  $id
    ) {}
}

final readonly class AppRestaurantClientId extends AppComposedId
{
}
```

### When to Use Each One

**Entities IMPORTED from external apps:**
```php
final class Restaurant extends BaseEntity
{
    public function __construct(
        public readonly RestaurantId $id,              // Internal ID (ULID)
        public readonly AppRestaurantId $appRestaurantId,  // External ID
        // ...
    ) {}
}
```
✅ Need BOTH IDs:
- Internal ID for relationships within CRM
- AppComposedId for synchronization and avoiding duplicates

**Entities CREATED in this microservice:**
```php
final class Campaign extends BaseEntity
{
    public function __construct(
        public readonly CampaignId $id,  // Only internal ID
        // ...
    ) {}
}
```
✅ Only need internal ID (ULID)
❌ DON'T need AppComposedId because they don't come from outside

### External Applications (AppEnum)
```php
enum AppEnum: string
{
    case coverManager = 'cover-manager';
    case premiumGuest = 'premium-guest';
    case guestOnline = 'guest-online';
    case zenChef = 'zen-chef';
}
```

### Practical Example
```php
// Client imported from Cover
$client = RestaurantClient::create(
    id: RestaurantClientId::random(),  // Internal: "01HQXXX..."
    appRestaurantClientId: new AppRestaurantClientId(
        app: AppEnum::coverManager,
        id: "12345"  // Original ID in Cover
    ),
    // ...
);

// When referencing client in a campaign
$recipient = CampaignRecipient::create(
    clientId: $client->id,  // Use internal ID
    // ...
);

// If we need to search by external ID
$client = $repository->findByAppId(
    new AppRestaurantClientId(
        app: AppEnum::coverManager,
        id: "12345"
    )
);
```

---

## 1. Database Performance

**MySQL database is HUGE** - Always analyze performance impact before any change

### Rules:
- ❌ **NEVER execute queries in loops**
- ✅ **Minimize number of SQL queries**
- ✅ **Fetch all IDs first, then query with IN clause**

### Bad Example:
```php
foreach ($clients as $client) {
    $bookings = $this->bookingRepository->findByClientId($client->id);
    // WRONG: N queries in loop
}
```

### Good Example:
```php
// 1. Get all client IDs
$clientIds = array_column($clients, 'id');

// 2. Get all bookings in ONE query
$bookings = $this->bookingRepository->findByClientIds($clientIds);

// 3. Join in PHP
foreach ($clients as $client) {
    $client->bookings = array_filter($bookings, fn($b) => $b->clientId === $client->id);
}
```

## 2. Commands in DDD (CQRS)

### CRITICAL: Commands NEVER Return Values

**Commands in CQRS ALWAYS return `void`** - They can ONLY throw exceptions.

### Rules:
- ❌ **Commands NEVER return anything** - return type is `void`
- ✅ **Generate ID BEFORE the command** and pass it in
- ✅ **Commands can ONLY throw exceptions** for errors
- ✅ **Use the generated ID** for subsequent operations

### Bad Example:
```php
// WRONG: Expecting command to return ID
$bookingId = $this->commandBus->dispatch(
    new CreateBookingCommand($data)
);
```

### Good Example:
```php
// CORRECT: Generate ID first, pass to command
$bookingId = BookingId::random();

$this->commandBus->dispatch(
    new CreateBookingCommand(
        id: $bookingId,    // ID passed in
        ...$data
    )
);

// Use already-generated $bookingId for queries or response
$booking = $this->queryBus->query(new GetBookingByIdQuery($bookingId));
```

### Command Structure:
```php
use CoverManager\Shared\Framework\Application\Commands\CommandInterface;

final class CreateBookingCommand implements CommandInterface
{
    public function __construct(
        public readonly BookingId $id,      // ID is PASSED IN
        public readonly ClientId $clientId,
        // ... other data
    ) {}
}

final class CreateBookingHandler
{
    public function __invoke(CreateBookingCommand $command): void  // Returns VOID
    {
        $booking = Booking::create(
            id: $command->id,   // Use ID from command
            // ...
        );

        $this->repository->save($booking);
        // NO RETURN
    }
}
```

### Exception: Security/Infrastructure Operations

**CRITICAL:** Commands **NEVER** return values. NO EXCEPTIONS.

**SOLUTION:** For pure infrastructure/security operations like JWT generation, token generation, or external service authentication where the generated value MUST be returned immediately:

**Use a Query that modifies system state (documented exception in PHPDoc)**

```php
/**
 * Generates a JWT token for authentication.
 *
 * ⚠️ ARCHITECTURAL EXCEPTION: This Query modifies system state (generates token).
 * This is acceptable ONLY for security/infrastructure operations where:
 * - The generated value MUST be returned immediately
 * - The value cannot be retrieved later via a separate Query
 * - Alternative would add unnecessary complexity
 *
 * This is a Query (not a Command) because we need to return the generated token.
 *
 * @return JwtToken The generated JWT token
 */
final class GenerateJwtHandler implements QueryHandlerInterface
{
    public function __invoke(GenerateJwtQuery $query): JwtToken
    {
        // Validate credentials
        // Generate token
        return $token;
    }
}
```

**When to use this exception:**
- ✅ JWT/Token generation (security infrastructure)
- ✅ One-time password generation (security)
- ✅ External authentication that returns session token
- ✅ Sending notification and returning message ID from external service
- ❌ NEVER for business logic operations
- ❌ NEVER for regular entity creation
- ❌ **NEVER use a Command that returns a value** - Use a Query instead

**Key Rule:**
- If you need to return something → Use a **Query** (document the exception in PHPDoc)
- If you don't need to return anything → Use a **Command** (returns void)

## 3. Utility Classes

### DO NOT Create New Utilities Without Permission

**Always check existing utilities first in `src/Shared/Framework/Helpers/`:**

Check the codebase for existing helpers before creating new ones:
- ArrayHelper
- AssertHelper
- CacheHelper
- DateHelper
- EmailHelper
- FileHelper
- NumberHelper
- ObjectHelper
- etc.

**Rule:** If you think you need a new utility, first check if an existing helper already solves it.

## 4. Development Environment

### Docker & Laravel
- ✅ All code runs in Docker
- ❌ **NEVER execute code directly on host**
- **PHP 8.4** environment
- **Laravel 12** framework

### Commands:
```bash
# CORRECT: Run in Docker
docker-compose exec php composer install
docker-compose exec php php artisan test

# WRONG: Run on host (unless explicitly running without Docker)
composer install  # DON'T DO THIS
php artisan test  # DON'T DO THIS
```

## 5. No Deprecated Classes

**Rule:** Never use deprecated classes

**How to check:**
- Look for `@deprecated` annotation
- Check with team if unsure
- Use modern alternatives

## 6. No Orphan Code

### Rules:
- Only create code you will **use NOW**
- Don't create entities/classes not immediately needed
- Don't commit unused code

### Bad Example:
```php
// Creating entity not used yet
class Payment {
    // ... 100 lines of code
}
// WRONG: Not using it in current task
```

### Good Example:
```php
// Only create what you need for current task
class BookingService {
    public function createBooking() {
        // Use this NOW
    }
}
```

## 7. HTTP Layer - Laravel Resources

### CRITICAL: Transform DTOs to Laravel Resources

**In Laravel controllers, ALWAYS transform application DTOs to Laravel API Resources**

### Rules:
- ❌ **NEVER return DTOs from Queries directly in HTTP responses**
- ✅ **ALWAYS create a Laravel Resource class** for each endpoint response
- ✅ **Transform DTO → Resource** before returning
- ✅ **Purpose:** Decouple application layer from HTTP layer

### Why Resources?
- Application DTOs can change without breaking API contracts
- Different endpoints may need different representations of same data
- API versioning becomes easier
- Clear separation of concerns

### Bad Example:
```php
// ❌ WRONG: Returning Query DTO directly
class ClientController extends Controller
{
    public function show(int $id)
    {
        $clientDTO = $this->queryBus->query(
            new GetClientDetailQuery($id)
        );

        // WRONG: Returning DTO directly
        return response()->json($clientDTO);
    }
}
```

### Good Example:
```php
// ✅ CORRECT: Transform DTO to Laravel Resource
class ClientController extends Controller
{
    public function show(int $id)
    {
        // 1. Get DTO from Query
        $clientDTO = $this->queryBus->query(
            new GetClientDetailQuery($id)
        );

        // 2. Transform to Resource (HTTP-specific)
        return new ClientResource($clientDTO);
    }
}
```

### Laravel Resource Structure:
```php
// app/Http/Resources/ClientResource.php
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id->getValue(),
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
        ];
    }
}
```

### Naming Convention:
- Query DTOs: `ClientDetailDTO`, `BookingRefDTO`
- Laravel Resources: `ClientResource`, `BookingResource`
- Suffix is always `Resource` (not `ResponseDTO` or `ViewModel`)

### Location:
- Laravel Resources: `app/Http/Resources/`
- Example: `app/Http/Resources/ClientResource.php`

## Quick Checklist Before Starting Any Task

- [ ] Read Critical Rules (this file)
- [ ] Understand the DDD architecture
- [ ] Check if performance impact on MySQL
- [ ] Verify existing utilities in `src/Shared/Framework/Helpers/`
- [ ] If creating new domain: Discuss with team first
- [ ] Always use Laravel Resources for HTTP responses
- [ ] **⚠️ CRITICAL: If creating/modifying Actions: Read [HTTP Layer Actions](http-layer-actions.md) first**
- [ ] **⚠️ CRITICAL: If creating/modifying Requests: Read [HTTP Requests Pattern](http-requests-pattern.md) first**
- [ ] **⚠️ Requests NEVER have `rules()` or `after()` - Only `getDto()`**

---

**See also:**
- [Architecture](architecture.md) - DDD architecture overview
- [Application Layer](application-layer.md) - Queries and Commands
- [HTTP Layer Actions](http-layer-actions.md) - **CRITICAL: Actions must be THIN**
- [HTTP Requests Pattern](http-requests-pattern.md) - **CRITICAL: NO Laravel validation in Requests**
- [Code Quality](code-quality.md) - SOLID principles and best practices
