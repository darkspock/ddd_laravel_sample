# Working Documentation

## Philosophy: Analysis Informs, Never Blocks

**THE USER ALWAYS DECIDES.**

| Principle | Meaning |
|-----------|---------|
| Analysis is informative | Shows risks, gaps, impacts - does NOT block |
| User decides | If they say "proceed", we proceed |
| Not bureaucracy | Better definition helps, but never creates gates |
| Historical context | Company has history of incomplete features, patches on patches, unused features |
| Purpose | Help avoid repeating patterns, but as a tool, not a barrier |

**In practice:** Flag concerns → Ask user → Execute their decision

---

## Requirement Types & Folder Structure

```
docs/working_docs/
├── epics/           # Large initiatives with full business justification
├── features/        # Features linked to epics (can reference parent epic)
├── hotfixes/        # Urgent fixes for production issues
└── cases/           # Incident analysis (investigation, NOT implementation)
```

Each type has different requirements and workflows:

| Type | Purpose | Business Justification | Full Analysis | Implementation |
|------|---------|----------------------|---------------|----------------|
| **Epic** | Large initiative | MANDATORY (full) | MANDATORY | Yes |
| **Feature** | Part of an epic | Reference to epic | Simplified | Yes |
| **Hotfix** | Fix production issue | Problem-focused | Minimal | Yes |
| **Case** | Analyze incident | N/A | Investigation only | NO |

---

## 1. EPICS (`docs/working_docs/epics/`)

### Purpose
Large business initiatives that justify investment. Epics contain the full business case.

### Required Content (ALL MANDATORY)
- Business Alignment (objectives, KPIs, evidence)
- Full context and problem statement
- Complete use case analysis
- Entity states and transitions
- Slicing strategy
- Time constraints
- Testing requirements
- Definition of Done

### Workflow
```
Epic Requirement → Full Validation → Design → Tasks → Implementation
```

### Folder Structure
```
docs/working_docs/epics/reservations/
├── reservations_requirements.md    # Full business case
├── reservations_validation.md      # Full validation
├── reservations_design.md          # Architecture design
└── reservations_tasks.md           # Master task list
```

### Example
```markdown
# Epic: Restaurant Reservations System

**Epic ID:** EPIC-001
**Objective:** Grow Revenue
**KPIs:**
- Increase table occupancy from 60% to 80%
- Reduce no-shows from 15% to 5%

[Full requirements with all sections...]
```

---

## 2. FEATURES (`docs/working_docs/features/`)

### Purpose
Individual features that are part of an epic. Can reference the parent epic instead of duplicating information.

### Required Content
- **Reference to parent epic** (MANDATORY)
- Feature-specific requirements
- Feature-specific acceptance criteria
- Inherited from epic: business alignment, context, slicing strategy

### What Can Be Referenced (not duplicated)
| Section | In Feature | Reference Epic For |
|---------|------------|-------------------|
| Business Alignment | Reference | Full KPIs, objectives, evidence |
| Context | Brief summary | Full business context |
| Slicing | This feature's scope | Overall slicing strategy |
| States/Transitions | Feature-specific | Full entity lifecycle |
| Testing | Feature tests | Overall testing strategy |
| DoD | Feature-specific | Project-wide DoD |

### Workflow
```
Feature Requirement → Simplified Validation → Design → Tasks → Implementation
```

### Folder Structure
```
docs/working_docs/features/reservation-creation/
├── reservation-creation_requirements.md    # References EPIC-001
├── reservation-creation_validation.md
├── reservation-creation_design.md
└── reservation-creation_tasks.md
```

### Example
```markdown
# Feature: Create Reservation

**Parent Epic:** [EPIC-001: Reservations](../epics/reservations/reservations_requirements.md)
**Feature Scope:** Create reservation flow only (part of epic slice 1)

## Business Alignment
See parent epic for full business justification.
This feature contributes to: Increase table occupancy (enables online booking)

## Feature Requirements
[Feature-specific requirements only...]
```

---

## 3. HOTFIXES (`docs/working_docs/hotfixes/`)

### Purpose
Urgent fixes for production issues. Focus on solving the problem, not business justification.

### Required Content
- **Problem description** (MANDATORY)
- **Impact** (who is affected, severity)
- **Root cause** (if known)
- **Proposed solution**
- **Testing to verify fix**
- **Rollback plan**

### NOT Required
- Full business alignment (the problem IS the justification)
- Full use case analysis
- Slicing (hotfixes should be small)
- KPIs

### Workflow
```
Problem Report → Quick Analysis → Fix → Test → Deploy
```

