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
   * @param {string|null} details - Optional technical details
   */
  window.showError = function (message, details = null) {
    // Remove existing errors
    $(".errorBoletin").remove();
    $(".riilsa-error-container").remove();

    const errorHtml = `
      <div class="riilsa-error-container" style="margin: 20px 0; padding: 15px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24;">
        <div class="riilsa-error-header" style="display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center;">
                <span class="dashicons dashicons-warning" style="font-size: 24px; margin-right: 10px; height: 24px; width: 24px;"></span>
                <strong>${escapeHtml(message)}</strong>
            </div>
            ${details ? `
            <button type="button" class="riilsa-error-toggle" style="background: none; border: none; cursor: pointer; color: #721c24; text-decoration: underline; font-size: 14px;">
                Ver detalles
            </button>
            ` : ''}
        </div>
        ${details ? `
        <div class="riilsa-error-details" style="display: none; margin-top: 10px; padding-top: 10px; border-top: 1px solid #f5c6cb; font-family: monospace; white-space: pre-wrap; font-size: 12px;">
            ${escapeHtml(typeof details === 'object' ? JSON.stringify(details, null, 2) : details)}
        </div>
        ` : ''}
      </div>
    `;

    let target = $("#errorContainer>.e-con-inner");
    if (target.length === 0) {
        target = $("#errorContainer");
    }
    
    if (target.length) {
        target.prepend(errorHtml);
    } else {
        // Fallback if error container not found
        const fallbackTarget = $('.entry-content, .post-content, #content, main').first();
        if (fallbackTarget.length) {
            fallbackTarget.prepend(errorHtml);
        } else {
            $('body').prepend(errorHtml);
        }
    }

    // Add toggle functionality
    if (details) {
        $(".riilsa-error-toggle").on("click", function() {
            const detailsDiv = $(this).closest(".riilsa-error-container").find(".riilsa-error-details");
            detailsDiv.slideToggle();
            $(this).text(detailsDiv.is(":visible") ? "Ver detalles" : "Ocultar detalles");
        });
    }

    // Scroll to error container
    const errorElement = $(".riilsa-error-container").first();
    if (errorElement.length) {
        $("html, body").animate(
        {
            scrollTop: errorElement.offset().top - 100,
        },
        1000
        );
    }
  };

  /**
   * Initialize newsletter sending process
   *
   * @returns {Promise} Promise that resolves when send completes
   */
  window.initSendBoletin = async function (fromFrame = true) {
    const confirmSend = confirm(
      "Are you sure you want to send the newsletter?"
    );

    if (!confirmSend) {
      return Promise.reject("Send cancelled by user");
    }
    let idNewsletter;
    const idSection = $(".btnSelection.active").attr("id").split("_")[1];

    if (fromFrame){
      idNewsletter = $("#boletinPreview").data("idNewsletter");
    } else {
      idNewsletter = $("#" + idSection).find(".numberHistory").text().replace(/[^0-9]/g, '');
    }
    
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
            // Don't show error here, let the caller handle it to avoid duplicates
            reject(response.message || response.data);
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
   * Setup async button with loading state and error handling
   * 
   * @param {jQuery} btn - Button element
   * @param {Function} callback - Async function to execute on click. Receives the button as argument.
   * @param {string} errorMessage - Error message to show on failure
   */
  window.setupAsyncButton = function(btn, callback, errorMessage) {
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
      $(".riilsa-error-container").remove();

      try {
        $(this).css("pointer-events", "none");
        
        if (icon.length !== 0) {
          window.toggleLoading(icon, btn);
        }

        await callback($(this));
      } catch (error) {
        console.error(errorMessage || "Action error:", error);
        window.showError(
          errorMessage || "Error processing request.",
          error.message || error
        );
      } finally {
        if (icon.length !== 0) {
          window.toggleLoading(icon, btn);
        }
        $(this).css("pointer-events", "auto");
      }
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

        $(".errorBoletin").remove();
        $(".riilsa-error-container").remove();

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
      window.setupAsyncButton(
        $(this),
        async () => await window.initGenerarBoletin(),
        "Error al generar el boletín."
      );
    });

    // Check Brevo availability on page load
    if (typeof riilsa_ajax !== "undefined" && (riilsa_ajax.brevo_available === false || riilsa_ajax.brevo_available === "")) {
      const message = riilsa_ajax.strings.brevo_unavailable || "Brevo service is unavailable";

      const banner = `
        <div class="riilsa-brevo-warning" style="background: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border: 1px solid #f5c6cb; border-radius: 4px; text-align: center;">
          <p style="margin: 0; font-weight: bold;">${message}</p>
        </div>
      `;

      // Try to insert before the main content area if possible
      const target = $(".entry-content, .post-content, #content, main").first();

      if (target.length) {
        target.prepend(banner);
      } else {
        // Fallback to body
        $("body").prepend(banner);
      }
    }
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
            reject(response.message || response.data);
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
          containerFrame.data("idNewsletter", idNewsletter);

          // Write newsletter HTML to iframe
          iframe.contentWindow.document.open();
          iframe.contentWindow.document.write(response.data.html);
          iframe.contentWindow.document.close();

          // Setup send button
          $(".sendBoletin")
            .off("click")
            .on("click", function () {
              window.initSendBoletin().catch(function(error) {
                  console.error("Send error:", error);
                  window.showError("Error al enviar el boletín.", error.message || error);
              });
            });

          display.show();
          resolve(true);
        },
        error: function (xhr, status, error) {
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
            reject(response.message || response.data);
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
