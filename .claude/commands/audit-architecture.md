---
description: Audits DDD and Hexagonal architecture compliance
proactive: true
triggers:
  - "violating"
  - "does not comply"
  - "incorrect architecture"
  - "review code"
  - "audit"
  - "validate architecture"
---

Launches a general-purpose agent that audits compliance with critical DDD and Hexagonal architecture rules.

**IMPORTANT:** Read ALL documentation in `ai_docs/` FIRST before starting the analysis.

**Required documentation:**
- `ai_docs/critical-rules.md` - Critical TOP 3 rules
- `ai_docs/architecture.md` - DDD and Hexagonal Architecture
- `ai_docs/http-layer-actions.md` - Actions pattern
- `ai_docs/http-requests-pattern.md` - Requests pattern
- `ai_docs/infrastructure.md` - Entity Construction Pattern

## Areas to audit:

### 1. HTTP Layer - Actions (CRITICAL)
Search in `Apps/Api/**/*/Action.php`:

**FORBIDDEN in Actions:**
- `DB::table()` or any `DB::` usage
- `Model::find()` or direct model access
- `foreach`, `array_map`, any type of loops
- Business validations or calculations
- Data transformations
- **CRITICAL:** Returning `JsonResponse` (must return Resource `XxxRes`)
- **CRITICAL:** Returning arrays or DTOs directly

**ALLOWED in Actions:**
- `verifyAccess()` - Security verification only
- `$this->commandBus->dispatch()` - Delegate commands
- `$this->queryBus->query()` - Delegate queries
- `$this->resService->getXxxResource()` - Get Resource
- **ALWAYS return Resource (`XxxRes`)**

**Report:** File, line, specific violation, problematic code

### 2. HTTP Layer - Requests (CRITICAL)
Search in `Apps/Api/**/*/Request.php`:

**FORBIDDEN in Requests:**
- `public function rules(): array`
- `public function after(): array`
- `$this->validated()`
- Any use of Laravel validation

**ALLOWED in Requests:**
- `public function getDto(): XxxDto` - Strongly-typed DTO mapping only

**Report:** File, line, specific violation

### 3. Application Layer - Handlers (CRITICAL)
Search in `src/**/Application/**/Handler.php`:

**FORBIDDEN in Handlers:**
- `DB::table()` or any direct Query Builder usage
- `DB::` in any form

**CORRECT in Handlers:**
- Inject `XxxRepositoryInterface` (Domain interface)
- Call repository methods
- Use CommandBus/QueryBus

**If you find DB:: in Handler:**
1. Identify what query it does
2. Suggest creating method in Repository Interface
3. Show implementation example in Repository

### 4. CQRS - Commands (CRITICAL)
Search in `src/**/Application/Commands/**Command.php`:

**FORBIDDEN:**
- Commands returning something other than `void`
- Handlers doing `return $id;` or similar

**CORRECT:**
- Command Handler with return type `void`
- ID generated BEFORE the command and passed as parameter

**Report:** File, line, incorrect signature

### 5. Entity Construction Pattern (CRITICAL)
Search in `src/**/Domain/Entities/*.php`:

**FORBIDDEN:**
- Public `__construct` without factory methods
- Public `setXxx()` methods for child collections
- Creating entities without `create()` factory

**CORRECT:**
- Private `__construct`
- `create()` for new entities (enforces invariants)
- `reconstitute()` for hydration (allows any state)

### 6. Performance - Queries in Loops
Search for N+1 patterns:

```php
foreach ($items as $item) {
    $related = $repository->findById($item->id);  // N queries
}
```

**Report:**
- Exact location
- Impact estimation (number of potential queries)
- Suggestion: use `findByIds()` with IN clause

### 7. ValueObjects - IDs as strings
Search in `src/**/Domain/Repositories/**Interface.php`:

**FORBIDDEN:**
```php
public function findById(string $id): ?Client;  // string
```

**CORRECT:**
```php
public function findById(ClientId $id): ?Client;  // ValueObject
```

**Report:** Interfaces using `string` for IDs

### 8. Bounded Contexts - Violations
Search for:
- Repositories used outside their BC
- Entities shared between BCs
- Cross-dependencies between BCs without QueryBus/CommandBus

## Report Format

Generate report in this format:

```markdown
# DDD and Hexagonal Architecture Audit

## CRITICAL (High Priority)

### HTTP Layer - Actions with business logic
1. **Apps/Api/Booking/Create/CreateBookingAction.php:45**
   - Violation: Use of `DB::table()` in Action
   - Code: `DB::table('bookings')->insert([...])`
   - Fix: Move to `CreateBookingCommand` and its Handler

### HTTP Layer - Requests with validation
2. **Apps/Api/Client/Create/CreateClientRequest.php:12**
   - Violation: Method `rules()` present
   - Fix: Remove `rules()`, only keep `getDto()`

### Entity Construction - Public constructor
3. **src/Reservation/Domain/Entities/Booking.php:28**
   - Violation: Public constructor allows invalid state
   - Fix: Make private, use `create()` and `reconstitute()`

## MEDIUM (Medium Priority)

### Performance - Query N+1
4. **src/Reservation/Application/Queries/GetBookingsHandler.php:34**
   - Violation: Query in loop
   - Impact: ~500 additional queries
   - Fix: Use `$this->bookingRepo->findByClientIds($clientIds)`

## Summary
- Critical: X violations
- Medium: X violations
- Low: X violations

## Action Priority
1. Fix Actions and Requests first (blocks clean development)
2. Fix entity construction patterns (data integrity)
3. Fix Handlers with DB:: (violates architecture)
4. Optimize N+1 queries (production impact)
```

**IMPORTANT:**
- Scan the ENTIRE project, not just some files
- Prioritize by severity (CRITICAL > MEDIUM > LOW)
- Include concrete fix suggestions
- Count totals by category

## SAVE REPORT

When the analysis is complete, you must:

1. Save the report in markdown format at: `docs/Reports/YYYY-MM-DD-audit-architecture.md`
2. Use current date in ISO format (example: `2025-01-07`)
3. Include at the beginning of the report:
   ```markdown
   # DDD and Hexagonal Architecture Audit

   **Date:** YYYY-MM-DD
   **Agent:** audit-architecture
   **Executed by:** Claude Code
   ```

4. The report must include ALL generated sections
5. Inform the user of the saved report location