# Architecture

Complete guide to DDD (Domain-Driven Design) and Hexagonal Architecture.

## Overview

This project follows **DDD** with **Hexagonal Architecture** principles:

- **Framework-agnostic domain layer**
- Location: `/src` folder
- Follows Hexagonal Architecture
- Modern, clean code
- Laravel 12 as infrastructure framework

## DDD Architecture (Hexagonal Architecture)

### Folder Structure:
```
src/
├── BoundedContext/
│   ├── Domain/
│   │   ├── Entities/          # Business entities
│   │   ├── ValueObjects/      # Immutable values
│   │   ├── Enums/             # Enumerations
│   │   ├── Exceptions/        # Domain exceptions
│   │   ├── ReadModels/        # Read-only DTOs for queries
│   │   ├── Services/          # Domain services
│   │   └── Repositories/      # Repository interfaces (ports)
│   ├── Application/
│   │   ├── Queries/           # Read operations (CQRS)
│   │   ├── Commands/          # Write operations (CQRS)
│   │   ├── ProcessManagers/   # Long-running processes
│   │   ├── Listeners/         # Business event listeners
│   │   └── Projections/       # Read model projections
│   └── Infrastructure/
│       ├── Hydrators/         # Object hydration
│       ├── Repositories/      # Repository implementations (adapters)
│       └── Listeners/         # Infrastructure listeners
```

### Key Concepts:

#### Domain Layer
- **Business logic center**
- No external dependencies
- Pure business rules
- Framework-agnostic

#### Ports (Interfaces)
- Interfaces to outside world
- Defined in Domain layer
- Example: `ClientRepositoryInterface`

#### Adapters (Implementations)
- Specific implementations of ports
- Located in Infrastructure layer
- Example: `EloquentClientRepository implements ClientRepositoryInterface`

#### Application Layer (CQRS)
- Communicates domain with infrastructure
- Queries: Read operations
- Commands: Write operations
- Process Managers: Orchestrate complex workflows

#### Controllers Organization
- Laravel controllers in `app/Http/Controllers`
- Organized by API purpose: internal, external, webhooks, etc.
- Call Application layer (Queries/Commands)
- Transform DTOs to Laravel Resources

## Bounded Contexts

### Current Bounded Contexts:

```
src/
├── Core/
│   ├── Group/              # Group management
│   ├── GroupClient/        # Group client relationships
│   ├── Restaurant/         # Restaurant management
│   └── RestaurantClient/   # Restaurant client relationships
├── Cover/
│   ├── Client/            # Client data from Cover system
│   ├── Group/             # Group data from Cover system
│   └── Restaurant/        # Restaurant data from Cover system
└── Shared/
    ├── Framework/         # Shared framework code
    └── [TransversalBC]/   # Cross-cutting bounded contexts
```

### Transversal (Cross-Cutting) Bounded Contexts

**Rule:** Place cross-cutting functionality in `src/Shared/` when it meets these criteria:

1. **Used by multiple bounded contexts** (not domain-specific)
2. **Provides utility/infrastructure services** (not core business logic)
3. **Reusable across microservices** (generic capability)

**Examples:**
- **Saga** - Process orchestration and long-running transactions
- **Audit** - Generic audit logging
- **Notification** - Cross-BC notifications
- **Search** - Generic search infrastructure

**Structure:**
```
src/Shared/
├── Framework/              # Framework utilities
├── Saga/                   # Process orchestration
│   ├── Domain/
│   ├── Application/
│   └── Infrastructure/
└── [OtherTransversal]/
```

**Decision Guide:**
- Business domain logic → `src/Core/` or `src/[Domain]/`
- Integration with external system → `src/[ExternalSystem]/`
- Cross-cutting utility → `src/Shared/`

### Communication Between Bounded Contexts

#### Primary Method: Query and Command Bus
```php
// From one BC, get data from another BC
$client = $this->queryBus->query(new GetClientByIdQuery($clientId));

// Trigger action in another BC
$this->commandBus->dispatch(new CreateInvoiceCommand($invoiceData));
```

### ReadModels Between BC

**Principle:** Avoid ReadModels between BC (creates coupling)

**When necessary:**

