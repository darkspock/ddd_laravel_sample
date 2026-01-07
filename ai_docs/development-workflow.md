# Development Workflow

**Strict implementation order for features in 3 phases**

---

## 📋 Workflow Summary

```
PHASE 1: Domain Layer
  ├── Entities (with create, update, transition methods)
  ├── Value Objects
  └── Enums
  ✅ CONFIRM before continuing

PHASE 2: Infrastructure Layer
  ├── Repository Interface (Domain)
  ├── Repository Implementation (Infrastructure)
  ├── Mapper/Hydrator
  ├── Eloquent Model
  └── Migrations
  ✅ CONFIRM before continuing

PHASE 3: Application + HTTP Layer
  ├── Request classes
  ├── Action classes
  ├── Commands (write - void)
  ├── Queries (read - DTOs)
  ├── Controller
  ├── Resources (Laravel)
  └── Routes
  ✅ CONFIRM before continuing
```

---

## PHASE 1: Domain Layer

### 1.1 Create Entities

**Important rules:**

#### ✅ Public Properties
```php
// ✅ CORRECT
public readonly CampaignId $id;
public ?GroupId $groupId;
public string $name;
public CampaignStatus $status;

// ❌ INCORRECT - DO NOT use getters/setters
private string $name;
public function getName(): string { return $this->name; }
public function setName(string $name): void { $this->name = $name; }
```

#### ✅ Static Factory Method: `create()`
```php
public static function create(
    CampaignId $id,
    ?GroupId $groupId,
    string $name
): self {
    // Validations
    if ($groupId === null && $restaurantId === null) {
        throw new CampaignMustHaveOwnerException();
    }

    $instance = new self(
        id: $id,
        groupId: $groupId,
        name: $name,
        status: CampaignStatus::DRAFT
    );

    // Record domain event
    $instance->recordLast(new CampaignCreatedEvent($id));

    return $instance;
}
```

#### ✅ `update()` Method
```php
public function update(
    string $name,
    ?string $description = null
): void {
    // Validate that it can be updated
    if ($this->status === CampaignStatus::RUNNING) {
        throw new CampaignCannotBeEditedException();
    }

    $this->name = $name;
    if ($description !== null) {
        $this->description = $description;
    }

    $this->recordLast(new CampaignUpdatedEvent($this->id));
}
```

#### ✅ Specific Methods for State Transitions

**NEVER change status directly**

```php
// ❌ INCORRECT
$campaign->status = CampaignStatus::SCHEDULED;

// ✅ CORRECT - Specific method
public function schedule(DateTimeImmutable $scheduledAt): void
{
    // Validate pre-conditions
    if ($this->status !== CampaignStatus::DRAFT) {
        throw new CampaignCannotBeScheduledException(
            "Only DRAFT campaigns can be scheduled"
        );
    }

    if ($scheduledAt <= new DateTimeImmutable()) {
        throw new InvalidScheduleDateException("Date must be in the future");
    }

    // State transition
    $this->status = CampaignStatus::SCHEDULED;
    $this->scheduledAt = $scheduledAt;

    // Domain event
    $this->recordLast(new CampaignScheduledEvent($this->id, $scheduledAt));
}

public function start(): void
{
    if ($this->status !== CampaignStatus::SCHEDULED) {
        throw new CampaignCannotBeStartedException();
    }

    $this->status = CampaignStatus::RUNNING;
    $this->sentAt = new DateTimeImmutable();
    $this->recordLast(new CampaignStartedEvent($this->id));
}

public function complete(): void
{
    if ($this->status !== CampaignStatus::RUNNING) {
        throw new CampaignCannotBeCompletedException();
    }

    $this->status = CampaignStatus::COMPLETED;
    $this->recordLast(new CampaignCompletedEvent($this->id));
}

public function cancel(): void
{
    if ($this->status === CampaignStatus::COMPLETED) {
        throw new CampaignCannotBeCancelledException();
    }

    $this->status = CampaignStatus::CANCELLED;
    $this->recordLast(new CampaignCancelledEvent($this->id));
}
```

