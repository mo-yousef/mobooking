/**
 * MoBooking Service Form Handler
 * Specialized handler for services form functionality
 * Works in conjunction with dashboard.js
 */

(function ($) {
  "use strict";

  const ServiceFormHandler = {
    init: function () {
      this.bindEvents();
      this.initializeFilters();
    },

    bindEvents: function () {
      // Category filter
      $("#category-filter").on("change", this.filterServices);

      // Icon selection
      $(document).on("click", ".icon-item", this.selectIcon);

      // Tab switching (fallback if not handled by main dashboard)
      $(document).on("click", ".tab-button", this.switchTab);
    },

    filterServices: function () {
      const selectedCategory = $(this).val();
      const $serviceCards = $(".service-card");

      if (!selectedCategory) {
        $serviceCards.show();
        return;
      }

      $serviceCards.each(function () {
        const cardCategory = $(this).data("category");
        if (cardCategory === selectedCategory) {
          $(this).show();
        } else {
          $(this).hide();
        }
      });
    },

    selectIcon: function () {
      const $this = $(this);
      const icon = $this.data("icon");

      // Update hidden input
      $("#service-icon").val(icon);

      // Update preview
      $(".icon-preview").html(`<span class="dashicons ${icon}"></span>`);

      // Update UI state
      $(".icon-item").removeClass("selected");
      $this.addClass("selected");
    },

    switchTab: function (e) {
      e.preventDefault();

      const $button = $(this);
      const tabId = $button.data("tab");

      // Update buttons
      $(".tab-button").removeClass("active");
      $button.addClass("active");

      // Update panes
      $(".tab-pane").removeClass("active");
      $(`#${tabId}`).addClass("active");

      // Update URL
      const url = new URL(window.location);
      url.searchParams.set("active_tab", tabId);
      window.history.replaceState({}, "", url);
    },

    initializeFilters: function () {
      // Set initial filter state from URL if needed
      const urlParams = new URLSearchParams(window.location.search);
      const category = urlParams.get("category");

      if (category) {
        $("#category-filter").val(category).trigger("change");
      }
    },

    // Utility functions
    showNotification: function (message, type = "info") {
      // Remove existing notifications
      $(".mobooking-notification").remove();

      const colors = {
        success: "#4CAF50",
        error: "#f44336",
        warning: "#ff9800",
        info: "#2196F3",
      };

      const $notification = $(`
                <div class="mobooking-notification notification-${type}" style="
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: ${colors[type]};
                    color: white;
                    padding: 15px 20px;
                    border-radius: 5px;
                    z-index: 10000;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    max-width: 350px;
                    font-weight: 500;
                ">${message}</div>
            `);

      $("body").append($notification);

      setTimeout(() => {
        $notification.fadeOut(300, function () {
          $(this).remove();
        });
      }, 4000);
    },
  };

  // Initialize when document is ready
  $(document).ready(function () {
    if ($(".services-section").length > 0) {
      ServiceFormHandler.init();
    }
  });

  // Make available globally
  window.ServiceFormHandler = ServiceFormHandler;
})(jQuery);
