---
description: Analyzes performance issues in queries and database
proactive: true
triggers:
  - "slow"
  - "performance"
  - "optimize"
  - "N+1"
  - "too many queries"
  - "timeout"
  - "indexes"
---

Launches a general-purpose agent that analyzes performance issues related to queries and database access.

**IMPORTANT:** Read `ai_docs/critical-rules.md` section "Database Performance" before starting.

## Analysis areas:

### 1. Queries in Loops (N+1) - CRITICAL
Search for patterns like:

```php
foreach ($clients as $client) {
    $bookings = $this->bookingRepository->findByClientId($client->id);
    // CRITICAL: N queries
}

foreach ($campaigns as $campaign) {
    $list = $this->listQuery->getById($campaign->listId);
    // CRITICAL: N queries
}
```

**For each case found, report:**
- Exact location (file and line)
- Estimated number of queries (based on typical data)
- Impact: CRITICAL/HIGH/MEDIUM
- Suggested fix with code:
  ```php
  // Fix: Extract IDs and query with IN
  $clientIds = array_column($clients, 'id');
  $bookings = $this->bookingRepository->findByClientIds($clientIds);

  // Join in PHP
  foreach ($clients as $client) {
      $client->bookings = array_filter($bookings, fn($b) => $b->clientId === $client->id);
  }
  ```

### 2. Handlers with direct DB:: - CRITICAL
Search in `src/**/Application/**/Handler.php`:

```php
// FORBIDDEN
DB::table('clients')->where('restaurant_id', $id)->get();
```

**For each case:**
- File and line
- Query being made
- Suggestion: Create method in Repository Interface with complete example

### 3. Missing Eager Loading
Search for relation access without eager loading:

```php
// BAD: N+1 in relations
$clients = Client::all();
foreach ($clients as $client) {
    echo $client->restaurant->name;  // N extra queries
}
```

**Report:**
- Location
- Relation not loaded
- Fix: `Client::with('restaurant')->get()`

### 4. Missing Indexes
Analyze queries and search for:
- Foreign keys without index
- WHERE clauses on columns without index
- JOIN on columns without index

**For each case, generate migration:**
```php
Schema::table('clients', function (Blueprint $table) {
    $table->index('restaurant_id');
    $table->index(['app', 'app_id']); // Composite index
});
```

### 5. Complex Queries without Optimization
Search for:
- Multiple JOINs without indexes
- Subqueries that could be CTEs
- Queries with LIKE '%value%' (doesn't use index)

### 6. Unnecessary Hydration
Search for cases where full entity is hydrated but only one field is used:

```php
// Inefficient
$client = $this->clientRepository->findById($id);
$email = $client->getEmail();

// Better: Specific query
$email = $this->clientRepository->getEmailById($id);
```

### 7. Missing Pagination
Search for queries that return large arrays without pagination:

```php
// Potentially thousands of records
public function getAllClients(): array

// With pagination
public function getClientsPaginated(int $page, int $perPage): array
```

## Metrics to include

For each problem, estimate:
- **Frequency**: How many times is this code called? (High/Medium/Low)
- **Volume**: How many records does it affect? (Thousands/Hundreds/Tens)
- **Impact**: CRITICAL/HIGH/MEDIUM/LOW

## Report Format

```markdown
# Performance Analysis

## CRITICAL (Immediate Fix)

### 1. N+1 Query in GetBookingsHandler
- **File:** `src/Reservation/Application/Queries/GetBookingsHandler.php:45`
- **Problem:** Query in loop - 1 query per booking
- **Impact:**
  - Frequency: High (every booking list)
  - Volume: ~500 bookings per typical query
  - **Total: 500 extra queries per execution**
- **Fix:**
  ```php
  // Before (line 45)
  foreach ($bookings as $booking) {
      $client = $this->clientRepo->findById($booking->clientId); // N queries
  }

  // After
  $clientIds = array_column($bookings, 'clientId');
  $clients = $this->clientRepo->findByIds($clientIds); // 1 query
  $clientsMap = array_column($clients, null, 'id');
  foreach ($bookings as $booking) {
      $client = $clientsMap[$booking->clientId] ?? null;
  }
  ```

### 2. Handler using direct DB::
- **File:** `src/Reservation/Application/Queries/IndexBookingsHandler.php:23`
- **Problem:** Direct DB access in Handler
- **Code:**
  ```php
  DB::table('bookings')
      ->where('client_id', $clientId)
      ->get();
  ```
- **Fix:** Create `BookingReadModelRepositoryInterface` with method:
  ```php
  // Domain/Repositories/BookingReadModelRepositoryInterface.php
  interface BookingReadModelRepositoryInterface {
      public function findPaginated(...): PaginatedCollection;
  }

  // Infrastructure/Persistence/BookingReadModelRepository.php
  public function findPaginated(...): PaginatedCollection {
      return DB::table('bookings')->...;
  }
  ```

## HIGH (Plan Fix)

### 3. Missing Index on foreign key
- **Table:** `bookings`
- **Column:** `client_id` (no index)
- **Impact:** Slow JOINs in booking queries
- **Migration:**
  ```php
  Schema::table('bookings', function (Blueprint $table) {
      $table->index('client_id');
  });
  ```

## Executive Summary

- **Critical:** X issues (estimated: +X avoidable queries)
- **High:** X issues (estimated: slow queries <500ms)
- **Medium:** X issues

**Estimated ROI of fixes:**
- ~80% reduction in database queries
- ~60% improvement in response times

## Action Priority
1. Fix N+1 queries (immediate impact)
2. Add missing indexes
3. Refactor handlers with DB::
```

**IMPORTANT:**
- Prioritize by real impact (frequency x volume)
- Include complete code in fixes
- Generate migrations when necessary
- Estimate improvement metrics

## SAVE REPORT

When the analysis is complete:

1. Save the report in: `docs/Reports/YYYY-MM-DD-analyze-performance.md`
2. Use current date in ISO format (example: `2025-01-07`)
3. Include at the beginning of the report:
   ```markdown
   # Performance Analysis

   **Date:** YYYY-MM-DD
   **Agent:** analyze-performance
   **Executed by:** Claude Code
   ```

4. The report must include ALL generated sections
5. Inform the user of the saved report location
