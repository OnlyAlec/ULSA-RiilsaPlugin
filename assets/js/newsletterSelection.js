/**
 * RIILSA Newsletter - News Selection
 *
 * Handles news item selection for newsletter creation
 * Compatible with Clean Architecture refactored backend (v3.1.0)
 *
 * @package RIILSA
 * @version 3.1.0
 * @author Alexis Chacon Trujillo
 */

(function ($) {
  "use strict";

  /**
   * Maximum number of news items that can be selected
   * Matches domain service limit (NewsletterContentService::getTotalCapacity)
   */
  const MAX_NEWS_LIMIT = 21;

  /**
   * Initialize news selection functionality
   */
  $(document).ready(function () {
    if (window.location.href.indexOf("gestion-boletin") === -1) {
      return; // Not on newsletter management page
    }

    setupNewsSelection();
    setupAutoSelectButton();
  });

  /**
   * Setup news selection checkboxes with limit enforcement
   */
  function setupNewsSelection() {
    $("#newsSelect .cb").each(function () {
      $(this).on("click", function (e) {
        const input = $(this).find("input");

        // Toggle checkbox with limit check
        if (input.prop("checked")) {
          input.prop("checked", false);
        } else {
          const selectedCount = $(".cb input:checked").length;

          if (selectedCount < MAX_NEWS_LIMIT) {
            input.prop("checked", true);
          } else {
            alert(`You cannot select more than ${MAX_NEWS_LIMIT} news items`);
            e.preventDefault();
            return false;
          }
        }

        // Update counter display
        updateNewsCounter();
      });
    });
  }

  /**
   * Setup auto-select button for recent news
   */
  function setupAutoSelectButton() {
    const autoSelectBtn = $("#b_newsAuto");

    if (!autoSelectBtn.length) {
      return;
    }

    autoSelectBtn.on("click", function (e) {
      e.preventDefault();

      // Select and disable all checkboxes in auto section
      $("#newsAuto .cb input").each(function () {
        $(this).prop("checked", true).prop("disabled", true);
      });

      // Update counter
      updateNewsCounter();

      // Show info message
      console.log("Auto-selected recent news items");
    });
  }

  /**
   * Update news counter display
   */
  function updateNewsCounter() {
    const selectedCount = $(".cb input:checked").length;
    $("#countNews h2").text(selectedCount);

    // Update visual feedback based on limit
    if (selectedCount >= MAX_NEWS_LIMIT) {
      $("#countNews").addClass("at-limit");
    } else {
      $("#countNews").removeClass("at-limit");
    }
  }

  /**
   * Get selected news IDs
   *
   * @returns {Array<string>} Array of selected news IDs
   */
  window.getSelectedNewsIds = function () {
    return $(".cb input:checked")
      .map(function () {
        return $(this).closest("[id]").attr("id");
      })
      .get();
  };

  /**
   * Clear all selections
   */
  window.clearNewsSelection = function () {
    $(".cb input").prop("checked", false).prop("disabled", false);
    updateNewsCounter();
  };

  /**
   * Select specific news items by ID
   *
   * @param {Array<string>} newsIds - Array of news IDs to select
   */
  window.selectNewsItems = function (newsIds) {
    clearNewsSelection();

    newsIds.forEach(function (id) {
      $(`#${id} .cb input`).prop("checked", true);
    });

    updateNewsCounter();
  };
})(jQuery);