#### Complete Entity Example

```php
<?php

declare(strict_types=1);

namespace CoverManager\Marketing\Campaign\Domain\Entities;

use CoverManager\Core\Group\Domain\ValueObjects\GroupId;
use CoverManager\Core\Restaurant\Domain\ValueObjects\RestaurantId;
use CoverManager\Marketing\Campaign\Domain\Enums\CampaignStatus;
use CoverManager\Marketing\Campaign\Domain\Events\CampaignCreatedEvent;
use CoverManager\Marketing\Campaign\Domain\ValueObjects\CampaignId;
use CoverManager\Marketing\List\Domain\ValueObjects\ListId;
use CoverManager\Marketing\Template\Domain\ValueObjects\TemplateId;
use CoverManager\Shared\Framework\Domain\Entities\BaseEntity;
use DateTimeImmutable;

final class Campaign extends BaseEntity
{
    // Private constructor
    private function __construct(
        public readonly CampaignId $id,
        public ?GroupId $groupId,
        public ?RestaurantId $restaurantId,
        public string $name,
        public ?string $description,
        public CampaignStatus $status,
        public ListId $listId,
        public TemplateId $templateId,
        public ?DateTimeImmutable $scheduledAt,
        public ?DateTimeImmutable $sentAt,
    ) {
    }

    // Factory method
    public static function create(
        CampaignId $id,
        ?GroupId $groupId,
        ?RestaurantId $restaurantId,
        string $name,
        ListId $listId,
        TemplateId $templateId
    ): self {
        // Validate ownership
        if ($groupId === null && $restaurantId === null) {
            throw new CampaignMustHaveOwnerException();
        }
        if ($groupId !== null && $restaurantId !== null) {
            throw new CampaignCannotHaveBothOwnersException();
        }

        $instance = new self(
            id: $id,
            groupId: $groupId,
            restaurantId: $restaurantId,
            name: $name,
            description: null,
            status: CampaignStatus::DRAFT,
            listId: $listId,
            templateId: $templateId,
            scheduledAt: null,
            sentAt: null
        );

        $instance->recordLast(CampaignCreatedEvent::fromEntity($instance));

        return $instance;
    }

    // Update
    public function update(string $name, ?string $description = null): void
    {
        if ($this->status !== CampaignStatus::DRAFT) {
            throw new CampaignCannotBeEditedException();
        }

        $this->name = $name;
        $this->description = $description;

        $this->recordLast(new CampaignUpdatedEvent($this->id));
    }

    // State transitions
    public function schedule(DateTimeImmutable $scheduledAt): void
    {
        if ($this->status !== CampaignStatus::DRAFT) {
            throw new CampaignCannotBeScheduledException();
        }

        if ($scheduledAt <= new DateTimeImmutable()) {
            throw new InvalidScheduleDateException();
        }

        $this->status = CampaignStatus::SCHEDULED;
        $this->scheduledAt = $scheduledAt;

        $this->recordLast(new CampaignScheduledEvent($this->id, $scheduledAt));
    }

    public function start(): void
    {
        if ($this->status !== CampaignStatus::SCHEDULED) {
            throw new CampaignCannotBeStartedException();
        }

        $this->status = CampaignStatus::RUNNING;
        $this->sentAt = new DateTimeImmutable();

        $this->recordLast(new CampaignStartedEvent($this->id));
    }

    public function complete(): void
    {
        if ($this->status !== CampaignStatus::RUNNING) {
            throw new CampaignCannotBeCompletedException();
        }

        $this->status = CampaignStatus::COMPLETED;

        $this->recordLast(new CampaignCompletedEvent($this->id));
    }

    public function cancel(): void
    {
        if ($this->status === CampaignStatus::COMPLETED) {
            throw new CampaignCannotBeCancelledException();
        }

        $this->status = CampaignStatus::CANCELLED;

        $this->recordLast(new CampaignCancelledEvent($this->id));
    }
}
```

### 1.2 Create Value Objects

