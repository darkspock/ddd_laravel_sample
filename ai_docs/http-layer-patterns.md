# HTTP Layer - Architecture Patterns

## 📋 Table of Contents
- [Folder Structure](#folder-structure)
- [Action-Request-Dto-Res Pattern](#action-request-dto-res-pattern)
- [Request (No rules)](#1-request-no-rules)
- [Dto (Transfer Object)](#2-dto-transfer-object)
- [Action (Returns JsonResponse)](#3-action-returns-jsonresponse)
- [ResService (Converts DTO → Res)](#4-resservice-converts-dto--res)
- [Res (JsonSerializable)](#5-res-jsonserializable)
- [Controller (Orchestrates Actions)](#6-controller-orchestrates-actions)
- [Complete Examples](#complete-examples)

---

## Folder Structure

```
Apps/Api/
├── Campaign/                    # By domain/aggregate
│   ├── Store/                  # By action
│   │   ├── StoreCampaignAction.php
│   │   ├── StoreCampaignRequest.php
│   │   └── StoreCampaignDto.php
│   ├── Update/
│   │   ├── UpdateCampaignAction.php
│   │   ├── UpdateCampaignRequest.php
│   │   └── UpdateCampaignDto.php
│   ├── Show/
│   │   ├── ShowCampaignAction.php
│   │   └── ShowCampaignRequest.php
│   ├── Index/
│   │   ├── IndexCampaignAction.php
│   │   └── IndexCampaignRequest.php
│   ├── Shared/
│   │   ├── Services/
│   │   │   └── CampaignResService.php    # DTO → Res converter
│   │   └── CampaignRes.php               # API Resource
│   └── CampaignController.php            # HTTP orchestration
```

---

## Action-Request-Dto-Res Pattern

This is the complete flow of an HTTP request:

```
HTTP Request
    ↓
Request::getDto() → Dto
    ↓
Action::__invoke(Dto) → JsonResponse
    ↓
    ├─→ CommandBus (writes)
    ├─→ QueryBus (reads) → Domain DTO
    └─→ ResService::fromDto(Domain DTO) → API Res
            ↓
        JsonResponse(Res)
```

---

## 1. Request (No rules)

### ❌ INCORRECT - DO NOT define rules()

```php
// DO NOT DO THIS
final class StoreCampaignRequest extends AbstractFormRequest
{
    // ❌ DO NOT include rules()
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ];
    }
}
```

### ✅ CORRECT - Only getDto()

```php
// Apps/Api/Campaign/Store/StoreCampaignRequest.php
final class StoreCampaignRequest extends AbstractFormRequest
{
    // Only parse and convert to DTO
    public function getDto(): StoreCampaignDto
    {
        return new StoreCampaignDto(
            groupId: GroupId::fromStringOrNull(
                $this->getHelper()->getStringOrNull('group_id')
            ),
            restaurantId: RestaurantId::fromStringOrNull(
                $this->getHelper()->getStringOrNull('restaurant_id')
            ),
            name: $this->getHelper()->getString('name'),
            description: $this->getHelper()->getStringOrNull('description'),
            listId: new ListId($this->getHelper()->getString('list_id')),
            templateId: new TemplateId($this->getHelper()->getString('template_id')),
        );
    }
}
```

**Reasons:**
- Validation is done in another layer (domain or request validation middleware)
- The Request should only parse and convert types
- Keeps the Request thin and focused

---

## 2. Dto (Transfer Object)

```php
// Apps/Api/Campaign/Store/StoreCampaignDto.php
final readonly class StoreCampaignDto
{
    public function __construct(
        public ?GroupId $groupId,
        public ?RestaurantId $restaurantId,
        public string $name,
        public ?string $description,
        public ListId $listId,
        public TemplateId $templateId,
    ) {
    }
}
```

**Characteristics:**
- `readonly` - Immutable
- Only public properties
- No logic, only data
- Uses Value Objects (GroupId, ListId, etc.)

---

## 3. Action (Returns Resource, NOT JsonResponse) - ⚠️ CRITICAL RULE

### 🚨 CRITICAL: Actions MUST return Resources (XxxRes), NEVER JsonResponse

**This is one of the most important rules in the project. Actions must be HTTP-agnostic.**

### ❌ INCORRECT - DO NOT return JsonResponse, arrays or DTOs

```php
// ❌ DO NOT DO THIS - Actions must be decoupled from HTTP
public function __invoke(StoreCampaignDto $dto): JsonResponse
{
    // ...
    return new JsonResponse([...]); // ❌
}

// ❌ DO NOT DO THIS
public function __invoke(StoreCampaignDto $dto): array
{
    // ...
    return ['id' => $id, 'name' => $name]; // ❌
}

// ❌ DO NOT DO THIS
public function __invoke(StoreCampaignDto $dto): CampaignDto
{
    // ...
    return $dto; // ❌
}
```

### ✅ CORRECT - Always return Resource (XxxRes)

```php
// Apps/Api/Campaign/Store/StoreCampaignAction.php
final readonly class StoreCampaignAction
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private CampaignResService $resService
    ) {
    }

    // ✅ Returns Resource, NOT JsonResponse
    public function __invoke(StoreCampaignDto $dto): CampaignRes
    {
        // 1. Generate ID at HTTP application layer
        $campaignId = CampaignId::random();

        // 2. Dispatch command (write operation)
        $command = new CreateCampaignCommand(
            id: $campaignId,
            groupId: $dto->groupId,
            restaurantId: $dto->restaurantId,
            name: $dto->name,
            description: $dto->description,
            listId: $dto->listId,
            templateId: $dto->templateId,
        );

        $this->commandBus->dispatch($command);

        // 3. Use ResService to convert Domain DTO → API Res
        // Return Resource directly, NOT JsonResponse
        return $this->resService->getCampaignResource($campaignId);
    }
}
```

**Flow:**
1. Generate ID (in HTTP layer)
2. Dispatch Command (write)
3. Query DTO (read) via ResService
4. Convert DTO → Res via ResService
5. Return Resource (Controller handles JsonResponse)

**Critical Rule (MUST FOLLOW):**
- ❌ **Actions NEVER return `JsonResponse`** - Actions must be decoupled from HTTP layer for testability
- ❌ **Actions NEVER return arrays** - Use Resources for consistent API responses
- ❌ **Actions NEVER return DTOs** - DTOs are internal transfer objects, not API responses
- ✅ **Actions ALWAYS return Resources (`XxxRes`)** - This allows testing Actions without HTTP context
- ✅ **Controller converts Resource to JsonResponse** - `response()->json($resource)` - This is the ONLY HTTP-specific code
- ✅ **ResService converts DTOs/Entities to Resources** - Located in `Apps/Api/{Module}/Shared/Services/XxxResService.php`
- ✅ **Resources implement `JsonSerializable`** - Located in `Apps/Api/{Module}/Shared/XxxRes.php`

---

## 4. ResService (Converts DTO → Res)

```php
// Apps/Api/Campaign/Shared/Services/CampaignResService.php
final readonly class CampaignResService
{
    public function __construct(
        private QueryBusInterface $queryBus
    ) {
    }

    // For a single entity
    public function getCampaignResource(CampaignId $campaignId): CampaignRes
    {
        $query = new GetCampaignByIdQuery($campaignId);
        $campaignDto = $this->queryBus->query($query);

        return $this->fromDto($campaignDto);
    }

    // For collections
    /**
     * @param CampaignDto[] $dtos
     * @return CampaignRes[]
     */
    public function getCampaignResourceCollection(array $dtos): array
    {
        return array_map(fn($dto) => $this->fromDto($dto), $dtos);
    }

    // Private mapping
    private function fromDto(CampaignDto $dto): CampaignRes
    {
        return new CampaignRes(
            id: $dto->id,
            groupId: $dto->groupId,
            restaurantId: $dto->restaurantId,
            name: $dto->name,
            description: $dto->description,
            listId: $dto->listId,
            templateId: $dto->templateId,
            status: $dto->status,
            scheduledAt: $dto->scheduledAt,
            startedAt: $dto->startedAt,
            completedAt: $dto->completedAt,
            totalRecipients: $dto->totalRecipients,
            processedRecipients: $dto->processedRecipients,
            failedRecipients: $dto->failedRecipients,
            pilotSent: $dto->pilotSent,
            pilotCompletedAt: $dto->pilotCompletedAt,
            approvedAt: $dto->approvedAt,
            pilotSize: $dto->pilotSize,
            createdAt: $dto->createdAt,
            updatedAt: $dto->updatedAt,
            repeatOn: $dto->repeatOn,
            executionType: $dto->executionType,
            isParentCampaign: $dto->isParentCampaign,
            parentCampaignId: $dto->parentCampaignId,
            autoApprovePilot: $dto->autoApprovePilot,
            minSuccessRateThreshold: $dto->minSuccessRateThreshold,
        );
    }
}
```

**Responsibilities:**
- Query domain DTOs via QueryBus
- Convert domain DTOs → API Res
- Handle collections
- Centralize conversion logic

---

## 5. Res (JsonSerializable)

```php
// Apps/Api/Campaign/Shared/CampaignRes.php
final readonly class CampaignRes implements \JsonSerializable
{
    public function __construct(
        public CampaignId $id,
        public ?GroupId $groupId,
        public ?RestaurantId $restaurantId,
        public string $name,
        public ?string $description,
        public ListId $listId,
        public TemplateId $templateId,
        public CampaignStatusEnum $status,
        public ?int $scheduledAt,
        public ?int $startedAt,
        public ?int $completedAt,
        public int $totalRecipients,
        public int $processedRecipients,
        public int $failedRecipients,
        public bool $pilotSent,
        public ?int $pilotCompletedAt,
        public ?int $approvedAt,
        public int $pilotSize,
        public int $createdAt,
        public int $updatedAt,
        public ?string $repeatOn,
        public string $executionType,
        public bool $isParentCampaign,
        public ?CampaignId $parentCampaignId,
        public bool $autoApprovePilot,
        public float $minSuccessRateThreshold,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id->getValue(),
            'groupId' => $this->groupId?->getValue(),
            'restaurantId' => $this->restaurantId?->getValue(),
            'name' => $this->name,
            'description' => $this->description,
            'listId' => $this->listId->getValue(),
            'templateId' => $this->templateId->getValue(),
            'status' => $this->status->value,
            'scheduledAt' => $this->scheduledAt,
            'startedAt' => $this->startedAt,
            'completedAt' => $this->completedAt,
            'totalRecipients' => $this->totalRecipients,
            'processedRecipients' => $this->processedRecipients,
            'failedRecipients' => $this->failedRecipients,
            'pilotSent' => $this->pilotSent,
            'pilotCompletedAt' => $this->pilotCompletedAt,
            'approvedAt' => $this->approvedAt,
            'pilotSize' => $this->pilotSize,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'repeatOn' => $this->repeatOn,
            'executionType' => $this->executionType,
            'isParentCampaign' => $this->isParentCampaign,
            'parentCampaignId' => $this->parentCampaignId?->getValue(),
            'autoApprovePilot' => $this->autoApprovePilot,
            'minSuccessRateThreshold' => $this->minSuccessRateThreshold,
        ];
    }
}
```

**Characteristics:**
- `readonly` - Immutable
- `implements JsonSerializable` - Automatic serialization
- Converts Value Objects to primitives (getValue())
- Converts Enums to strings (.value)

---

## 6. Controller (Orchestrates Actions and handles JsonResponse)

```php
// Apps/Api/Campaign/CampaignController.php
final readonly class CampaignController
{
    public function store(
        StoreCampaignRequest $request,
        StoreCampaignAction $action
    ): JsonResponse {
        // Action returns CampaignRes (Resource), NOT JsonResponse
        $campaign = $action($request->getDto());

        // Controller converts Resource to JsonResponse
        return response()->json($campaign, 201);
    }

    public function show(
        string $id,
        ShowCampaignAction $action
    ): JsonResponse {
        $campaign = $action($id);
        // DO NOT wrap in ['data' => ...] - Resource already implements JsonSerializable
        return response()->json($campaign);
    }

    public function index(
        IndexCampaignRequest $request,
        IndexCampaignAction $action
    ): JsonResponse {
        // Action returns array<CampaignRes>
        $campaigns = $action($request->getDto());

        return response()->json($campaigns);
    }
}
```

**Responsibilities:**
- Inject Request and Action
- Call Request::getDto() or Request::getXxx()
- Call Action::__invoke() - receives Resource (XxxRes)
- Convert Resource to JsonResponse with `response()->json($resource)`
- Set appropriate HTTP status codes (201 for create, 200 for others)

**Critical Rules:**
- ❌ **NO wrap in `['data' => ...]`** - Resource already implements `JsonSerializable`
- ✅ **Controller handles JsonResponse conversion** - This is the only HTTP-specific code
- ✅ **Actions are HTTP-agnostic** - Can be tested without HTTP context

---

## Complete Examples

### Example 1: Create (POST)

**Request:**
```http
POST /api/campaigns
{
    "name": "Summer Campaign",
    "group_id": "01H2K3M4N5P6Q7R8S9T0VW",
    "list_id": "01H2K3M4N5P6Q7R8S9T0VX",
    "template_id": "01H2K3M4N5P6Q7R8S9T0VY"
}
```

**Flow:**
1. `StoreCampaignRequest::getDto()` → `StoreCampaignDto`
2. `StoreCampaignAction::__invoke(dto)` →
   - Generate ID
   - Dispatch `CreateCampaignCommand`
   - Query via `ResService`
   - Return `JsonResponse(CampaignRes)`
3. Controller wraps: `['data' => CampaignRes]`

**Response:**
```json
{
    "data": {
        "id": "01H2K3M4N5P6Q7R8S9T0VZ",
        "name": "Summer Campaign",
        "status": "draft",
        ...
    }
}
```

### Example 2: Index (GET)

**Request:**
```http
GET /api/campaigns?group_id=01H2K3M4N5P6Q7R8S9T0VW
```

**Flow:**
1. `IndexCampaignRequest::getGroupId()` → `GroupId`
2. `IndexCampaignAction::__invoke(groupId)` →
   - Query `FindCampaignsByGroupIdQuery`
   - Get `CampaignDto[]`
   - Convert via `ResService::getCampaignResourceCollection()`
   - Return `JsonResponse(CampaignRes[])`
3. Controller wraps: `['data' => CampaignRes[]]`

**Response:**
```json
{
    "data": [
        {
            "id": "01H2K3M4N5P6Q7R8S9T0VZ",
            "name": "Summer Campaign",
            ...
        },
        {
            "id": "01H2K3M4N5P6Q7R8S9T0WA",
            "name": "Winter Campaign",
            ...
        }
    ]
}
```

---

## Implementation Checklist

When implementing a new endpoint, follow this checklist:

- [ ] **Request** without `rules()`, only `getDto()` or `getXxx()`
- [ ] **Dto** readonly with public properties
- [ ] **🚨 CRITICAL: Action does NOT return `JsonResponse`** - Actions must be decoupled from HTTP for testability
- [ ] **🚨 CRITICAL: Action does NOT return arrays** - Use Resources for consistent responses
- [ ] **🚨 CRITICAL: Action does NOT return DTOs** - DTOs are internal objects, not API responses
- [ ] **✅ Action ALWAYS returns Resource (`XxxRes`)** - Allows testing Actions without HTTP context
- [ ] **Action** uses `ResService` to convert DTOs/Entities → Res (NO direct queries in Action)
- [ ] **ResService** located in `Apps/Api/{Module}/Shared/Services/XxxResService.php`
- [ ] **ResService** has `getXxxResource()` and `getXxxResourceCollection()` methods
- [ ] **ResService** uses `QueryBus` internally to fetch DTOs/Entities (NO direct queries)
- [ ] **Res** located in `Apps/Api/{Module}/Shared/XxxRes.php`
- [ ] **Res** implements `JsonSerializable` interface
- [ ] **Res** converts Value Objects to primitives in `jsonSerialize()` method
- [ ] **Controller** delegates to Action, receives Resource (not JsonResponse)
- [ ] **Controller** converts Resource to JsonResponse with `response()->json($resource)`
- [ ] **Controller** does NOT wrap in `['data' => ...]` - Resource already implements JsonSerializable

---

## Anti-Patterns (DO NOT DO)

### ❌ Request with rules()
```php
public function rules(): array { ... }  // NO!
```

### ❌ Action returning JsonResponse
```php
public function __invoke(): JsonResponse { ... }  // NO! Actions must be decoupled from HTTP
```

### ❌ Action returning array
```php
public function __invoke(): array { ... }  // NO!
```

### ❌ Action returning DTO
```php
public function __invoke(): CampaignDto { ... }  // NO!
```

### ❌ Controller manually mapping
```php
public function store(Request $request) {
    // ...
    return response()->json([
        'id' => $campaign->id,  // DO NOT map here!
        'name' => $campaign->name,
    ]);
}
```

### ❌ Using old Laravel Resources
```php
class CampaignResource extends JsonResource { ... }  // NO!
```

---

## Benefits of this Pattern

1. **Clear separation of responsibilities**
   - Request: Parse
   - Action: Orchestration
   - ResService: Conversion
   - Res: Serialization

2. **Testable**
   - Each piece can be tested independently

3. **Reusable**
   - ResService can be used from multiple Actions

4. **Type-safe**
   - Typed JsonResponse
   - Readonly Res with types

5. **Consistent**
   - All endpoints follow the same pattern
