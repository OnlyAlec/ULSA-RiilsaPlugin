# Plugin Architecture

## Overview

The `includes/` directory contains all of the plugin's source code, organized according to an adaptation of **Clean Architecture**. The primary goal of this structure is the **separation of concerns**, isolating business logic from implementation details like WordPress, the database, or external APIs.

This approach leads to more maintainable, testable, and flexible code over the long term.

## Layer Structure

```
/includes/
├── Domain/         # Pure, framework-agnostic business rules and entities.
├── Application/    # Orchestrates the application's use cases.
├── Infrastructure/ # Concrete implementations (WordPress, Brevo, Database).
├── Presentation/   # Handles user interaction (Hooks, AJAX, Controllers).
└── Core/           # Orchestrates bootstrapping, dependency container, and utilities.
```

## Dependency Flow

The fundamental rule of this architecture is that **dependencies always point inwards**. Inner layers must know nothing about the outer layers.

```
+-------------------------------------------------------------+
| Presentation (WordPress UI)                                 |
|      ↓ Depends on Application                               |
+-------------------------------------------------------------+
| Application (Use Cases)                                     |
|      ↓ Depends on Domain Interfaces                         |
+-------------------------------------------------------------+
| Domain (Business Logic) - NO DEPENDENCIES                   |
+-------------------------------------------------------------+

// Infrastructure implements interfaces defined in Application/Domain
// and is "injected" where needed.
```

- `Presentation` knows about `Application`.
- `Application` knows about `Domain`.
- `Domain` knows about nothing else. It is the pure core of the logic.
- `Infrastructure` implements interfaces that `Application` and `Domain` define, but it does not depend directly on them.

## Coding Conventions

- **Naming**:
  - Classes: `PascalCase`
  - Methods & Properties: `camelCase`
  - Interfaces: `NameInterface` (e.g., `NewsletterRepositoryInterface`)
  - Constants: `UPPER_SNAKE_CASE`
- **Typing**: PHP 8.3 with `strict_types=1` is enforced. The `mixed` type should be avoided.
- **Dependency Injection**: The `PHP-DI` container (configured in `Core/`) is used to wire abstractions (interfaces) to their concrete implementations.
- **Error Handling**:
  - Domain-specific exceptions are thrown from the `Domain` layer.
  - They are caught and translated in the `Application` or `Presentation` layers to provide an appropriate user response.

## Layer Index

For a detailed description of each layer, its purpose, and its contents, refer to the following files:

- **[./Domain/README.md](./Domain/README.md)**: Entities and Business Rules.
- **[./Application/README.md](./Application/README.md)**: Use Cases.
- **[./Infrastructure/README.md](./Infrastructure/README.md)**: Implementations.
- **[./Presentation/README.md](./Presentation/README.md)**: User Interface.
- **[./Core/README.md](./Core/README.md)**: Bootstrap and Core.

---

<p align="center">
  &copy; 2025 &bull; Developed by <a href="https://github.com/OnlyAlec" target="_blank">Alexis Chacon Trujillo</a>
</p>