#### IDs (extends Ulid)
```php
<?php

declare(strict_types=1);

namespace CoverManager\Marketing\Campaign\Domain\ValueObjects;

use CoverManager\Shared\Framework\Domain\ValueObjects\Ulid;

final class CampaignId extends Ulid
{
}
```

#### Complex Value Objects
```php
<?php

declare(strict_types=1);

namespace CoverManager\Marketing\List\Domain\ValueObjects;

final readonly class ListCriteria
{
    /**
     * @param array<FilterRule> $filters
     * @param string $logic 'AND' | 'OR'
     */
    public function __construct(
        public array $filters,
        public string $logic
    ) {
        if (!in_array($logic, ['AND', 'OR'], true)) {
            throw new InvalidLogicException();
        }
    }
}
```

### 1.3 Create Enums

```php
<?php

declare(strict_types=1);

namespace CoverManager\Marketing\Campaign\Domain\Enums;

enum CampaignStatus: string
{
    case DRAFT = 'draft';
    case SCHEDULED = 'scheduled';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
```

---

## PHASE 2: Infrastructure Layer

### 2.1 Repository Interface (Domain)

```php
<?php

namespace CoverManager\Marketing\Campaign\Domain\Repositories;

use CoverManager\Marketing\Campaign\Domain\Entities\Campaign;
use CoverManager\Marketing\Campaign\Domain\ValueObjects\CampaignId;

interface CampaignRepositoryInterface
{
    public function findById(CampaignId $id): ?Campaign;
    public function save(Campaign $campaign): void;
    public function delete(CampaignId $id): void;
}
```

### 2.2 Repository Implementation (Infrastructure)

```php
<?php

namespace CoverManager\Marketing\Campaign\Infrastructure\Persistence;

use CoverManager\Marketing\Campaign\Domain\Entities\Campaign;
use CoverManager\Marketing\Campaign\Domain\Repositories\CampaignRepositoryInterface;
use CoverManager\Marketing\Campaign\Domain\ValueObjects\CampaignId;

final class CampaignRepository implements CampaignRepositoryInterface
{
    public function __construct(
        private readonly CampaignMapper $mapper
    ) {
    }

    public function findById(CampaignId $id): ?Campaign
    {
        $model = CampaignModel::find($id->value());

        if ($model === null) {
            return null;
        }

        return $this->mapper->toDomain($model);
    }

    public function save(Campaign $campaign): void
    {
        $data = $this->mapper->toModel($campaign);

        CampaignModel::updateOrCreate(
            ['id' => $campaign->id->value()],
            $data
        );
    }

    public function delete(CampaignId $id): void
    {
        CampaignModel::where('id', $id->value())->delete();
    }
}
```

### 2.3 Mapper/Hydrator

```php
<?php

namespace CoverManager\Marketing\Campaign\Infrastructure\Persistence;

use CoverManager\Marketing\Campaign\Domain\Entities\Campaign;
use CoverManager\Marketing\Campaign\Domain\Enums\CampaignStatus;
use CoverManager\Marketing\Campaign\Domain\ValueObjects\CampaignId;

final class CampaignMapper
{
    public function toDomain(CampaignModel $model): Campaign
    {
        // Use reflection to instantiate entity with private constructor
        $reflection = new \ReflectionClass(Campaign::class);
        $instance = $reflection->newInstanceWithoutConstructor();

        // Set public properties
        $instance->id = CampaignId::fromString($model->id);
        $instance->groupId = $model->group_id ? GroupId::fromString($model->group_id) : null;
        $instance->status = CampaignStatus::from($model->status);
        // ...

        return $instance;
    }

    public function toModel(Campaign $campaign): array
    {
        return [
            'id' => $campaign->id->value(),
            'group_id' => $campaign->groupId?->value(),
            'restaurant_id' => $campaign->restaurantId?->value(),
            'name' => $campaign->name,
            'status' => $campaign->status->value,
            // ...
        ];
    }
}
```

### 2.4 Eloquent Model

```php
<?php

namespace CoverManager\Marketing\Campaign\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class CampaignModel extends Model
{
    protected $table = 'marketing_campaigns';

    protected $fillable = [
        'id',
        'group_id',
        'restaurant_id',
        'name',
        'description',
        'status',
        'list_id',
        'template_id',
        'scheduled_at',
        'sent_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';
}
```

