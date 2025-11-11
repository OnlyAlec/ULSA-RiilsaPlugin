/**
 * RIILSA Newsletter - Configuration Management
 *
 * Handles subscriber and dependency management for newsletters
 * Compatible with Clean Architecture refactored backend (v3.1.0)
 *
 * @package RIILSA
 * @version 3.1.0
 * @author Alexis Chacon Trujillo
 */

(function ($) {
  "use strict";

  /**
   * Initialize event handlers when DOM is ready
   */
  $(document).ready(function () {
    if (window.location.href.indexOf("gestion-boletin") === -1) {
      return; // Not on newsletter management page
    }

    setupEventHandlers();
  });

  /**
   * Setup all event handlers for configuration management
   */
  function setupEventHandlers() {
    const btnAddEmail = $("#addEmail");
    const btnAddDep = $("#addDep");

    // Setup add email button
    if (btnAddEmail.length) {
      initButtons(btnAddEmail, addEmailInit);
    }

    // Setup add dependency button
    if (btnAddDep.length) {
      initButtons(btnAddDep, addDepInit);
    }

    // Setup delete email buttons
    $(".delete-email").each(function () {
      initButtons($(this), deleteEmailInit, "action");
    });

    // Setup resend confirmation buttons
    $(".verify-email").each(function () {
      initButtons($(this), requestEmailInit, "action");
    });

    // Setup delete dependency buttons
    $(".delete-dep").each(function () {
      initButtons($(this), deleteDepInit, "action");
    });
  }

  /**
   * Initialize button with loading state and event handler
   *
   * @param {jQuery} btn - Button element
   * @param {Function} fn - Function to execute on click
   * @param {string} type - Button type ('action' or '')
   */
  function initButtons(btn, fn, type = "") {
    const icon = btn.find(".elementor-button-icon");
    let args, shortcode;

    shortcode = btn.closest(".elementor-widget-button").data("shortcode");

    if (type === "action") {
      shortcode = btn.closest(".actions-wrapper").data("shortcode");
      args = btn;
    }

    const container = $(`.shortcode-container[data-shortcode="${shortcode}"]`);

    // Setup loading icon
    if (icon.length !== 0) {
      icon.addClass("fa-spin");
      icon.hide();
    }

    // Setup click handler
    btn.on("click", async function (e) {
      e.preventDefault();

      $(".errorBoletin").remove();

      try {
        $(this).css("pointer-events", "none");

        if (icon.length !== 0) {
          window.toggleLoading(icon, btn);
        }

        await fn(args);

        // Update container if exists
        if (container && container.length) {
          await window.updateContainer(container, shortcode);
          setupEventHandlers(); // Re-attach events to new elements
        }
      } catch (error) {
        console.error("Button action error:", error);
        window.showError("Operation failed: " + (error.message || error));
      } finally {
        if (icon.length !== 0) {
          window.toggleLoading(icon, btn);
        }
        $(this).css("pointer-events", "auto");
      }
    });
  }

  /**
   * Validate email address format
   *
   * @param {string} email - Email address to validate
   * @returns {boolean} True if valid, false otherwise
   */
  function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  /**
   * Initialize add email process
   * Validates input and calls addEmail
   */
  function addEmailInit() {
    const email = $("#newEmail").val().trim();
    const dep = $("#selectDep").val();

    // Validate email input
    if (!email) {
      alert("Please enter an email address!");
      return Promise.reject("No email provided");
    }

    $(".alert").remove();

    if (validateEmail(email)) {
      return addEmail(email, dep);
    } else {
      window.showError('The email "' + email + '" is not valid');
      return Promise.reject("Invalid email format");
    }
  }

  /**
   * Add email subscriber via AJAX
   *
   * @param {string} email - Email address to add
   * @param {number} dep - Dependency/department ID
   * @returns {Promise} Promise that resolves when email is added
   */
  function addEmail(email, dep) {
    return new Promise((resolve, reject) => {
      $.ajax({
        url: riilsa_ajax.ajax_url || ajaxurl,
        type: "POST",
        data: {
          action: "controlEmails",
          nonce: riilsa_ajax.nonce,
          emailAction: "add",
          data: {
            process: "create",
            email: email,
            dep: dep,
          },
        },
        success: function (response) {
          if (response.success === false) {
            window.showError(response.message || response.data);
            reject(response);
            return;
          }

          alert("Email saved successfully. A confirmation email will be sent.");
          resolve(true);
        },
        error: function (xhr, status, error) {
          window.showError("Failed to save email: " + error);
          reject(error);
        },
      });
    });
  }

  /**
   * Initialize delete email process
   *
   * @param {jQuery} btn - Button element that was clicked
   * @returns {Promise} Promise that resolves when email is deleted
   */
  function deleteEmailInit(btn) {
    const id = btn.closest(".actions-wrapper").data("id");

    if (!confirm("Are you sure you want to delete this email?")) {
      return Promise.reject("Delete cancelled");
    }

    return deleteEmail(id);
  }

  /**
   * Delete email subscriber via AJAX
   *
   * @param {number} id - Subscriber ID to delete
   * @returns {Promise} Promise that resolves when email is deleted
   */
  function deleteEmail(id) {
    return new Promise((resolve, reject) => {
      $.ajax({
        url: riilsa_ajax.ajax_url || ajaxurl,
        type: "POST",
        data: {
          action: "controlEmails",
          nonce: riilsa_ajax.nonce,
          emailAction: "remove",
          data: {
            process: "delete",
            id: id,
          },
        },
        success: function (response) {
          if (response.success === false) {
            window.showError(response.message || response.data);
            reject(response);
            return;
          }

          alert("Email deleted successfully.");
          resolve(true);
        },
        error: function (xhr, status, error) {
          window.showError("Failed to delete email: " + error);
          reject(error);
        },
      });
    });
  }

  /**
   * Initialize resend confirmation email process
   *
   * @param {jQuery} btn - Button element that was clicked
   * @returns {Promise} Promise that resolves when confirmation is sent
   */
  function requestEmailInit(btn) {
    const id = btn.closest(".actions-wrapper").data("id");
    return requestEmail(id);
  }

  /**
   * Request confirmation email resend via AJAX
   *
   * @param {number} id - Subscriber ID
   * @returns {Promise} Promise that resolves when email is sent
   */
  function requestEmail(id) {
    return new Promise((resolve, reject) => {
      $.ajax({
        url: riilsa_ajax.ajax_url || ajaxurl,
        type: "POST",
        data: {
          action: "controlEmails",
          nonce: riilsa_ajax.nonce,
          emailAction: "resend",
          data: {
            process: "request",
            id: id,
          },
        },
        success: function (response) {
          if (response.success === false) {
            window.showError(response.message || response.data);
            reject(response);
            return;
          }

          alert("Confirmation email sent successfully.");
          resolve(true);
        },
        error: function (xhr, status, error) {
          window.showError("Failed to send confirmation email: " + error);
          reject(error);
        },
      });
    });
  }

  /**
   * Initialize add dependency process
   * Validates input and calls addDep
   */
  function addDepInit() {
    const dep = $("#newDep").val().trim();

    if (!dep) {
      alert("Please enter a department name!");
      return Promise.reject("No department provided");
    }

    $(".alert").remove();
    return addDep(dep);
  }

  /**
   * Add dependency/department via AJAX
   *
   * @param {string} dep - Department name to add
   * @returns {Promise} Promise that resolves when department is added
   */
  function addDep(dep) {
    return new Promise((resolve, reject) => {
      $.ajax({
        url: riilsa_ajax.ajax_url || ajaxurl,
        type: "POST",
        data: {
          action: "controlDependencies",
          nonce: riilsa_ajax.nonce,
          dependencyAction: "add",
          data: {
            process: "create",
            dep: dep,
            description: dep,
          },
        },
        success: function (response) {
          if (response.success === false) {
            window.showError(response.message || response.data);
            reject(response);
            return;
          }

          alert("Department saved successfully.");
          resolve(true);
        },
        error: function (xhr, status, error) {
          window.showError("Failed to save department: " + error);
          reject(error);
        },
      });
    });
  }

  /**
   * Initialize delete dependency process
   *
   * @param {jQuery} btn - Button element that was clicked
   * @returns {Promise} Promise that resolves when department is deleted
   */
  function deleteDepInit(btn) {
    const id = btn.closest(".actions-wrapper").data("id");

    if (!confirm("Are you sure you want to delete this department?")) {
      return Promise.reject("Delete cancelled");
    }

    return deleteDep(id);
  }

  /**
   * Delete dependency/department via AJAX
   *
   * @param {number} id - Department ID to delete
   * @returns {Promise} Promise that resolves when department is deleted
   */
  function deleteDep(id) {
    return new Promise((resolve, reject) => {
      $.ajax({
        url: riilsa_ajax.ajax_url || ajaxurl,
        type: "POST",
        data: {
          action: "controlDependencies",
          nonce: riilsa_ajax.nonce,
          dependencyAction: "remove",
          data: {
            process: "delete",
            id: id,
          },
        },
        success: function (response) {
          if (response.success === false) {
            window.showError(response.message || response.data);
            reject(response);
            return;
          }

          alert("Department deleted successfully.");
          resolve(true);
        },
        error: function (xhr, status, error) {
          window.showError("Failed to delete department: " + error);
          reject(error);
        },
      });
    });
  }

  /**
   * Escape HTML to prevent XSS
   *
   * @param {string} text - Text to escape
   * @returns {string} Escaped text
   */
  function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  // Expose setupEventHandlers for re-initialization
  window.riilsaSetupConfigHandlers = setupEventHandlers;
})(jQuery);
