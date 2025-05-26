/**
 * MoBooking Enhanced Frontend Booking Form Handler
 * Complete booking form with improved ZIP validation, service flow, and navigation
 */

(function ($) {
  "use strict";

  const BookingForm = {
    // Configuration
    config: {
      ajaxUrl: mobookingBooking?.ajaxUrl || "/wp-admin/admin-ajax.php",
      userId: mobookingBooking?.userId || 0,
      nonces: mobookingBooking?.nonces || {},
      strings: mobookingBooking?.strings || {},
      currency: mobookingBooking?.currency || { symbol: "$", position: "left" },
      zipDebounceDelay: 800, // Debounce delay for ZIP validation
    },

    // State
    state: {
      currentStep: 1,
      totalSteps: 6,
      isProcessing: false,
      selectedServices: [],
      serviceOptions: {},
      servicesData: {},
      pricing: {
        subtotal: 0,
        discount: 0,
        total: 0,
      },
      customerData: {},
      isZipValid: false,
      zipDebounceTimer: null,
      debugMode: false,
    },

    // Initialize
    init: function () {
      if ($(".mobooking-booking-form-container").length === 0) {
        return;
      }

      this.state.debugMode = window.location.search.includes("debug=1");
      this.log("🚀 Enhanced Booking Form initializing...");

      if (!this.validateConfig()) {
        return;
      }

      this.cacheElements();
      this.loadServicesData();
      this.attachEventListeners();
      this.initializeForm();
      this.log("✅ Enhanced Booking Form initialized");
    },

    // Validate configuration
    validateConfig: function () {
      const issues = [];

      if (!this.config.ajaxUrl) issues.push("AJAX URL not configured");
      if (!this.config.userId || this.config.userId === "0")
        issues.push("User ID not configured");
      if (!this.config.nonces || !this.config.nonces.booking)
        issues.push("Security nonce not configured");

      if (issues.length > 0) {
        console.error("MoBooking Configuration Issues:", issues);
        this.showNotification(
          "Form configuration error. Please contact support.",
          "error"
        );
        return false;
      }

      return true;
    },

    // Enhanced logging
    log: function (...args) {
      if (this.state.debugMode) {
        console.log("MoBooking:", ...args);
      }
    },

    // Cache DOM elements
    cacheElements: function () {
      this.elements = {
        container: $(".mobooking-booking-form-container"),
        form: $("#mobooking-booking-form"),
        steps: $(".booking-step"),
        progressBar: $(".progress-fill"),
        progressSteps: $(".progress-steps .step"),

        // Step 1 - ZIP Code (Streamlined)
        zipInput: $("#customer_zip_code"),
        zipInputGroup: $(".zip-input-group"),
        zipResult: $(".zip-result"),
        zipValidationIcon: null, // Will be created dynamically

        // Step 2 - Services
        serviceCards: $(".service-card"),
        serviceCheckboxes: $('input[name="selected_services[]"]'),
        servicesGrid: $(".services-grid"),

        // Step 3 - Service Options
        optionsContainer: $(".service-options-container"),

        // Step 4 - Customer Info
        customerForm: {
          name: $("#customer_name"),
          email: $("#customer_email"),
          phone: $("#customer_phone"),
          address: $("#customer_address"),
          date: $("#service_date"),
          notes: $("#booking_notes"),
        },

        // Step 5 - Review
        summaryContainer: $(".booking-summary"),
        selectedServicesList: $(".selected-services-list"),
        pricingSummary: $(".pricing-summary"),
        discountInput: $("#discount_code"),
        applyDiscountBtn: $(".apply-discount-btn"),
        confirmBtn: $(".confirm-booking-btn"),

        // Navigation
        nextBtns: $(".next-step"),
        prevBtns: $(".prev-step"),

        // Hidden fields
        totalPriceField: $("#total_price"),
        discountAmountField: $("#discount_amount"),
        serviceOptionsField: $("#service_options_data"),
      };

      // Create streamlined ZIP validation UI
      this.createZipValidationUI();
    },

    // Create streamlined ZIP validation interface
    createZipValidationUI: function () {
      const $zipGroup = this.elements.zipInputGroup;
      const $zipInput = this.elements.zipInput;

      // Remove the separate button approach
      $(".check-zip-btn").remove();

      // Style the input for inline validation
      $zipInput.wrap('<div class="zip-input-wrapper"></div>');

      const $wrapper = $zipInput.parent();
      $wrapper.append('<div class="zip-validation-icon"></div>');

      this.elements.zipValidationIcon = $wrapper.find(".zip-validation-icon");

      // Add help text
      if (!$zipGroup.find(".zip-help").length) {
        $zipGroup.append(
          '<p class="zip-help">Enter your ZIP code to check service availability</p>'
        );
      }
    },

    // Load services data on initialization
    loadServicesData: function () {
      this.elements.serviceCards.each((index, card) => {
        const $card = $(card);
        const serviceId = parseInt($card.data("service-id"));

        this.state.servicesData[serviceId] = {
          id: serviceId,
          name: $card.find("h3").text().trim(),
          price: parseFloat($card.data("service-price")) || 0,
          hasOptions:
            $card.find('input[type="checkbox"]').data("has-options") === 1,
          description: $card.find(".service-description").text().trim(),
          duration: $card.find(".service-duration").text().trim(),
        };
      });

      this.log("Services data loaded:", this.state.servicesData);
    },

    // Attach event listeners
    attachEventListeners: function () {
      const self = this;

      // Enhanced ZIP Code validation with debouncing
      this.elements.zipInput.on("input", function () {
        self.handleZipInput();
      });

      this.elements.zipInput.on("blur", function () {
        self.validateZipOnBlur();
      });

      this.elements.zipInput.on("keypress", function (e) {
        if (e.which === 13) {
          e.preventDefault();
          self.validateZipCode();
        }
      });

      // Service selection with immediate validation
      this.elements.serviceCheckboxes.on("change", function () {
        self.handleServiceSelection($(this));
      });

      // Navigation with validation
      this.elements.nextBtns.on("click", function (e) {
        e.preventDefault();
        const currentStep = $(this).closest(".booking-step").index() + 1;
        self.nextStep(currentStep);
      });

      this.elements.prevBtns.on("click", function (e) {
        e.preventDefault();
        const currentStep = $(this).closest(".booking-step").index() + 1;
        self.prevStep(currentStep);
      });

      // Service options change with real-time pricing
      $(document).on(
        "change",
        ".option-field input, .option-field select, .option-field textarea",
        function () {
          self.updatePricing();
        }
      );

      // Discount code handling
      this.elements.applyDiscountBtn.on("click", function (e) {
        e.preventDefault();
        self.applyDiscountCode();
      });

      this.elements.discountInput.on("keypress", function (e) {
        if (e.which === 13) {
          e.preventDefault();
          self.applyDiscountCode();
        }
      });

      // Form submission
      this.elements.form.on("submit", function (e) {
        e.preventDefault();
        self.submitBooking();
      });

      // Customer info validation
      Object.values(this.elements.customerForm).forEach((field) => {
        field.on("blur", function () {
          self.validateField($(this));
        });

        field.on("change", function () {
          self.updateCustomerData();
        });
      });

      // Keyboard navigation
      $(document).on("keydown", function (e) {
        if (e.altKey) {
          if (e.key === "ArrowRight") {
            e.preventDefault();
            self.nextStep(self.state.currentStep);
          } else if (e.key === "ArrowLeft") {
            e.preventDefault();
            self.prevStep(self.state.currentStep);
          }
        }
      });
    },

    // Initialize form
    initializeForm: function () {
      this.updateProgressBar();
      this.setMinDateTime();
      this.updateNavigationButtons();
    },

    // Enhanced ZIP code handling with debouncing
    handleZipInput: function () {
      const zipCode = this.elements.zipInput.val().trim();

      // Clear previous timer
      clearTimeout(this.state.zipDebounceTimer);

      // Reset validation state
      this.state.isZipValid = false;
      this.updateZipValidationIcon("checking");

      // Clear results
      this.elements.zipResult.empty();

      if (!zipCode) {
        this.updateZipValidationIcon("none");
        return;
      }

      // Basic format validation
      const zipRegex = /^\d{5}(-\d{4})?$/;
      if (!zipRegex.test(zipCode)) {
        this.updateZipValidationIcon("error");
        if (zipCode.length >= 5) {
          this.showMessage(
            this.elements.zipResult,
            "Please enter a valid ZIP code (e.g., 12345)",
            "error"
          );
        }
        return;
      }

      // Debounce the validation
      this.state.zipDebounceTimer = setTimeout(() => {
        this.validateZipCode();
      }, this.config.zipDebounceDelay);
    },

    validateZipOnBlur: function () {
      const zipCode = this.elements.zipInput.val().trim();
      if (zipCode && !this.state.isZipValid) {
        clearTimeout(this.state.zipDebounceTimer);
        this.validateZipCode();
      }
    },

    // Streamlined ZIP validation
    validateZipCode: function () {
      const zipCode = this.elements.zipInput.val().trim();

      if (!zipCode) {
        this.updateZipValidationIcon("error");
        this.showMessage(
          this.elements.zipResult,
          "Please enter a ZIP code",
          "error"
        );
        return;
      }

      const zipRegex = /^\d{5}(-\d{4})?$/;
      if (!zipRegex.test(zipCode)) {
        this.updateZipValidationIcon("error");
        this.showMessage(
          this.elements.zipResult,
          "Please enter a valid ZIP code (e.g., 12345)",
          "error"
        );
        return;
      }

      this.updateZipValidationIcon("checking");

      const data = {
        action: "mobooking_check_zip_coverage",
        zip_code: zipCode,
        user_id: this.config.userId,
        nonce: this.config.nonces.booking,
      };

      this.log("Validating ZIP code:", zipCode);

      $.ajax({
        url: this.config.ajaxUrl,
        type: "POST",
        data: data,
        timeout: 15000,
        success: (response) => {
          this.log("ZIP validation response:", response);

          if (response && response.success) {
            this.state.isZipValid = true;
            this.updateZipValidationIcon("success");
            this.showMessage(
              this.elements.zipResult,
              response.data?.message || "✓ Service available in your area!",
              "success"
            );
            this.updateNavigationButtons();
          } else {
            this.state.isZipValid = false;
            this.updateZipValidationIcon("error");
            const errorMessage =
              response?.data?.message ||
              "Sorry, we don't service this area yet.";
            this.showMessage(this.elements.zipResult, errorMessage, "error");
            this.updateNavigationButtons();
          }
        },
        error: (xhr, status, error) => {
          this.log("ZIP validation error:", xhr.status, error);
          this.state.isZipValid = false;
          this.updateZipValidationIcon("error");

          let errorMessage = "Unable to verify ZIP code. Please try again.";
          if (xhr.status === 0) {
            errorMessage = "Network error. Please check your connection.";
          }

          this.showMessage(this.elements.zipResult, errorMessage, "error");
          this.updateNavigationButtons();
        },
      });
    },

    // Update ZIP validation icon
    updateZipValidationIcon: function (state) {
      const $icon = this.elements.zipValidationIcon;
      $icon.removeClass("checking success error none");

      switch (state) {
        case "checking":
          $icon.addClass("checking").html('<div class="spinner"></div>');
          break;
        case "success":
          $icon.addClass("success").html("✓");
          break;
        case "error":
          $icon.addClass("error").html("✗");
          break;
        default:
          $icon.addClass("none").empty();
      }
    },

    // Enhanced service selection
    handleServiceSelection: function ($checkbox) {
      const serviceId = parseInt($checkbox.val());
      const $serviceCard = $checkbox.closest(".service-card");

      if ($checkbox.is(":checked")) {
        $serviceCard.addClass("selected");
        if (!this.state.selectedServices.includes(serviceId)) {
          this.state.selectedServices.push(serviceId);
        }
      } else {
        $serviceCard.removeClass("selected");
        const index = this.state.selectedServices.indexOf(serviceId);
        if (index > -1) {
          this.state.selectedServices.splice(index, 1);
        }
        delete this.state.serviceOptions[serviceId];
      }

      this.updatePricing();
      this.updateNavigationButtons();
      this.log("Selected services updated:", this.state.selectedServices);
    },

    // Set minimum date/time for service
    setMinDateTime: function () {
      const tomorrow = new Date();
      tomorrow.setDate(tomorrow.getDate() + 1);
      tomorrow.setHours(8, 0, 0, 0);

      const minDateTime = tomorrow.toISOString().slice(0, 16);
      this.elements.customerForm.date.attr("min", minDateTime);
    },

    // Enhanced navigation with proper validation
    nextStep: function (currentStep) {
      if (this.state.isProcessing) return;

      this.log(
        `Attempting to go from step ${currentStep} to ${currentStep + 1}`
      );

      if (!this.validateStep(currentStep)) {
        return;
      }

      // Handle step-specific logic
      switch (currentStep) {
        case 1:
          this.log("Moving from ZIP to Services");
          break;
        case 2:
          this.log("Moving from Services to Options");
          this.prepareServiceOptions();
          break;
        case 3:
          this.log("Moving from Options to Customer Info");
          this.collectServiceOptions();
          break;
        case 4:
          this.log("Moving from Customer Info to Review");
          this.updateCustomerData();
          this.buildOrderSummary();
          break;
        case 5:
          this.log("Moving to Success (should not happen via nextStep)");
          return;
      }

      this.showStep(currentStep + 1);
    },

    prevStep: function (currentStep) {
      if (this.state.isProcessing) return;

      this.log(`Going back from step ${currentStep} to ${currentStep - 1}`);

      if (currentStep > 1) {
        this.showStep(currentStep - 1);
      }
    },

    showStep: function (stepNumber) {
      if (stepNumber < 1 || stepNumber > this.state.totalSteps) return;

      this.log(`Showing step ${stepNumber}`);

      this.state.currentStep = stepNumber;

      this.elements.steps.removeClass("active");
      $(`.booking-step.step-${stepNumber}`).addClass("active");

      this.updateProgressBar();
      this.updateProgressSteps();
      this.updateNavigationButtons();

      // Smooth scroll to form
      this.elements.container[0].scrollIntoView({
        behavior: "smooth",
        block: "start",
      });

      // Focus on first input in the step
      setTimeout(() => {
        const $activeStep = $(`.booking-step.step-${stepNumber}`);
        const $firstInput = $activeStep.find("input, select, textarea").first();
        if ($firstInput.length && $firstInput.is(":visible")) {
          $firstInput.focus();
        }
      }, 500);
    },

    // Enhanced step validation
    validateStep: function (stepNumber) {
      this.clearFieldErrors();

      switch (stepNumber) {
        case 1:
          return this.validateZipStep();
        case 2:
          return this.validateServicesStep();
        case 3:
          return this.validateServiceOptionsStep();
        case 4:
          return this.validateCustomerStep();
        case 5:
          return true; // Review step doesn't need validation
        default:
          return true;
      }
    },

    validateZipStep: function () {
      const zipCode = this.elements.zipInput.val().trim();

      if (!zipCode) {
        this.showFieldError(this.elements.zipInput, "Please enter a ZIP code");
        return false;
      }

      if (!this.state.isZipValid) {
        this.showNotification(
          "Please enter a valid ZIP code that we service",
          "error"
        );
        this.elements.zipInput.focus();
        return false;
      }

      return true;
    },

    validateServicesStep: function () {
      if (this.state.selectedServices.length === 0) {
        this.showNotification("Please select at least one service", "error");
        this.elements.servicesGrid[0].scrollIntoView({ behavior: "smooth" });
        return false;
      }
      return true;
    },

    validateServiceOptionsStep: function () {
      let isValid = true;
      const errors = [];

      $(".option-field").each(function () {
        const $field = $(this);
        const $input = $field.find("input, select, textarea");
        const isRequired = $input.prop("required") || $input.data("required");

        if (isRequired) {
          let value = "";

          if ($input.is('input[type="checkbox"]')) {
            value = $input.is(":checked") ? "1" : "";
          } else if ($input.is('input[type="radio"]')) {
            value = $field.find('input[type="radio"]:checked').val() || "";
          } else {
            value = $input.val() || "";
          }

          if (!value || value.trim() === "") {
            const optionName = $field
              .find(".option-label")
              .text()
              .replace("*", "")
              .trim();
            errors.push(optionName);
            $input.addClass("error");
            isValid = false;
          }
        }
      });

      if (!isValid) {
        this.showNotification(
          `Please fill in required fields: ${errors.join(", ")}`,
          "error"
        );
      }

      return isValid;
    },

    validateCustomerStep: function () {
      let isValid = true;
      const errors = [];

      // Name validation
      const name = this.elements.customerForm.name.val().trim();
      if (!name) {
        errors.push("Name");
        this.showFieldError(
          this.elements.customerForm.name,
          "Name is required"
        );
        isValid = false;
      }

      // Email validation
      const email = this.elements.customerForm.email.val().trim();
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!email) {
        errors.push("Email");
        this.showFieldError(
          this.elements.customerForm.email,
          "Email is required"
        );
        isValid = false;
      } else if (!emailRegex.test(email)) {
        this.showFieldError(
          this.elements.customerForm.email,
          "Please enter a valid email address"
        );
        isValid = false;
      }

      // Address validation
      const address = this.elements.customerForm.address.val().trim();
      if (!address) {
        errors.push("Address");
        this.showFieldError(
          this.elements.customerForm.address,
          "Service address is required"
        );
        isValid = false;
      }

      // Date validation
      const serviceDate = this.elements.customerForm.date.val();
      if (!serviceDate) {
        errors.push("Service date");
        this.showFieldError(
          this.elements.customerForm.date,
          "Please select a service date and time"
        );
        isValid = false;
      } else {
        const selectedDate = new Date(serviceDate);
        const now = new Date();
        if (selectedDate <= now) {
          this.showFieldError(
            this.elements.customerForm.date,
            "Please select a future date and time"
          );
          isValid = false;
        }
      }

      if (!isValid && errors.length > 0) {
        this.showNotification(`Please fill in: ${errors.join(", ")}`, "error");
        // Focus on first error field
        const $firstError = $(".error").first();
        if ($firstError.length) {
          $firstError.focus();
        }
      }

      return isValid;
    },

    // Prepare service options for selected services
    prepareServiceOptions: function () {
      this.elements.optionsContainer.empty();

      const servicesWithOptions = this.state.selectedServices.filter(
        (serviceId) => this.state.servicesData[serviceId]?.hasOptions
      );

      this.log("Services with options:", servicesWithOptions);

      if (servicesWithOptions.length === 0) {
        this.log("No services with options, skipping to customer info");
        // Skip options step if no services have options
        this.showStep(4);
        return;
      }

      // Load options for each service
      servicesWithOptions.forEach((serviceId) => {
        this.loadServiceOptions(serviceId);
      });
    },

    // Load options for a specific service
    loadServiceOptions: function (serviceId) {
      const serviceData = this.state.servicesData[serviceId];
      if (!serviceData) return;

      const data = {
        action: "mobooking_get_service_options",
        service_id: serviceId,
        nonce: this.config.nonces.booking,
      };

      $.ajax({
        url: this.config.ajaxUrl,
        type: "POST",
        data: data,
        success: (response) => {
          if (response.success && response.data.options) {
            this.renderServiceOptions(
              serviceId,
              serviceData,
              response.data.options
            );
          }
        },
        error: (xhr, status, error) => {
          this.log(`Error loading options for service ${serviceId}:`, error);
        },
      });
    },

    // Render service options
    renderServiceOptions: function (serviceId, serviceData, options) {
      const $section = $(`
        <div class="service-options-section" data-service-id="${serviceId}">
          <div class="service-options-header">
            <h3 class="service-options-title">${serviceData.name}</h3>
          </div>
          <div class="service-options-fields">
            ${this.generateOptionsHTML(options)}
          </div>
        </div>
      `);

      this.elements.optionsContainer.append($section);
      this.initializeOptionHandlers($section);
    },

    // Generate HTML for service options
    generateOptionsHTML: function (options) {
      let html = "";

      options.forEach((option) => {
        const isRequired = option.is_required == 1;
        const requiredMark = isRequired
          ? ' <span class="required">*</span>'
          : "";
        const priceImpact =
          option.price_impact > 0
            ? ` <span class="price-impact">(+${this.formatPrice(
                option.price_impact
              )})</span>`
            : "";

        html += `
          <div class="option-field" data-option-id="${
            option.id
          }" data-price-type="${option.price_type}" data-price-impact="${
          option.price_impact
        }">
            <label class="option-label">
              ${option.name}${requiredMark}${priceImpact}
            </label>
            ${
              option.description
                ? `<p class="option-description">${option.description}</p>`
                : ""
            }
            <div class="option-input">
              ${this.generateOptionInput(option, isRequired)}
            </div>
          </div>
        `;
      });

      return html;
    },

    // Generate input HTML for specific option types
    generateOptionInput: function (option, isRequired) {
      const requiredAttr = isRequired ? "required" : "";
      const placeholderAttr = option.placeholder
        ? `placeholder="${option.placeholder}"`
        : "";

      switch (option.type) {
        case "text":
          return `<input type="text" ${requiredAttr} ${placeholderAttr} 
                    ${
                      option.min_length
                        ? `minlength="${option.min_length}"`
                        : ""
                    }
                    ${
                      option.max_length
                        ? `maxlength="${option.max_length}"`
                        : ""
                    }>`;

        case "textarea":
          return `<textarea ${requiredAttr} ${placeholderAttr} rows="${
            option.rows || 3
          }"
                    ${
                      option.min_length
                        ? `minlength="${option.min_length}"`
                        : ""
                    }
                    ${
                      option.max_length
                        ? `maxlength="${option.max_length}"`
                        : ""
                    }></textarea>`;

        case "number":
        case "quantity":
          return `<input type="number" ${requiredAttr} ${placeholderAttr}
                    ${
                      option.min_value !== null
                        ? `min="${option.min_value}"`
                        : ""
                    }
                    ${
                      option.max_value !== null
                        ? `max="${option.max_value}"`
                        : ""
                    }
                    step="${option.step || 1}" value="${
            option.default_value || ""
          }">
                    ${
                      option.unit
                        ? `<span class="input-unit">${option.unit}</span>`
                        : ""
                    }`;

        case "select":
          const selectOptions = this.parseChoices(option.options);
          let selectHTML = `<select ${requiredAttr}>`;
          if (!isRequired) {
            selectHTML += '<option value="">Choose an option...</option>';
          }
          selectOptions.forEach((choice) => {
            selectHTML += `<option value="${choice.value}" ${
              choice.price > 0 ? `data-price="${choice.price}"` : ""
            }>${choice.label}${
              choice.price > 0
                ? " (+" + this.formatPrice(choice.price) + ")"
                : ""
            }</option>`;
          });
          selectHTML += "</select>";
          return selectHTML;

        case "radio":
          const radioOptions = this.parseChoices(option.options);
          let radioHTML = '<div class="radio-group">';
          radioOptions.forEach((choice, index) => {
            const radioId = `option_${option.id}_${index}`;
            radioHTML += `
              <label class="radio-label" for="${radioId}">
                <input type="radio" id="${radioId}" name="option_${
              option.id
            }" value="${choice.value}" 
                       ${
                         choice.price > 0 ? `data-price="${choice.price}"` : ""
                       } ${requiredAttr}>
                <span class="radio-text">${choice.label}</span>
                ${
                  choice.price > 0
                    ? `<span class="choice-price">+${this.formatPrice(
                        choice.price
                      )}</span>`
                    : ""
                }
              </label>
            `;
          });
          radioHTML += "</div>";
          return radioHTML;

        case "checkbox":
          return `
            <label class="checkbox-label">
              <input type="checkbox" ${option.default_value ? "checked" : ""}>
              <span class="checkbox-text">${option.option_label || "Yes"}</span>
            </label>
          `;

        default:
          return `<input type="text" ${requiredAttr} ${placeholderAttr}>`;
      }
    },

    // Parse option choices
    parseChoices: function (choicesString) {
      if (!choicesString) return [];

      const choices = [];
      const lines = choicesString.split("\n");

      lines.forEach((line) => {
        line = line.trim();
        if (!line) return;

        const parts = line.split("|");
        const value = parts[0].trim();

        let label = value;
        let price = 0;

        if (parts[1]) {
          const labelPrice = parts[1].split(":");
          label = labelPrice[0].trim();
          if (labelPrice[1]) {
            price = parseFloat(labelPrice[1]) || 0;
          }
        }

        choices.push({ value, label, price });
      });

      return choices;
    },

    // Initialize option handlers
    initializeOptionHandlers: function ($section) {
      // Range inputs
      $section.find('input[type="range"]').on("input", function () {
        const $this = $(this);
        const $display = $this.siblings(".range-display");
        $display.text($this.val());
      });

      // Clear errors on change
      $section.find("input, select, textarea").on("change", function () {
        $(this).removeClass("error");
        $(this).siblings(".field-error").remove();
      });
    },

    // Collect service options data
    collectServiceOptions: function () {
      const optionsData = {};

      $(".service-options-section").each(function () {
        const serviceId = $(this).data("service-id");
        optionsData[serviceId] = {};

        $(this)
          .find(".option-field")
          .each(function () {
            const optionId = $(this).data("option-id");
            const $input = $(this).find("input, select, textarea");

            let value = null;
            if ($input.is('input[type="checkbox"]')) {
              value = $input.is(":checked") ? "1" : "0";
            } else if ($input.is('input[type="radio"]')) {
              value = $(this).find('input[type="radio"]:checked').val() || "";
            } else {
              value = $input.val() || "";
            }

            optionsData[serviceId][optionId] = value;
          });
      });

      this.state.serviceOptions = optionsData;
      this.elements.serviceOptionsField.val(JSON.stringify(optionsData));
      this.log("Collected service options:", optionsData);
    },

    // Update customer data
    updateCustomerData: function () {
      this.state.customerData = {
        name: this.elements.customerForm.name.val().trim(),
        email: this.elements.customerForm.email.val().trim(),
        phone: this.elements.customerForm.phone.val().trim(),
        address: this.elements.customerForm.address.val().trim(),
        date: this.elements.customerForm.date.val(),
        notes: this.elements.customerForm.notes.val().trim(),
      };
    },

    // Build order summary for review step
    buildOrderSummary: function () {
      this.buildServicesSummary();
      this.buildCustomerSummary();
      this.updatePricing();
      this.showDiscountSection();
    },

    // Build services summary
    buildServicesSummary: function () {
      let html = "";

      this.state.selectedServices.forEach((serviceId) => {
        const serviceData = this.state.servicesData[serviceId];
        if (!serviceData) return;

        html += `
          <div class="service-summary-item">
            <div class="service-info">
              <h4>${serviceData.name}</h4>
              ${
                serviceData.description
                  ? `<p class="service-description">${serviceData.description}</p>`
                  : ""
              }
              ${this.buildServiceOptionsHTML(serviceId)}
            </div>
            <div class="service-price">${this.formatPrice(
              serviceData.price
            )}</div>
          </div>
        `;
      });

      this.elements.selectedServicesList.html(html);
    },

    // Build service options HTML for summary
    buildServiceOptionsHTML: function (serviceId) {
      const options = this.state.serviceOptions[serviceId];
      if (!options) return "";

      let html = '<div class="service-options">';

      Object.entries(options).forEach(([optionId, value]) => {
        if (!value || value === "0") return;

        const $optionField = $(`.option-field[data-option-id="${optionId}"]`);
        if ($optionField.length === 0) return;

        const optionName = $optionField
          .find(".option-label")
          .text()
          .replace(/\*|\(.*\)/g, "")
          .trim();

        // Format value based on option type
        let displayValue = value;
        const $input = $optionField.find("input, select, textarea");

        if ($input.is('input[type="checkbox"]') && value === "1") {
          displayValue = "Yes";
        } else if ($input.is("select") || $input.is('input[type="radio"]')) {
          const $selected = $input.find(
            `option[value="${value}"], input[value="${value}"]:checked`
          );
          if ($selected.length) {
            displayValue = $selected.parent().is("label")
              ? $selected.parent().find(".radio-text").text()
              : $selected.text();
          }
        }

        html += `
          <div class="option-summary">
            <span class="option-name">${optionName}:</span>
            <span class="option-value">${displayValue}</span>
          </div>
        `;
      });

      html += "</div>";
      return html;
    },

    // Build customer summary
    buildCustomerSummary: function () {
      const data = this.state.customerData;
      const serviceDate = new Date(data.date);

      $(".service-address").html(`
        <strong>Service Address:</strong><br>
        ${data.address}<br>
        ZIP: ${this.elements.zipInput.val()}
      `);

      $(".service-datetime").html(`
        <strong>Service Date & Time:</strong><br>
        ${serviceDate.toLocaleDateString()} at ${serviceDate.toLocaleTimeString(
        [],
        {
          hour: "2-digit",
          minute: "2-digit",
        }
      )}
      `);

      $(".customer-info").html(`
        <div><strong>Name:</strong> ${data.name}</div>
        <div><strong>Email:</strong> ${data.email}</div>
        ${data.phone ? `<div><strong>Phone:</strong> ${data.phone}</div>` : ""}
        ${data.notes ? `<div><strong>Notes:</strong> ${data.notes}</div>` : ""}
      `);
    },

    // Show discount section
    showDiscountSection: function () {
      $(".discount-section").show();
    },

    // Enhanced pricing calculations
    updatePricing: function () {
      let subtotal = 0;

      // Calculate base service prices
      this.state.selectedServices.forEach((serviceId) => {
        const serviceData = this.state.servicesData[serviceId];
        if (serviceData) {
          subtotal += serviceData.price;
        }
      });

      // Calculate option pricing
      $(".option-field").each(function () {
        const $field = $(this);
        const priceType = $field.data("price-type") || "fixed";
        const priceImpact = parseFloat($field.data("price-impact")) || 0;

        if (priceImpact === 0) return;

        const $input = $field.find("input, select, textarea");
        let shouldApply = false;
        let multiplier = 1;

        if ($input.is('input[type="checkbox"]')) {
          shouldApply = $input.is(":checked");
        } else if ($input.is('input[type="radio"]')) {
          const $checked = $field.find('input[type="radio"]:checked');
          if ($checked.length) {
            shouldApply = true;
            const choicePrice = parseFloat($checked.data("price")) || 0;
            if (choicePrice > 0) {
              subtotal += choicePrice;
              return; // Skip standard price impact calculation
            }
          }
        } else if ($input.is("select")) {
          const $selected = $input.find("option:selected");
          if ($selected.val()) {
            shouldApply = true;
            const choicePrice = parseFloat($selected.data("price")) || 0;
            if (choicePrice > 0) {
              subtotal += choicePrice;
              return; // Skip standard price impact calculation
            }
          }
        } else if (
          $input.is('input[type="number"]') &&
          priceType === "multiply"
        ) {
          const value = parseFloat($input.val()) || 0;
          if (value > 0) {
            shouldApply = true;
            multiplier = value;
          }
        } else if ($input.val()) {
          shouldApply = true;
        }

        if (shouldApply) {
          switch (priceType) {
            case "percentage":
              subtotal += (subtotal * priceImpact) / 100;
              break;
            case "multiply":
              subtotal += priceImpact * multiplier;
              break;
            default: // fixed
              subtotal += priceImpact;
          }
        }
      });

      this.state.pricing.subtotal = Math.max(0, subtotal);
      this.state.pricing.total =
        this.state.pricing.subtotal - this.state.pricing.discount;

      this.updatePricingDisplay();
    },

    // Update pricing display
    updatePricingDisplay: function () {
      const pricing = this.state.pricing;

      $(".pricing-summary .subtotal .amount").text(
        this.formatPrice(pricing.subtotal)
      );
      $(".pricing-summary .total .amount").text(
        this.formatPrice(pricing.total)
      );

      this.elements.totalPriceField.val(pricing.total);

      if (pricing.discount > 0) {
        $(".pricing-summary .discount").show();
        $(".pricing-summary .discount .amount").text(
          "-" + this.formatPrice(pricing.discount)
        );
        this.elements.discountAmountField.val(pricing.discount);
      } else {
        $(".pricing-summary .discount").hide();
        this.elements.discountAmountField.val(0);
      }
    },

    // Enhanced discount code handling
    applyDiscountCode: function () {
      const code = this.elements.discountInput.val().trim();

      if (!code) {
        this.showMessage(
          $(".discount-message"),
          "Please enter a discount code",
          "error"
        );
        return;
      }

      if (this.elements.applyDiscountBtn.hasClass("loading")) {
        return; // Prevent duplicate requests
      }

      this.setLoading(this.elements.applyDiscountBtn, true);

      const data = {
        action: "mobooking_validate_discount",
        code: code,
        user_id: this.config.userId,
        total: this.state.pricing.subtotal,
        nonce: this.config.nonces.booking,
      };

      $.ajax({
        url: this.config.ajaxUrl,
        type: "POST",
        data: data,
        success: (response) => {
          if (response.success) {
            this.state.pricing.discount =
              parseFloat(response.data.discount_amount) || 0;
            this.updatePricing();
            this.showMessage(
              $(".discount-message"),
              response.data.message || "Discount applied successfully!",
              "success"
            );
            this.elements.applyDiscountBtn
              .text("Applied")
              .prop("disabled", true);
            this.elements.discountInput.prop("disabled", true);
          } else {
            this.showMessage(
              $(".discount-message"),
              response.data?.message || "Invalid discount code",
              "error"
            );
          }
        },
        error: (xhr, status, error) => {
          this.log("Discount validation error:", xhr, status, error);
          this.showMessage(
            $(".discount-message"),
            "Error applying discount code. Please try again.",
            "error"
          );
        },
        complete: () => {
          this.setLoading(this.elements.applyDiscountBtn, false);
        },
      });
    },

    // Enhanced booking submission
    submitBooking: function () {
      if (this.state.isProcessing) return;

      // Final validation
      if (!this.validateStep(5)) {
        return;
      }

      this.state.isProcessing = true;
      this.setLoading(this.elements.confirmBtn, true);

      // Collect all form data
      const formData = new FormData(this.elements.form[0]);
      formData.append("action", "mobooking_save_booking");
      formData.append("nonce", this.config.nonces.booking);

      // Add selected services as array
      this.state.selectedServices.forEach((serviceId) => {
        formData.append("selected_services[]", serviceId);
      });

      this.log("Submitting booking with data:", Object.fromEntries(formData));

      $.ajax({
        url: this.config.ajaxUrl,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        timeout: 60000,
        success: (response) => {
          this.log("Booking submission response:", response);
          if (response && response.success) {
            $(".reference-number").text("#" + response.data.id);
            this.showStep(6); // Success step
            this.showNotification(
              response.data.message || "Booking confirmed successfully!",
              "success"
            );
          } else {
            this.showNotification(
              response?.data?.message || "Booking failed. Please try again.",
              "error"
            );
          }
        },
        error: (xhr, status, error) => {
          this.log("Booking submission error:", xhr, status, error);

          let errorMessage = "An error occurred. Please try again.";
          if (xhr.status === 0) {
            errorMessage = "Network error. Please check your connection.";
          } else if (xhr.status >= 500) {
            errorMessage = "Server error. Please try again in a few minutes.";
          } else if (xhr.responseText) {
            try {
              const errorData = JSON.parse(xhr.responseText);
              errorMessage = errorData.data?.message || errorMessage;
            } catch (e) {
              // Not JSON response
            }
          }

          this.showNotification(errorMessage, "error");
        },
        complete: () => {
          this.state.isProcessing = false;
          this.setLoading(this.elements.confirmBtn, false);
        },
      });
    },

    // Enhanced navigation button updates
    updateNavigationButtons: function () {
      const $currentStep = $(`.booking-step.step-${this.state.currentStep}`);
      const $nextBtn = $currentStep.find(".next-step");
      const $prevBtn = $currentStep.find(".prev-step");

      // Update previous button
      if (this.state.currentStep <= 1) {
        $prevBtn.hide();
      } else {
        $prevBtn.show();
      }

      // Update next button based on step validation
      switch (this.state.currentStep) {
        case 1:
          if (this.state.isZipValid) {
            $nextBtn.prop("disabled", false).text("Continue");
          } else {
            $nextBtn.prop("disabled", true).text("Enter ZIP Code");
          }
          break;
        case 2:
          if (this.state.selectedServices.length > 0) {
            $nextBtn.prop("disabled", false).text("Continue");
          } else {
            $nextBtn.prop("disabled", true).text("Select Services");
          }
          break;
        case 3:
          $nextBtn.prop("disabled", false).text("Continue");
          break;
        case 4:
          $nextBtn.prop("disabled", false).text("Review Booking");
          break;
        case 5:
          // This is handled by the confirm button, not next button
          $nextBtn.hide();
          break;
        default:
          $nextBtn.prop("disabled", false).text("Continue");
      }
    },

    // UI Helper methods
    updateProgressBar: function () {
      const progress = Math.min(
        100,
        (this.state.currentStep / this.state.totalSteps) * 100
      );
      this.elements.progressBar.css("width", progress + "%");
    },

    updateProgressSteps: function () {
      this.elements.progressSteps.each((index, el) => {
        const $step = $(el);
        const stepNum = index + 1;

        $step.removeClass("active completed");

        if (stepNum < this.state.currentStep) {
          $step.addClass("completed");
        } else if (stepNum === this.state.currentStep) {
          $step.addClass("active");
        }
      });
    },

    // Field validation helpers
    validateField: function ($field) {
      const value = $field.val().trim();
      const isRequired = $field.prop("required");

      this.clearFieldError($field);

      if (isRequired && !value) {
        this.showFieldError($field, "This field is required");
        return false;
      }

      // Email validation
      if ($field.attr("type") === "email" && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
          this.showFieldError($field, "Please enter a valid email address");
          return false;
        }
      }

      return true;
    },

    showFieldError: function ($field, message) {
      $field.addClass("error");
      $field.siblings(".field-error").remove();
      $field.after(`<div class="field-error">${message}</div>`);
    },

    clearFieldError: function ($field) {
      $field.removeClass("error");
      $field.siblings(".field-error").remove();
    },

    clearFieldErrors: function () {
      $(".error").removeClass("error");
      $(".field-error").remove();
    },

    setLoading: function ($btn, loading) {
      if (loading) {
        $btn.addClass("loading").prop("disabled", true);
        const loadingText = $btn.find(".btn-loading").text() || "Loading...";
        $btn.data("original-text", $btn.text()).text(loadingText);
      } else {
        $btn.removeClass("loading").prop("disabled", false);
        if ($btn.data("original-text")) {
          $btn.text($btn.data("original-text"));
        }
      }
    },

    showMessage: function ($container, message, type) {
      $container
        .removeClass("success error info warning")
        .addClass(type)
        .html(
          `
        <div class="message ${type}">
          <span class="message-text">${message}</span>
        </div>
      `
        )
        .show();
    },

    showNotification: function (message, type = "info") {
      // Remove existing notifications
      $(".booking-notification").remove();

      const $notification = $(`
        <div class="booking-notification ${type}">
          <span class="notification-message">${message}</span>
          <button class="notification-close" aria-label="Close">&times;</button>
        </div>
      `);

      $("body").append($notification);

      // Handle close button
      $notification.find(".notification-close").on("click", function () {
        $notification.removeClass("show");
        setTimeout(() => $notification.remove(), 300);
      });

      setTimeout(() => {
        $notification.addClass("show");
      }, 100);

      // Auto-hide after 5 seconds for non-error messages
      if (type !== "error") {
        setTimeout(() => {
          $notification.removeClass("show");
          setTimeout(() => $notification.remove(), 300);
        }, 5000);
      }
    },

    formatPrice: function (amount) {
      const symbol = this.config.currency.symbol;
      const formatted = parseFloat(amount || 0).toFixed(2);

      return this.config.currency.position === "right"
        ? formatted + symbol
        : symbol + formatted;
    },
  };

  // Initialize when document is ready
  $(document).ready(function () {
    BookingForm.init();
  });

  // Export for debugging
  window.BookingForm = BookingForm;
})(jQuery);
