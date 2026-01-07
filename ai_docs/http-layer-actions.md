# HTTP Layer - Actions (CRITICAL)

**⚠️ IMPORTANT: Read before creating or modifying any Action**

## Executive Summary

**Actions MUST be thin. NEVER use `DB::`, loops, validations, or business logic in Actions.**

All that goes in **Handlers** (Application layer) and **Repositories** (Infrastructure layer).

---

## What is an Action?

An **Action** is the HTTP entry point in hexagonal architecture. Its only responsibility is to **orchestrate** the call between the HTTP layer and the Application layer.

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

---

## The Golden Rule: THIN Actions

**An Action has exactly 3 responsibilities (and ONLY 3):**

1. ✅ **Verify access** - Call `verifyAccess()` with JWT
2. ✅ **Dispatch Command/Query** - Delegate to Application layer via CommandBus/QueryBus
3. ✅ **Return response** - Use `ResService` to convert DTO → Res

**Anything else does NOT belong in the Action.**

---

## ❌ FORBIDDEN in Actions

### 0. ❌ CRITICAL: DO NOT return JsonResponse, arrays, or DTOs

**NEVER return these types from Actions:**

```php
// ❌ FORBIDDEN
public function __invoke(...): JsonResponse {
    return new JsonResponse([...]);
}

// ❌ FORBIDDEN
public function __invoke(...): array {
    return ['id' => $id, 'name' => $name];
}

// ❌ FORBIDDEN
public function __invoke(...): CampaignDto {
    return $dto;
}
```

**✅ ALWAYS return Resource (XxxRes):**

```php
// ✅ CORRECT
public function __invoke(...): CampaignRes {
    $this->commandBus->dispatch(new CreateCampaignCommand(...));
    return $this->resService->getCampaignResource($campaignId);
}
```

**Why is it wrong?**
- Actions must be decoupled from HTTP layer
- Not testable without HTTP context
- Not reusable from CLI/Queue
- Violates separation of responsibilities

**Where should it go?**
- **Action**: Returns `XxxRes` via `ResService`
- **Controller**: Converts `Res` to `JsonResponse` with `response()->json($resource)`
- **ResService**: Located in `Apps/Api/{Module}/Shared/Services/XxxResService.php`
- **Resource**: Located in `Apps/Api/{Module}/Shared/XxxRes.php`, implements `JsonSerializable`

### 1. DO NOT use `DB::` or Query Builder

```php
// ❌ INCORRECT
public function __invoke(AddClientsDto $dto): JsonResponse
{
    // NEVER do this in Action
    DB::table('marketing_list_clients')
        ->where('list_id', $dto->id)
        ->insert([...]);

    $exists = DB::table('crm_marketing_list_clients')
        ->where('email', $email)
        ->exists();
}
```

**Why is it wrong?**
- Violates hexagonal architecture (HTTP layer accessing data)
- Not testable
- Not reusable (what if you need it from CLI?)
- Mixes responsibilities

**Where should it go?**
- In **Repository** (`addManualContacts()`, `checkExists()`)

---

### 2. DO NOT put business logic

```php
// ❌ INCORRECT
public function __invoke(CreateOrderDto $dto): JsonResponse
{
    // Validations
    if (!$dto->email && !$dto->phone) {
        throw new ValidationException('Email or phone required');
    }

    // Calculations
    $total = $dto->price * $dto->quantity;
    $tax = $total * 0.21;
    $finalTotal = $total + $tax;

    // Loops
    foreach ($dto->items as $item) {
        // processing...
    }

    // Transformations
    $normalizedEmail = strtolower(trim($dto->email));
}
```

**Why is it wrong?**
- Business logic does NOT belong in HTTP layer
- Not unit testable
- Hard to maintain
- Violates Single Responsibility Principle

**Where should it go?**
- In **Handler** (Application layer)
- In **Entity** (Domain layer) if it's a domain rule
- In **Domain Service** if it involves multiple entities

---

### 3. DO NOT access Models directly

```php
// ❌ INCORRECT
public function __invoke(UpdateCampaignDto $dto): JsonResponse
{
    $campaign = CampaignModel::find($dto->id);
    $campaign->update([
        'status' => 'active',
        'name' => $dto->name
    ]);
    $campaign->save();
}
```

