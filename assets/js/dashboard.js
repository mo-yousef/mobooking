/**
 * MoBooking Dashboard JavaScript
 * Updated for separate tables architecture
 * Version: 2.0.0
 */

(function ($) {
  ("use strict");

  // Global state management
  const MoBookingDashboard = {
    // Configuration
    config: {
      ajaxUrl:
        (typeof mobookingServices !== "undefined"
          ? mobookingServices.ajaxUrl
          : null) ||
        (typeof mobookingDashboard !== "undefined"
          ? mobookingDashboard.ajaxUrl
          : null) ||
        "/wp-admin/admin-ajax.php",
      serviceNonce:
        (typeof mobookingServices !== "undefined"
          ? mobookingServices.serviceNonce
          : null) ||
        (typeof mobookingDashboard !== "undefined"
          ? mobookingDashboard.nonces?.service
          : null) ||
        "",
      currentServiceId:
        (typeof mobookingServices !== "undefined"
          ? mobookingServices.currentServiceId
          : null) || null,
      currentView:
        (typeof mobookingServices !== "undefined"
          ? mobookingServices.currentView
          : null) || "list",
      activeTab:
        (typeof mobookingServices !== "undefined"
          ? mobookingServices.activeTab
          : null) || "basic-info",
      endpoints:
        (typeof mobookingServices !== "undefined"
          ? mobookingServices.endpoints
          : null) || {},
    },

    // State
    state: {
      currentOptionId: null,
      isSubmitting: false,
      deleteTarget: null,
      deleteType: null,
      optionsSortable: null,
      mediaUploader: null,
    },

    // Cache DOM elements
    elements: {
      // Service elements
      serviceForm: null,
      serviceId: null,
      serviceName: null,
      servicePrice: null,
      serviceDuration: null,
      serviceCategory: null,
      serviceStatus: null,
      serviceIcon: null,
      serviceImage: null,
      serviceDescription: null,
      saveServiceBtn: null,
      deleteServiceBtns: null,

      // Option elements
      optionModal: null,
      optionForm: null,
      optionId: null,
      optionName: null,
      optionType: null,
      optionDescription: null,
      optionRequired: null,
      optionPriceType: null,
      optionPriceImpact: null,
      optionDynamicFields: null,
      optionsContainer: null,
      addOptionBtn: null,
      saveOptionBtn: null,
      deleteOptionBtn: null,
      cancelOptionBtn: null,

      // UI elements
      tabButtons: null,
      tabPanes: null,
      confirmationModal: null,
      confirmDeleteBtn: null,
      cancelDeleteBtn: null,
      loadingOverlay: null,
      notification: null,

      // Media elements
      selectImageBtn: null,
      imagePreview: null,
      iconItems: null,
      iconPreview: null,
    },

    // Initialize the dashboard
    init: function () {
      console.log("🚀 MoBooking Dashboard initializing...");

      this.cacheElements();
      this.attachEventListeners();
      this.initializeComponents();

      console.log("✅ MoBooking Dashboard initialized successfully");
    },

    // Cache all DOM elements
    cacheElements: function () {
      const elements = this.elements;

      // Service elements
      elements.serviceForm = $("#service-form");
      elements.serviceId = $("#service-id");
      elements.serviceName = $("#service-name");
      elements.servicePrice = $("#service-price");
      elements.serviceDuration = $("#service-duration");
      elements.serviceCategory = $("#service-category");
      elements.serviceStatus = $("#service-status");
      elements.serviceIcon = $("#service-icon");
      elements.serviceImage = $("#service-image");
      elements.serviceDescription = $("#service-description");
      elements.saveServiceBtn = $("#save-service-button");
      elements.deleteServiceBtns = $(".delete-service-btn");

      // Option elements
      elements.optionModal = $("#option-modal");
      elements.optionForm = $("#option-form");
      elements.optionId = $("#option-id");
      elements.optionName = $("#option-name");
      elements.optionType = $("#option-type");
      elements.optionDescription = $("#option-description");
      elements.optionRequired = $("#option-required");
      elements.optionPriceType = $("#option-price-type");
      elements.optionPriceImpact = $("#option-price-impact");
      elements.optionDynamicFields = $("#option-dynamic-fields");
      elements.optionsContainer = $("#service-options-container");
      elements.addOptionBtn = $("#add-option-btn");
      elements.saveOptionBtn = elements.optionForm.find(
        'button[type="submit"]'
      );
      elements.deleteOptionBtn = $("#delete-option-btn");
      elements.cancelOptionBtn = $("#cancel-option-btn");

      // UI elements
      elements.tabButtons = $(".tab-button");
      elements.tabPanes = $(".tab-pane");
      elements.confirmationModal = $("#confirmation-modal");
      elements.confirmDeleteBtn = $(".confirm-delete-btn");
      elements.cancelDeleteBtn = $(".cancel-delete-btn");
      elements.loadingOverlay = $("#loading-overlay");
      elements.notification = $("#mobooking-notification");

      // Media elements
      elements.selectImageBtn = $(".select-image");
      elements.imagePreview = $(".image-preview");
      elements.iconItems = $(".icon-item");
      elements.iconPreview = $(".icon-preview");
    },

    // Attach all event listeners
    attachEventListeners: function () {
      const self = this;

      // Service form submission
      this.elements.serviceForm.on("submit", function (e) {
        e.preventDefault();
        self.handleServiceSubmit();
      });

      // Tab switching
      this.elements.tabButtons.on("click", function () {
        const tabId = $(this).data("tab");
        self.switchTab(tabId);
      });

      // Service deletion
      $(document).on("click", ".delete-service-btn", function () {
        const serviceId = $(this).data("id");
        self.showDeleteConfirmation("service", serviceId);
      });

      // Add new option
      this.elements.addOptionBtn.on("click", function () {
        self.showAddOptionModal();
      });

      // Option form submission
      this.elements.optionForm.on("submit", function (e) {
        e.preventDefault();
        self.handleOptionSubmit();
      });

      // Edit option
      $(document).on("click", ".edit-option-btn", function () {
        const optionCard = $(this).closest(".option-card");
        const optionId = optionCard.data("option-id");
        self.editOption(optionId);
      });

      // Delete option
      $(document).on("click", ".delete-option-btn", function () {
        const optionId = $(this).data("option-id");
        self.showDeleteConfirmation("option", optionId);
      });

      // Option type change
      this.elements.optionType.on("change", function () {
        self.updateDynamicFields($(this).val());
      });

      // Price type change
      this.elements.optionPriceType.on("change", function () {
        self.updatePriceImpactVisibility($(this).val());
      });

      // Cancel option editing
      this.elements.cancelOptionBtn.on("click", function () {
        self.hideModals();
      });

      // Delete option (from modal)
      this.elements.deleteOptionBtn.on("click", function () {
        const optionId = self.elements.optionId.val();
        if (optionId) {
          self.showDeleteConfirmation("option", optionId);
        }
      });

      // Modal close events
      $(".modal-close, .cancel-delete-btn").on("click", function () {
        self.hideModals();
      });

      // Confirmation modal
      this.elements.confirmDeleteBtn.on("click", function () {
        self.handleDeleteConfirmation();
      });

      // Icon selection
      this.elements.iconItems.on("click", function () {
        const icon = $(this).data("icon");
        self.selectIcon(icon);
      });

      // Media uploader
      this.elements.selectImageBtn.on("click", function (e) {
        e.preventDefault();
        self.openMediaUploader();
      });

      // Close modals on escape key
      $(document).on("keydown", function (e) {
        if (e.key === "Escape") {
          self.hideModals();
        }
      });

      // Close modals when clicking outside
      $(".mobooking-modal").on("click", function (e) {
        if (e.target === this) {
          self.hideModals();
        }
      });
    },

    // Initialize components
    initializeComponents: function () {
      // Initialize sortable for options if we're editing a service
      if (this.config.currentView === "edit" && this.config.currentServiceId) {
        this.initializeOptionsSortable();
        this.loadServiceOptions(this.config.currentServiceId);
      }

      // Initialize media uploader if WordPress media is available
      if (typeof wp !== "undefined" && wp.media) {
        this.initializeMediaUploader();
      }

      // Set initial tab if specified
      if (this.config.activeTab && this.config.activeTab !== "basic-info") {
        this.switchTab(this.config.activeTab);
      }

      // Update price impact visibility based on current selection
      if (this.elements.optionPriceType.length) {
        this.updatePriceImpactVisibility(this.elements.optionPriceType.val());
      }
    },

    // Handle service form submission
    handleServiceSubmit: function () {
      if (this.state.isSubmitting) {
        return;
      }

      console.log("🔄 Submitting service form...");

      if (!this.validateServiceForm()) {
        return;
      }

      this.state.isSubmitting = true;
      this.showLoading(this.elements.saveServiceBtn);

      const formData = new FormData(this.elements.serviceForm[0]);
      formData.append(
        "action",
        this.config.endpoints.saveService || "mobooking_save_service"
      );

      this.makeAjaxRequest(formData)
        .done((response) => {
          this.handleServiceSaveSuccess(response);
        })
        .fail((xhr) => {
          this.handleServiceSaveError(xhr);
        })
        .always(() => {
          this.state.isSubmitting = false;
          this.hideLoading(this.elements.saveServiceBtn);
        });
    },

    // Validate service form
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

    // Handle successful service save
    handleServiceSaveSuccess: function (response) {
      console.log("✅ Service saved successfully:", response);

      if (response.success) {
        this.showNotification(
          response.data.message || "Service saved successfully!",
          "success"
        );

        // If this was a new service, update the state and URL
        if (response.data.id && !this.config.currentServiceId) {
          this.config.currentServiceId = response.data.id;
          this.elements.serviceId.val(response.data.id);
          this.elements.optionForm
            .find("#option-service-id")
            .val(response.data.id);

          // Enable the add option button
          this.elements.addOptionBtn
            .prop("disabled", false)
            .removeAttr("title");

          // Update URL
          const newUrl =
            window.location.pathname +
            "?view=edit&service_id=" +
            response.data.id +
            "&active_tab=options";
          window.history.pushState({}, "", newUrl);

          // Switch to options tab
          this.switchTab("options");
        }
      } else {
        this.handleServiceSaveError(
          response.data?.message || "Failed to save service"
        );
      }
    },

    // Handle service save error
    handleServiceSaveError: function (error) {
      console.error("❌ Service save error:", error);

      let message = "Error saving service";
      if (typeof error === "string") {
        message = error;
      } else if (error.responseJSON?.data?.message) {
        message = error.responseJSON.data.message;
      } else if (error.statusText) {
        message = "Error: " + error.statusText;
      }

      this.showNotification(message, "error");
    },

    // Load service options
    loadServiceOptions: function (serviceId) {
      if (!serviceId) {
        console.warn("⚠️ No service ID provided for loading options");
        return;
      }

      console.log("🔄 Loading options for service:", serviceId);

      const data = {
        action:
          this.config.endpoints.getOptions || "mobooking_get_service_options",
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
        .fail((xhr) => {
          console.error("❌ Error loading options:", xhr);
          this.showNoOptionsMessage();
        });
    },

    // Display options in the container
    displayOptions: function (options) {
      console.log("📋 Displaying options:", options);

      this.elements.optionsContainer.empty();

      if (!options || options.length === 0) {
        this.showNoOptionsMessage();
        return;
      }

      options.forEach((option) => {
        const optionCard = this.createOptionCard(option);
        this.elements.optionsContainer.append(optionCard);
      });

      // Reinitialize sortable
      this.initializeOptionsSortable();
    },

    // Create option card HTML
    createOptionCard: function (option) {
      const optionTypes = {
        checkbox: "Checkbox",
        text: "Text Input",
        number: "Number Input",
        select: "Dropdown Select",
        radio: "Radio Buttons",
        textarea: "Text Area",
        quantity: "Quantity Selector",
      };

      const typeLabel = optionTypes[option.type] || option.type;
      const requiredBadge =
        option.is_required == 1
          ? '<span class="option-required">Required</span>'
          : "";

      return $(`
                <div class="option-card" data-option-id="${option.id}">
                    <div class="option-card-header">
                        <div class="option-drag-handle">
                            <span class="dashicons dashicons-menu"></span>
                        </div>
                        <div class="option-title">
                            <span class="option-name">${this.escapeHtml(
                              option.name
                            )}</span>
                            <span class="option-type">${this.escapeHtml(
                              typeLabel
                            )}</span>
                            ${requiredBadge}
                        </div>
                        <div class="option-actions">
                            <button type="button" class="button button-small edit-option-btn">
                                <span class="dashicons dashicons-edit"></span>
                            </button>
                            <button type="button" class="button button-small delete-option-btn" data-option-id="${
                              option.id
                            }">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    </div>
                </div>
            `);
    },

    // Show no options message
    showNoOptionsMessage: function () {
      this.elements.optionsContainer.html(`
                <div class="no-options-message">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <p>No options configured yet. Add your first option to customize this service.</p>
                </div>
            `);
    },

    // Show add option modal
    showAddOptionModal: function () {
      if (!this.config.currentServiceId) {
        this.showNotification(
          "Please save the service first before adding options",
          "warning"
        );
        return;
      }

      console.log("📝 Opening add option modal");

      this.state.currentOptionId = null;
      this.resetOptionForm();
      this.elements.optionModal
        .find("#option-modal-title")
        .text("Add New Option");
      this.elements.deleteOptionBtn.hide();
      this.updateDynamicFields("checkbox");
      this.updatePriceImpactVisibility("fixed");
      this.showModal(this.elements.optionModal);
    },

    // Edit option
    editOption: function (optionId) {
      if (!optionId) {
        console.warn("⚠️ No option ID provided for editing");
        return;
      }

      console.log("✏️ Editing option:", optionId);

      this.state.currentOptionId = optionId;

      const data = {
        action:
          this.config.endpoints.getOption || "mobooking_get_service_option",
        id: optionId,
        nonce: this.config.serviceNonce,
      };

      this.makeAjaxRequest(data)
        .done((response) => {
          if (response.success && response.data.option) {
            this.populateOptionForm(response.data.option);
            this.elements.optionModal
              .find("#option-modal-title")
              .text("Edit Option");
            this.elements.deleteOptionBtn.show();
            this.showModal(this.elements.optionModal);
          } else {
            this.showNotification("Error loading option data", "error");
          }
        })
        .fail((xhr) => {
          console.error("❌ Error loading option:", xhr);
          this.showNotification("Error loading option data", "error");
        });
    },

    // Reset option form
    resetOptionForm: function () {
      this.elements.optionForm[0].reset();
      this.elements.optionId.val("");
      this.elements.optionForm
        .find("#option-service-id")
        .val(this.config.currentServiceId);
      this.elements.optionDynamicFields.empty();
      this.clearErrors();
    },

    // Populate option form with data
    populateOptionForm: function (option) {
      console.log("📝 Populating option form:", option);

      this.elements.optionId.val(option.id);
      this.elements.optionForm
        .find("#option-service-id")
        .val(option.service_id);
      this.elements.optionName.val(option.name);
      this.elements.optionDescription.val(option.description || "");
      this.elements.optionType.val(option.type);
      this.elements.optionRequired.val(option.is_required || "0");
      this.elements.optionPriceType.val(option.price_type || "fixed");
      this.elements.optionPriceImpact.val(option.price_impact || "0");

      this.updateDynamicFields(option.type, option);
      this.updatePriceImpactVisibility(option.price_type || "fixed");
    },

    // Update dynamic fields based on option type
    updateDynamicFields: function (type, optionData = {}) {
      console.log("🔄 Updating dynamic fields for type:", type);

      const container = this.elements.optionDynamicFields;
      container.empty();

      let fieldsHtml = "";

      switch (type) {
        case "checkbox":
          fieldsHtml = this.getCheckboxFields(optionData);
          break;
        case "select":
        case "radio":
          fieldsHtml = this.getChoiceFields(optionData);
          break;
        case "number":
        case "quantity":
          fieldsHtml = this.getNumberFields(optionData);
          break;
        case "text":
          fieldsHtml = this.getTextFields(optionData);
          break;
        case "textarea":
          fieldsHtml = this.getTextareaFields(optionData);
          break;
      }

      container.html(fieldsHtml);

      // Attach events for choice management if needed
      if (type === "select" || type === "radio") {
        this.attachChoiceEvents();
      }
    },

    // Get checkbox fields HTML
    getCheckboxFields: function (optionData) {
      return `
                <div class="form-row">
                    <div class="form-group half">
                        <label for="option-default-value">Default Value</label>
                        <select id="option-default-value" name="default_value">
                            <option value="0" ${
                              optionData.default_value != "1" ? "selected" : ""
                            }>Unchecked</option>
                            <option value="1" ${
                              optionData.default_value == "1" ? "selected" : ""
                            }>Checked</option>
                        </select>
                    </div>
                    <div class="form-group half">
                        <label for="option-label">Option Label</label>
                        <input type="text" id="option-label" name="option_label" value="${this.escapeHtml(
                          optionData.option_label || ""
                        )}" placeholder="Check this box to add...">
                    </div>
                </div>
            `;
    },

    // Get choice fields HTML (for select/radio)
    getChoiceFields: function (optionData) {
      const choices = this.parseChoices(optionData.options || "");

      let choicesHtml = "";
      if (choices.length === 0) {
        choicesHtml = this.createChoiceRow();
      } else {
        choices.forEach((choice) => {
          choicesHtml += this.createChoiceRow(
            choice.value,
            choice.label,
            choice.price
          );
        });
      }

      return `
                <div class="form-group">
                    <label>Choices</label>
                    <div class="choices-container">
                        <div class="choices-list" id="choices-list">
                            ${choicesHtml}
                        </div>
                        <button type="button" class="button button-secondary" id="add-choice-btn">Add Choice</button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="option-default-value">Default Value</label>
                    <input type="text" id="option-default-value" name="default_value" value="${this.escapeHtml(
                      optionData.default_value || ""
                    )}" placeholder="Enter the value of the default choice">
                </div>
            `;
    },

    // Get number fields HTML
    getNumberFields: function (optionData) {
      return `
                <div class="form-row">
                    <div class="form-group half">
                        <label for="option-min-value">Minimum Value</label>
                        <input type="number" id="option-min-value" name="min_value" value="${
                          optionData.min_value !== null
                            ? optionData.min_value
                            : ""
                        }" step="any">
                    </div>
                    <div class="form-group half">
                        <label for="option-max-value">Maximum Value</label>
                        <input type="number" id="option-max-value" name="max_value" value="${
                          optionData.max_value !== null
                            ? optionData.max_value
                            : ""
                        }" step="any">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group half">
                        <label for="option-default-value">Default Value</label>
                        <input type="number" id="option-default-value" name="default_value" value="${
                          optionData.default_value || ""
                        }" step="any">
                    </div>
                    <div class="form-group half">
                        <label for="option-step">Step</label>
                        <input type="number" id="option-step" name="step" value="${
                          optionData.step || "1"
                        }" step="any">
                    </div>
                </div>
                <div class="form-group">
                    <label for="option-unit">Unit Label</label>
                    <input type="text" id="option-unit" name="unit" value="${this.escapeHtml(
                      optionData.unit || ""
                    )}" placeholder="e.g., hours, sq ft">
                </div>
            `;
    },

    // Get text fields HTML
    getTextFields: function (optionData) {
      return `
                <div class="form-row">
                    <div class="form-group half">
                        <label for="option-default-value">Default Value</label>
                        <input type="text" id="option-default-value" name="default_value" value="${this.escapeHtml(
                          optionData.default_value || ""
                        )}">
                    </div>
                    <div class="form-group half">
                        <label for="option-placeholder">Placeholder</label>
                        <input type="text" id="option-placeholder" name="placeholder" value="${this.escapeHtml(
                          optionData.placeholder || ""
                        )}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group half">
                        <label for="option-min-length">Minimum Length</label>
                        <input type="number" id="option-min-length" name="min_length" value="${
                          optionData.min_length !== null
                            ? optionData.min_length
                            : ""
                        }" min="0">
                    </div>
                    <div class="form-group half">
                        <label for="option-max-length">Maximum Length</label>
                        <input type="number" id="option-max-length" name="max_length" value="${
                          optionData.max_length !== null
                            ? optionData.max_length
                            : ""
                        }" min="0">
                    </div>
                </div>
            `;
    },

    // Get textarea fields HTML
    getTextareaFields: function (optionData) {
      return `
                <div class="form-group">
                    <label for="option-default-value">Default Value</label>
                    <textarea id="option-default-value" name="default_value" rows="3">${this.escapeHtml(
                      optionData.default_value || ""
                    )}</textarea>
                </div>
                <div class="form-row">
                    <div class="form-group half">
                        <label for="option-placeholder">Placeholder</label>
                        <input type="text" id="option-placeholder" name="placeholder" value="${this.escapeHtml(
                          optionData.placeholder || ""
                        )}">
                    </div>
                    <div class="form-group half">
                        <label for="option-rows">Rows</label>
                        <input type="number" id="option-rows" name="rows" value="${
                          optionData.rows || "3"
                        }" min="2">
                    </div>
                </div>
                <div class="form-group">
                    <label for="option-max-length">Maximum Length</label>
                    <input type="number" id="option-max-length" name="max_length" value="${
                      optionData.max_length !== null
                        ? optionData.max_length
                        : ""
                    }" min="0">
                </div>
            `;
    },

    // Create choice row HTML
    createChoiceRow: function (value = "", label = "", price = 0) {
      return `
                <div class="choice-row">
                    <div class="choice-value">
                        <input type="text" placeholder="Value" value="${this.escapeHtml(
                          value
                        )}">
                    </div>
                    <div class="choice-label">
                        <input type="text" placeholder="Label" value="${this.escapeHtml(
                          label
                        )}">
                    </div>
                    <div class="choice-price">
                        <input type="number" placeholder="0.00" value="${price}" step="0.01">
                    </div>
                    <div class="choice-actions">
                        <button type="button" class="remove-choice-btn">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
            `;
    },

    // Attach events for choice management
    attachChoiceEvents: function () {
      const self = this;

      // Add choice button
      $("#add-choice-btn")
        .off("click")
        .on("click", function () {
          $("#choices-list").append(self.createChoiceRow());
        });

      // Remove choice button
      $(document)
        .off("click", ".remove-choice-btn")
        .on("click", ".remove-choice-btn", function () {
          const choicesCount = $("#choices-list .choice-row").length;
          if (choicesCount <= 1) {
            self.showNotification(
              "You must have at least one choice",
              "warning"
            );
            return;
          }
          $(this).closest(".choice-row").remove();
        });
    },

    // Parse choices from options string
    parseChoices: function (optionsString) {
      if (!optionsString) {
        return [];
      }

      const choices = [];
      const lines = optionsString.split("\n");

      lines.forEach((line) => {
        line = line.trim();
        if (!line) return;

        const parts = line.split("|");
        const value = parts[0]?.trim() || "";

        let label = value;
        let price = 0;

        if (parts[1]) {
          const labelPriceParts = parts[1].split(":");
          label = labelPriceParts[0]?.trim() || value;
          price = parseFloat(labelPriceParts[1]) || 0;
        }

        choices.push({ value, label, price });
      });

      return choices;
    },

    // Handle option form submission
    handleOptionSubmit: function () {
      console.log("🔄 Submitting option form...");

      if (!this.validateOptionForm()) {
        return;
      }

      this.showLoading(this.elements.saveOptionBtn);

      const formData = new FormData(this.elements.optionForm[0]);
      formData.append(
        "action",
        this.config.endpoints.saveOption || "mobooking_save_service_option"
      );

      // Process choices for select/radio types
      const optionType = this.elements.optionType.val();
      if (optionType === "select" || optionType === "radio") {
        const choices = this.collectChoices();
        formData.append("options", choices);
      }

      this.makeAjaxRequest(formData)
        .done((response) => {
          this.handleOptionSaveSuccess(response);
        })
        .fail((xhr) => {
          this.handleOptionSaveError(xhr);
        })
        .always(() => {
          this.hideLoading(this.elements.saveOptionBtn);
        });
    },

    // Validate option form
    validateOptionForm: function () {
      let isValid = true;
      this.clearErrors();

      const name = this.elements.optionName.val().trim();
      if (!name) {
        this.showFieldError(
          this.elements.optionName,
          "Option name is required"
        );
        isValid = false;
      }

      const optionType = this.elements.optionType.val();
      if (optionType === "select" || optionType === "radio") {
        const choices = this.collectChoices();
        if (!choices || choices.trim() === "") {
          this.showNotification(
            "At least one choice with a value is required",
            "error"
          );
          isValid = false;
        }
      }

      return isValid;
    },

    // Collect choices data
    collectChoices: function () {
      const choices = [];

      $("#choices-list .choice-row").each(function () {
        const value = $(this).find("input").eq(0).val().trim();
        const label = $(this).find("input").eq(1).val().trim();
        const price = parseFloat($(this).find("input").eq(2).val()) || 0;

        if (value) {
          if (price > 0) {
            choices.push(value + "|" + label + ":" + price);
          } else {
            choices.push(value + "|" + label);
          }
        }
      });

      return choices.join("\n");
    },

    // Handle successful option save
    handleOptionSaveSuccess: function (response) {
      console.log("✅ Option saved successfully:", response);

      if (response.success) {
        this.showNotification(
          response.data.message || "Option saved successfully!",
          "success"
        );
        this.hideModals();
        this.loadServiceOptions(this.config.currentServiceId);
      } else {
        this.handleOptionSaveError(
          response.data?.message || "Failed to save option"
        );
      }
    },

    // Handle option save error
    handleOptionSaveError: function (error) {
      console.error("❌ Option save error:", error);

      let message = "Error saving option";
      if (typeof error === "string") {
        message = error;
      } else if (error.responseJSON?.data?.message) {
        message = error.responseJSON.data.message;
      } else if (error.statusText) {
        message = "Error: " + error.statusText;
      }

      this.showNotification(message, "error");
    },

    // Update price impact visibility
    updatePriceImpactVisibility: function (priceType) {
      const group = $("#price-impact-group");
      if (priceType === "none") {
        group.hide();
      } else {
        group.show();
      }
    },

    // Initialize options sortable
    initializeOptionsSortable: function () {
      if (!$.fn.sortable || this.state.optionsSortable) {
        return;
      }

      if (this.elements.optionsContainer.children(".option-card").length > 0) {
        this.elements.optionsContainer.sortable({
          handle: ".option-drag-handle",
          items: ".option-card",
          placeholder: "option-card-placeholder",
          axis: "y",
          opacity: 0.8,
          update: () => {
            this.updateOptionsOrder();
          },
        });

        this.state.optionsSortable = true;
        console.log("✅ Options sortable initialized");
      }
    },

    // Update options order
    updateOptionsOrder: function () {
      const orderData = [];

      this.elements.optionsContainer
        .find(".option-card")
        .each(function (index) {
          orderData.push({
            id: $(this).data("option-id"),
            order: index,
          });
        });

      const data = {
        action:
          this.config.endpoints.updateOptionsOrder ||
          "mobooking_update_options_order",
        service_id: this.config.currentServiceId,
        order_data: JSON.stringify(orderData),
        nonce: this.config.serviceNonce,
      };

      this.makeAjaxRequest(data)
        .done((response) => {
          if (response.success) {
            console.log("✅ Options order updated");
          }
        })
        .fail((xhr) => {
          console.error("❌ Error updating options order:", xhr);
        });
    },

    // Initialize media uploader
    initializeMediaUploader: function () {
      if (typeof wp === "undefined" || !wp.media) {
        return;
      }

      console.log("📷 Initializing media uploader");
    },

    // Open media uploader
    openMediaUploader: function () {
      if (!this.state.mediaUploader) {
        this.state.mediaUploader = wp.media({
          title: "Choose Image",
          button: { text: "Select" },
          multiple: false,
        });

        this.state.mediaUploader.on("select", () => {
          const attachment = this.state.mediaUploader
            .state()
            .get("selection")
            .first()
            .toJSON();

          this.elements.serviceImage.val(attachment.url);
          this.elements.imagePreview.html(
            `<img src="${attachment.url}" alt="">`
          );
        });
      }

      this.state.mediaUploader.open();
    },

    // Switch tabs
    switchTab: function (tabId) {
      console.log("🔄 Switching to tab:", tabId);

      this.elements.tabButtons.removeClass("active");
      this.elements.tabButtons
        .filter(`[data-tab="${tabId}"]`)
        .addClass("active");
      this.elements.tabPanes.removeClass("active");
      $(`#${tabId}`).addClass("active");

      // Update URL without reload
      const url = new URL(window.location);
      url.searchParams.set("active_tab", tabId);
      window.history.replaceState({}, "", url);
    },

    // Select icon
    selectIcon: function (icon) {
      this.elements.serviceIcon.val(icon);
      this.elements.iconPreview.html(`<span class="dashicons ${icon}"></span>`);
    },

    // Show delete confirmation
    showDeleteConfirmation: function (type, id) {
      console.log("⚠️ Showing delete confirmation for:", type, id);

      this.state.deleteType = type;
      this.state.deleteTarget = id;

      const message =
        type === "service"
          ? "Are you sure you want to delete this service? This will also delete all its options and cannot be undone."
          : "Are you sure you want to delete this option? This action cannot be undone.";

      this.elements.confirmationModal
        .find("#confirmation-message")
        .text(message);
      this.showModal(this.elements.confirmationModal);
    },

    // Handle delete confirmation
    handleDeleteConfirmation: function () {
      if (!this.state.deleteType || !this.state.deleteTarget) {
        this.hideModals();
        return;
      }

      console.log(
        "🗑️ Executing delete:",
        this.state.deleteType,
        this.state.deleteTarget
      );

      this.showLoading(this.elements.confirmDeleteBtn);

      if (this.state.deleteType === "service") {
        this.deleteService(this.state.deleteTarget);
      } else if (this.state.deleteType === "option") {
        this.deleteOption(this.state.deleteTarget);
      }
    },

    // Delete service
    deleteService: function (serviceId) {
      const data = {
        action:
          this.config.endpoints.deleteService || "mobooking_delete_service",
        id: serviceId,
        nonce: this.config.serviceNonce,
      };

      this.makeAjaxRequest(data)
        .done((response) => {
          if (response.success) {
            this.showNotification("Service deleted successfully", "success");
            setTimeout(() => {
              window.location.href = window.location.pathname + "?view=list";
            }, 1000);
          } else {
            this.showNotification("Error deleting service", "error");
            this.hideModals();
            this.hideLoading(this.elements.confirmDeleteBtn);
          }
        })
        .fail((xhr) => {
          console.error("❌ Error deleting service:", xhr);
          this.showNotification("Error deleting service", "error");
          this.hideModals();
          this.hideLoading(this.elements.confirmDeleteBtn);
        });
    },

    // Delete option
    deleteOption: function (optionId) {
      const data = {
        action:
          this.config.endpoints.deleteOption ||
          "mobooking_delete_service_option",
        id: optionId,
        nonce: this.config.serviceNonce,
      };

      this.makeAjaxRequest(data)
        .done((response) => {
          if (response.success) {
            this.showNotification("Option deleted successfully", "success");
            this.hideModals();
            this.loadServiceOptions(this.config.currentServiceId);
          } else {
            this.showNotification("Error deleting option", "error");
            this.hideModals();
            this.hideLoading(this.elements.confirmDeleteBtn);
          }
        })
        .fail((xhr) => {
          console.error("❌ Error deleting option:", xhr);
          this.showNotification("Error deleting option", "error");
          this.hideModals();
          this.hideLoading(this.elements.confirmDeleteBtn);
        });
    },

    // Show modal
    showModal: function (modal) {
      modal.fadeIn(300);
      $("body").addClass("modal-open");
    },

    // Hide all modals
    hideModals: function () {
      $(".mobooking-modal").fadeOut(300);
      $("body").removeClass("modal-open");
      this.state.deleteType = null;
      this.state.deleteTarget = null;
      this.state.currentOptionId = null;
    },

    // Show loading state
    showLoading: function (button) {
      if (!button || !button.length) return;

      const normalState = button.find(".normal-state");
      const loadingState = button.find(".loading-state");

      if (normalState.length && loadingState.length) {
        normalState.hide();
        loadingState.show();
      } else {
        button.data("original-text", button.text());
        button.text("Loading...");
      }

      button.prop("disabled", true);
    },

    // Hide loading state
    hideLoading: function (button) {
      if (!button || !button.length) return;

      const normalState = button.find(".normal-state");
      const loadingState = button.find(".loading-state");

      if (normalState.length && loadingState.length) {
        normalState.show();
        loadingState.hide();
      } else if (button.data("original-text")) {
        button.text(button.data("original-text"));
      }

      button.prop("disabled", false);
    },

    // Show field error
    showFieldError: function (element, message) {
      element.addClass("has-error");
      element.parent().find(".field-error").remove();
      element.after(`<div class="field-error">${message}</div>`);
    },

    // Clear all errors
    clearErrors: function () {
      $(".has-error").removeClass("has-error");
      $(".field-error").remove();
    },

    // Show notification
    showNotification: function (message, type = "info") {
      console.log(`📢 Notification [${type}]:`, message);

      // Remove existing notifications
      this.elements.notification.remove();
      $("#mobooking-notification").remove();

      const colors = {
        success: "#4CAF50",
        error: "#f44336",
        warning: "#ff9800",
        info: "#2196F3",
      };

      const notification = $(`
                <div id="mobooking-notification" class="mobooking-notification ${type}" style="
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
                    animation: slideInRight 0.3s ease;
                ">${message}</div>
            `);

      $("body").append(notification);

      setTimeout(() => {
        notification.fadeOut(300, function () {
          $(this).remove();
        });
      }, 4000);
    },

    // Make AJAX request
    makeAjaxRequest: function (data) {
      // Ensure nonce is included
      if (typeof data === "object" && !data.nonce) {
        data.nonce = this.config.serviceNonce;
      }

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

    // Escape HTML
    escapeHtml: function (text) {
      if (!text) return "";
      const div = document.createElement("div");
      div.textContent = text;
      return div.innerHTML;
    },
  };

  // Initialize when document is ready
  $(document).ready(function () {
    // Only initialize on services pages
    if ($(".services-section").length > 0) {
      MoBookingDashboard.init();
    }
  });

  // Make globally available for debugging
  window.MoBookingDashboard = MoBookingDashboard;
})(jQuery);
