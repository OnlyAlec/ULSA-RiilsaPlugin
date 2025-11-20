/**
 * RIILSA Newsletter - Automatic Newsletter
 * 
 * Handles automatic newsletter with recent news
 * Compatible with Clean Architecture refactored backend (v3.1.0)
 * 
 * @package RIILSA
 * @version 3.1.0
 * @author Alexis Chacon Trujillo
 */

(function ($) {
  'use strict';

  /**
   * Initialize automatic newsletter functionality
   */
  $(document).ready(function () {
    if (window.location.href.indexOf('gestion-boletin') === -1) {
      return; // Not on newsletter management page
    }

    setupDateDisplay();
    setupAutoSelectButton();
  });

  /**
   * Setup date range display for automatic newsletter
   */
  function setupDateDisplay() {
    const today = new Date();
    
    // Calculate one month ago
    const monthAgo = new Date(today);
    monthAgo.setMonth(monthAgo.getMonth() - 1);
    
    const dateMonthAgo = formatDate(monthAgo);
    const dateToday = formatDate(today);

    // Update date displays
    $('#oldBoletin h3').text(dateMonthAgo);
    $('#newBoletin h3').text(dateToday);
  }

  /**
   * Format date as DD/MM/YYYY
   * 
   * @param {Date} date - Date to format
   * @returns {string} Formatted date string
   */
  function formatDate(date) {
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    
    return `${day}/${month}/${year}`;
  }

  /**
   * Setup auto-select button for recent news
   */
  function setupAutoSelectButton() {
    const autoBtn = $('#b_newsAuto');
    
    if (!autoBtn.length) {
      return;
    }

    autoBtn.on('click', function (e) {
      e.preventDefault();

      // Select all checkboxes in automatic newsletter section
      $('#newsAuto .cb input').each(function () {
        $(this).prop('checked', true).prop('disabled', true);
      });

      // Update counter if available
      if (typeof window.updateNewsCounter === 'function') {
        window.updateNewsCounter();
      }

      // Log action
      console.log('Auto-selected news items for automatic newsletter');
    });
  }

  /**
   * Get automatic newsletter date range
   * 
   * @returns {Object} Object with start and end dates
   */
  window.getAutoNewsletterDateRange = function () {
    const today = new Date();
    const monthAgo = new Date(today);
    monthAgo.setMonth(monthAgo.getMonth() - 1);

    return {
      start: monthAgo,
      end: today,
      startFormatted: formatDate(monthAgo),
      endFormatted: formatDate(today)
    };
  };

})(jQuery);
