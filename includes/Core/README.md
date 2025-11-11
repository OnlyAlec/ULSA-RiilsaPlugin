# Core Layer

## Overview

The **Core** layer provides the foundational scaffolding for the plugin. It handles bootstrapping, configuring the dependency injection (DI) container, defining global constants, and providing helper functions.

Its main responsibility is to "assemble" the application by wiring the abstractions defined in the `Domain` and `Application` layers to their concrete implementations in the `Infrastructure` layer.

## File Structure

```
/Core/
├── Bootstrap.php   # Plugin initialization and hooks registration.
├── Container.php   # Dependency injection container configuration (PHP-DI).
├── Constants.php   # Global configuration constants.
└── helpers.php     # Global, framework-agnostic helper functions.
```

### Component Breakdown

- **`Bootstrap.php`**: The main entry point after the plugin is loaded. It is responsible for:

  - Initializing the dependency container.
  - Registering the WordPress hooks (actions and filters) that bootstrap the `Presentation` layer controllers.
  - Enqueuing scripts and styles (`.js`, `.css`).
  - Localizing data for the frontend (like the AJAX URL and security nonce).

- **`Container.php`**: Defines how dependencies should be resolved. It uses `PHP-DI` to map interfaces to their concrete implementations. For example, it specifies that when a use case requests a `ProjectRepositoryInterface`, the container should provide it with an instance of `WordPressProjectRepository`.

- **`Constants.php`**: Centralizes all "magic" values or configuration constants, such as WordPress option names, meta field keys, template paths, etc. This avoids having scattered strings throughout the codebase.

- **`helpers.php`**: Contains a set of global convenience functions. The most important is `resolve()`, which provides easy access to the dependency container from parts of the code that do not support direct injection (like templates or legacy WordPress functions).

## How to Extend This Layer

- **To register a new service or repository**:

  1.  Open `Container.php`.
  2.  Add a new entry to the array, mapping the `Interface::class` to the `Implementation::class`.

  ```php
  // Container.php
  return [
      // ...other definitions
      NewRepositoryInterface::class => \RIILSA\Infrastructure\Repositories\WordPressNewRepository::class,
  ];
  ```

- **To register a new Presentation controller**:

  1.  Open `Bootstrap.php`.
  2.  In the `register_hooks` method, add a new `add_action` or `add_filter` call, resolving the controller from the container so its dependencies are automatically injected.

  ```php
  // Bootstrap.php
  add_action('wp_ajax_my_action', [resolve(MyAjaxHandler::class), 'handle']);
  ```

- **To add a new constant**:
  1.  Open `Constants.php`.
  2.  Define the new constant with a descriptive name.

---

<p align="center">
  &copy; 2025 &bull; Developed by <a href="https://github.com/OnlyAlec" target="_blank">Alexis Chacon Trujillo</a>
</p>
