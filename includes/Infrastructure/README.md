# Infrastructure Layer

## Overview

The **Infrastructure** layer is the bridge between the application and the outside world. It contains the **concrete implementations** of the interfaces defined in the `Domain` and `Application` layers. This is where all code that interacts directly with WordPress, databases, third-party APIs (like Brevo), the filesystem, etc., resides.

The goal of this layer is to isolate implementation details, allowing the rest of the application to be agnostic to the underlying technology. If the decision were made to switch email service providers tomorrow, only this layer would need to be modified.

**Key Rule**: It implements the interfaces from the inner layers. It may depend on `Domain` and `Application` to understand the objects it handles, but the inner layers never depend on it.

## Directory Structure

```
/Infrastructure/
├── Database/
│   └── DatabaseManager.php
├── Repositories/
│   ├── WordPressProjectRepository.php
│   └── DatabaseSubscriberRepository.php
├── Services/
│   ├── BrevoMailService.php
│   └── PhpSpreadsheetExcelService.php
└── WordPress/
    ├── PostTypeRegistrar.php
    ├── TaxonomyRegistrar.php
    └── ...
```

### Component Breakdown

- **`Repositories/`**: Contains the classes that implement the repository interfaces from the `Domain`. Their responsibility is to translate domain objects into a persistent format (WordPress posts, custom table rows) and vice versa. This is where functions like `wp_insert_post`, `get_post_meta`, or `$wpdb` are used.

- **`Services/`**: Includes implementations of services that depend on external tools or APIs. For example, `BrevoMailService` encapsulates the logic for communicating with the Brevo API, while `PhpSpreadsheetExcelService` uses an external library to read Excel files.

- **`WordPress/`**: Groups WordPress-specific integration code that is not a repository. This includes the registration of Custom Post Types (`PostTypeRegistrar`), taxonomies, shortcodes, and other low-level hooks.

- **`Database/`**: Manages database interactions that are not related to WordPress repositories, such as creating custom tables and handling migrations.

## How to Extend This Layer

- **To implement a new Repository**:

  1.  Create a class in `Repositories/` that implements the corresponding Domain interface (e.g., `MyEntityRepositoryInterface`).
  2.  Inject `$wpdb` or any other necessary dependencies into the constructor.
  3.  Implement the interface methods, performing the "translation" between domain objects and the WordPress structure. **Always use `$wpdb->prepare()`** for SQL queries.

- **To add a new External Service**:

  1.  Create a class in `Services/` that implements an interface defined in the `Application` layer (e.g., `MailServiceInterface`).
  2.  Encapsulate the provider's SDK or HTTP calls within this class.
  3.  Handle configuration (like API keys) securely.

- **Don't Forget the Container**:
  - After creating a new implementation, go to `Core/Container.php` and "wire" the interface to its new concrete class so that dependency injection works.

## Testing

- This layer is the primary candidate for **integration tests**.
- Repositories should be tested against a WordPress test database to ensure that data mapping is correct.
- External services should be tested by mocking HTTP calls so as not to depend on the actual service during tests.

---

<p align="center">
  &copy; 2025 &bull; Developed by <a href="https://github.com/OnlyAlec" target="_blank">Alexis Chacon Trujillo</a>
</p>
