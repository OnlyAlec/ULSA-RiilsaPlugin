/**
 * RIILSA Newsletter - General Functionality
 *
 * Core newsletter management functions for the RIILSA plugin
 * Compatible with Clean Architecture refactored backend (v3.1.0)
 *
 * @package RIILSA
 * @version 3.1.0
 * @author Alexis Chacon Trujillo
 */

(function ($) {
  "use strict";

  /**
   * Initialize newsletter generation from user selection or history
   *
   * @param {Array|undefined} params - Optional parameters from history [idNews, textHeader, idNewsletter]
   * @returns {Promise} Promise that resolves when generation completes
   */
  window.initGenerarBoletin = function (params) {
    // If direct parameters provided (from history)
    if (typeof params === "object" && $(params).length === 3) {
      const [idNews, textHeader, idNewsletter] = params;
      return generarBoletinAJAX(idNews, textHeader, idNewsletter, false);
    }

    // If called from main newsletter interface
    const idSection = $(".btnSelection.active").attr("id").split("_")[1];

    const textHeader = $("#" + idSection)
      .find(".textHeader")
      .val()
      .trim();

    const idNewsletter =
      $("#" + idSection)
        .find(".numberBoletin")
        .val() || $("#numBoletin h2").text();

    const idNews = $("#" + idSection)
      .find(".cb input:checked")
      .map(function () {
        return $(this).closest("[id]").attr("id");
      })
      .get();

    // Validate inputs
    if (!textHeader) {
      alert("Please enter a header text");
      return Promise.reject("No header text provided");
    }

    if (idNews.length === 0) {
      alert("Please select at least one news item");
      return Promise.reject("No news selected");
    }

    return generarBoletinAJAX(idNews, textHeader, idNewsletter, true);
  };

  /**
   * Toggle loading state for button
   *
   * @param {jQuery} icon - Icon element to toggle
   * @param {jQuery} btn - Button element containing text to toggle
   */
  window.toggleLoading = function (icon, btn) {
    icon.toggle();
    btn.find(".elementor-button-text").toggle();
  };

  /**
   * Display error message in error container
   *
   * @param {string} message - Error message to display
   */
  window.showError = function (message) {
    $("#errorContainer>.e-con-inner").prepend(`
      <div class="alert alert-danger errorBoletin" role="alert">
        ${escapeHtml(message)}
      </div>
    `);

    // Scroll to error container
    $("html, body").animate(
      {
        scrollTop: $("#errorContainer").offset().top,
      },
      1000
    );
  };

  /**
   * Initialize newsletter sending process
   *
   * @returns {Promise} Promise that resolves when send completes
   */
  window.initSendBoletin = async function () {
    const confirmSend = confirm(
      "Are you sure you want to send the newsletter?"
    );

    if (!confirmSend) {
      return Promise.reject("Send cancelled by user");
    }

    const idSection = $(".btnSelection.active").attr("id").split("_")[1];

    const idNewsletter =
      $("#" + idSection)
        .find(".numberBoletin")
        .val() || $("#numBoletin h2").text();

    const htmlBoletin = $("#boletinPreview")
      .find("iframe")
      .prop("contentDocument").body.innerHTML;

    return sendBoletinAJAX(htmlBoletin, idNewsletter);
  };

  /**
   * Update container with shortcode content via AJAX
   *
   * @param {jQuery} container - Container element to update
   * @param {string} shortcode - Shortcode to execute
   * @returns {Promise} Promise that resolves when update completes
   */
  window.updateContainer = function (container, shortcode) {
    return new Promise((resolve, reject) => {
      $.ajax({
        url: riilsa_ajax.ajax_url || ajaxurl,
        type: "POST",
        data: {
          action: "updateShortcodes",
          nonce: riilsa_ajax.nonce,
          shortcode: shortcode,
        },
        success: function (response) {
          if (response.success === false) {
            window.showError(response.message || response.data);
            reject(response);
            return;
          }

          container.empty();
          const newDiv = $("<div></div>").append(response.data);
          container.append(newDiv);
          resolve(true);
        },
        error: function (xhr, status, error) {
          window.showError("Failed to update container: " + error);
          reject(error);
        },
      });
    });
  };

  /**
   * Initialize newsletter interface when on newsletter management page
   */
  $(document).ready(function () {
    if (window.location.href.indexOf("gestion-boletin") === -1) {
      return; // Not on newsletter page
    }

    // Initialize sections
    hideSections();

    // Hide parent newsletter taxonomy filter
    $(".e-filter-item").each(function () {
      const dataFilter = $(this).data("filter");
      if (dataFilter === "parent-boletin") {
        $(this).hide();
      }
    });

    // Setup section switching
    $(".btnSelection").each(function () {
      const idSection = $(this).attr("id").split("_")[1];

      $(this).on("click", function (e) {
        e.preventDefault();

        hideSections();
        wipeCheckbox();
        wipeActive();
        wipeInputText();

        $("#" + idSection).show();
        $(this).addClass("active");
      });
    });

    // Setup generate newsletter button
    $(".doBoletin").each(function () {
      const btn = $(this);
      const icon = btn.find(".elementor-button-icon");

      if (icon.length !== 0) {
        icon.addClass("fa-spin");
        icon.hide();
      }

      btn.on("click", async function (e) {
        e.preventDefault();

        $(".errorBoletin").remove();

        try {
          $(this).css("pointer-events", "none");
          window.toggleLoading(icon, btn);
          await window.initGenerarBoletin();
        } catch (error) {
          console.error("Newsletter generation error:", error);
          window.showError(
            "Failed to generate newsletter: " + (error.message || error)
          );
        } finally {
          window.toggleLoading(icon, btn);
          $(this).css("pointer-events", "auto");
        }
      });
    });
  });

  /**
   * Hide all newsletter sections
   */
  function hideSections() {
    $("#newsContainer>.e-con-inner").children().hide();
    $(".preview").hide();
  }

  /**
   * Deselect all checkboxes and enable buttons
   */
  function wipeCheckbox() {
    $(".cb input").prop("checked", false).prop("disabled", false);
    $("#countNews h2").text(0);
  }

  /**
   * Deselect all active buttons
   */
  function wipeActive() {
    $(".btnSelection").each(function () {
      $(this).removeClass("active");
    });
  }

  /**
   * Clear all text inputs
   */
  function wipeInputText() {
    $(".textHeader").val("");
    $(".numberBoletin").val("");
  }

  /**
   * AJAX request to generate newsletter
   *
   * @param {Array<string>} idNews - Array of news IDs
   * @param {string} text - Header text for newsletter
   * @param {number} idNewsletter - Newsletter number
   * @param {boolean} updateDB - Whether to save to database
   * @returns {Promise} Promise that resolves with newsletter HTML
   */
  function generarBoletinAJAX(idNews, text, idNewsletter, updateDB) {
    return new Promise((resolve, reject) => {
      $.ajax({
        url: riilsa_ajax.ajax_url || ajaxurl,
        type: "POST",
        data: {
          action: "generateNewsletter",
          nonce: riilsa_ajax.nonce,
          data: {
            idNews: idNews,
            text: text,
            idNewsletter: idNewsletter,
            updateDB: updateDB,
          },
        },
        success: function (response) {
          if (response.success === false) {
            window.showError(response.message || response.data);
            reject(response.data || response.message);
            return;
          }

          // Create iframe for preview
          const iframe = document.createElement("iframe");
          const display = $(".preview");
          const containerFrame = $("#boletinPreview");

          iframe.style.width = "100%";
          iframe.style.height = "100%";

          containerFrame.empty();
          containerFrame.append(iframe);

          // Write newsletter HTML to iframe
          iframe.contentWindow.document.open();
          iframe.contentWindow.document.write(response.data.html);
          iframe.contentWindow.document.close();

          // Setup send button
          $(".sendBoletin")
            .off("click")
            .on("click", function () {
              window.initSendBoletin();
            });

          display.show();
          resolve(true);
        },
        error: function (xhr, status, error) {
          window.showError("Newsletter generation failed: " + error);
          reject(error);
        },
      });
    });
  }

  /**
   * AJAX request to send newsletter
   *
   * @param {string} html - Newsletter HTML content
   * @param {number} id - Newsletter ID
   * @returns {Promise} Promise that resolves when send completes
   */
  function sendBoletinAJAX(html, id) {
    return new Promise((resolve, reject) => {
      $.ajax({
        url: riilsa_ajax.ajax_url || ajaxurl,
        type: "POST",
        data: {
          action: "sendNewsletter",
          nonce: riilsa_ajax.nonce,
          data: {
            html: html,
            id: id,
          },
        },
        success: function (response) {
          if (response.success === false) {
            window.showError(
              "Cannot send newsletter: " + (response.message || response.data)
            );
            reject(response);
            return;
          }

          alert("Newsletter sent successfully!");

          // Show statistics if available
          if (response.data && response.data.statistics) {
            console.log("Send statistics:", response.data.statistics);
          }

          resolve(true);
        },
        error: function (xhr, status, error) {
          window.showError("Failed to send newsletter: " + error);
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

  /**
   * Show loading indicator
   *
   * @param {string} message - Optional loading message
   */
  window.showLoading = function (message) {
    const loadingHtml = `
      <div class="riilsa-loading-overlay">
        <div class="riilsa-loading-content">
          <div class="riilsa-spinner"></div>
          <p>${escapeHtml(message || "Processing...")}</p>
        </div>
      </div>
    `;

    $(".riilsa-loading-overlay").remove();
    $("body").append(loadingHtml);
  };

  /**
   * Hide loading indicator
   */
  window.hideLoading = function () {
    $(".riilsa-loading-overlay").fadeOut(300, function () {
      $(this).remove();
    });
  };
})(jQuery);
