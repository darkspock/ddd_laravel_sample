# HTTP Requests Pattern - NO Laravel Validation

**🚨 CRITICAL: Requests DO NOT use Laravel validation 🚨**

---

## ⚠️ BEFORE CONTINUING - Read this first:

If you're about to write any of these lines in a Request, **STOP**:

```php
public function rules(): array       // ❌ FORBIDDEN
public function after(): array       // ❌ FORBIDDEN
public function messages(): array    // ❌ FORBIDDEN
$this->validated()                   // ❌ FORBIDDEN
```

**If you see any of these lines in an existing Request, REMOVE THEM.**

---

## Executive Summary

**Requests only map data to strongly typed DTOs. They do NOT have `rules()`, they do NOT have `after()`, they do NOT use `validated()`.**

If something is wrong, **it will fail when constructing the DTO** with `TypeError`.

**Golden rule:** A Request must have ONLY one method: `getDto()`

---

## ❌ What NOT to do

### ❌ INCORRECT: Using Laravel Validation

```php
final class AddMarketingListClientsRequest extends AbstractFormRequest
{
    // ❌ NEVER do this
    public function rules(): array
    {
        return [
            'clients' => ['required', 'array', 'min:1'],
            'clients.*.name' => ['required', 'string', 'max:255'],
            'clients.*.email' => ['nullable', 'email', 'max:255'],
            'clients.*.phone' => ['nullable', 'string', 'max:20'],
        ];
    }

    // ❌ NEVER do this
    public function after(): array
    {
        return [
            function ($validator) {
                foreach ($this->input('clients', []) as $index => $client) {
                    if (empty($client['email']) && empty($client['phone'])) {
                        $validator->errors()->add(...);
                    }
                }
            }
        ];
    }

    // ❌ NEVER use validated()
    public function getDto(): AddMarketingListClientsDto
    {
        $clients = $this->validated()['clients'];  // NO!
        // ...
    }
}
```

**Why is it wrong?**
1. Duplicate validation (Laravel + Domain)
2. Not strongly typed
3. Hard to test
4. Mixes HTTP concerns with business rules
5. Not reusable outside HTTP layer

---

## ✅ What TO do

### ✅ CORRECT: Map to Strongly Typed DTOs

```php
final class AddMarketingListClientsRequest extends AbstractFormRequest
{
    // ✅ ONLY getDto() method
    public function getDto(): AddMarketingListClientsDto
    {
        $helper = $this->getHelper();

        $id = new ListId($helper->routeString('id'));
        $app = AppEnum::from($helper->routeString('app'));

        // Map each client to ContactDto (strongly typed)
        $contacts = array_map(
            fn(array $data): ContactDto => new ContactDto(
                name: (string) $data['name'],
                email: isset($data['email']) ? (string) $data['email'] : null,
                phone: isset($data['phone']) ? (string) $data['phone'] : null,
                countryCode: isset($data['country_code']) ? (string) $data['country_code'] : null,
                acceptsMarketing: isset($data['accepts_marketing']) ? (bool) $data['accepts_marketing'] : true,
            ),
            $helper->getArray('clients')
        );

        return new AddMarketingListClientsDto(
            app: $app,
            id: $id,
            clients: $contacts,  // array<ContactDto>
        );
    }
}
```

**Benefits:**
1. ✅ Strong typing - If it fails, it fails with `TypeError`
2. ✅ No duplicate validation
3. ✅ Easy to test (unit test without HTTP)
4. ✅ Reusable (DTOs can be used from CLI, Queue, etc.)
5. ✅ Business validation in Handler (where it belongs)

---

## Pattern: Request → DTO → Command → Handler

### 1. ContactDto (Individual Transfer Object)

```php
// Apps/Api/MarketingList/AddMarketingListClients/ContactDto.php
final readonly class ContactDto
{
    public function __construct(
        public string $name,
        public ?string $email,
        public ?string $phone,
        public ?string $countryCode,
        public bool $acceptsMarketing,
    ) {}
}
```

### 2. AddMarketingListClientsDto (Main Transfer Object)

```php
// Apps/Api/MarketingList/AddMarketingListClients/AddMarketingListClientsDto.php
final readonly class AddMarketingListClientsDto
{
    /**
     * @param array<ContactDto> $clients
     */
    public function __construct(
        public AppEnum $app,
        public ListId $id,
        public array $clients,  // Array of DTOs, NOT array<mixed>
    ) {}
}
```

