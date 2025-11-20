/**
 * RIILSA Content Manager - General JS
 *
 * Helper funcions
 *
 * @package RIILSA
 * @version 3.2.0
 * @author Alexis Chacon Trujillo
 */

(function ($) {
  "use strict";

  /**
   * Initialize event handlers when DOM is ready
   */
  $(document).ready(function () {
    if (window.location.href.indexOf("gestor-de-contenido") === -1) {
      return;
    }

    hideDownloadButtons();
  });

  /**
   * Hide buttons when href is empty
   */
  function hideDownloadButtons() {
    const btns = [
        $("#downloadProject")[0],
        $("#downloadCall")[0],
        $("#downloadNews")[0]
    ]

    btns.forEach((btn) => {
        if (btn != undefined && btn.href.includes("#")){
            $(btn).hide()
        }
    });
  }

})(jQuery);