Example: Get invoices with client data
```php
// 1. Get invoices from Billing BC
$invoices = $this->invoiceQuery->findByRestaurant($restaurantId);

// 2. Extract client IDs
$clientIds = array_map(fn($inv) => $inv->clientId, $invoices);

// 3. Get all clients in ONE query from Client BC
$clients = $this->clientQuery->findByIds($clientIds);

// 4. Join in PHP (not in database)
foreach ($invoices as $invoice) {
    $invoice->client = $clients[$invoice->clientId] ?? null;
}
```

**Impact:** One extra query (acceptable) vs. tight coupling (not acceptable)

### BC Domain Experts

Each BC has:
- **Product domain expert**
- **Dev domain expert** (may be Tech Lead)

#### Before Touching Another BC:
1. ✅ Ask domain expert before coding
2. ✅ Tag expert in PR
3. ✅ Discuss approach and impact

**Purpose:** Prevent corruption and conflicts in parallel work

### What Can Leave a BC

#### ❌ Cannot Leave (Stay within BC):
- **Repositories** - Data access must stay internal
- **Entities** - Business entities are BC-specific
- **Domain Services** - Business logic stays internal
- **Complex ValueObjects** - Susceptible to changes

#### ✅ Can Leave (Can be shared):
- **Simple ValueObjects** - Example: `ClientId`, `RestaurantId`
- **Enums** - Example: `BookingStatus`, `PaymentStatus`

**Reasoning:** Simple, stable objects can be shared. Complex, evolving ones cannot.

## Communication Patterns

### Within Same BC

#### Between Domains (within BC):
**Preferred:** Query and Command Bus
```php
$client = $this->queryBus->query(new GetClientQuery($id));
```

**Exception:** Very closely related domains (same module)
```php
// OK if domains are VERY closely related
$client = $this->clientRepository->findById($id);
```

### Between Different BCs

**Always use:** Query and Command Bus
```php
// From any BC, get client from Client BC
$client = $this->queryBus->query(new GetClientByIdQuery($clientId));
```

## Development Environment

- **PHP 8.4**
- **Docker** - All code runs in Docker
- **MySQL** - Large database (always consider performance)
- **Laravel 12** - Modern framework
- **Symfony Components** - Used for some infrastructure (event bus, etc.)

## Architecture Decision Guidelines

### When to Use Full DDD:
- New domain
- Time available for proper design
- Long-term maintainability critical
- Complex business rules

