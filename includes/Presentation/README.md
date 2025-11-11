# Presentation Layer

## Overview

The **Presentation** layer is the entry point for all user interactions. It is the outermost layer of the architecture and is responsible for handling incoming requests (HTTP, AJAX) and returning responses.

Its main responsibilities are:

- Receiving and validating user requests.
- Performing security checks (nonces, permissions/capabilities).
- Mapping request data to DTOs for the `Application` layer.
- Invoking the corresponding use case in the `Application` layer.
- Taking the result from the use case and formatting it into an appropriate response (JSON, HTML, redirect).

**Key Rule**: This layer should be "thin." Its logic should be limited to request/response handling. All business logic must be delegated to the `Application` layer. It depends only on the `Application` layer.

## Directory Structure

```
/Presentation/
├── Actions/
│   └── ExcelProcessAction.php
├── Ajax/
│   └── NewsletterAjaxHandler.php
└── Controllers/
    ├── ContentManagerController.php
    ├── NewsletterController.php
    └── SubscriptionController.php
```

### Component Breakdown

- **`Controllers/`**: Handle the logic for the plugin's admin pages. They orchestrate the rendering of views (templates) and process non-AJAX form submissions.

- **`Ajax/`**: Contains handlers for WordPress `admin-ajax.php` requests. Each method in these classes corresponds to a specific AJAX action. They are responsible for security (nonces) and for returning JSON responses (`wp_send_json_success` or `wp_send_json_error`).

- **`Actions/`**: Classes that hook into specific actions from WordPress or other plugins, such as processing an Elementor form (`ExcelProcessAction`).

## How to Extend This Layer

- **To add a new AJAX Endpoint**:
  1.  Add a new public method to an existing `Ajax/` class or create a new `*AjaxHandler` class.
  2.  The method must:
      a. Verify the nonce (`check_ajax_referer`).
      b. Check user permissions (`current_user_can`).
      c. Sanitize input data (`$_POST`, `$_GET`).
      d. Create the request DTO for the use case.
      e. Invoke the use case, which is injected via the constructor.
      f. Send a JSON response.
  3.  Register the new endpoint in `Core/Bootstrap.php` using `add_action('wp_ajax_...')`.

## Testing

- Handlers in this layer are best tested with **integration tests** that simulate real WordPress requests.
- It is crucial to verify that security checks (nonces, capabilities) correctly reject unauthorized requests.
- Ensure that the mapping of request data to the DTO is correct.

---

<p align="center">
  &copy; 2025 &bull; Developed by <a href="https://github.com/OnlyAlec" target="_blank">Alexis Chacon Trujillo</a>
</p>
