/**
 * MoBooking Service Form Handler - Complete Version
 * Handles all service-related functionality including tabs, options, and visual elements
 */

(function ($) {
  "use strict";

  const ServiceFormHandler = {
    // Configuration
    config: {
      ajaxUrl: mobookingDashboard?.ajaxUrl || "/wp-admin/admin-ajax.php",
      serviceNonce: mobookingDashboard?.nonces?.service || "",
      currentServiceId: mobookingDashboard?.currentServiceId || null,
      currentView: mobookingDashboard?.currentView || "list",
    },

    // State management
    state: {
      isSubmitting: false,
      currentTab: "basic-info",
      optionModalOpen: false,
      preventDuplicateSubmission: false,
    },

    // Initialize
    init: function () {
      console.log("🚀 Service Form Handler initializing...");
      this.cacheElements();
      this.attachEventListeners();
      this.initializeComponents();
      console.log("✅ Service Form Handler initialized");
    },

    // Cache DOM elements
    cacheElements: function () {
      this.elements = {
        // Service form elements
        serviceForm: $("#service-form"),
        serviceId: $("#service-id"),
        serviceName: $("#service-name"),
        servicePrice: $("#service-price"),
        serviceDuration: $("#service-duration"),

        // Tab elements
        tabButtons: $(".tab-button"),
        tabPanes: $(".tab-pane"),

        // Icon selection elements
        iconInput: $("#service-icon"),
        iconPreview: $(".icon-preview"),
        iconOptions: $(".icon-option"),

        // Image upload elements
        imageInput: $("#service-image"),
        imagePreview: $(".image-preview"),
        selectImageBtn: $(".select-image-btn"),

        // Option elements
        optionModal: $("#option-modal"),
        optionForm: $("#option-form"),
        addOptionBtn: $("#add-option-btn"),
        optionsContainer: $("#service-options-container"),

        // Filter elements
        categoryFilter: $("#category-filter"),
        serviceCards: $(".service-card"),
      };
    },

    // Attach all event listeners
    attachEventListeners: function () {
      const self = this;

      // ===== SERVICE FORM EVENTS =====
      this.elements.serviceForm
        .off("submit.serviceHandler")
        .on("submit.serviceHandler", function (e) {
          e.preventDefault();
          e.stopImmediatePropagation();

          if (
            self.state.preventDuplicateSubmission ||
            self.state.isSubmitting
          ) {
            console.log("Preventing duplicate submission");
            return false;
          }

          self.handleServiceSubmit();
          return false;
        });

      // ===== TAB FUNCTIONALITY =====
      this.elements.tabButtons
        .off("click.tabHandler")
        .on("click.tabHandler", function (e) {
          e.preventDefault();
          const tabId = $(this).data("tab");
          if (tabId) {
            self.switchTab(tabId);
          }
        });

      // ===== VISUAL PRESENTATION =====
      // Icon selection
      this.elements.iconOptions
        .off("click.iconHandler")
        .on("click.iconHandler", function () {
          self.selectIcon($(this));
        });

      // Image upload button
      this.elements.selectImageBtn
        .off("click.imageHandler")
        .on("click.imageHandler", function (e) {
          e.preventDefault();
          self.openImageSelector();
        });

      // Image URL input change
      this.elements.imageInput
        .off("input.imageHandler")
        .on("input.imageHandler", function () {
          self.updateImagePreview($(this).val());
        });

      // ===== SERVICE OPTIONS =====
      this.elements.addOptionBtn
        .off("click.optionHandler")
        .on("click.optionHandler", function () {
          self.showAddOptionModal();
        });

      // Edit option
      $(document)
        .off("click.optionEdit", ".edit-option-btn")
        .on("click.optionEdit", ".edit-option-btn", function () {
          const optionCard = $(this).closest(".option-card");
          const optionId = optionCard.data("option-id");
          if (optionId) {
            self.editOption(optionId);
          }
        });

      // Delete option
      $(document)
        .off("click.optionDelete", ".delete-option-btn")
        .on("click.optionDelete", ".delete-option-btn", function () {
          const optionId = $(this).data("option-id");
          if (optionId) {
            self.showDeleteConfirmation("option", optionId);
          }
        });

      // ===== FILTERS =====
      this.elements.categoryFilter
        .off("change.filterHandler")
        .on("change.filterHandler", function () {
          self.filterServices();
        });

      // ===== MODAL EVENTS =====
      $(".modal-close, .cancel-delete-btn, #cancel-option-btn")
        .off("click.modalHandler")
        .on("click.modalHandler", function () {
          self.hideModals();
        });

      // ===== KEYBOARD NAVIGATION =====
      $(document)
        .off("keydown.tabNavigation")
        .on("keydown.tabNavigation", ".tab-button", function (e) {
          self.handleTabKeyNavigation(e, $(this));
        });

      // Close modals on escape
      $(document)
        .off("keydown.escapeHandler")
        .on("keydown.escapeHandler", function (e) {
          if (e.key === "Escape") {
            self.hideModals();
          }
        });
    },

    // Initialize components
    initializeComponents: function () {
      this.initializeTabs();
      this.initializeIconSelection();
      this.initializeImagePreview();

      if (this.config.currentView === "edit" && this.config.currentServiceId) {
        this.loadServiceOptions(this.config.currentServiceId);
      }

      // Set option service ID if we have it
      const serviceId =
        this.config.currentServiceId ||
        new URLSearchParams(window.location.search).get("service_id") ||
        this.elements.serviceId.val();

      if (serviceId) {
        $("#option-service-id").val(serviceId);
        this.config.currentServiceId = parseInt(serviceId);
      }
    },

    // ===== TAB FUNCTIONALITY =====
    initializeTabs: function () {
      const urlParams = new URLSearchParams(window.location.search);
      const activeTab = urlParams.get("active_tab") || "basic-info";
      this.switchTab(activeTab);
    },

    switchTab: function (tabId) {
      if (!tabId || this.state.currentTab === tabId) return;

      // Update button states
      this.elements.tabButtons
        .removeClass("active")
        .attr("aria-selected", "false");
      this.elements.tabButtons
        .filter(`[data-tab="${tabId}"]`)
        .addClass("active")
        .attr("aria-selected", "true");

      // Update tab panes
      this.elements.tabPanes.removeClass("active");
      $(`#${tabId}`).addClass("active");

      // Update URL
      const url = new URL(window.location);
      url.searchParams.set("active_tab", tabId);
      window.history.replaceState({}, "", url);

      // Update state
      this.state.currentTab = tabId;

      // Trigger custom event
      $(document).trigger("mobooking:tab-changed", [tabId]);

      console.log(`Tab switched to: ${tabId}`);
    },

    handleTabKeyNavigation: function (e, $currentTab) {
      const $buttons = this.elements.tabButtons;
      const currentIndex = $buttons.index($currentTab);
      let newIndex;

      switch (e.key) {
        case "ArrowLeft":
          e.preventDefault();
          newIndex = currentIndex > 0 ? currentIndex - 1 : $buttons.length - 1;
          $buttons.eq(newIndex).focus().click();
          break;
        case "ArrowRight":
          e.preventDefault();
          newIndex = currentIndex < $buttons.length - 1 ? currentIndex + 1 : 0;
          $buttons.eq(newIndex).focus().click();
          break;
        case "Home":
          e.preventDefault();
          $buttons.first().focus().click();
          break;
        case "End":
          e.preventDefault();
          $buttons.last().focus().click();
          break;
      }
    },

    // ===== VISUAL PRESENTATION =====
    initializeIconSelection: function () {
      const currentIcon = this.elements.iconInput.val();
      if (currentIcon) {
        this.elements.iconOptions
          .filter(`[data-icon="${currentIcon}"]`)
          .addClass("selected");
        this.updateIconPreview(currentIcon);
      }
    },

    selectIcon: function ($iconElement) {
      const icon = $iconElement.data("icon");
      if (!icon) return;

      // Update hidden input
      this.elements.iconInput.val(icon);

      // Update preview
      this.updateIconPreview(icon);

      // Update UI state
      this.elements.iconOptions.removeClass("selected");
      $iconElement.addClass("selected");

      // Visual feedback
      $iconElement.addClass("icon-selected");
      setTimeout(() => {
        $iconElement.removeClass("icon-selected");
      }, 300);

      console.log(`Icon selected: ${icon}`);
    },

    updateIconPreview: function (icon) {
      if (icon) {
        this.elements.iconPreview.html(
          `<span class="dashicons ${icon}"></span>`
        );
      } else {
        this.elements.iconPreview.html(`
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
          </svg>
        `);
      }
    },

    initializeImagePreview: function () {
      const currentImage = this.elements.imageInput.val();
      if (currentImage) {
        this.updateImagePreview(currentImage);
      }
    },

    openImageSelector: function () {
      // Check if WordPress media library is available
      if (typeof wp !== "undefined" && wp.media) {
        const mediaUploader = wp.media({
          title: "Choose Service Image",
          button: {
            text: "Use This Image",
          },
          multiple: false,
          library: {
            type: "image",
          },
        });

        mediaUploader.on("select", () => {
          const attachment = mediaUploader
            .state()
            .get("selection")
            .first()
            .toJSON();
          this.elements.imageInput.val(attachment.url);
          this.updateImagePreview(attachment.url);
          this.showNotification("Image selected successfully", "success");
        });

        mediaUploader.open();
      } else {
        // Fallback: prompt for URL
        const imageUrl = prompt("Enter image URL:");
        if (imageUrl) {
          this.elements.imageInput.val(imageUrl);
          this.updateImagePreview(imageUrl);
        }
      }
    },

    updateImagePreview: function (url) {
      const $preview = this.elements.imagePreview;

      if (url) {
        // Test if image loads
        const img = new Image();
        img.onload = () => {
          $preview.html(`<img src="${url}" alt="Service image">`);
        };
        img.onerror = () => {
          $preview.html(`
            <div class="image-placeholder">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                <circle cx="9" cy="9" r="2"/>
                <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
              </svg>
              <span>Invalid image URL</span>
            </div>
          `);
        };
        img.src = url;
      } else {
        $preview.html(`
          <div class="image-placeholder">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
              <circle cx="9" cy="9" r="2"/>
              <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
            </svg>
            <span>No image selected</span>
          </div>
        `);
      }
    },

    // ===== SERVICE FORM HANDLING =====
    handleServiceSubmit: function () {
      if (this.state.isSubmitting || this.state.preventDuplicateSubmission) {
        console.log("Submit blocked - already processing");
        return false;
      }

      if (!this.validateServiceForm()) {
        return false;
      }

      // Set flags to prevent duplicate submissions
      this.state.isSubmitting = true;
      this.state.preventDuplicateSubmission = true;

      // Reset flag after a delay as additional protection
      setTimeout(() => {
        this.state.preventDuplicateSubmission = false;
      }, 2000);

      this.showLoading($("#save-service-button"));

      const formData = new FormData(this.elements.serviceForm[0]);
      formData.append("action", "mobooking_save_service");
      formData.append("nonce", this.config.serviceNonce);

      // Add request ID to prevent duplicates
      formData.append("request_id", Date.now() + "_" + Math.random());

      this.makeAjaxRequest(formData)
        .done((response) => {
          if (response.success) {
            this.showNotification(
              response.data.message || "Service saved successfully!",
              "success"
            );

            // Update state for new services
            if (response.data.id && !this.config.currentServiceId) {
              this.config.currentServiceId = response.data.id;
              this.elements.serviceId.val(response.data.id);
              $("#option-service-id").val(response.data.id);
              this.elements.addOptionBtn
                .prop("disabled", false)
                .removeAttr("title");
            }
          } else {
            this.showNotification(
              response.data?.message || "Failed to save service",
              "error"
            );
          }
        })
        .fail(() => {
          this.showNotification("Error saving service", "error");
        })
        .always(() => {
          this.state.isSubmitting = false;
          this.hideLoading($("#save-service-button"));

          // Reset duplicate prevention flag after successful completion
          setTimeout(() => {
            this.state.preventDuplicateSubmission = false;
          }, 500);
        });
    },

    validateServiceForm: function () {
      let isValid = true;
      this.clearErrors();

      const name = this.elements.serviceName.val().trim();
      const price = parseFloat(this.elements.servicePrice.val());
      const duration = parseInt(this.elements.serviceDuration.val());

      if (!name) {
        this.showFieldError(
          this.elements.serviceName,
          "Service name is required"
        );
        isValid = false;
      }

      if (isNaN(price) || price <= 0) {
        this.showFieldError(
          this.elements.servicePrice,
          "Price must be greater than zero"
        );
        isValid = false;
      }

      if (isNaN(duration) || duration < 15) {
        this.showFieldError(
          this.elements.serviceDuration,
          "Duration must be at least 15 minutes"
        );
        isValid = false;
      }

      return isValid;
    },

    // ===== SERVICE OPTIONS =====
    loadServiceOptions: function (serviceId) {
      if (!serviceId) return;

      const data = {
        action: "mobooking_get_service_options",
        service_id: serviceId,
        nonce: this.config.serviceNonce,
      };

      this.makeAjaxRequest(data)
        .done((response) => {
          if (response.success && response.data.options) {
            this.displayOptions(response.data.options);
          } else {
            this.showNoOptionsMessage();
          }
        })
        .fail(() => {
          this.showNoOptionsMessage();
        });
    },

    displayOptions: function (options) {
      this.elements.optionsContainer.empty();

      if (!options || options.length === 0) {
        this.showNoOptionsMessage();
        return;
      }

      options.forEach((option) => {
        const optionCard = this.createOptionCard(option);
        this.elements.optionsContainer.append(optionCard);
      });
    },

    createOptionCard: function (option) {
      const typeLabels = {
        checkbox: "Checkbox",
        text: "Text Input",
        number: "Number",
        select: "Dropdown",
        radio: "Radio Buttons",
        textarea: "Text Area",
        quantity: "Quantity",
      };

      const typeLabel = typeLabels[option.type] || option.type;
      const isRequired = option.is_required == 1;

      return $(`
        <div class="option-card" data-option-id="${option.id}">
          <div class="option-card-header">
            <div class="option-drag-handle">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="9" cy="12" r="1"></circle>
                <circle cx="9" cy="5" r="1"></circle>
                <circle cx="9" cy="19" r="1"></circle>
                <circle cx="15" cy="12" r="1"></circle>
                <circle cx="15" cy="5" r="1"></circle>
                <circle cx="15" cy="19" r="1"></circle>
              </svg>
            </div>
            <div class="option-content">
              <div class="option-header">
                <h4 class="option-name">${this.escapeHtml(option.name)}</h4>
                <div class="option-badges">
                  <span class="option-type-badge">${typeLabel}</span>
                  ${
                    isRequired
                      ? '<span class="option-required-badge">Required</span>'
                      : ""
                  }
                  ${
                    option.price_impact > 0
                      ? `<span class="option-price-badge">+${option.price_impact}</span>`
                      : ""
                  }
                </div>
              </div>
              ${
                option.description
                  ? `<p class="option-description">${this.escapeHtml(
                      option.description
                    )}</p>`
                  : ""
              }
            </div>
            <div class="option-actions">
              <button type="button" class="btn-icon edit-option-btn" title="Edit Option">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                  <path d="m18.5 2.5-9.5 9.5L4 15l1-4 9.5-9.5 3 3Z"></path>
                </svg>
              </button>
              <button type="button" class="btn-icon delete-option-btn" data-option-id="${
                option.id
              }" title="Delete Option">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="m3 6 3 18h12l3-18"></path>
                  <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                </svg>
              </button>
            </div>
          </div>
        </div>
      `);
    },

    showNoOptionsMessage: function () {
      this.elements.optionsContainer.html(`
        <div class="no-options-message">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
            <path d="M12.5 8.5 16 12l-3.5 3.5M16 12H8m0 0a8 8 0 1 1 8-8 8 8 0 0 1-8 8Z"></path>
          </svg>
          <h3>No options yet</h3>
          <p>Add your first option to let customers customize this service</p>
        </div>
      `);
    },

    showAddOptionModal: function () {
      const serviceId =
        this.config.currentServiceId ||
        new URLSearchParams(window.location.search).get("service_id") ||
        this.elements.serviceId.val();

      if (!serviceId) {
        this.showNotification("Please save the service first", "warning");
        return;
      }

      $("#option-modal-title").text("Add New Option");
      $("#delete-option-btn").hide();
      this.showModal(this.elements.optionModal);
    },

    editOption: function (optionId) {
      // This would integrate with the existing option modal functionality
      $("#option-modal-title").text("Edit Option");
      $("#delete-option-btn").show();
      this.showModal(this.elements.optionModal);
    },

    showDeleteConfirmation: function (type, id) {
      // This would show the delete confirmation modal
      console.log(`Delete ${type} with ID: ${id}`);
    },

    // ===== FILTERS =====
    filterServices: function () {
      const selectedCategory = this.elements.categoryFilter.val();

      if (!selectedCategory) {
        this.elements.serviceCards.show();
        return;
      }

      this.elements.serviceCards.each(function () {
        const cardCategory = $(this).data("category");
        if (cardCategory === selectedCategory) {
          $(this).show();
        } else {
          $(this).hide();
        }
      });
    },

    // ===== UTILITY METHODS =====
    showModal: function (modal) {
      modal.fadeIn(300);
      $("body").addClass("modal-open");
      this.state.optionModalOpen = true;
    },

    hideModals: function () {
      $(".mobooking-modal").fadeOut(300);
      $("body").removeClass("modal-open");
      this.state.optionModalOpen = false;
    },

    showLoading: function (button) {
      if (!button || !button.length) return;
      button.prop("disabled", true).addClass("loading");
    },

    hideLoading: function (button) {
      if (!button || !button.length) return;
      button.prop("disabled", false).removeClass("loading");
    },

    showFieldError: function (element, message) {
      element.addClass("error");
      element.siblings(".error-message").remove();
      element.after(`<div class="error-message">${message}</div>`);
    },

    clearErrors: function () {
      $(".error").removeClass("error");
      $(".error-message").remove();
    },

    showNotification: function (message, type = "info") {
      // Remove existing notifications
      $(".notification").remove();

      const colors = {
        success: "#22c55e",
        error: "#ef4444",
        warning: "#f59e0b",
        info: "#3b82f6",
      };

      const notification = $(`
        <div class="notification notification-${type}" style="
          position: fixed; top: 24px; right: 24px; z-index: 1000;
          display: flex; align-items: center; gap: 12px;
          padding: 16px 20px; border-radius: 8px;
          background: ${colors[type]}; color: white;
          box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
          font-weight: 500; max-width: 400px;
          animation: slideIn 0.3s ease;
        ">${message}</div>
      `);

      $("body").append(notification);

      setTimeout(() => {
        notification.fadeOut(300, function () {
          $(this).remove();
        });
      }, 4000);
    },

    makeAjaxRequest: function (data) {
      return $.ajax({
        url: this.config.ajaxUrl,
        type: "POST",
        data: data,
        processData: !(data instanceof FormData),
        contentType:
          data instanceof FormData
            ? false
            : "application/x-www-form-urlencoded; charset=UTF-8",
      });
    },

    escapeHtml: function (text) {
      if (!text) return "";
      const div = document.createElement("div");
      div.textContent = text;
      return div.innerHTML;
    },
  };

  // Initialize when document is ready
  $(document).ready(function () {
    if ($(".services-section").length > 0) {
      ServiceFormHandler.init();

      // Set as primary handler to prevent conflicts
      if (typeof mobookingConfig === "undefined") {
        window.mobookingConfig = {};
      }
      mobookingConfig.primaryHandler = "service-handler";
    }
  });

  // Add slideIn animation
  if (!document.getElementById("service-handler-styles")) {
    $("<style>")
      .attr("id", "service-handler-styles")
      .prop("type", "text/css")
      .html(
        `
        @keyframes slideIn {
          from { transform: translateX(100%); opacity: 0; }
          to { transform: translateX(0); opacity: 1; }
        }
        
        .tab-switching {
          transform: scale(0.98);
          transition: transform 0.2s ease;
        }
        
        .icon-selected {
          transform: scale(1.2);
          transition: transform 0.3s ease;
        }
        
        .tab-pane {
          animation: fadeInTab 0.3s ease-in-out;
        }
        
        @keyframes fadeInTab {
          from {
            opacity: 0;
            transform: translateY(10px);
          }
          to {
            opacity: 1;
            transform: translateY(0);
          }
        }
        
        .error {
          border-color: #ef4444 !important;
          box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2) !important;
        }
        
        .error-message {
          color: #ef4444;
          font-size: 0.75rem;
          margin-top: 0.25rem;
        }
      `
      )
      .appendTo("head");
  }

  // Make globally available
  window.ServiceFormHandler = ServiceFormHandler;
})(jQuery);
