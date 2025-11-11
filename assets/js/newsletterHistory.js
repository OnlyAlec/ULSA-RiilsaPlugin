/**
 * RIILSA Newsletter - History Management
 *
 * Handles newsletter history display and interaction
 * Compatible with Clean Architecture refactored backend (v3.1.0)
 *
 * @package RIILSA
 * @version 3.1.0
 * @author Alexis Chacon Trujillo
 */

(function ($) {
  "use strict";

  /**
   * Initialize history functionality when DOM is ready
   */
  $(document).ready(function () {
    if (window.location.href.indexOf("gestion-boletin") === -1) {
      return; // Not on newsletter management page
    }

    setupHistoryButton();
  });

  /**
   * Setup history button event handler
   */
  function setupHistoryButton() {
    const historyButton = $("#b_newsHistory");

    if (!historyButton.length) {
      return;
    }

    historyButton.on("click", async function (e) {
      e.preventDefault();

      const loading = $(".loadingCover");

      try {
        $(this).css("pointer-events", "none");

        // Show loading indicator
        loading.css("display", "flex").hide().fadeIn();

        // Fetch newsletter history
        await updateHistoryAJAX();

        // Setup event handlers for history items
        setupHistoryItemHandlers();
      } catch (error) {
        console.error("Error updating history:", error);
        window.showError(
          "Failed to load newsletter history: " + (error.message || error)
        );
      } finally {
        loading.fadeOut();
        $(this).css("pointer-events", "auto");
      }
    });
  }

  /**
   * Setup event handlers for history items (view and send buttons)
   */
  function setupHistoryItemHandlers() {
    $(".btnHistory.sendBoletin, .btnHistory.doBoletin").each(function () {
      const btn = $(this);
      const icon = btn.find(".elementor-button-icon");

      // Setup loading icon
      if (icon.length !== 0) {
        icon.addClass("fa-spin");
        icon.hide();
      }

      // Setup click handler
      btn.off("click").on("click", async function (e) {
        e.preventDefault();

        $(".errorBoletin").remove();
        $(this).css("pointer-events", "none");

        try {
          if (icon.length !== 0) {
            window.toggleLoading(icon, btn);
          }

          // Handle send or view action
          if (btn.hasClass("sendBoletin")) {
            await handleSendFromHistory($(this));
          } else if (btn.hasClass("doBoletin")) {
            await handleViewFromHistory($(this));
          }
        } catch (error) {
          console.error("History action error:", error);
          window.showError(
            "Cannot view/send newsletter: " + (error.message || error)
          );
        } finally {
          if (icon.length !== 0) {
            window.toggleLoading(icon, btn);
          }
          $(this).css("pointer-events", "auto");
        }
      });
    });
  }

  /**
   * Handle view newsletter from history
   *
   * @param {jQuery} btn - Button element that was clicked
   * @returns {Promise} Promise that resolves when newsletter is displayed
   */
  async function handleViewFromHistory(btn) {
    const parent = btn.parent();
    const idNews = parent.data("id").toString().split(",");
    const textHeader = parent.data("text");
    const idNewsletter = parent.data("newsletter");

    if (!idNews || !idNewsletter) {
      throw new Error("Invalid newsletter data");
    }

    return window.initGenerarBoletin([idNews, textHeader, idNewsletter]);
  }

  /**
   * Handle send newsletter from history
   *
   * @param {jQuery} btn - Button element that was clicked
   * @returns {Promise} Promise that resolves when newsletter is sent
   */
  async function handleSendFromHistory(btn) {
    // First, generate the newsletter to get HTML
    await handleViewFromHistory(btn.siblings(".doBoletin"));

    // Then send it
    return window.initSendBoletin();
  }

  /**
   * Fetch newsletter history via AJAX
   *
   * @returns {Promise} Promise that resolves with history HTML
   */
  function updateHistoryAJAX() {
    return new Promise((resolve, reject) => {
      $.ajax({
        url: riilsa_ajax.ajax_url || ajaxurl,
        type: "POST",
        data: {
          action: "historyBoletin",
          nonce: riilsa_ajax.nonce,
        },
        success: function (response) {
          const container = $(".historySC");

          if (!container.length) {
            reject("History container not found");
            return;
          }

          container.empty();
          container.html(response);

          resolve(true);
        },
        error: function (xhr, status, error) {
          window.showError("Failed to load history: " + error);
          reject(error);
        },
      });
    });
  }

  /**
   * Refresh newsletter history display
   * Useful for reloading after operations
   */
  window.refreshNewsletterHistory = async function () {
    try {
      await updateHistoryAJAX();
      setupHistoryItemHandlers();
      return true;
    } catch (error) {
      console.error("Failed to refresh history:", error);
      return false;
    }
  };
})(jQuery);