### Folder Structure
```
docs/working_docs/hotfixes/HF-2024-001-booking-error/
├── hotfix_requirements.md     # Problem + solution
├── hotfix_validation.md       # Quick validation
└── hotfix_tasks.md            # Fix steps
```

### Example
```markdown
# Hotfix: Booking Error on Peak Hours

**Hotfix ID:** HF-2024-001
**Severity:** Critical
**Reported:** 2024-01-15
**Affected:** All users trying to book during 19:00-21:00

## Problem
Users receive 500 error when attempting to book during peak hours.

## Impact
- ~50 failed bookings/day
- Customer complaints increasing
- Revenue loss estimated: €500/day

## Root Cause
Database connection pool exhausted during high load.

## Proposed Solution
Increase connection pool size from 10 to 50.
Add connection timeout handling.

## Testing
- Load test with 100 concurrent bookings
- Monitor connection pool during peak hours

## Rollback Plan
Revert connection pool config to previous values.
```

---

## 4. CASES (`docs/working_docs/cases/`)

### Purpose
**INVESTIGATION ONLY** - Analyze incidents to understand what happened. NOT for implementing solutions.

### Workflow (DIFFERENT from other types)
```
Incident Report → Investigation → Root Cause Analysis → Recommendations
                                                              ↓
                            (If fix needed) → Create Hotfix or Feature
```

### Required Content
- **Incident description**
- **Timeline of events**
- **Investigation findings**
- **Root cause analysis**
- **Recommendations** (may lead to hotfix/feature)

### NOT Included
- Implementation details
- Task lists
- Design documents
- Solution code

### Folder Structure
```
docs/working_docs/cases/CASE-2024-001-data-loss/
├── case_report.md              # Investigation report
└── case_recommendations.md     # What to do next
```

### Example
```markdown
# Case: Customer Data Loss Incident

**Case ID:** CASE-2024-001
**Reported:** 2024-01-10
**Status:** Under Investigation

## Incident Description
Customer reported missing reservation history after account migration.

## Timeline
- 2024-01-08 10:00: Migration script executed
- 2024-01-08 10:15: Script completed with no errors
- 2024-01-09 09:00: Customer reports missing data
- 2024-01-09 14:00: Investigation started

## Investigation Findings
1. Migration script did not handle reservations with NULL dates
2. 47 reservations were skipped silently
3. No backup was created before migration

## Root Cause
Missing NULL check in migration script line 234.
No pre-migration backup procedure.

## Recommendations
1. **Hotfix:** Recover lost data from transaction logs → Create HF-2024-002
2. **Feature:** Add pre-migration backup step → Add to EPIC-003
3. **Process:** Update migration checklist
```

---

## Validation by Type

### `/requirement-validate` Behavior

The command detects the type based on folder path:

| Path Contains | Type | Validation Level |
|---------------|------|------------------|
| `/epics/` | Epic | FULL (all checks) |
| `/features/` | Feature | SIMPLIFIED (check epic reference) |
| `/hotfixes/` | Hotfix | PROBLEM-FOCUSED |
| `/cases/` | Case | INVESTIGATION (no implementation) |

### Validation Differences

| Check | Epic | Feature | Hotfix | Case |
|-------|------|---------|--------|------|
| Business Alignment | Full | Reference | N/A | N/A |
| KPIs | Required | Reference | N/A | N/A |
| Use Cases | Full | Feature-specific | N/A | N/A |
| States/Transitions | Full | Feature-specific | If relevant | N/A |
| Collateral Impact | Full | Feature-specific | Quick check | N/A |
| Slicing | Required | Reference | N/A | N/A |
| Time Constraints | Full | Feature deadline | ASAP | N/A |
| Testing | Full | Feature tests | Verify fix | N/A |
| DoD | Full | Feature-specific | Fix verified | N/A |
| Root Cause | N/A | N/A | Required | Required |
| Recommendations | N/A | N/A | Rollback | Next steps |

---

## Claude Commands

### `/requirement-validate`
Validates requirement based on type (detected from path).

### `/requirement-design-solution`
Designs implementation (NOT for Cases).

### For Cases: `/case-analyze` (if created)
Investigates incident and produces recommendations.

---

## Quick Reference: Which Type to Use?

| Situation | Use |
|-----------|-----|
| "We need a new reservation system" | **Epic** |
| "Add cancellation feature to reservations" | **Feature** (link to epic) |
| "Users can't book right now!" | **Hotfix** |
| "Why did bookings fail yesterday?" | **Case** |
| "Customers want email confirmations" | **Feature** or **Epic** (depending on size) |
| "The app is slow" | **Case** (investigate first) |