**Why is it wrong?**
- HTTP layer accessing Infrastructure directly
- Bypassing domain logic and events
- Not using CQRS
- Not testable without DB

**Where should it go?**
- Dispatch **UpdateCampaignCommand**
- Handler gets entity via **Repository**
- Calls domain method `$campaign->update(...)`
- Repository persists

---

### 4. DO NOT transform data

```php
// ❌ INCORRECT
public function __invoke(ImportClientsDto $dto): JsonResponse
{
    $transformed = array_map(function($client) {
        return [
            'name' => ucfirst(strtolower($client['name'])),
            'email' => strtolower(trim($client['email'])),
            'phone' => preg_replace('/[^0-9]/', '', $client['phone']),
        ];
    }, $dto->clients);

    // ... do something with $transformed
}
```

**Why is it wrong?**
- Transformations are business logic
- Not reusable
- Makes testing difficult

**Where should it go?**
- In **Handler** if it's application transformation
- In **ValueObject** if it's domain normalization
- In **Domain Service** if it's complex

---

## ✅ CORRECT: Thin Action

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
        // 1. Verify access (security concern)
        $list = $this->queryBus->query(
            new GetListByIdQuery($dto->id, $dto->app)
        );
        $this->verifyAccess(
            $jwtPayload,
            $dto->app,
            $list->groupId,
            $list->restaurantId
        );

        // 2. Dispatch command (delegation to Application layer)
        $this->commandBus->dispatch(
            new AddManualContactsToListCommand(
                listId: $dto->id,
                app: $dto->app,
                contacts: $dto->clients
            )
        );

        // 3. Return Resource (NOT JsonResponse)
        // Controller will be responsible for converting to JsonResponse
        return $this->resService->getMarketingListResource(
            $dto->id,
            $dto->app
        );
    }
}
```

**Characteristics of a correct Action:**
- ✅ 15-20 lines of code
- ✅ No `DB::` or `Model::`
- ✅ No `foreach` or `array_map`
- ✅ No complex `if` (only null checks)
- ✅ Dispatch to ONE Command/Query
- ✅ **CRITICAL: Returns Resource (`XxxRes`) via ResService** - ❌ NOT JsonResponse, ❌ NOT arrays, ❌ NOT DTOs
- ✅ All logic is in Handler
- ✅ Controller handles JsonResponse using `response()->json($resource)`

---

## Distribution of Responsibilities

| What | Where | Why |
|-----|-------|---------|
| Verify JWT and permissions | Action | HTTP/Security concern |
| Generate IDs (creation) | Action | Before creating command |
| Validate email/phone required | Handler | Business rule |
| Check duplicates | Repository | Data access |
| Loop over contacts | Handler | Processing |
| Insert to database | Repository | Data access |
| Update estimated size | Handler + Entity | Domain logic |
| Publish events | Handler | Application orchestration |
| Convert DTO → Res | ResService | HTTP serialization |

---

## Real Project Example

### ❌ BEFORE - Incorrect Action (58 lines)

```php
final readonly class AddMarketingListClientsAction
{
    public function __invoke(AddMarketingListClientsDto $dto): MarketingListRes
    {
        // Verify access (OK)
        $list = $this->queryBus->query(new GetListByIdQuery($dto->id, $dto->app));
        $this->verifyAccess($jwtPayload, $dto->app, $list->groupId, $list->restaurantId);

        // ❌ Logic in Action
        $clientType = $list->groupId !== null ? 'GroupClient' : 'RestaurantClient';
        $now = time();

        // ❌ Loop in Action
        foreach ($dto->clients as $clientData) {
            $name = $clientData['name'];
            $email = $clientData['email'] ?? null;
            $phone = $clientData['phone'] ?? null;
            $countryCode = $clientData['country_code'] ?? null;
            $acceptsMarketing = $clientData['accepts_marketing'] ?? true;

            // ❌ Validation in Action
            if (!$email && !$phone) {
                continue;
            }

            // ❌ DB:: direct in Action
            $exists = DB::table('crm_marketing_list_clients')
                ->where('list_id', $dto->id->getValue())
                ->where(function ($query) use ($email, $phone) {
                    if ($email) {
                        $query->orWhere('email', $email);
                    }
                    if ($phone) {
                        $query->orWhere('phone', $phone);
                    }
                })
                ->exists();

            // ❌ More logic
            if ($exists) {
                continue;
            }

            // ❌ Direct insert with DB::
            DB::table('crm_marketing_list_clients')->insert([
                'list_id' => $dto->id->getValue(),
                'client_id' => null,
                'client_type' => $clientType,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'country_code' => $countryCode,
                'accepts_marketing' => $acceptsMarketing,
                'created_at' => $now,
            ]);
        }

        return $this->marketingListResService->getMarketingListResource($dto->id, $dto->app);
    }
}
```

**Problems:**
1. ❌ Uses `DB::` directly (lines 28, 47)
2. ❌ Has `foreach` loop (line 17)
3. ❌ Validations in Action (line 26)
4. ❌ Business logic (determine clientType, line 13)
5. ❌ 58 lines of code
6. ❌ Injects `CommandBusInterface` but never uses it
7. ❌ Not testable without database
8. ❌ Not reusable from CLI/Queue

---

### ✅ AFTER - Correct Action (15 lines)

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
        // 1. Verify access
        $list = $this->queryBus->query(new GetListByIdQuery($dto->id, $dto->app));
        $this->verifyAccess($jwtPayload, $dto->app, $list->groupId, $list->restaurantId);

        // 2. Dispatch command (all logic goes to Handler)
        $this->commandBus->dispatch(
            new AddManualContactsToListCommand(
                listId: $dto->id,
                app: $dto->app,
                contacts: $dto->clients
            )
        );

        // 3. Return response
        return $this->resService->getMarketingListResource($dto->id, $dto->app);
    }
}
```

