# CRM Service AI Documentation

Complete development documentation for AI assistants working on this CRM service project.

## 📋 Quick Start

**ALWAYS read these first:**
1. [Critical Rules](critical-rules.md) - **MUST READ** before any task
2. [Architecture](architecture.md) - Understand DDD and Hexagonal Architecture

## 📚 Documentation Index

### Core Architecture
- **[Critical Rules](critical-rules.md)** ⚠️ - Database Performance, CQRS, Laravel Resources (READ FIRST)
- **[Architecture](architecture.md)** - DDD, Hexagonal Architecture, Bounded Contexts
- **[Code Quality](code-quality.md)** - SOLID Principles, Naming Conventions, Best Practices

### Application Layer (CQRS)
- **[Application Layer](application-layer.md)** - Queries, Commands, Events, Process Managers

### Infrastructure
- **[Infrastructure](infrastructure.md)** - Repositories, Tables, Entities, Hydrators

### Documentation Workflow
- **[Working Documentation](working_documentation.md)** - Documentation types and workflow
- **[Requirement Analysis Guide](requirement_analysis_guide.md)** - AI instructions for analyzing requirements
- **[Requirement Template](/docs/working_docs/requirement_template.md)** - What content a requirement should have

### Claude Commands
- **`/requirement-validate`** - Validate a requirement document (content, use cases, states, slicing)
- **`/requirement-design-solution`** - Design technical solution for a validated requirement

## 🎯 Quick Reference by Task

### Adding New Feature
1. Read: [Critical Rules](critical-rules.md) → Database Performance + CQRS
2. Read: [Architecture](architecture.md) → DDD section
3. Design: Use [Aggregate Design Canvas](https://github.com/ddd-crew/aggregate-design-canvas)

### Creating New Query
1. Read: [Application Layer](application-layer.md) → Queries section
2. Read: [Critical Rules](critical-rules.md) → Performance rules

### Creating New Command
1. Read: [Application Layer](application-layer.md) → Commands section
2. Read: [Critical Rules](critical-rules.md) → CQRS rules

### Creating Repository or Entity
1. Read: [Infrastructure](infrastructure.md) → Complete guide
2. Read: [Code Quality](code-quality.md) → Domain Layer

### Working with Events
1. Read: [Application Layer](application-layer.md) → Events section

## 🔍 Search Tips

- **Database Performance**: See [Critical Rules](critical-rules.md)
- **Query Naming**: See [Application Layer](application-layer.md)
- **Repository Patterns**: See [Infrastructure](infrastructure.md)
- **SOLID Principles**: See [Code Quality](code-quality.md)
- **Laravel Resources**: See [Critical Rules](critical-rules.md)

## 📝 File Structure

```
ai_docs/
├── README.md                       # This file - Documentation index
├── working_documentation.md        # Documentation workflow (requirements, analysis, tasks)
├── requirement_analysis_guide.md   # AI instructions for analyzing requirements
├── critical-rules.md               # ⚠️ MUST READ - DB, CQRS, Laravel
├── architecture.md                 # DDD, Hexagonal Arch, Bounded Contexts
├── application-layer.md            # Queries, Commands, Events, Process Managers
├── infrastructure.md               # Repositories, Tables, Entities, Hydrators
└── code-quality.md                 # SOLID, Naming, Best Practices, Enums

docs/working_docs/
└── requirement_template.md         # What content a requirement should have

.claude/commands/
├── requirement-validate.md         # /requirement-validate command
└── requirement-design-solution.md  # /requirement-design-solution command
```

## 🚀 Development Environment

- **PHP 8.4**
- **Laravel 12** - Modern framework
- **Docker** - All code runs in Docker (never execute on host)
- **MySQL** - Large database (always consider performance)
- **DDD + Hexagonal Architecture** - Framework-agnostic domain in /src

## 🏗️ Project Structure

```
/Users/juanmacias/Projects/crm-service/
├── app/                         # Laravel HTTP layer
│   └── Http/
│       ├── Controllers/        # API controllers
│       └── Resources/          # Laravel API Resources
├── src/                        # DDD domain layer
│   ├── Core/                   # Core bounded context
│   │   ├── Group/
│   │   ├── GroupClient/
│   │   ├── Restaurant/
│   │   └── RestaurantClient/
│   ├── Cover/                  # Cover system integration
│   │   ├── Client/
│   │   ├── Group/
│   │   └── Restaurant/
│   └── Shared/                 # Shared framework code
│       └── Framework/
├── database/                   # Laravel migrations
└── tests/                      # Tests
```

## 🔗 External Resources

- [Aggregate Design Canvas](https://github.com/ddd-crew/aggregate-design-canvas)

---

**Note:** This documentation is optimized for AI consumption and reflects the current state of this CRM service project.
