# Code Quality & Best Practices

SOLID principles, naming conventions, enums, and domain layer best practices.

## SOLID Principles

### Single Responsibility Principle (SRP)

**Rule:** A class should solve ONE problem (not do one thing)

#### Understanding SRP

❌ **Common Misunderstanding:**
"A class should only have one method"

✅ **Correct Understanding:**
"A class should focus on solving one problem"

#### Example:

```php
// ✅ CORRECT - One responsibility: Client repository
class ClientRepository
{
    public function findById(ClientId $id): ?Client {}
    public function findByEmail(string $email): ?Client {}
    public function findByRestaurant(RestaurantId $id): array {}
    public function save(Client $client): void {}
    // Multiple methods, but ONE responsibility: Client data access
}
```

#### Large Files with Single Responsibility

**Problem:** File is huge but has single responsibility

**Solution:** Split into sub-responsibilities

```php
// Before: ClientRepository.php (2000 lines)
class ClientRepository
{
    public function findById() {}
    public function findByEmail() {}
    public function findWithStats() {}
    public function findWithBookings() {}
    // ... 50 more methods
}

// After: Split by sub-responsibility
// ClientRepository.php (core methods)
class ClientRepository
{
    public function findById() {}
    public function save() {}
}

// ClientRMQueryRepository.php (read models)
class ClientRMQueryRepository
{
    public function findWithStats() {}
    public function findWithBookings() {}
}
```

### Dependency Principle - "Ask Only What You Need"

**Rule:** A method should only ask for what it actually needs.

#### Bad Example:
```php
public function sendEmail(Client $client)
{
    $email = $client->getEmail();
    $this->mailer->send($email, 'Welcome!');
    // Only needs email, but coupled to entire Client object
}

// Problems:
// - Can't reuse for non-Client emails
// - Hard to test (need full Client mock)
// - Breaks when Client changes
```

#### Good Example:
```php
public function sendEmail(string $email)
{
    $this->mailer->send($email, 'Welcome!');
    // Only asks for what it needs
}

// Benefits:
// - Reusable for any email
// - Easy to test (just pass string)
// - Not affected by Client changes
```

#### When to Make Exceptions

```php
// Question: Will I need more client data tomorrow?

// If YES and certain:
public function sendWelcomeEmail(Client $client) {
    // Acceptable if you know you'll need name, preferences, etc.
}

// If NO or uncertain:
public function sendWelcomeEmail(string $email, string $name) {
    // Better - only ask for what you need
}
```

**Video resource:** https://www.youtube.com/watch?v=ci12akiGg1s&t=362s&ab_channel=ProductCrafter

Also covers "Tell Don't Ask" pattern.

### Stateless Business Services

**Rule:** Services should NEVER store state

#### Correct Pattern:
```php
class BookingPriceCalculator
{
    public function __construct(
        private readonly TaxService $taxService  // Readonly, no state
    ) {}

    public function calculate(Booking $booking): Money
    {
        // No state stored, just computation
        return $this->taxService->applyTax($booking->basePrice());
    }
}
```

#### Wrong Pattern - Services with State (1990s anti-pattern):
```php
class Calculator
{
    private int $result = 0;  // ❌ STATE!

    public function add(int $n): void
    {
        $this->result += $n;
    }

    public function subtract(int $n): void
    {
        $this->result -= $n;
    }

    public function getResult(): int
    {
        return $this->result;
    }
}

// Problems:
// - Multiple entry points with shared state
// - Unpredictable results depending on call order
// - Not thread-safe
// - Hard to test
```

#### Exception: Single Entry Point with State

```php
class ReportGenerator
{
    private array $data = [];  // State OK if...

    public function __invoke(ReportRequest $request): Report
    {
        // ... only ONE entry point (__invoke)
        $this->data = $this->fetchData($request);
        return $this->generateReport();
    }

    // Private methods can use $this->data
    private function fetchData() {}
    private function generateReport() {}
}

// Acceptable because:
// - Only one entry point (__invoke)
// - State is predictable (always starts fresh)
// - Can't have inconsistent state
```

**Note:** Don't confuse with utility/helper classes (those have no state at all).

---

## Domain Layer Best Practices

### Anemic Entities - What Goes Where?

#### Business Logic in Entities ✅

**Should be in entity:**
- Validate data and maintain invariants
- Business relationships within the domain
- Calculations and business rules specific to entity

```php
class Client
{
    public function changeEmail(string $newEmail): void
    {
        // Invariant: Email must be valid
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException($newEmail);
        }

        $this->email = $newEmail;
        $this->recordEvent(new ClientEmailChangedEvent($this->id, $newEmail));
    }

    public function getFullName(): string
    {
        // Calculation within entity
        return $this->name . ' ' . $this->surname;
    }

    public function isSpecial(): bool
    {
        // Business rule specific to client
        return $this->type === ClientType::VIP;
    }
}
```

#### Business Logic in Domain Services ✅

**Should be in domain service:**
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
        DateTimeImmutable $datetime
    ): bool {
        // Needs repository - can't be in entity
        $existingBooking = $this->repository->findByRestaurantAndDatetime(
            $restaurantId,
            $datetime
        );

        return $existingBooking === null;
    }
}