### 2.5 Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_campaigns', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('group_id', 26)->nullable();
            $table->char('restaurant_id', 26)->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'scheduled', 'running', 'completed', 'cancelled']);
            $table->char('list_id', 26);
            $table->char('template_id', 26);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('group_id');
            $table->index('restaurant_id');
            $table->index('status');
        });

        // Constraint: XOR between group_id and restaurant_id
        DB::statement('
            ALTER TABLE marketing_campaigns
            ADD CONSTRAINT chk_campaign_owner
            CHECK (
                (group_id IS NOT NULL AND restaurant_id IS NULL) OR
                (group_id IS NULL AND restaurant_id IS NOT NULL)
            )
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_campaigns');
    }
};
```

---

## PHASE 3: Application + HTTP Layer

### 3.1 Request Class

```php
<?php

namespace CoverManager\Apps\Api\Marketing\Campaign\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCampaignRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'group_id' => 'required_without:restaurant_id|string|size:26',
            'restaurant_id' => 'required_without:group_id|string|size:26',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'list_id' => 'required|string|size:26',
            'template_id' => 'required|string|size:26',
        ];
    }
}
```

### 3.2 Action Class

```php
<?php

namespace CoverManager\Apps\Api\Marketing\Campaign\Actions;

use CoverManager\Marketing\Campaign\Application\Commands\CreateCampaign\CreateCampaignCommand;
use CoverManager\Marketing\Campaign\Application\Queries\GetCampaignById\GetCampaignByIdQuery;
use CoverManager\Marketing\Campaign\Domain\ValueObjects\CampaignId;
use CoverManager\Shared\Framework\Infrastructure\Bus\CommandBus\CommandBusInterface;
use CoverManager\Shared\Framework\Application\QueryBus;

final class CreateCampaignAction
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus
    ) {
    }

    public function __invoke(array $data): CampaignDTO
    {
        // 1. Generate ID
        $id = CampaignId::random();

        // 2. Dispatch command (void)
        $this->commandBus->dispatch(
            new CreateCampaignCommand(
                id: $id,
                groupId: $data['group_id'] ?? null,
                restaurantId: $data['restaurant_id'] ?? null,
                name: $data['name'],
                listId: $data['list_id'],
                templateId: $data['template_id']
            )
        );

        // 3. Query for response
        return $this->queryBus->query(
            new GetCampaignByIdQuery($id)
        );
    }
}
```

### 3.3 Command (Write Operation)

```php
<?php

namespace CoverManager\Marketing\Campaign\Application\Commands\CreateCampaign;

use CoverManager\Marketing\Campaign\Domain\ValueObjects\CampaignId;
use CoverManager\Shared\Framework\Application\Commands\CommandInterface;

final readonly class CreateCampaignCommand implements CommandInterface
{
    public function __construct(
        public CampaignId $id,
        public ?string $groupId,
        public ?string $restaurantId,
        public string $name,
        public string $listId,
        public string $templateId
    ) {
    }
}
```

```php
<?php

namespace CoverManager\Marketing\Campaign\Application\Commands\CreateCampaign;

use CoverManager\Marketing\Campaign\Domain\Entities\Campaign;
use CoverManager\Marketing\Campaign\Domain\Repositories\CampaignRepositoryInterface;

final class CreateCampaignHandler
{
    public function __construct(
        private readonly CampaignRepositoryInterface $repository
    ) {
    }

    public function __invoke(CreateCampaignCommand $command): void
    {
        $campaign = Campaign::create(
            id: $command->id,
            groupId: $command->groupId ? GroupId::fromString($command->groupId) : null,
            restaurantId: $command->restaurantId ? RestaurantId::fromString($command->restaurantId) : null,
            name: $command->name,
            listId: ListId::fromString($command->listId),
            templateId: TemplateId::fromString($command->templateId)
        );

        $this->repository->save($campaign);

        // Command does NOT return anything
    }
}
```

### 3.4 Query (Read Operation)

```php
<?php