### Before Creating New Domain:
1. Use [Aggregate Design Canvas](https://github.com/ddd-crew/aggregate-design-canvas)
2. Discuss with team
3. Identify domain experts
4. Design entities, events, commands, queries
5. **Only then start coding**

## HTTP Layer - Actions (CRITICAL)

### What is an Action?

**Actions are thin HTTP entry points** that orchestrate Commands/Queries but contain ZERO business logic.

### Location
```
Apps/Api/
├── Campaign/
│   ├── Store/
│   │   ├── StoreCampaignAction.php    # HTTP orchestration
│   │   ├── StoreCampaignRequest.php   # Parse & validate
│   │   └── StoreCampaignDto.php       # Transfer object
│   └── CampaignController.php         # Injects Actions
```

### The Golden Rule: Actions Must Be THIN

**An Action should have MAX 3 responsibilities:**
1. **Verify access** - Check JWT permissions
2. **Dispatch Command/Query** - Delegate to Application layer
3. **Return response** - Convert to Res via ResService

### ❌ PROHIBITED in Actions

**NEVER do these in Actions:**

#### 1. NO DB:: or Query Builder
```php
// ❌ WRONG
public function __invoke(AddClientsDto $dto): JsonResponse
{
    DB::table('marketing_list_clients')
        ->where('list_id', $dto->id)
        ->insert([...]);
}
```

#### 2. NO Business Logic
```php
// ❌ WRONG - Validation logic
if (!$email && !$phone) {
    throw new ValidationException();
}

// ❌ WRONG - Loops and processing
foreach ($dto->clients as $client) {
    // processing...
}

// ❌ WRONG - Calculations
$total = $price * $quantity * (1 + $taxRate);
```

#### 3. NO Direct Model Access
```php
// ❌ WRONG
$campaign = CampaignModel::find($id);
$campaign->update(['status' => 'active']);
```

#### 4. NO Data Transformation
```php
// ❌ WRONG - Transforming data
$transformed = array_map(fn($c) => [
    'name' => strtoupper($c['name']),
    'email' => strtolower($c['email'])
], $clients);
```

### ✅ CORRECT Action Pattern

```php
final readonly class AddMarketingListClientsAction
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private MarketingListResService $resService,
        private SecurityServiceInterface $securityService
    ) {}

    public function __invoke(
        AddMarketingListClientsDto $dto,
        JwtPayload $jwtPayload
    ): MarketingListRes {
        // 1. Verify access (ONLY security check)
        $list = $this->queryBus->query(new GetListByIdQuery($dto->id, $dto->app));
        $this->verifyAccess($jwtPayload, $dto->app, $list->groupId, $list->restaurantId);

        // 2. Dispatch command (ALL logic goes to Handler)
        $this->commandBus->dispatch(
            new AddManualContactsToListCommand(
                listId: $dto->id,
                app: $dto->app,
                contacts: $dto->clients
            )
        );

        // 3. Return response (via ResService)
        return $this->resService->getMarketingListResource($dto->id, $dto->app);
    }
}
```

### Where Does Logic Go?

| What | Where | Why |
|------|-------|-----|
| Validation (email/phone required) | Handler | Business rule |
| Duplicate checking | Repository | Data access logic |
| Loop through contacts | Handler | Processing logic |
| Insert to database | Repository | Data access |
| Update estimated size | Handler + Entity | Business logic |
| Publish events | Handler | Application orchestration |

### Real World Example

**❌ BEFORE - Action with 58 lines, DB::, loops, validation:**
```php
public function __invoke(AddMarketingListClientsDto $dto): MarketingListRes
{
    $clientType = $list->groupId !== null ? 'GroupClient' : 'RestaurantClient';
    $now = time();

    foreach ($dto->clients as $clientData) {
        // Validation in Action ❌
        if (!$email && !$phone) {
            continue;
        }

        // DB:: in Action ❌
        $exists = DB::table('crm_marketing_list_clients')
            ->where('list_id', $dto->id->getValue())
            ->exists();

        // Insert with DB:: ❌
        DB::table('crm_marketing_list_clients')->insert([...]);
    }

    return $this->resService->getMarketingListResource($dto->id, $dto->app);
}
```

**✅ AFTER - Action with 13 lines, delegates to Command:**
```php
public function __invoke(
    AddMarketingListClientsDto $dto,
    JwtPayload $jwtPayload
): MarketingListRes {
    // 1. Verify access
    $list = $this->queryBus->query(new GetListByIdQuery($dto->id, $dto->app));
    $this->verifyAccess($jwtPayload, $dto->app, $list->groupId, $list->restaurantId);

    // 2. Dispatch (all logic in Handler)
    $this->commandBus->dispatch(
        new AddManualContactsToListCommand($dto->id, $dto->app, $dto->clients)
    );

    // 3. Return
    return $this->resService->getMarketingListResource($dto->id, $dto->app);
}
```

**The logic moved to:**
- `AddManualContactsToListHandler` - Validation, orchestration, event publishing
- `MarketingListRepository->addManualContacts()` - Duplicate checking, insertion

### Benefits of Thin Actions

1. **Testability** - Easy to unit test (just verify correct command dispatched)
2. **Reusability** - Command can be reused from CLI, queue, etc.
3. **Maintainability** - Business logic in one place (Handler)
4. **Separation of Concerns** - HTTP layer doesn't know about business rules
5. **DDD Compliance** - Proper hexagonal architecture

### Checklist Before Committing Action

- [ ] Action has ≤ 20 lines of code
- [ ] No `DB::` or `Model::` usage
- [ ] No `foreach`, `array_map`, or loops
- [ ] No `if` statements beyond null checks
- [ ] Dispatches exactly ONE Command or Query
- [ ] Returns Res via ResService
- [ ] All logic is in Handler or Domain

### Layered Dependencies

```
HTTP Layer (Laravel Controllers)
    ↓ depends on
Application Layer (Queries, Commands)
    ↓ depends on
Domain Layer (Entities, Value Objects, Business Rules)
    ↑ defines
Infrastructure Layer (Repositories, External Services)
```

**Key Rule:** Domain layer has NO dependencies. Everything depends on it.

---

**See also:**
- [Critical Rules](critical-rules.md) - Performance and CQRS rules
- [Application Layer](application-layer.md) - CQRS implementation
- [Infrastructure](infrastructure.md) - Repositories and entities
- [Code Quality](code-quality.md) - SOLID principles
