# Application Layer

## Overview

The **Application** layer is the heart of the application's logic. Its function is to orchestrate the workflows (use cases) that respond to user actions. It acts as an intermediary between the `Presentation` layer (which receives the request) and the `Domain` layer (which contains the pure business logic).

This layer **contains no business logic** itself; instead, it coordinates domain objects (entities, domain services) and repositories to accomplish a specific task.

**Key Rule**: It depends only on the `Domain` layer. It must have no knowledge of WordPress or any other external implementation.

## Directory Structure

```
/Application/
├── DTOs/
│   ├── ExcelProcessingResultDTO.php
│   ├── NewsletterGenerationDTO.php
│   └── SubscriptionDTO.php
├── Services/
│   ├── ExcelParsingService.php
│   └── TemplateGenerationService.php
└── UseCases/
    ├── ContentManager/
    │   ├── ProcessExcelFileUseCase.php
    │   └── ...
    └── Newsletter/
        ├── GenerateNewsletterUseCase.php
        ├── SendNewsletterUseCase.php
        └── ...
```

### Component Breakdown

- **`UseCases/`**: Each class in this directory represents a single, specific business **use case** (e.g., `GenerateNewsletterUseCase`). They are stateless classes that receive their dependencies (repositories, services) via the constructor. Their main method (usually `execute()`) receives a request DTO and returns a response DTO.

- **`DTOs/` (Data Transfer Objects)**: These are simple, immutable objects used to transfer data between layers. They define the input and output structure for use cases. They contain no logic, only public, read-only properties.

- **`Services/`**: Contains services that are specific to the application but are not pure business rules. For example, `ExcelParsingService` is responsible for reading an Excel file—an orchestration task that belongs neither to the domain nor to the infrastructure.

## How to Extend This Layer

To add new functionality, follow these steps:

1.  **Define the Use Case**:

    - Think about the user action (verb + noun), e.g., `CancelSubscription`.
    - Create the `CancelSubscriptionUseCase.php` class in the appropriate directory (`UseCases/Newsletter/`).

2.  **Create the DTOs**:

    - Define a `CancelSubscriptionRequest.php` with the necessary data (e.g., `email`).
    - Define a `CancelSubscriptionResponse.php` with the result (e.g., `success`).

3.  **Implement the Use Case**:

    - Inject the interfaces you need (e.g., `SubscriberRepositoryInterface`) into the constructor.
    - In the `execute()` method, perform the orchestration:
      1.  Find the subscriber using the repository.
      2.  Call the relevant domain method (e.g., `$subscriber->cancel()`).
      3.  Save the change using the repository.
    - Return the response DTO.

4.  **Handle Errors**:
    - Throw domain or application exceptions if something goes wrong (e.g., `SubscriberNotFoundException`). The `Presentation` layer will be responsible for translating these exceptions into an appropriate HTTP response.

## Testing

- Use cases should have **unit tests**.
- Use test doubles (mocks/stubs) for dependencies (repositories, services).
- Validate all possible flows: the happy path (success), validation errors, and dependency failures.

---

<p align="center">
  &copy; 2025 &bull; Developed by <a href="https://github.com/OnlyAlec" target="_blank">Alexis Chacon Trujillo</a>
</p>
