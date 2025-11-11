# Automated Tests

## Overview

This directory contains the plugin's automated test suite. The goal is to ensure the quality, stability, and correctness of the business logic and key integrations.

We prioritize high test coverage for the **Domain** and **Application** layers, using more targeted integration tests to validate the wiring with WordPress and external services.

## Directory Structure

```
/tests/
├── Unit/          # Unit tests for isolated classes (Domain, Application).
│   ├── Domain/
│   └── Application/
├── Integration/   # Tests that interact with WordPress, the DB, or external services.
│   └── Infrastructure/
└── bootstrap.php  # Bootstrap file for the test environment.
```

## Commands

Run the following Composer scripts from the plugin root to execute tests and code quality tools:

```bash
# Run the full test suite (unit and integration)
composer test

# Check code style against PSR-12 rules
composer cs-check

# Automatically fix code style issues
composer cs-fix
```

## How to Write Tests

Follow these guidelines based on the layer you are testing:

- **Domain Layer**:

  - **Type**: Pure unit tests.
  - **Approach**: Instantiate entities and value objects directly. Assert that business invariants are enforced and methods behave as expected. Avoid mocks unless absolutely necessary.
  - **Example**:
    ```php
    public function testNewsletterRequiresHtmlAndItems(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Newsletter(1, '', []); // Empty HTML should throw
    }
    ```

- **Application Layer**:

  - **Type**: Unit tests with test doubles (mocks/stubs).
  - **Approach**: Use doubles for repositories and external services. Assert that the use case correctly orchestrates interactions and handles success/error paths.

- **Infrastructure Layer**:

  - **Type**: Integration tests.
  - **Approach**: Use the WordPress test environment. Assert that data is correctly mapped between the domain and the database, and that interactions with external APIs work as expected.

- **Presentation Layer**:
  - **Type**: Integration tests (request simulation).
  - **Approach**: Simulate AJAX requests or WordPress hooks. Assert that security checks (nonces, capabilities) are enforced and that responses are shaped correctly.

## Coverage Strategy

- **High Priority**: Critical business rules and core use cases.
- **Medium Priority**: Repositories, mail services, and data mappers.
- **Low Priority**: Simple controllers and presentation hooks.

---

<p align="center">
  &copy; 2025 RIILSA &bull; Developed by <a href="https://github.com/OnlyAlec" target="_blank">Alexis Chacón Trujillo</a>
</p>
