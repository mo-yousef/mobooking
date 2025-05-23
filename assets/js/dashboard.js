/**
 * MoBooking Dashboard JavaScript - Fixed Duplicate Issues
 * Version: 2.1.0
 */

(function ($) {
  ("use strict");

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
      endpoints:
        (typeof mobookingServices !== "undefined"
          ? mobookingServices.endpoints
          : null) || {},
    },

    // State
    state: {
      currentOptionId: null,
      isSubmitting: false,
      isOptionSubmitting: false, // Add separate flag for options
      deleteTarget: null,
      deleteType: null,
      lastSubmitTime: 0, // Prevent rapid double-clicks
      optionProcessingFlags: {}, // Track processing flags
    },

    // Initialize
    init: function () {
      console.log("🚀 MoBooking Dashboard initializing...");
      this.cacheElements();
      this.attachEventListeners();
      this.initializeComponents();
      console.log("✅ MoBooking Dashboard initialized");
    },

    // Cache DOM elements
    cacheElements: function () {
      this.elements = {
        // Service elements
        serviceForm: $("#service-form"),
        serviceId: $("#service-id"),

        // Option elements
        optionModal: $("#option-modal"),
        optionForm: $("#option-form"),
        optionId: $("#option-id"),
        optionServiceId: $("#option-service-id"),
        optionName: $("#option-name"),
        optionType: $("#option-type"),
        optionDescription: $("#option-description"),
        optionRequired: $("#option-required"),
        optionPriceType: $("#option-price-type"),
        optionPriceImpact: $("#option-price-impact"),
        optionDynamicFields: $("#option-dynamic-fields"),
        optionsContainer: $("#service-options-container"),
        addOptionBtn: $("#add-option-btn"),
        saveOptionBtn: $("#option-form").find('button[type="submit"]'),
        deleteOptionBtn: $("#delete-option-btn"),
        cancelOptionBtn: $("#cancel-option-btn"),

        // UI elements
        tabButtons: $(".tab-button"),
        confirmationModal: $("#confirmation-modal"),
        confirmDeleteBtn: $(".confirm-delete-btn"),
        cancelDeleteBtn: $(".cancel-delete-btn"),
      };
    },

    // Attach event listeners
    attachEventListeners: function () {
      const self = this;

      // Service form submission - prevent multiple submissions
      this.elements.serviceForm.on("submit", function (e) {
        e.preventDefault();
        if (!self.state.isSubmitting) {
          self.handleServiceSubmit();
        }
      });

      // Tab switching
      this.elements.tabButtons.on("click", function () {
        const tabId = $(this).data("tab");
        self.switchTab(tabId);
      });

      // Add new option
      this.elements.addOptionBtn.on("click", function () {
        self.showAddOptionModal();
      });

      // Option form submission - FIXED to prevent duplicates
      this.elements.optionForm.on("submit", function (e) {
        e.preventDefault();

        // Prevent rapid double-clicks
        const now = Date.now();
        if (now - self.state.lastSubmitTime < 1000) {
          console.log("⚠️ Preventing rapid double-click");
          return;
        }

        if (!self.state.isOptionSubmitting) {
          self.state.lastSubmitTime = now;
          self.handleOptionSubmit();
        } else {
          console.log("⚠️ Option submission already in progress");
        }
      });

      // Edit option - ensure we pass the correct ID
      $(document).on("click", ".edit-option-btn", function () {
        const optionCard = $(this).closest(".option-card");
        const optionId = optionCard.data("option-id");
        if (optionId) {
          self.editOption(optionId);
        }
      });

      // Delete option
      $(document).on("click", ".delete-option-btn", function () {
        const optionId = $(this).data("option-id");
        if (optionId) {
          self.showDeleteConfirmation("option", optionId);
        }
      });

      // Service deletion
      $(document).on("click", ".delete-service-btn", function () {
        const serviceId = $(this).data("id");
        self.showDeleteConfirmation("service", serviceId);
      });

      // Option type change
      this.elements.optionType.on("change", function () {
        self.updateDynamicFields($(this).val());
      });

      // Price type change
      this.elements.optionPriceType.on("change", function () {
        self.updatePriceImpactVisibility($(this).val());
      });

      // Modal close events
      $(".modal-close, .cancel-delete-btn, #cancel-option-btn").on(
        "click",
        function () {
          self.hideModals();
        }
      );

      // Confirmation modal
      this.elements.confirmDeleteBtn.on("click", function () {
        self.handleDeleteConfirmation();
      });

      // Close modals on escape key
      $(document).on("keydown", function (e) {
        if (e.key === "Escape") {
          self.hideModals();
        }
      });
    },

    /**
     * Code to add sortable functionality to service options
     * Add this to your dashboard.js file
     */

    // Add this to the initializeComponents function
    initializeComponents: function () {
      if (this.config.currentView === "edit" && this.config.currentServiceId) {
        this.loadServiceOptions(this.config.currentServiceId);
      }

      // Set option service ID if we have it
      const serviceId =
        this.config.currentServiceId ||
        new URLSearchParams(window.location.search).get("service_id") ||
        this.elements.serviceId.val();

      if (serviceId) {
        this.elements.optionServiceId.val(serviceId);
        this.config.currentServiceId = parseInt(serviceId);
      }

      // Initialize sortable - this makes options draggable
      this.initSortableOptions();
    },

    // Add this new method to the MoBookingDashboard object
    initSortableOptions: function () {
      const self = this;

      // Wait for options to be loaded before initializing sortable
      setTimeout(() => {
        if (this.elements.optionsContainer.children().length > 1) {
          this.elements.optionsContainer.sortable({
            handle: ".option-drag-handle",
            placeholder: "option-card-placeholder",
            opacity: 0.7,
            cursor: "grabbing",
            tolerance: "pointer",
            update: function (event, ui) {
              self.updateOptionsOrder();
            },
          });

          console.log("✅ Sortable initialized for options");
        } else {
          console.log("⚠️ Not enough options to make sortable yet");
        }
      }, 500);
    },

    // Also add this method to update the order in the database
    updateOptionsOrder: function () {
      if (!this.config.currentServiceId) return;

      const orderData = [];

      // Get the order of options
      this.elements.optionsContainer
        .find(".option-card")
        .each(function (index) {
          orderData.push({
            id: $(this).data("option-id"),
            order: index + 1,
          });
        });

      // Save the new order
      if (orderData.length < 2) return; // Don't bother if there's only one item

      const data = {
        action: "mobooking_update_options_order",
        service_id: this.config.currentServiceId,
        order_data: JSON.stringify(orderData),
        nonce: this.config.serviceNonce,
      };

      this.makeAjaxRequest(data)
        .done((response) => {
          if (response.success) {
            this.showNotification("Options order updated", "success");
          } else {
            this.showNotification("Failed to update options order", "error");
          }
        })
        .fail(() => {
          this.showNotification("Error updating options order", "error");
        });
    },

    // Add this to the displayOptions method to re-initialize sortable after loading options
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

      // Re-initialize sortable after loading options
      if (options.length > 1) {
        this.initSortableOptions();
      }
    },

    // Also modify the loadServiceOptions method to ensure we have the latest order
    loadServiceOptions: function (serviceId) {
      if (!serviceId) return;

      console.log("🔄 Loading options for service:", serviceId);

      // Disable sorting while loading
      if (this.elements.optionsContainer.hasClass("ui-sortable")) {
        this.elements.optionsContainer.sortable("destroy");
      }

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

    // Handle service form submission
    handleServiceSubmit: function () {
      if (this.state.isSubmitting) return;

      if (!this.validateServiceForm()) return;

      this.state.isSubmitting = true;
      this.showLoading(this.elements.serviceForm.find('button[type="submit"]'));

      const formData = new FormData(this.elements.serviceForm[0]);
      formData.append("action", "mobooking_save_service");
      formData.append("nonce", this.config.serviceNonce);

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
              this.elements.optionServiceId.val(response.data.id);
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
        .fail((xhr) => {
          this.showNotification("Error saving service", "error");
        })
        .always(() => {
          this.state.isSubmitting = false;
          this.hideLoading(
            this.elements.serviceForm.find('button[type="submit"]')
          );
        });
    },

    // Handle option form submission - COMPLETELY FIXED
    handleOptionSubmit: function () {
      console.log("🔄 Option submit started");

      // IMPROVED DUPLICATE PREVENTION
      if (this.state.isOptionSubmitting) {
        console.log("⚠️ Option submission already in progress");
        return;
      }

      // Get the service ID from various possible sources - FIXED: single declaration
      const serviceId =
        this.elements.optionServiceId.val() ||
        this.config.currentServiceId ||
        new URLSearchParams(window.location.search).get("service_id");

      if (!serviceId) {
        this.showNotification("Service ID is missing", "error");
        return;
      }

      // Record the submission attempt in sessionStorage to prevent duplicates across page reloads
      const optionName = this.elements.optionName.val().trim();
      const submissionKey = `option_submission_${serviceId}_${optionName}`;
      const lastSubmission = sessionStorage.getItem(submissionKey);

      // Prevent submission if the same option was submitted in the last 5 seconds
      if (lastSubmission && Date.now() - parseInt(lastSubmission) < 5000) {
        console.log("⚠️ Preventing duplicate submission of the same option");
        this.showNotification(
          "Please wait before submitting the same option again",
          "warning"
        );
        return;
      }

      // Store the submission timestamp
      sessionStorage.setItem(submissionKey, Date.now().toString());

      if (!this.validateOptionForm()) {
        return;
      }

      this.state.isOptionSubmitting = true;
      this.showLoading(this.elements.saveOptionBtn);

      // Get form data
      const formData = new FormData(this.elements.optionForm[0]);

      // Set the service ID and action
      formData.set("service_id", serviceId);
      formData.set("action", "mobooking_save_service_option");
      formData.set("nonce", this.config.serviceNonce);

      // Add a unique request ID to help identify duplicates on server
      const requestId = `${Date.now()}-${Math.random()
        .toString(36)
        .substr(2, 9)}`;
      formData.set("request_id", requestId);

      // Handle option ID for updates vs creates
      const optionId = this.elements.optionId.val();
      if (optionId && optionId !== "") {
        formData.set("id", optionId);
        console.log("🔄 Updating option ID:", optionId);
      } else {
        // Remove any ID field to ensure we create new
        formData.delete("id");
        console.log("🔄 Creating new option");
      }

      // Process choices for select/radio types
      const optionType = this.elements.optionType.val();
      if (optionType === "select" || optionType === "radio") {
        const choices = this.collectChoices();
        formData.set("options", choices);
      }

      console.log("📤 Submitting option with service ID:", serviceId);

      // Use a flag in localStorage to track processing status
      const processingKey = `processing_option_${serviceId}_${optionName}_${requestId}`;
      localStorage.setItem(processingKey, "true");

      // Store this flag in our state object too
      this.state.optionProcessingFlags[processingKey] = true;

      this.makeAjaxRequest(formData)
        .done((response) => {
          console.log("✅ Option save response:", response);
          if (response.success) {
            this.showNotification(
              response.data.message || "Option saved successfully!",
              "success"
            );
            this.hideModals();
            // Reload options to show the update
            this.loadServiceOptions(serviceId);
          } else {
            this.showNotification(
              response.data?.message || "Failed to save option",
              "error"
            );
          }
        })
        .fail((xhr) => {
          console.error("❌ Option save failed:", xhr);
          this.showNotification("Error saving option", "error");
        })
        .always(() => {
          this.state.isOptionSubmitting = false;
          this.hideLoading(this.elements.saveOptionBtn);
          // Remove the processing flag from both localStorage and our state
          localStorage.removeItem(processingKey);
          delete this.state.optionProcessingFlags[processingKey];
        });
    },

    // Validate service form
    validateServiceForm: function () {
      let isValid = true;
      this.clearErrors();

      const name = $("#service-name").val().trim();
      const price = parseFloat($("#service-price").val());
      const duration = parseInt($("#service-duration").val());

      if (!name) {
        this.showFieldError($("#service-name"), "Service name is required");
        isValid = false;
      }

      if (isNaN(price) || price <= 0) {
        this.showFieldError(
          $("#service-price"),
          "Price must be greater than zero"
        );
        isValid = false;
      }

      if (isNaN(duration) || duration < 15) {
        this.showFieldError(
          $("#service-duration"),
          "Duration must be at least 15 minutes"
        );
        isValid = false;
      }

      return isValid;
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
          this.showNotification("At least one choice is required", "error");
          isValid = false;
        }
      }

      return isValid;
    },

    // Load service options
    loadServiceOptions: function (serviceId) {
      if (!serviceId) return;

      console.log("🔄 Loading options for service:", serviceId);

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

    // Display options
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

    // Create option card - IMPROVED DESIGN
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
                
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M11 3.99998H4C3.46957 3.99998 2.96086 4.2107 2.58579 4.58577C2.21071 4.96084 2 5.46955 2 5.99998V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13M18.5 2.49998C18.8978 2.10216 19.4374 1.87866 20 1.87866C20.5626 1.87866 21.1022 2.10216 21.5 2.49998C21.8978 2.89781 22.1213 3.43737 22.1213 3.99998C22.1213 4.56259 21.8978 5.10216 21.5 5.49998L12 15L8 16L9 12L18.5 2.49998Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>

              </button>
              <button type="button" class="btn-icon delete-option-btn" data-option-id="${
                option.id
              }" title="Delete Option">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 6H5M5 6H21M5 6V20C5 20.5304 5.21071 21.0391 5.58579 21.4142C5.96086 21.7893 6.46957 22 7 22H17C17.5304 22 18.0391 21.7893 18.4142 21.4142C18.7893 21.0391 19 20.5304 19 20V6H5ZM8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M10 11V17M14 11V17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
              </button>
            </div>
          </div>
        </div>
      `);
    },

    // Show add option modal - FIXED
    showAddOptionModal: function () {
      const serviceId =
        this.config.currentServiceId ||
        new URLSearchParams(window.location.search).get("service_id") ||
        this.elements.serviceId.val();

      if (!serviceId) {
        this.showNotification("Please save the service first", "warning");
        return;
      }

      console.log("📝 Opening add option modal for service:", serviceId);

      // Reset state
      this.state.currentOptionId = null;

      // Reset form completely
      this.elements.optionForm[0].reset();
      this.elements.optionId.val(""); // Clear the ID field
      this.elements.optionServiceId.val(serviceId); // Set service ID

      // Clear dynamic fields
      this.elements.optionDynamicFields.empty();
      this.clearErrors();

      // Update modal title and hide delete button
      $("#option-modal-title").text("Add New Option");
      this.elements.deleteOptionBtn.hide();

      // Set defaults
      this.updateDynamicFields("checkbox");
      this.updatePriceImpactVisibility("fixed");

      // Show modal
      this.showModal(this.elements.optionModal);
    },

    // Edit option - FIXED
    editOption: function (optionId) {
      if (!optionId) return;

      console.log("✏️ Editing option:", optionId);
      this.state.currentOptionId = optionId;

      const data = {
        action: "mobooking_get_service_option",
        id: optionId,
        nonce: this.config.serviceNonce,
      };

      this.makeAjaxRequest(data)
        .done((response) => {
          if (response.success && response.data.option) {
            this.populateOptionForm(response.data.option);
            $("#option-modal-title").text("Edit Option");
            this.elements.deleteOptionBtn.show();
            this.showModal(this.elements.optionModal);
          } else {
            this.showNotification("Error loading option", "error");
          }
        })
        .fail(() => {
          this.showNotification("Error loading option", "error");
        });
    },

    // Populate option form
    populateOptionForm: function (option) {
      console.log("📝 Populating form with option:", option);

      // Set form values
      this.elements.optionId.val(option.id);
      this.elements.optionServiceId.val(option.service_id);
      this.elements.optionName.val(option.name);
      this.elements.optionDescription.val(option.description || "");
      this.elements.optionType.val(option.type);
      this.elements.optionRequired.val(option.is_required || "0");
      this.elements.optionPriceType.val(option.price_type || "fixed");
      this.elements.optionPriceImpact.val(option.price_impact || "0");

      // Update dynamic fields and visibility
      this.updateDynamicFields(option.type, option);
      this.updatePriceImpactVisibility(option.price_type || "fixed");
    },

    // Update dynamic fields based on option type
    updateDynamicFields: function (type, optionData = {}) {
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

      // Attach events for choice management
      if (type === "select" || type === "radio") {
        this.attachChoiceEvents();
      }
    },

    // Get checkbox fields
    getCheckboxFields: function (optionData) {
      return `
        <div class="form-row">
          <div class="form-group">
            <label for="option-default-value">Default State</label>
            <select id="option-default-value" name="default_value" class="form-control">
              <option value="0" ${
                optionData.default_value != "1" ? "selected" : ""
              }>Unchecked</option>
              <option value="1" ${
                optionData.default_value == "1" ? "selected" : ""
              }>Checked</option>
            </select>
          </div>
          <div class="form-group">
            <label for="option-label">Checkbox Label</label>
            <input type="text" id="option-label" name="option_label" class="form-control" 
                   value="${this.escapeHtml(optionData.option_label || "")}" 
                   placeholder="e.g., Add extra cleaning supplies">
          </div>
        </div>
      `;
    },

    // Get choice fields for select/radio
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
          <label>Options</label>
          <div class="choices-container">
            <div class="choices-list" id="choices-list">
              ${choicesHtml}
            </div>
            <button type="button" class="btn-add-choice" id="add-choice-btn">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 5v14M5 12h14"></path>
              </svg>
              Add Choice
            </button>
          </div>
        </div>
      `;
    },

    // Create choice row
    createChoiceRow: function (value = "", label = "", price = 0) {
      return `
        <div class="choice-row">
          <input type="text" placeholder="Value" value="${this.escapeHtml(
            value
          )}" class="choice-value">
          <input type="text" placeholder="Label" value="${this.escapeHtml(
            label
          )}" class="choice-label">
          <input type="number" placeholder="0.00" value="${price}" step="0.01" class="choice-price">
          <button type="button" class="btn-remove-choice">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6 6 18M6 6l12 12"></path>
            </svg>
          </button>
        </div>
      `;
    },

    // Get number fields
    getNumberFields: function (optionData) {
      return `
        <div class="form-row">
          <div class="form-group">
            <label for="option-min-value">Minimum Value</label>
            <input type="number" id="option-min-value" name="min_value" class="form-control"
                   value="${
                     optionData.min_value !== null ? optionData.min_value : ""
                   }" step="any">
          </div>
          <div class="form-group">
            <label for="option-max-value">Maximum Value</label>
            <input type="number" id="option-max-value" name="max_value" class="form-control"
                   value="${
                     optionData.max_value !== null ? optionData.max_value : ""
                   }" step="any">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="option-default-value">Default Value</label>
            <input type="number" id="option-default-value" name="default_value" class="form-control"
                   value="${optionData.default_value || ""}" step="any">
          </div>
          <div class="form-group">
            <label for="option-step">Step</label>
            <input type="number" id="option-step" name="step" class="form-control"
                   value="${optionData.step || "1"}" step="any">
          </div>
        </div>
      `;
    },

    // Get text fields
    getTextFields: function (optionData) {
      return `
        <div class="form-row">
          <div class="form-group">
            <label for="option-placeholder">Placeholder Text</label>
            <input type="text" id="option-placeholder" name="placeholder" class="form-control"
                   value="${this.escapeHtml(optionData.placeholder || "")}" 
                   placeholder="Enter placeholder text">
          </div>
          <div class="form-group">
            <label for="option-default-value">Default Value</label>
            <input type="text" id="option-default-value" name="default_value" class="form-control"
                   value="${this.escapeHtml(optionData.default_value || "")}">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="option-min-length">Min Length</label>
            <input type="number" id="option-min-length" name="min_length" class="form-control"
                   value="${
                     optionData.min_length !== null ? optionData.min_length : ""
                   }" min="0">
          </div>
          <div class="form-group">
            <label for="option-max-length">Max Length</label>
            <input type="number" id="option-max-length" name="max_length" class="form-control"
                   value="${
                     optionData.max_length !== null ? optionData.max_length : ""
                   }" min="0">
          </div>
        </div>
      `;
    },

    // Get textarea fields
    getTextareaFields: function (optionData) {
      return `
        <div class="form-row">
          <div class="form-group">
            <label for="option-placeholder">Placeholder Text</label>
            <input type="text" id="option-placeholder" name="placeholder" class="form-control"
                   value="${this.escapeHtml(optionData.placeholder || "")}" 
                   placeholder="Enter placeholder text">
          </div>
          <div class="form-group">
            <label for="option-rows">Rows</label>
            <input type="number" id="option-rows" name="rows" class="form-control"
                   value="${optionData.rows || "3"}" min="2" max="10">
          </div>
        </div>
        <div class="form-group">
          <label for="option-default-value">Default Value</label>
          <textarea id="option-default-value" name="default_value" class="form-control" rows="3">${this.escapeHtml(
            optionData.default_value || ""
          )}</textarea>
        </div>
      `;
    },

    // Attach choice events
    attachChoiceEvents: function () {
      const self = this;

      // Add choice
      $("#add-choice-btn")
        .off("click")
        .on("click", function () {
          $("#choices-list").append(self.createChoiceRow());
        });

      // Remove choice
      $(document)
        .off("click", ".btn-remove-choice")
        .on("click", ".btn-remove-choice", function () {
          const choicesCount = $("#choices-list .choice-row").length;
          if (choicesCount <= 1) {
            self.showNotification("At least one choice is required", "warning");
            return;
          }
          $(this).closest(".choice-row").remove();
        });
    },

    // Parse choices from options string
    parseChoices: function (optionsString) {
      if (!optionsString) return [];

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

    // Collect choices data
    collectChoices: function () {
      const choices = [];

      $("#choices-list .choice-row").each(function () {
        const value = $(this).find(".choice-value").val().trim();
        const label = $(this).find(".choice-label").val().trim();
        const price = parseFloat($(this).find(".choice-price").val()) || 0;

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

    // Show no options message
    showNoOptionsMessage: function () {
      this.elements.optionsContainer.html(`
        <div class="no-options-state">
          <div class="no-options-icon">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
              <path d="M12.5 8.5 16 12l-3.5 3.5M16 12H8m0 0a8 8 0 1 1 8-8 8 8 0 0 1-8 8Z"></path>
            </svg>
          </div>
          <h3>No options yet</h3>
          <p>Add your first option to let customers customize this service</p>
        </div>
      `);
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

    // Switch tabs
    switchTab: function (tabId) {
      this.elements.tabButtons.removeClass("active");
      this.elements.tabButtons
        .filter(`[data-tab="${tabId}"]`)
        .addClass("active");
      $(".tab-pane").removeClass("active");
      $(`#${tabId}`).addClass("active");

      const url = new URL(window.location);
      url.searchParams.set("active_tab", tabId);
      window.history.replaceState({}, "", url);
    },

    // Show delete confirmation
    showDeleteConfirmation: function (type, id) {
      this.state.deleteType = type;
      this.state.deleteTarget = id;

      const message =
        type === "service"
          ? "Delete this service and all its options? This cannot be undone."
          : "Delete this option? This cannot be undone.";

      $("#confirmation-message").text(message);
      this.showModal(this.elements.confirmationModal);
    },

    // Handle delete confirmation
    handleDeleteConfirmation: function () {
      if (!this.state.deleteType || !this.state.deleteTarget) {
        this.hideModals();
        return;
      }

      this.showLoading(this.elements.confirmDeleteBtn);

      const action =
        this.state.deleteType === "service"
          ? "mobooking_delete_service"
          : "mobooking_delete_service_option";

      const data = {
        action: action,
        id: this.state.deleteTarget,
        nonce: this.config.serviceNonce,
      };

      this.makeAjaxRequest(data)
        .done((response) => {
          if (response.success) {
            this.showNotification(
              `${this.state.deleteType} deleted successfully`,
              "success"
            );
            this.hideModals();

            if (this.state.deleteType === "service") {
              setTimeout(() => {
                window.location.href = window.location.pathname + "?view=list";
              }, 1000);
            } else {
              this.loadServiceOptions(this.config.currentServiceId);
            }
          } else {
            this.showNotification(
              `Error deleting ${this.state.deleteType}`,
              "error"
            );
            this.hideModals();
          }
        })
        .fail(() => {
          this.showNotification(
            `Error deleting ${this.state.deleteType}`,
            "error"
          );
          this.hideModals();
        })
        .always(() => {
          this.hideLoading(this.elements.confirmDeleteBtn);
        });
    },

    // Show modal
    showModal: function (modal) {
      modal.fadeIn(300);
      $("body").addClass("modal-open");
    },

    // Hide modals
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
      button.prop("disabled", true).addClass("loading");
    },

    // Hide loading state
    hideLoading: function (button) {
      if (!button || !button.length) return;
      button.prop("disabled", false).removeClass("loading");
    },

    // Show field error
    showFieldError: function (element, message) {
      element.addClass("error");
      element.siblings(".error-message").remove();
      element.after(`<div class="error-message">${message}</div>`);
    },

    // Clear errors
    clearErrors: function () {
      $(".error").removeClass("error");
      $(".error-message").remove();
    },

    // Show notification
    showNotification: function (message, type = "info") {
      $(".notification").remove();

      const colors = {
        success: "#22c55e",
        error: "#ef4444",
        warning: "#f59e0b",
        info: "#3b82f6",
      };

      const icons = {
        success: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z"></path><path d="m9 12 2 2 4-4"></path></svg>`,
        error: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="m15 9-6 6M9 9l6 6"></path></svg>`,
        warning: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><path d="M12 9v4M12 17h.01"></path></svg>`,
        info: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="m9 12 2 2 4-4"></path></svg>`,
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
        ">
          ${icons[type]}
          ${message}
        </div>
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
      if (
        typeof data === "object" &&
        !(data instanceof FormData) &&
        !data.nonce
      ) {
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
    if ($(".services-section").length > 0) {
      MoBookingDashboard.init();
    }
  });

  // Add slideIn animation
  $("<style>")
    .prop("type", "text/css")
    .html(
      `
    @keyframes slideIn {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
  `
    )
    .appendTo("head");

  // Make globally available
  window.MoBookingDashboard = MoBookingDashboard;
})(jQuery);