**Improvements:**
1. ✅ 15 lines of code
2. ✅ No `DB::` or direct data access
3. ✅ No loops or validations
4. ✅ Uses `CommandBusInterface` correctly
5. ✅ Testable without database
6. ✅ Reusable (Command can be called from CLI/Queue)
7. ✅ All logic in `AddManualContactsToListHandler`

---

### Logic moved to correct layers:

#### `AddManualContactsToListCommand.php` (Application)
```php
final readonly class AddManualContactsToListCommand implements CommandInterface
{
    /**
     * @param array<array{name: string, email: ?string, phone: ?string, country_code: ?string, accepts_marketing: bool}> $contacts
     */
    public function __construct(
        public ListId $listId,
        public AppEnum $app,
        public array $contacts,
    ) {}
}
```

#### `AddManualContactsToListHandler.php` (Application)
```php
final readonly class AddManualContactsToListHandler implements CommandHandlerInterface
{
    public function __construct(
        private MarketingListRepositoryInterface $repository,
        private EventBusInterface $eventBus,
    ) {}

    public function __invoke(AddManualContactsToListCommand $command): void
    {
        $list = $this->repository->getById($command->listId, $command->app);
        $clientType = $list->getClientType();

        // Validate and filter contacts
        $validContacts = [];
        foreach ($command->contacts as $contact) {
            // Validation: email or phone required
            if (empty($contact['email']) && empty($contact['phone'])) {
                continue;
            }

            $validContacts[] = [
                'name' => $contact['name'],
                'email' => $contact['email'] ?? null,
                'phone' => $contact['phone'] ?? null,
                'country_code' => $contact['country_code'] ?? null,
                'accepts_marketing' => $contact['accepts_marketing'],
            ];
        }

        if (empty($validContacts)) {
            return;
        }

        // Delegate to Repository for insertion with duplicate checking
        $this->repository->addManualContacts($command->listId, $validContacts, $clientType);

        // Update estimated size
        $newCount = $this->repository->countClients($command->listId);
        $list->updateEstimatedSize($newCount);

        // Persist and publish events
        $this->repository->save($list);
        $this->eventBus->publishEvents($list->releaseEvents());
    }
}
```

