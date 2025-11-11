# Frontend Scripts

## Overview

This directory contains all client-side JavaScript files that power the plugin's interactive features, primarily for the **Newsletter Module**.

The scripts are written in jQuery (leveraging the version bundled with WordPress) and follow a simple modular pattern. Each file handles a specific piece of the UI, exposing a public API through the `window` object when cross-file communication is needed.

## File Structure

```
/assets/js/
├── riilsa-modal.js         # Reusable modal component for displaying results (success, warning, error).
├── newsletterGeneral.js    # Core logic for generating and sending the newsletter.
├── newsletterSelection.js  # Handles the selection of news items (with a limit).
├── newsletterHistory.js    # Loads, displays, and re-sends historical newsletters.
├── newsletterConfig.js     # Manages subscribers and mailing lists (dependencies).
└── newsletterAuto.js       # Utilities for automatic date ranges.
```

## Public API & Communication

To facilitate interaction between modules, key functions are exposed globally.

```javascript
// Display a results modal
window.showRiilsaModal({
  title: "Operation Result",
  type: "success", // 'success', 'warning', 'error'
  successes: ["Newsletter sent successfully."],
  errors: [],
  warnings: [],
});

// Get the IDs of selected news items
const selectedIds = window.getSelectedNewsIds();

// Clear the current selection
window.clearNewsSelection();

// Programmatically select news items
window.selectNewsItems(["123", "124"]);
```

## AJAX Endpoints

All scripts communicate with the backend via WordPress AJAX endpoints. Nonce validation is **mandatory** for every request.

- `generateNewsletter`: Handled by `Presentation/Ajax/NewsletterAjaxHandler::handleGenerateNewsletter()`
- `sendNewsletter`: Handled by `Presentation/Ajax/NewsletterAjaxHandler::handleSendNewsletter()`
- `historyBoletin`: Handled by `Presentation/Ajax/NewsletterAjaxHandler::handleGetHistory()`
- `controlEmails`: Handled by `Presentation/Ajax/SubscriberAjaxHandler::handleControlEmails()`
- `controlDependencies`: Handled by `Presentation/Ajax/SubscriberAjaxHandler::handleControlDependencies()`

### Localization Object

WordPress localizes a `riilsa_ajax` object, making it globally available to all scripts with essential data:

```javascript
// Globally available object
const riilsa_ajax = {
  ajax_url: "https://.../wp-admin/admin-ajax.php",
  nonce: "a_unique_and_secure_nonce",
};
```

## Security

- **Nonce Verification**: All AJAX requests send the `nonce` for backend validation.
- **HTML Escaping**: An `escapeHtml` utility function is used to sanitize any data before injecting it into the DOM, preventing XSS attacks.
- **Input Validation**: Basic validation (emails, numbers, strings) is performed before sending data to the server.

---

<p align="center">
  &copy; 2025 &bull; Developed by <a href="https://github.com/OnlyAlec" target="_blank">Alexis Chacon Trujillo</a>
</p>
