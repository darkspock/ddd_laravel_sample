# Requirement Analysis Guide for AI

Instructions for AI to analyze and validate requirement documents.

**IMPORTANT**: This is NOT a strict template validation. AI must verify that the requirement contains the necessary **content**, regardless of the exact format or structure used.

---

## Philosophy: Analysis Informs, Never Blocks

**THE USER ALWAYS DECIDES.** This analysis framework is informative, not bureaucratic.

### Core Principles

1. **Analysis identifies risks and gaps - it does NOT block development**
   - Show risks, impacts, missing information
   - The user decides whether to proceed anyway
   - If they say "proceed", we proceed

2. **Better upfront definition helps, but never creates bureaucracy**
   - If more analysis adds value → do it
   - If the user wants to move fast → move fast
   - Document concerns, then execute

3. **Context: Why this analysis matters**
   The company has a history of:
   - Features not fully developed ("parches sobre parches")
   - Shipping features nobody uses
   - Incomplete implementations

   This analysis helps avoid repeating those patterns, but it's a **tool, not a gate**.

### What This Means in Practice

| Situation | AI Response |
|-----------|-------------|
| Missing KPIs | Flag it, ask if they want to proceed anyway |
| Incomplete states | Document the gap, continue if user says so |
| High risk identified | Show the risk clearly, let user decide |
| User says "just do it" | Document concerns briefly, then execute |

**NEVER say:** "Cannot proceed until X is defined"
**ALWAYS say:** "X is missing/risky. Do you want to proceed anyway or define it first?"

---

## STEP 0: Detect Requirement Type (FIRST)

**Before any analysis, detect the requirement type from the file path:**

| Path Contains | Type | Validation Mode |
|---------------|------|-----------------|
| `/epics/` | Epic | FULL validation |
| `/features/` | Feature | SIMPLIFIED (check epic reference) |
| `/hotfixes/` | Hotfix | PROBLEM-FOCUSED |
| `/cases/` | Case | INVESTIGATION ONLY |

### Validation Rules by Type

#### EPIC (Full Validation)
- All steps apply
- Business alignment MANDATORY with full evidence
- KPIs MANDATORY (or Experiment Definition - see bypass below)
- Full use case analysis
- Full state/transition analysis
- Slicing analysis required