#### `MarketingListRepository->addManualContacts()` (Infrastructure)
```php
public function addManualContacts(ListId $listId, array $contacts, string $clientType): void
{
    $now = time();

    foreach ($contacts as $contact) {
        $email = $contact['email'] ?? null;
        $phone = $contact['phone'] ?? null;

        // Duplicate checking
        $exists = MarketingListClientModel::query()
            ->where('list_id', $listId->value())
            ->where(function ($query) use ($email, $phone) {
                if ($email) {
                    $query->orWhere('email', $email);
                }
                if ($phone) {
                    $query->orWhere('phone', $phone);
                }
            })
            ->exists();

        if ($exists) {
            continue;
        }

        // Insertion
        MarketingListClientModel::query()->insert([
            'list_id' => $listId->value(),
            'client_id' => null,
            'client_type' => $clientType,
            'name' => $contact['name'],
            'email' => $email,
            'phone' => $phone,
            'country_code' => $contact['country_code'] ?? null,
            'accepts_marketing' => $contact['accepts_marketing'],
            'created_at' => $now,
        ]);
    }
}
```

---

## Benefits of Thin Actions

### 1. Testability
```php
// Action test - No DB, just verify correct dispatch
public function test_dispatches_add_manual_contacts_command(): void
{
    $commandBus = Mockery::mock(CommandBusInterface::class);
    $commandBus->shouldReceive('dispatch')
        ->once()
        ->with(Mockery::type(AddManualContactsToListCommand::class));

    $action = new AddMarketingListClientsAction($commandBus, ...);
    $action($dto, $jwtPayload);
}
```

### 2. Reusability
```php
// Now you can use the Command from CLI
php artisan marketing:add-contacts {listId} --contacts=file.csv

// Or from Queue
dispatch(new AddManualContactsToListCommand($listId, $app, $contacts));
```

### 3. Maintainability
- Business logic in ONE place (Handler)
- Changes to business rules do NOT touch HTTP layer
- Easy to understand (3 clear responsibilities)

### 4. Separation of Concerns
- HTTP layer handles HTTP
- Application layer handles orchestration
- Domain layer handles business rules
- Infrastructure layer handles data

### 5. DDD Compliance
- Correct hexagonal architecture
- Well-defined ports and adapters
- Domain independent of HTTP

---

## Checklist Before Committing Action

Before committing any Action, verify:

- [ ] **Action has ≤ 20 lines of code**
- [ ] **Does NOT use `DB::`** or `Model::` directly
- [ ] **Does NOT have `foreach`, `array_map`, `array_filter`** or other loops
- [ ] **Does NOT have complex `if`** (only null checks allowed)
- [ ] **Dispatches exactly ONE Command or Query**
- [ ] **❌ CRITICAL: Does NOT return `JsonResponse`** - Actions must be decoupled from HTTP
- [ ] **✅ Returns Resource (`XxxRes`) via ResService** - NOT arrays, NOT DTOs, NOT JsonResponse
- [ ] **All logic is in Handler or Domain**
- [ ] **If it injects CommandBusInterface, it uses it**
- [ ] **Maximum 3 responsibilities:** verify access, dispatch, return Res
- [ ] **Controller handles JsonResponse** - Controller converts Res to JsonResponse

If any answer is NO, refactor before committing.

---

## Frequently Asked Questions

### Can I do an if to check null?

✅ Yes, simple null checks are OK:
```php
if ($dto->groupId === null) {
    throw new InvalidArgumentException('groupId required');
}
```

❌ No, complex ifs are NOT OK:
```php
if (!$email && !$phone) {
    // complex validation -> goes to Handler
}
```

### Can I generate IDs in Action?

✅ Yes, for creation operations:
```php
$campaignId = CampaignId::random();
$this->commandBus->dispatch(new CreateCampaignCommand($campaignId, ...));
```

### Can I do two dispatches in one Action?

⚠️ Avoid it. If you need two Commands, you probably need:
- A ProcessManager (for complex workflows)
- Or a Handler that dispatches the second Command

### Can I call a Repository from Action?

❌ NO. Always use QueryBus/CommandBus.

Exception: The `VerifiesJwtAccess` trait may need QueryBus to verify ownership, but that's an HTTP security concern, not business.

---

## See Also

- [architecture.md](architecture.md) - DDD and Hexagonal Architecture
- [application-layer.md](application-layer.md) - Commands, Queries, Handlers
- [critical-rules.md](critical-rules.md) - Critical project rules
- [http-requests-pattern.md](http-requests-pattern.md) - HTTP Requests pattern
