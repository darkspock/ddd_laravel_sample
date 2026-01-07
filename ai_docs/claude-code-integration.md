# Claude Code Integration

This project uses **Claude Code** with custom commands (agents) and skills for automated architecture auditing and code generation.

## For Other AI Assistants

If you're an AI assistant other than Claude Code, the core architectural rules and patterns are documented in this `ai_docs/` directory. You should read all files here to understand the project's architecture.

## For Claude Code Users

Claude Code has additional capabilities beyond what's documented here:

### Custom Commands (Agents)
Located in `.claude/commands/`:
- `/audit-architecture` - Audits DDD/Hexagonal architecture compliance
- `/analyze-performance` - Analyzes performance issues and N+1 queries
- `/validate-http-layer` - Validates HTTP layer (Actions and Requests)

### Skills
Located in `.claude/skills/`:
- `cqrs-generator` - Generates Queries and Commands following CQRS patterns
- `ddd-entity` - Generates complete Entities with DDD structure

### Documentation Conventions
Claude Code follows specific conventions for document generation:
- Analysis documents: `[original]_analysis.md`
- Task lists: `[feature]_tasks.md`
- Requirements analysis format (fields, enums, no implementation code)

**Full documentation**: See [.claude/README.md](../.claude/README.md)

## Documentation Structure

### Core Rules (Read First)
1. **[critical-rules.md](critical-rules.md)** - TOP 3 errors to avoid
2. **[architecture.md](architecture.md)** - DDD and Hexagonal Architecture
3. **[application-layer.md](application-layer.md)** - CQRS patterns

### HTTP Layer
4. **[http-layer-actions.md](http-layer-actions.md)** - Actions pattern
5. **[http-requests-pattern.md](http-requests-pattern.md)** - Requests pattern (no Laravel validation)
6. **[http-layer-patterns.md](http-layer-patterns.md)** - Complete HTTP patterns

### Implementation
7. **[development-workflow.md](development-workflow.md)** - Development workflow and phases
8. **[infrastructure.md](infrastructure.md)** - Repositories, Entities, Migrations
9. **[code-quality.md](code-quality.md)** - SOLID principles and best practices

## Key Differences from Standard Laravel

This project follows **strict DDD/Hexagonal Architecture** with these key differences:

1. **No Laravel Validation in Requests** - Requests only map to DTOs
2. **Actions Return Resources, Not JsonResponse** - Controller handles HTTP conversion
3. **No DB:: in Actions or Handlers** - Always use Repositories
4. **All IDs are Value Objects (ULID)** - Never use strings for IDs
5. **Domain Layer in `/src`** - Framework-agnostic business logic
6. **Commands Always Return Void** - Queries return DTOs
7. **Events Use `::fromEntity()`** - Not `new Event()`
8. **Timestamps are `int` (Unix)** - Not `DateTimeImmutable`
9. **Cross-cutting concerns in `src/Shared/`** - Not domain-specific BCs

## Quick Reference

```
src/                           # Domain layer (framework-agnostic)
├── Core/                     # Core business domains
├── [ExternalSystem]/         # External system integrations
└── Shared/                   # Cross-cutting utilities (Saga, Audit, etc.)

Apps/Api/                      # HTTP layer
├── [Module]/
│   ├── [Action]/
│   │   ├── XxxAction.php     # Thin orchestration, returns Resource
│   │   ├── XxxRequest.php    # Maps to DTO (no validation)
│   │   └── XxxDto.php        # Transfer object
│   ├── Shared/
│   │   ├── XxxRes.php        # API Resource (JsonSerializable)
│   │   └── Services/
│   │       └── XxxResService.php  # Converts DTOs → Resources
│   └── XxxController.php     # Converts Resources → JsonResponse

app/                           # Laravel framework layer
└── Console/Commands/          # CLI commands
```

## Integration Notes

### Reading This Documentation
1. Start with `critical-rules.md` (most common errors)
2. Read `architecture.md` (DDD structure)
3. Read `application-layer.md` (CQRS patterns)
4. Refer to HTTP layer docs when working with endpoints
5. Use `development-workflow.md` for step-by-step implementation

### Working with Claude Code
- Claude Code can automatically audit architecture
- Use `/audit-architecture` before PRs
- Skills activate automatically when creating Queries/Commands/Entities
- Reports are saved in `docs/Reports/YYYY-MM-DD-[agent].md`

### Working with Other AIs
- All patterns are documented in this directory
- Follow the same rules as Claude Code
- No special integration needed
- Refer to examples in existing code

---

**Last Updated**: 2025-11-14