### 3. Request (Parse to DTOs)

```php
// Apps/Api/MarketingList/AddMarketingListClients/AddMarketingListClientsRequest.php
final class AddMarketingListClientsRequest extends AbstractFormRequest
{
    public function getDto(): AddMarketingListClientsDto
    {
        $helper = $this->getHelper();

        // Parse route params
        $id = new ListId($helper->routeString('id'));
        $app = AppEnum::from($helper->routeString('app'));

        // Parse body - map each client to ContactDto
        $clientsData = $helper->getArray('clients');

        $contacts = array_map(
            fn(array $data): ContactDto => new ContactDto(
                name: (string) $data['name'],
                email: isset($data['email']) ? (string) $data['email'] : null,
                phone: isset($data['phone']) ? (string) $data['phone'] : null,
                countryCode: isset($data['country_code']) ? (string) $data['country_code'] : null,
                acceptsMarketing: isset($data['accepts_marketing']) ? (bool) $data['accepts_marketing'] : true,
            ),
            $clientsData
        );

        return new AddMarketingListClientsDto(
            app: $app,
            id: $id,
            clients: $contacts,
        );
    }
}
```

### 4. Command (Application Layer)

```php
// src/Marketing/List/Application/Commands/.../AddManualContactsToListCommand.php
final readonly class AddManualContactsToListCommand implements CommandInterface
{
    /**
     * @param array<ContactDto> $contacts
     */
    public function __construct(
        public ListId $listId,
        public AppEnum $app,
        public array $contacts,
    ) {}
}
```

### 5. Handler (Business Logic)

```php
// src/Marketing/List/Application/Commands/.../AddManualContactsToListHandler.php
final readonly class AddManualContactsToListHandler implements CommandHandlerInterface
{
    public function __invoke(AddManualContactsToListCommand $command): void
    {
        // Business validation (HERE, not in Request)
        $validContacts = [];
        foreach ($command->contacts as $contactDto) {
            // Validate business rule: email OR phone required
            if ($contactDto->email === null && $contactDto->phone === null) {
                continue; // Skip invalid contacts
            }

            $validContacts[] = [
                'name' => $contactDto->name,
                'email' => $contactDto->email,
                'phone' => $contactDto->phone,
                'country_code' => $contactDto->countryCode,
                'accepts_marketing' => $contactDto->acceptsMarketing,
            ];
        }

        if (empty($validContacts)) {
            return;
        }

        // Delegate to Repository
        $this->repository->addManualContacts($command->listId, $validContacts, $clientType);

        // Update size, publish events, etc.
    }
}
```

---

## What type of validation goes where?

| Validation Type | Where | Example |
|-------------------|-------|---------|
| **Strong typing** | Request → DTO | `name: (string) $data['name']` |
| **Basic format** | DTO constructor | `new Email($string)` throws exception if invalid |
| **Business rules** | Handler | "email OR phone required" |
| **Complex validation** | Entity or Domain Service | "Only DRAFT campaigns can be scheduled" |
| **Permissions/Ownership** | Action (via verifyAccess) | JWT verifies group/restaurant |

---

## Complete Example: Create Campaign

### Request

```php
final class StoreCampaignRequest extends AbstractFormRequest
{
    public function getDto(): StoreCampaignDto
    {
        $helper = $this->getHelper();

        return new StoreCampaignDto(
            groupId: GroupId::fromStringOrNull($helper->getStringOrNull('group_id')),
            restaurantId: RestaurantId::fromStringOrNull($helper->getStringOrNull('restaurant_id')),
            name: $helper->getString('name'),
            description: $helper->getStringOrNull('description'),
            listId: new ListId($helper->getString('list_id')),
            templateId: new TemplateId($helper->getString('template_id')),
            scheduledAt: $helper->getIntOrNull('scheduled_at'),
        );
    }
}
```

### DTO

```php
final readonly class StoreCampaignDto
{
    public function __construct(
        public ?GroupId $groupId,
        public ?RestaurantId $restaurantId,
        public string $name,
        public ?string $description,
        public ListId $listId,
        public TemplateId $templateId,
        public ?int $scheduledAt,
    ) {}
}
```