class RelateClientsInGroupService
{
    public function relateClients(Client $client1, Client $client2): void
    {
        // Relates TWO different client instances
        // Can't be in Client entity (doesn't know about other instances)
    }
}
```


---

## Naming Conventions

### Reservations

#### ✅ Use: `Booking` (consistently)
```php
class Booking {}
class BookingRepository {}
class CreateBookingCommand {}
```

#### ❌ Avoid:
- `Reservs` (not English)
- `Reservation` (ambiguous in restaurant context)

**Reason:** In English, "Booking" is standard for restaurants with payment. "Reservation" is more generic.

### "Comprove" is Not English

#### ❌ Don't use: `Comprove`
```php
public function comproveBooking() {}  // Not English!
```

#### ✅ Replace with proper English:
```php
public function validateBooking() {}  // Check rules
public function checkBooking() {}     // Verify existence
public function ensureBooking() {}    // Make certain
```

**Context determines which word:**
- `Validate` - Check business rules
- `Check` - Verify condition
- `Ensure` - Guarantee something is true

---

## Enums

### Allowed Functionality in Enums

In theory, enums should only have constants. In practice, we allow some functionality for simplicity.

### 1. Decoration (Translations)

```php
enum ClientType: string
{
    case REGULAR = 'regular';
    case VIP = 'vip';
    case CORPORATE = 'corporate';

    public function getLabel(string $language): string
    {
        return match($this) {
            self::REGULAR => $language === 'es' ? 'Regular' : 'Regular',
            self::VIP => $language === 'es' ? 'VIP' : 'VIP',
            self::CORPORATE => $language === 'es' ? 'Corporativo' : 'Corporate',
        };
    }

    public function getLabels(string $language): array
    {
        return array_map(
            fn(self $type) => $type->getLabel($language),
            self::cases()
        );
    }
}
```

### 2. Mini Business Rules

```php
enum ClientType: string
{
    case REGULAR = 'regular';
    case VIP = 'vip';
    case CORPORATE = 'corporate';

    public function isSpecialClient(): bool
    {
        // Business rule closely tied to enum values
        return $this === self::VIP || $this === self::CORPORATE;
    }

    public function getDiscountPercentage(): int
    {
        return match($this) {
            self::VIP => 20,
            self::CORPORATE => 15,
            self::REGULAR => 0,
        };
    }
}
```

**Reasoning:** Business logic is directly related to the enum values.

### 3. Mappings/Translations Between Systems

```php
enum AdyenPaymentStatus: string
{
    case AUTHORIZED = 'authorized';
    case CANCELLED = 'cancelled';
    case ERROR = 'error';

    public function toCoverStatusEnum(): PaymentStatus
    {
        // Map external system enum to internal enum
        return match($this) {
            self::AUTHORIZED => PaymentStatus::PAID,
            self::CANCELLED => PaymentStatus::CANCELLED,
            self::ERROR => PaymentStatus::FAILED,
        };
    }
}

enum BookingSource: string
{
    case WEB = 'web';
    case PHONE = 'phone';
    case WIDGET = 'widget';

    public function toAnalyticsCode(): string
    {
        return match($this) {
            self::WEB => 'WB',
            self::PHONE => 'PH',
            self::WIDGET => 'WG',
        };
    }
}
```

### When NOT to Add to Enum

❌ **Don't add if logic is complex or depends on external data:**

```php
// ❌ BAD - Too complex for enum
enum ClientType: string
{
    case VIP = 'vip';

    public function calculateLoyaltyPoints(
        Booking $booking,
        LoyaltyRules $rules
    ): int {
        // Too complex - belongs in service
    }
}

// ✅ GOOD - Move to service
class LoyaltyPointsCalculator
{
    public function calculate(
        Client $client,
        Booking $booking
    ): int {
        // Complex logic here
    }
}
```

---

## Communication Between Domains (within same BC)

### Preferred: Query and Command Bus

```php
class BookingService
{
    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly CommandBus $commandBus
    ) {}

    public function createBooking(CreateBookingDTO $dto): BookingId
    {
        // Get client via Query Bus
        $client = $this->queryBus->query(
            new GetClientByIdQuery($dto->clientId)
        );

        // Validate
        // ...

        // Create booking via Command Bus
        return $this->commandBus->dispatch(
            new CreateBookingCommand($dto)
        );
    }
}
```

### Exception: Very Closely Related Domains

**When domains are VERY, VERY closely related** (ideally same module):

```php
class BookingService
{
    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly BookingRepository $bookingRepository
    ) {}

    public function createBooking(CreateBookingDTO $dto): BookingId
    {
        // Direct repository access OK if domains very closely related
        $client = $this->clientRepository->findById($dto->clientId);
        // ...
    }
}
```

**Rule of thumb:** If in doubt, use Query/Command Bus.

---

**See also:**
- [Architecture](architecture.md) - DDD structure
- [Application Layer](application-layer.md) - Queries and Commands
- [Infrastructure](infrastructure.md) - Repositories and Entities
- [Critical Rules](critical-rules.md) - Utility classes
