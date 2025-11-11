# Domain Layer

## Overview

The **Domain** layer is the core of the plugin. It contains all the application's **business logic and rules**. It is written in pure PHP and is completely agnostic to any framework or external implementation details like WordPress or databases.

The goal of this layer is to model the business through rich objects that encapsulate both state and behavior, ensuring that the data is always valid (invariants).

**Key Rule**: This layer **depends on no other layer**. It is the center of the application's universe.

## Directory Structure

```
/Domain/
├── Entities/
│   ├── Newsletter.php
│   ├── Project.php
│   └── ...
├── ValueObjects/
│   ├── Email.php
│   ├── DateRange.php
│   └── ...
├── Repositories/
│   ├── NewsletterRepositoryInterface.php
│   ├── ProjectRepositoryInterface.php
│   └── ...
└── Services/
    ├── ExcelValidationService.php
    └── NewsletterContentService.php
```

### Component Breakdown

- **`Entities/`**: These are the core objects of the domain that have an identity and a lifecycle. They represent business concepts like a `Newsletter` or a `Project`. They are responsible for maintaining their own invariants (consistency rules) through their methods.

- **`ValueObjects/`**: These are immutable objects defined by their attributes, not by an identity. Examples include `Email` or `DateRange`. They are validated upon creation and guarantee that they always represent a correct value within the domain.

- **`Repositories/` (Repository Interfaces)**: These define the **contracts** for the persistence of entities. They specify what methods must exist to save, find, or delete domain entities (e.g., `findByNumber()`, `save()`). The concrete implementations of these interfaces are located in the `Infrastructure` layer.

- **`Services/` (Domain Services)**: These contain business logic that does not naturally fit within a single entity. They are stateless operations that coordinate multiple domain objects. For example, a `NewsletterContentService` might be responsible for applying complex rules to build the content of a newsletter.

## How to Extend This Layer

- **To add a new Entity**:

  1.  Create the class in `Entities/`.
  2.  Define its properties with strict types.
  3.  Ensure data validity in the constructor by throwing exceptions if rules are not met (guard clauses).
  4.  Expose methods that reveal business intent (e.g., `publish()`, `archive()`) instead of generic setters (`set_status()`).

- **To add a new Value Object**:

  1.  Create a `final` class in `ValueObjects/`.
  2.  Validate the value in the constructor.
  3.  Make its properties `readonly` to ensure immutability.

- **To define a new Repository**:
  1.  Create an `interface` in `Repositories/`.
  2.  Define the methods that the use cases will need, always using domain objects in the signatures (e.g., `save(Newsletter $newsletter)`).

## Testing

- Entities and Value Objects must have exhaustive and isolated **unit tests**.
- Do not use mocks to test domain objects; they should be instantiated and tested directly.
- Use PHPUnit's `DataProvider` to test edge cases and validations (e.g., invalid emails, incorrect date ranges).

---

<p align="center">
  &copy; 2025 &bull; Developed by <a href="https://github.com/OnlyAlec" target="_blank">Alexis Chacon Trujillo</a>
</p>