**If something fails:**
- `$helper->getString('name')` throws exception if `name` doesn't exist or is not a string
- `new ListId($helper->getString('list_id'))` throws exception if not a valid ULID
- `GroupId::fromStringOrNull()` throws exception if invalid string (not if null)

---

## Arrays of DTOs vs arrays of mixed

### ❌ INCORRECT: array<mixed>

```php
final readonly class AddMarketingListClientsDto
{
    /**
     * @param array<array{name: string, email?: string}> $clients
     */
    public function __construct(
        public array $clients,  // Array of mixed arrays
    ) {}
}
```

**Problems:**
- Not strongly typed
- Easy to put incorrect data
- PHPStan can't help you
- Not reusable

### ✅ CORRECT: array<ContactDto>

```php
final readonly class AddMarketingListClientsDto
{
    /**
     * @param array<ContactDto> $clients
     */
    public function __construct(
        public array $clients,  // Array of strongly typed DTOs
    ) {}
}
```

**Benefits:**
- Strong typing for each item
- PHPStan checks correctly
- IDE autocomplete
- Impossible to put incorrect data

---

## Error Handling

### If data comes wrong from request:

```php
// Request body:
{
    "name": null,  // Expected string
    "email": 123    // Expected string|null
}

// In Request:
$helper->getString('name');  // Throws exception: "name must be string"

// Or in DTO:
name: (string) $data['name'],  // PHP throws TypeError if null
```

### If format is invalid:

```php
// Request body:
{
    "id": "not-a-valid-ulid"
}

// In Request:
new ListId($helper->getString('id'));  // Throws InvalidUlidException
```

### If business rule fails:

```php
// In Handler (NOT in Request):
if ($contactDto->email === null && $contactDto->phone === null) {
    // Skip or throw domain exception
    throw new ContactRequiresEmailOrPhoneException();
}
```

---

## Testing

### Request Test (Unit)

```php
public function test_maps_request_data_to_dto(): void
{
    $request = new AddMarketingListClientsRequest([
        'clients' => [
            ['name' => 'John', 'email' => 'john@example.com'],
        ],
    ]);
    $request->setRouteResolver(fn() => new Route('POST', '/lists/{app}/{id}/clients', []));
    $request->route()->setParameter('id', '01HQXXX...');
    $request->route()->setParameter('app', 'cover-manager');

    $dto = $request->getDto();

    $this->assertInstanceOf(AddMarketingListClientsDto::class, $dto);
    $this->assertCount(1, $dto->clients);
    $this->assertInstanceOf(ContactDto::class, $dto->clients[0]);
    $this->assertEquals('John', $dto->clients[0]->name);
}
```

### Handler Test (Unit)

```php
public function test_filters_invalid_contacts(): void
{
    $command = new AddManualContactsToListCommand(
        listId: new ListId('01HQXXX...'),
        app: AppEnum::coverManager,
        contacts: [
            new ContactDto('John', 'john@example.com', null, null, true),
            new ContactDto('Jane', null, null, null, true),  // Invalid: no email/phone
        ]
    );

    $this->handler->__invoke($command);

    // Verify only valid contact was added
    $this->repositoryMock->shouldHaveReceived('addManualContacts')
        ->once()
        ->with(
            Mockery::any(),
            Mockery::on(fn($contacts) => count($contacts) === 1),
            Mockery::any()
        );
}
```

---

## Checklist Before Committing Request

- [ ] **Does NOT have `rules()` method**
- [ ] **Does NOT have `after()` method**
- [ ] **Does NOT use `validated()`**
- [ ] **Has ONLY `getDto()` method**
- [ ] **Returns strongly typed DTO**
- [ ] **Arrays are arrays of DTOs** (not `array<mixed>`)
- [ ] **Uses `$helper->getString()`, `getInt()`, etc.** for parsing
- [ ] **Explicit casts** when necessary: `(string)`, `(int)`, `(bool)`

---

## See Also

- [architecture.md](architecture.md) - DDD and Hexagonal Architecture
- [http-layer-actions.md](http-layer-actions.md) - Actions THIN pattern
- [critical-rules.md](critical-rules.md) - Critical project rules
- [http-layer-patterns.md](http-layer-patterns.md) - HTTP Layer patterns