**Experimentation Bypass:** If the requirement is marked as an experiment, KPIs can be replaced with:
- Hypothesis (what we believe will happen)
- Test method (how we'll validate)
- Success metrics (what tells us it works)
- Investment limit (max effort before validation)
- Decision criteria (what happens if success/failure)

#### FEATURE (Simplified Validation)
- **MUST have parent epic reference**
- Business alignment: verify reference to epic exists
- KPIs: reference epic's KPIs
- Use cases: feature-specific only
- States: feature-specific subset
- Slicing: verify this is part of epic's slice

#### HOTFIX (Problem-Focused Validation)
- Skip: Business alignment, KPIs, slicing, full use cases
- **REQUIRED:** Problem description, Impact, Root cause
- **REQUIRED:** Proposed solution, Testing, Rollback plan
- Focus: Will this fix the problem? What could go wrong?

#### CASE (Investigation Only - NO Implementation)
- **THIS IS NOT AN IMPLEMENTATION REQUEST**
- Skip all implementation-related checks
- **REQUIRED:** Incident description, Timeline, Findings
- **REQUIRED:** Root cause analysis, Recommendations
- Output: Investigation report, NOT design/tasks
- If fix needed → recommend creating Hotfix or Feature

---

## Analysis Objective

When analyzing a requirement, AI must ensure:
1. All necessary information is present (content over form)
2. No obvious use cases are missing (Epics/Features only)
3. Entity states and transitions are defined (Epics/Features only)
4. The requirement is complete enough to implement (NOT for Cases)

Reference template: `/docs/working_docs/requirement_template.md`

---

## Step-by-Step Analysis Process

**Note:** Steps below apply to EPICS. For Features/Hotfixes/Cases, see type-specific rules above.

### Step 1: Identify Entities

- What is the main entity/resource being acted upon?
- Are there secondary entities involved?
- Example: "Add reservation screen" → Entity = Reservation

### Step 2: Apply CRUD Check

For each entity, verify if ALL CRUD operations are addressed or explicitly excluded:

| Operation | Question |
|-----------|----------|
| **Create** | Is there a way to create this entity? |
| **Read** | Is there a way to view this entity? |
| **Update** | Is there a way to modify this entity? |
| **Delete** | Is there a way to remove this entity? |
| **List** | Is there a way to see all entities? |

### Step 3: Apply Status & State Analysis (MANDATORY)

**Almost every business entity has a status.** At minimum: "exists" vs "deleted".

For EVERY entity, AI MUST verify:

| Check | Question |
|-------|----------|
| Initial status | What status does the entity have when created? |
| All statuses | What are ALL possible statuses? |
| Transitions | What transitions are valid between statuses? |
| Triggers | What triggers each transition? (user action, system event, time) |
| Conditions | What conditions must be met for each transition? |
| Side effects | What happens on each transition? (notifications, logs, cascades) |
| Delete strategy | Is delete hard or soft? |
| Restore | If soft delete, can it be restored? |
| Time-based | Are there automatic transitions? (auto-archive after X days) |

**If requirement doesn't specify states → ASK STAKEHOLDER**

### Step 4: Apply Use Case Pattern Detection

When a requirement mentions ONE action, check if related actions are needed:

#### CRUD Pattern
| If requested | Check if needed |
|--------------|-----------------|
| Create X | Read X, Update X, Delete X, List X |
| Add X | View X, Edit X, Remove X, Search X |

#### Lifecycle Pattern
| If requested | Check if needed |
|--------------|-----------------|
| Create reservation | Modify, Cancel, Confirm reservation |
| Register user | Update profile, Deactivate, Delete account |
| Submit order | Edit, Cancel, Track, Complete order |
| Open ticket | Assign, Update, Close, Reopen ticket |

#### State Machine Pattern
| If requested | Check if needed |
|--------------|-----------------|
| Approve X | Reject X, Request changes, Pending review |
| Activate X | Deactivate X, Suspend X |
| Start process | Pause, Resume, Stop process |

#### Bulk Operations Pattern
| If requested | Check if needed |
|--------------|-----------------|
| Add item | Add multiple, Import items |
| Delete item | Bulk delete |
| Update item | Bulk update, Mass edit |

#### Reporting Pattern
| If requested | Check if needed |
|--------------|-----------------|
| View data | Export, Print, Share data |
| List items | Filter, Sort, Paginate items |

### Step 5: Apply Inverse Operation Check

For every action, consider its opposite:

| Action | Inverse |
|--------|---------|
| Add | Remove |
| Enable | Disable |
| Open | Close |
| Start | Stop |
| Assign | Unassign |
| Activate | Deactivate |
| Archive | Restore |

### Step 6: Apply User Journey Check

- What happens **BEFORE** this action? (preconditions)
- What happens **AFTER** this action? (consequences)
- What if the user makes a **MISTAKE**? (error recovery)
- What if the user **CHANGES THEIR MIND**? (undo/cancel)

### Step 7: Collateral Impact Analysis (MANDATORY)

**New features rarely exist in isolation.** AI MUST analyze how the new requirement affects existing functionality.

#### 7.1 Identify Affected Areas

| Question | What to check |
|----------|---------------|
| **Existing entities** | Does this modify an existing entity? What else uses that entity? |
| **Shared data** | What other features read/write the same data? |
| **Business rules** | Does this change rules that apply elsewhere? |
| **Workflows** | Does this interrupt or modify existing workflows? |
| **Integrations** | What external systems depend on affected components? |
| **Reports/Dashboards** | Will existing reports show different data? |
| **Permissions** | Do existing permission rules need updating? |
| **Notifications** | Will existing notification triggers be affected? |

#### 7.2 Impact Categories

| Category | Description | Example |
|----------|-------------|---------|
| **Breaking Change** | Existing functionality will stop working | Changing a required field to optional |
| **Behavioral Change** | Same action produces different results | Modifying calculation logic |
| **Data Impact** | Existing data needs migration or reprocessing | Adding new status to existing records |
| **UI Impact** | Existing screens need updates | Adding field that should appear in lists |
| **API Impact** | Existing API contracts change | Adding required parameter |
| **Performance Impact** | Existing operations may slow down | Adding validation that queries DB |

#### 7.3 Impact Analysis Questions

For EACH affected area, AI must ask:

1. **What currently exists?** - Document current behavior
2. **What will change?** - Describe the difference
3. **Who is affected?** - Users, systems, processes
4. **Is it backwards compatible?** - Can old behavior coexist?
5. **What needs migration?** - Data, configuration, user training
6. **What could break?** - Worst case scenarios

#### 7.4 Common Collateral Impact Patterns

| New Requirement | Likely Collateral Impact |
|-----------------|-------------------------|
| Add new status to entity | All queries filtering by status, all UI showing status, reports grouping by status |
| Add required field | All creation forms, all import processes, existing records need default value |
| Change business rule | All places enforcing the rule, all tests validating old rule |
| Add new entity type | Lists that should include it, searches, permissions, reports |
| Modify permissions | All features checking that permission, user roles |
| Add soft delete | All queries need to filter deleted, restore functionality needed |
| Change data format | All consumers of that data, exports, integrations |

#### 7.5 Document Impact

AI must produce an impact report:

```
## Collateral Impact Analysis

### Affected Components
| Component | Type | Impact | Action Required |
|-----------|------|--------|-----------------|
| [name] | Breaking/Behavioral/Data/UI/API | [description] | [what to do] |

### Migration Requirements
- [ ] Data migration: [description]
- [ ] Configuration changes: [description]
- [ ] User communication: [description]

### Risk Assessment
| Risk | Probability | Severity | Mitigation |
|------|-------------|----------|------------|
| [risk] | High/Medium/Low | High/Medium/Low | [action] |
```

### Step 8: Requirement Slicing Analysis

**Large requirements should be divided into smaller, deliverable pieces.** AI must analyze if the requirement is properly sized and sliced.

#### 8.1 Size Assessment

| Indicator | Too Large | Right Size |
|-----------|-----------|------------|
| Entities involved | 4+ entities | 1-3 entities |
| Use cases | 10+ use cases | 3-7 use cases |
| States/transitions | Complex state machine | Simple lifecycle |
| Integrations | Multiple external systems | 0-2 integrations |
| Estimated effort | Weeks/months | Days/1-2 weeks |

**If requirement is too large → MUST have slicing defined**

#### 8.2 Slicing Validation

When a requirement comes with slicing (phases, iterations, out of scope items), verify:

| Check | Question |
|-------|----------|
| **Independence** | Can each slice be delivered and provide value independently? |
| **Completeness** | Does slice 1 work without slice 2? Or is it incomplete? |
| **Dependencies** | Are dependencies between slices clearly documented? |
| **Logical grouping** | Are related use cases kept together? |
| **MVP first** | Does the first slice deliver the minimum viable functionality? |

#### 8.3 Out of Scope Analysis

**CRITICAL:** Items marked "out of scope" may still affect the current requirement.

For EACH out-of-scope item, AI must ask:

| Question | Why it matters |
|----------|----------------|
| Does current scope depend on this? | If yes, current scope may be incomplete |
| Does this depend on current scope? | If yes, current design must accommodate future needs |
| Will adding this later require changes to current scope? | If yes, design for extensibility now |
| Is this truly independent or just deferred? | Deferred dependencies create tech debt |

#### 8.4 Slicing Red Flags

| Red Flag | Problem | Action |
|----------|---------|--------|
| "Phase 2: Edit functionality" | Can't deliver Create without Edit | Combine or clarify why |
| "Out of scope: Error handling" | Core functionality incomplete | Must include basic error handling |
| "Future: Delete feature" | CRUD is incomplete | Clarify if soft delete is needed now |
| "Later: Status management" | Entity lifecycle undefined | Define statuses now, implement later |
| "Not included: Validation" | Data integrity at risk | Must include essential validation |
| Large "out of scope" list | Requirement may be too narrow | Review if scope is viable |

#### 8.5 Information Dependencies

When something is out of scope, identify if we need information about it NOW:

```
## Information Needed from Out-of-Scope Items

| Out of Scope Item | Info Needed Now | Why |
|-------------------|-----------------|-----|
| Edit reservation | Field list | To design DB schema correctly |
| Cancellation policy | Basic rules | To define valid status transitions |
| Payment integration | Payment states | To reserve status values |
| Reporting | Key metrics | To capture necessary data |
```

#### 8.6 Slicing Recommendations

AI should recommend slicing when:
- Requirement has 10+ use cases
- Multiple independent user journeys exist
- Clear phases can be identified (MVP → Enhanced → Full)
- Different user roles have different needs

Suggested slicing strategies:
1. **By user role** - Admin features vs User features
2. **By CRUD** - Read-only first, then Create, then Update/Delete
3. **By workflow stage** - Creation → Processing → Completion
4. **By priority** - Must Have → Should Have → Could Have
5. **By risk** - Core functionality → Edge cases → Nice-to-have

### Step 9: Generate Analysis Report

AI must produce a report with:

1. **Missing Use Cases** - Potentially missing use cases with priority and question for stakeholder
2. **Missing State Information** - States or transitions not defined
3. **Collateral Impact** - How this affects existing functionality
4. **Slicing Assessment** - Is the requirement properly sized? Is slicing needed or valid?
5. **Out of Scope Dependencies** - Information needed from out-of-scope items
6. **Open Questions** - Questions that need stakeholder clarification
7. **Recommendations** - Suggestions to improve the requirement

---

## Common Status Patterns Reference

**Pattern 1: Simple Active/Inactive**
```
[Created] → Active ↔ Inactive → [Deleted]
```

**Pattern 2: Approval Workflow**
```
[Created] → Draft → Pending Review → Approved → Active → [Deleted]
                         ↓
                      Rejected → Draft (revision)
```

**Pattern 3: Order/Transaction Lifecycle**
```
[Created] → Pending → Confirmed → In Progress → Completed
                ↓           ↓            ↓
            Cancelled   Cancelled    Cancelled
```

**Pattern 4: Soft Delete with Archive**
```
Active ↔ Inactive → Archived → [Soft Deleted] → [Hard Deleted after X days]
```

---

## Delete Strategy Reference

| Delete Type | When to Use | Considerations |
|-------------|-------------|----------------|
| **Hard Delete** | No audit trail needed, no references | Data is gone forever |
| **Soft Delete** | Need audit, may need restore | Must filter in queries |
| **Soft Delete + Purge** | Audit + GDPR compliance | Scheduled cleanup job |
| **Archive** | Historical data, rarely accessed | Move to archive table |

---

## Analysis Checklist

### Business Alignment (AI MUST verify - MANDATORY)
- [ ] Primary company objective identified (Revenue / Churn / Sales)
- [ ] Contribution to objective is clearly explained
- [ ] KPIs defined with baseline and target values
- [ ] If no KPIs: objective justification with real data provided
- [ ] Justification is NOT subjective ("CS says it's requested")
- [ ] Justification includes specific numbers (customers, revenue, tickets)
- [ ] Evidence source is documented (customer names, ticket IDs, dates)
- [ ] Willingness to pay assessed (for demand-based features)
- [ ] Revenue impact quantified (potential gain or loss)

### Content Completeness
- [ ] Summary explains What, Why, Who
- [ ] Business context/problem is clear
- [ ] Functional requirements have acceptance criteria
- [ ] Data requirements (input/output) are specified
- [ ] Out of scope is defined

### Use Case Coverage
- [ ] CRUD operations checked for each entity
- [ ] Lifecycle transitions identified (cancel, confirm, complete)
- [ ] Inverse operations considered (add→remove, enable→disable)
- [ ] Error recovery paths defined (what if user makes mistake?)
- [ ] Undo/cancel flows addressed (what if user changes mind?)
- [ ] Missing use cases report generated

### Entity Status & Transitions
- [ ] All entities have their possible statuses defined
- [ ] Initial status for each entity is specified
- [ ] All valid transitions are documented
- [ ] Transition triggers are defined (user action, system event, time-based)
- [ ] Transition conditions/rules are specified
- [ ] Side effects are documented (notifications, cascades)
- [ ] Delete strategy defined (hard/soft/archive)
- [ ] Restore capability defined if soft delete

### Collateral Impact (AI MUST verify)
- [ ] Affected existing entities identified
- [ ] Shared data dependencies mapped
- [ ] Impact on existing business rules analyzed
- [ ] Impact on existing workflows documented
- [ ] External integrations checked
- [ ] Reports/dashboards affected identified
- [ ] Permission changes needed documented
- [ ] Breaking changes flagged and escalated
- [ ] Data migration requirements identified
- [ ] Backwards compatibility assessed

### Requirement Slicing (AI MUST verify)
- [ ] Requirement size assessed (entities, use cases, complexity)
- [ ] If too large: slicing is defined
- [ ] Each slice can deliver value independently
- [ ] Dependencies between slices are documented
- [ ] MVP is clearly identified in first slice
- [ ] Out of scope items analyzed for dependencies
- [ ] Information needed from out-of-scope items identified
- [ ] No critical functionality deferred (error handling, validation)
- [ ] Slicing strategy is logical (by role, CRUD, workflow, priority)
- [ ] Red flags addressed (incomplete CRUD, missing states)

### Time Constraints & Deadlines (AI MUST verify)
- [ ] Deadline is specified (if exists)
- [ ] Deadline reason is clear (business event, season, contract)
- [ ] Consequences of missing deadline documented
- [ ] Business calendar conflicts checked (holidays, peak seasons)
- [ ] Buffer time for testing/training considered
- [ ] Fallback plan if deadline is missed
- [ ] Deadline is realistic given scope
- [ ] If deadline is tight: scope reduction options identified

### Testing Requirements (AI MUST verify)
- [ ] Test types specified (unit, integration, E2E, UAT)
- [ ] Critical test scenarios identified
- [ ] Test data requirements defined
- [ ] Regression scope identified
- [ ] UAT process and participants defined
- [ ] Performance testing needs assessed
- [ ] Security testing needs assessed

### Definition of Done (AI MUST verify)
- [ ] DoD criteria are defined
- [ ] Acceptance criteria are testable
- [ ] Quality gates specified (code review, tests, PHPStan)
- [ ] Documentation requirements listed
- [ ] Deployment/release criteria defined
- [ ] Business sign-off process defined
- [ ] Training requirements identified (if applicable)

### Clarity
- [ ] No ambiguous language (avoid: "should", "might", "could")
- [ ] Each requirement is testable
- [ ] Technical jargon is explained
- [ ] Examples provided where helpful

### Consistency
- [ ] No contradicting requirements
- [ ] Terminology is consistent
- [ ] Priority levels match business value

---

## Anti-Patterns to Flag

| Anti-Pattern | Example | Better Alternative |
|--------------|---------|-------------------|
| Vague language | "The system should be fast" | "Response time < 200ms for 95th percentile" |
| Solution as requirement | "Use Redis for caching" | "Frequently accessed data must load in < 50ms" |
| Missing acceptance criteria | "User can filter results" | "Given filters X,Y,Z, when applied, then only matching records shown" |
| Unbounded scope | "Support all file types" | "Support PDF, DOCX, and XLSX files" |
| Missing context | "Add export button" | "Users need to export client lists for offline analysis" |
| Missing states | "User can create orders" | "Orders have states: draft, pending, confirmed, shipped, delivered, cancelled" |
| Incomplete lifecycle | "Add reservation" | "Add, view, edit, cancel, confirm reservation" |
| Ignoring collateral impact | "Add discount field to orders" | "Add discount field. Impact: affects total calculation, reports, invoices, tax calculation" |
| Isolated feature thinking | "Add VIP status to clients" | "Add VIP status. Impact: affects pricing, priority, reports, notifications, permissions" |
| Incomplete CRUD slicing | "Phase 1: Create orders. Phase 2: Edit orders" | Include at least Create + View, or clarify why Edit is separate |
| Deferred essentials | "Out of scope: validation, error handling" | Core functionality must include basic validation and errors |
| Blind out-of-scope | "Out of scope: reporting" without analysis | "Out of scope: reporting. Info needed: key metrics to capture data now" |
| Monolithic requirement | 15 use cases, 5 entities, no slicing | Split into logical phases with clear MVP |
| Missing deadline | No date mentioned for time-sensitive feature | "Must be live by Dec 1st for Christmas season" |
| Unrealistic deadline | "Need this tomorrow" for complex feature | Negotiate scope reduction or phased delivery |
| Missing DoD | "Just build it and we'll see" | Define clear acceptance criteria and sign-off process |
| No testing plan | "We'll test it later" | Define test types, scenarios, and responsibilities upfront |
| Calendar blindness | Launch during peak season without buffer | Avoid launches during critical business periods, or add buffer |
| Subjective justification | "CS says it's highly requested" | "12 customers requested this. 5 willing to pay €50/month" |
| Vague demand | "Many users want this" | "47 users requested via support (tickets #X, #Y, #Z)" |
| No revenue impact | "This would be nice to have" | "Potential revenue: €10K/year. Churn risk: 3 customers (€5K MRR)" |
| Missing evidence | "Sales team thinks this helps" | "Lost 3 deals citing this gap. Total value: €25K" |
| No business alignment | Feature without link to objectives | "Contributes to Reduce Churn: prevents X cancellations/month" |

---

## Example Analysis Output

**Requirement received:** "Create a screen to add reservations"

### Identified Entities
- Reservation (main)

### Missing Use Cases Analysis

| Missing Use Case | Reason | Priority | Question for Stakeholder |
|------------------|--------|----------|--------------------------|
| View reservation | Users need to see details after creation | Must Have | Confirmed? |
| Edit reservation | Users may need to change date/time/guests | Must Have | Can users modify after creation? |
| Cancel reservation | Users change plans | Must Have | What is the cancellation policy? |
| List reservations | Staff needs overview | Must Have | Who can see the list? |
| Search reservations | Finding specific reservations | Should Have | What search criteria? |
| Confirm reservation | Business may need to confirm | Should Have | Is manual confirmation required? |

### Missing State Information

| Entity | Missing Info | Question |
|--------|--------------|----------|
| Reservation | Initial status not specified | Is it "pending" or "confirmed" on creation? |
| Reservation | Possible statuses not listed | What statuses can a reservation have? |
| Reservation | Delete strategy not defined | Hard delete or soft delete? |

### Collateral Impact Analysis

| Component | Type | Impact | Action Required |
|-----------|------|--------|-----------------|
| Restaurant availability | Behavioral | Reservations block time slots | Update availability calculation |
| Client history | Data | Client should see their reservations | Add to client profile view |
| Dashboard | UI | Staff needs to see today's reservations | Add widget or section |
| Notification system | New | Confirmation emails needed | Integrate with email service |
| Reports | New | Reservation statistics may be needed | Confirm reporting requirements |

**Migration Requirements:**
- None (new feature)

**Risk Assessment:**

| Risk | Probability | Severity | Mitigation |
|------|-------------|----------|------------|
| Double booking | High | High | Implement slot locking |
| No-shows affect revenue | Medium | Medium | Add confirmation/reminder flow |

### Slicing Assessment

**Size:** Medium (1 entity, 6+ use cases, simple lifecycle) - Acceptable but could benefit from slicing

**Out of Scope Analysis:**

| Out of Scope Item | Depends on Current? | Current Depends on It? | Info Needed Now |
|-------------------|---------------------|------------------------|-----------------|
| Payment/deposits | No | No | Payment statuses (to reserve in enum) |
| Waitlist | No | No | None |
| Table assignment | No | Yes (capacity) | Max capacity per slot |
| Notifications | No | Yes (triggers) | When to send (define now) |

**Red Flags:**
- "Edit reservation" not mentioned → Must be in scope or clarify why not
- Cancellation policy not defined → Need at least basic rules

**Suggested Slicing (if needed):**
1. **Slice 1 (MVP):** Create + View + List reservations
2. **Slice 2:** Edit + Cancel reservations
3. **Slice 3:** Confirmations + Reminders
4. **Slice 4:** Reports + Analytics

### Recommendations
1. Define all reservation statuses and transitions
2. Specify what happens on each status change (notifications?)
3. Clarify cancellation policy and time limits
4. Define who can perform each action (roles/permissions)
5. **Confirm integration with restaurant availability system**
6. **Define what client profile should show about reservations**
7. **Clarify: Is Edit in scope? If not, why?**
8. **Define max capacity per time slot (needed even if table assignment is out of scope)**