namespace CoverManager\Marketing\Campaign\Application\Queries\GetCampaignById;

use CoverManager\Marketing\Campaign\Domain\ValueObjects\CampaignId;
use CoverManager\Shared\Framework\Infrastructure\Bus\QueryBus\QueryInterface;

/**
 * @implements QueryInterface<CampaignDTO>
 */
final readonly class GetCampaignByIdQuery implements QueryInterface
{
    public function __construct(
        public CampaignId $id
    ) {
    }
}
```

```php
<?php

namespace CoverManager\Marketing\Campaign\Application\Queries\GetCampaignById;

use CoverManager\Marketing\Campaign\Domain\Repositories\CampaignRepositoryInterface;

final class GetCampaignByIdHandler
{
    public function __construct(
        private readonly CampaignRepositoryInterface $repository
    ) {
    }

    public function __invoke(GetCampaignByIdQuery $query): CampaignDTO
    {
        $campaign = $this->repository->findById($query->id);

        if ($campaign === null) {
            throw new CampaignNotFoundException();
        }

        return CampaignDTO::fromEntity($campaign);
    }
}
```

### 3.5 Controller

```php
<?php

namespace CoverManager\Apps\Api\Marketing\Campaign;

use CoverManager\Apps\Api\Marketing\Campaign\Actions\CreateCampaignAction;
use CoverManager\Apps\Api\Marketing\Campaign\Requests\CreateCampaignRequest;
use CoverManager\Apps\Api\Marketing\Campaign\Resources\CampaignResource;
use Illuminate\Http\JsonResponse;

class CampaignController
{
    public function __construct(
        private readonly CreateCampaignAction $createAction
    ) {
    }

    public function store(CreateCampaignRequest $request): JsonResponse
    {
        $dto = ($this->createAction)($request->validated());

        return (new CampaignResource($dto))
            ->response()
            ->setStatusCode(201);
    }
}
```

### 3.6 Laravel Resource

```php
<?php

namespace CoverManager\Apps\Api\Marketing\Campaign\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'group_id' => $this->groupId,
            'restaurant_id' => $this->restaurantId,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'list_id' => $this->listId,
            'template_id' => $this->templateId,
            'scheduled_at' => $this->scheduledAt,
            'sent_at' => $this->sentAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
```

### 3.7 Routes

```php
// routes/api.php
use CoverManager\Apps\Api\Marketing\Campaign\CampaignController;

Route::prefix('campaigns')->group(function () {
    Route::post('/', [CampaignController::class, 'store']);
    Route::get('/', [CampaignController::class, 'index']);
    Route::get('/{id}', [CampaignController::class, 'show']);
    Route::put('/{id}', [CampaignController::class, 'update']);
    Route::delete('/{id}', [CampaignController::class, 'destroy']);
});
```

---

## ✅ Checklist per Phase

### PHASE 1: Domain
- [ ] Entity created with public properties
- [ ] Static `create()` method
- [ ] `update()` method
- [ ] Specific methods for state transitions
- [ ] Status is NOT changed directly
- [ ] NO getters/setters
- [ ] Value Objects created
- [ ] Enums created
- [ ] Domain events

### PHASE 2: Infrastructure
- [ ] Repository Interface in Domain
- [ ] Repository Implementation in Infrastructure
- [ ] Mapper/Hydrator
- [ ] Eloquent Model
- [ ] Migration with constraints

### PHASE 3: Application + HTTP
- [ ] Request class with validations
- [ ] Action class
- [ ] Command + Handler (void)
- [ ] Query + Handler (DTO)
- [ ] Controller (uses Action)
- [ ] Laravel Resource
- [ ] Routes registered

---

**See also:**
- [critical-rules.md](critical-rules.md) - Critical project rules
- [architecture.md](architecture.md) - DDD Architecture
- [MARKETING_CAMPAIGNS_IMPLEMENTATION_PLAN.md](../docs/MARKETING_CAMPAIGNS_IMPLEMENTATION_PLAN.md) - Detailed plan
