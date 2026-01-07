---
description: Validates HTTP Layer (Actions and Requests)
proactive: true
triggers:
  - "create action"
  - "new action"
  - "create request"
  - "new request"
  - "DB:: in action"
  - "action with logic"
  - "rules() in request"
---

Launches a general-purpose agent that specifically validates the HTTP Layer according to the project's critical rules.

**IMPORTANT:** Read FIRST:
- `ai_docs/critical-rules.md` - TOP 3 rules
- `ai_docs/http-layer-actions.md` - Complete Actions pattern
- `ai_docs/http-requests-pattern.md` - Complete Requests pattern

## RULE #2: Actions NEVER have Business Logic

Search in `Apps/Api/**/*/Action.php`:

### CRITICAL PROHIBITIONS

#### 1. Database Access
```php
// FORBIDDEN
DB::table('users')->where(...)->get();
Model::find($id);
User::where(...)->first();
```

#### 2. Business Logic
```php
// FORBIDDEN - Validations
if (!$email && !$phone) {
    throw new ValidationException();
}

// FORBIDDEN - Loops
foreach ($dto->clients as $client) {
    // processing..
}

// FORBIDDEN - Transformations
$data = array_map(fn($c) => strtoupper($c), $clients);

// FORBIDDEN - Calculations
$total = $price * $quantity * (1 + $taxRate);
```

#### 3. Incorrect Return (CRITICAL)
```php
// FORBIDDEN - JsonResponse
public function __invoke(...): JsonResponse {
    return response()->json([...]);
}

// FORBIDDEN - Array
public function __invoke(...): array {
    return ['data' => ...];
}

// FORBIDDEN - DTO
public function __invoke(...): ClientDto {
    return $dto;
}
```

#### 4. Complexity
```php
// FORBIDDEN - More than 20 lines in Action
public function __invoke(...) {
    // ... 50 lines of code
}
```

### CORRECT PATTERN

**An Action must have MAXIMUM 3 responsibilities:**

```php
final readonly class CreateBookingAction
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private BookingResService $resService,
    ) {}

    public function __invoke(CreateBookingDto $dto): BookingCreatedRes {
        // 1. Dispatch command (ALL business goes to Handler)
        $bookingId = BookingId::random();
        $this->commandBus->dispatch(
            new CreateBookingCommand($bookingId, $dto->clientId, ...)
        );

        // 2. Return response (via ResService)
        return $this->resService->getBookingCreatedResource($bookingId);
    }
}
```

**Valid Action checklist:**
- [ ] <= 20 lines of code
- [ ] No `DB::` or `Model::`
- [ ] No `foreach`, `array_map`, loops
- [ ] No `if` statements (except null checks)
- [ ] Dispatches exactly ONE Command or Query
- [ ] Returns `XxxRes` (Resource) via ResService
- [ ] Controller converts Res to JsonResponse

### For each violation, report:

```markdown
### Action: CreateBookingAction.php

**File:** Apps/Api/Booking/Create/CreateBookingAction.php

**Violations found:**

1. **Line 45: DB:: in Action**
   ```php
   DB::table('bookings')->insert([...]);
   ```
   - **Severity:** CRITICAL
   - **Fix:** Create `CreateBookingCommand` and move logic to Handler

2. **Line 52: Loop in Action**
   ```php
   foreach ($dto->products as $product) {
       // processing
   }
   ```
   - **Severity:** CRITICAL
   - **Fix:** Move processing to Handler

3. **Line 78: Returns JsonResponse**
   ```php
   return response()->json($data);
   ```
   - **Severity:** CRITICAL
   - **Fix:** Return `BookingRes` and let Controller convert it

**Complexity:**
- Lines of code: 85 (limit: 20)
- Cyclomatic complexity: High

**Suggested refactor:** See example in ai_docs/http-layer-actions.md
```

## RULE #1: Requests NEVER use Laravel Validation

Search in `Apps/Api/**/*/Request.php`:

### ABSOLUTE PROHIBITIONS

```php
// NEVER these methods
public function rules(): array {
    return [...];
}

public function after(): array {
    return [...];
}

// NEVER call
$this->validated();
```

### CORRECT PATTERN

```php
final class CreateBookingRequest extends AbstractFormRequest
{
    // ONLY allowed method
    public function getDto(): CreateBookingDto
    {
        return new CreateBookingDto(
            id: BookingId::random(),
            clientId: ClientId::fromString($this->input('client_id')),
            // ... strongly typed mapping
        );
    }
}
```

### For each violation, report:

```markdown
### Request: CreateBookingRequest.php

**File:** Apps/Api/Booking/Create/CreateBookingRequest.php

**Violations found:**

1. **Line 12: Method rules() present**
   ```php
   public function rules(): array {
       return [
           'client_id' => 'required|string',
           'date' => 'required|date',
       ];
   }
   ```
   - **Severity:** CRITICAL TOP 1
   - **Fix:**
     1. Remove `rules()` method
     2. Move validation to DTO or Command Handler
     3. Request should only map to DTO

2. **Line 23: Use of validated()**
   ```php
   $data = $this->validated();
   ```
   - **Severity:** CRITICAL
   - **Fix:** Use `$this->input()` directly

**Complete refactor:**
```php
// CORRECT
public function getDto(): CreateBookingDto {
    return new CreateBookingDto(
        id: BookingId::random(),
        clientId: ClientId::fromString($this->input('client_id')),
    );
}

// Validation is done in DTO constructor or in Handler
```

**Read:** ai_docs/http-requests-pattern.md for complete pattern
```

## Report Format

```markdown
# HTTP Layer Validation

## Executive Summary

- **Actions analyzed:** X
- **Requests analyzed:** X
- **Critical violations:** X
- **Medium violations:** X

## Actions with CRITICAL Violations

### 1. CreateBookingAction (3 violations)
[Complete detail as example above]

### 2. IndexBookingsAction (2 violations)
[...]

## Requests with CRITICAL Violations

### 1. CreateBookingRequest (rules() present)
[Complete detail as example above]

## Action Checklist

**Immediate Priority (Blocks clean development):**
- [ ] Remove all `rules()` and `after()` from Requests
- [ ] Refactor Actions with DB:: to Commands
- [ ] Change returns to Resources (not JsonResponse)

**High Priority:**
- [ ] Simplify Actions > 20 lines
- [ ] Move loops and logic to Handlers

## Perfect Actions (Examples to follow)

These Actions comply 100% with the pattern:
1. `ShowBookingAction.php` - 10 lines, only query and return
2. `CancelBookingAction.php` - 12 lines, perfect pattern

## References

- `ai_docs/critical-rules.md` - TOP 3 rules
- `ai_docs/http-layer-actions.md` - Complete Actions pattern
- `ai_docs/http-requests-pattern.md` - Complete Requests pattern
```

**IMPORTANT:**
- Analyze ALL Actions and Requests in the project
- Prioritize TOP 1 and TOP 2 violations (most critical)
- Include correct code examples
- Point out perfect Actions as reference

## SAVE REPORT

When the analysis is complete, you must:

1. Save the report in markdown format at: `docs/Reports/YYYY-MM-DD-validate-http-layer.md`
2. Use current date in ISO format (example: `2025-01-07`)
3. Include at the beginning of the report:
   ```markdown
   # HTTP Layer Validation

   **Date:** YYYY-MM-DD
   **Agent:** validate-http-layer
   **Executed by:** Claude Code
   ```

4. The report must include ALL generated sections
5. Inform the user of the saved report location