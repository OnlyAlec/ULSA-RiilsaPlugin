/**
 * RIILSA Modal Component
 *
 * Displays processing results from Excel upload and other operations
 * Compatible with Clean Architecture refactored backend (v3.1.0)
 *
 * @package RIILSA
 * @version 3.1.0
 * @author Alexis Chacon Trujillo
 */

(function ($) {
  "use strict";

  /**
   * Initialize modal functionality on document ready
   */
  $(document).ready(function () {
    /**
     * Listen for AJAX completions and display modal if response contains modal data
     * Pattern: Observer Pattern - listens for AJAX events
     */
    $(document).ajaxComplete(function (event, xhr, settings) {
      if (settings.url && settings.url.includes("admin-ajax.php")) {
        try {
          const response = JSON.parse(xhr.responseText);

          // Check if response contains RIILSA modal data
          if (
            response &&
            response.data &&
            response.data.data &&
            response.data.data.riilsa_modal
          ) {
            showRiilsaModal(response.data.data.riilsa_modal);
          }
        } catch (e) {
          // Silently fail if response is not JSON or doesn't contain modal data
        }
      }
    });
  });

  /**
   * Display RIILSA modal with processing results
   *
   * @param {Object} modalData - Modal configuration object
   * @param {string} modalData.title - Modal title
   * @param {string} modalData.type - Modal type (success, warning, error)
   * @param {Array} modalData.errors - Array of error messages
   * @param {Array} modalData.warnings - Array of warning messages
   * @param {Array} modalData.successes - Array of success messages
   * @param {Object} modalData.statistics - Optional statistics object
   */
  function showRiilsaModal(modalData) {
    // Remove any existing modal
    $("#riilsa-processing-modal").remove();

    // Build modal HTML
    const modalHtml = `
      <div id="riilsa-processing-modal" class="riilsa-modal-overlay" style="display: none;">
        <div class="riilsa-modal-content ${modalData.type}">
          <div class="riilsa-modal-header">
            <h3 class="riilsa-modal-title">
              <span class="riilsa-modal-icon">
                ${getModalIcon(modalData.type)}
              </span>
              ${escapeHtml(modalData.title)}
            </h3>
            <button class="riilsa-modal-close" type="button" aria-label="Close modal">&times;</button>
          </div>
          <div class="riilsa-modal-body">
            ${generateModalBody(modalData)}
          </div>
          <div class="riilsa-modal-footer">
            <button class="riilsa-btn riilsa-btn-primary riilsa-modal-close-btn" type="button">
              Close
            </button>
          </div>
        </div>
      </div>
    `;

    // Append modal to body
    $("body").append(modalHtml);

    // Show modal with fade effect
    $("#riilsa-processing-modal").fadeIn(300);

    // Setup event handlers
    setupModalEventHandlers();
  }

  /**
   * Setup modal event handlers
   */
  function setupModalEventHandlers() {
    /**
     * Close modal with animation
     */
    function closeModal() {
      $("#riilsa-processing-modal").fadeOut(300, function () {
        $(this).remove();
        $(document).off("keydown.riilsa-modal");
      });
    }

    // Close on overlay click
    $("#riilsa-processing-modal").on("click", function (e) {
      if (e.target === this) {
        closeModal();
      }
    });

    // Close on X button click
    $("#riilsa-processing-modal .riilsa-modal-close").on("click", function (e) {
      e.preventDefault();
      e.stopPropagation();
      closeModal();
    });

    // Close on footer button click
    $("#riilsa-processing-modal .riilsa-modal-close-btn").on(
      "click",
      function (e) {
        e.preventDefault();
        e.stopPropagation();
        closeModal();
      }
    );

    // Prevent close on content click
    $("#riilsa-processing-modal .riilsa-modal-content").on(
      "click",
      function (e) {
        e.stopPropagation();
      }
    );

    // Close on ESC key
    $(document).on("keydown.riilsa-modal", function (e) {
      if (e.key === "Escape" || e.keyCode === 27) {
        closeModal();
      }
    });
  }

  /**
   * Get icon for modal type
   *
   * @param {string} type - Modal type (success, warning, error, info)
   * @returns {string} Icon character
   */
  function getModalIcon(type) {
    const icons = {
      success: "✅",
      warning: "⚠️",
      error: "❌",
      info: "ℹ️",
    };

    return icons[type] || icons.info;
  }

  /**
   * Generate modal body content
   *
   * @param {Object} modalData - Modal data object
   * @returns {string} HTML string for modal body
   */
  function generateModalBody(modalData) {
    let html = "";

    // Render errors section
    if (modalData.errors && modalData.errors.length > 0) {
      html += '<div class="riilsa-message-section riilsa-errors">';
      html += '<h4><span class="riilsa-section-icon">❌</span> Errors</h4>';
      html += "<ul>";
      modalData.errors.forEach(function (error) {
        html += `<li class="riilsa-error-item">${escapeHtml(error)}</li>`;
      });
      html += "</ul>";
      html += "</div>";
    }

    // Render warnings section
    if (modalData.warnings && modalData.warnings.length > 0) {
      html += '<div class="riilsa-message-section riilsa-warnings">';
      html += '<h4><span class="riilsa-section-icon">⚠️</span> Warnings</h4>';
      html += "<ul>";
      modalData.warnings.forEach(function (warning) {
        html += `<li class="riilsa-warning-item">${escapeHtml(warning)}</li>`;
      });
      html += "</ul>";
      html += "</div>";
    }

    // Render successes section
    if (modalData.successes && modalData.successes.length > 0) {
      html += '<div class="riilsa-message-section riilsa-successes">';
      html += '<h4><span class="riilsa-section-icon">✅</span> Success</h4>';
      html += "<ul>";
      modalData.successes.forEach(function (success) {
        html += `<li class="riilsa-success-item">${escapeHtml(success)}</li>`;
      });
      html += "</ul>";
      html += "</div>";
    }

    // Render statistics if available
    if (modalData.statistics && Object.keys(modalData.statistics).length > 0) {
      html += '<div class="riilsa-message-section riilsa-statistics">';
      html += '<h4><span class="riilsa-section-icon">📊</span> Statistics</h4>';
      html += "<ul>";

      for (const [key, value] of Object.entries(modalData.statistics)) {
        const formattedKey = formatStatKey(key);
        html += `<li><strong>${formattedKey}:</strong> ${escapeHtml(
          String(value)
        )}</li>`;
      }

      html += "</ul>";
      html += "</div>";
    }

    return html || "<p>No detailed information available.</p>";
  }

  /**
   * Format statistics key for display
   *
   * @param {string} key - Statistics key
   * @returns {string} Formatted key
   */
  function formatStatKey(key) {
    return key
      .replace(/([A-Z])/g, " $1")
      .replace(/^./, function (str) {
        return str.toUpperCase();
      })
      .trim();
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

  // Expose modal function globally for external use
  window.showRiilsaModal = showRiilsaModal;

  /**
   * Test function for modal (development only)
   * Usage: window.testRiilsaModal()
   */
  window.testRiilsaModal = function () {
    showRiilsaModal({
      title: "RIILSA Test Modal",
      type: "success",
      errors: ["Example error for testing"],
      warnings: ["Example warning for testing"],
      successes: ["Example success message", "Second success message"],
      statistics: {
        total: 100,
        processed: 95,
        failed: 5,
      },
    });
  };
})(jQuery);
