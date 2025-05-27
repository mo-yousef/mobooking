/**
 * MoBooking Enhanced Frontend Booking Form Handler - FIXED VERSION
 * Fixed issues: ZIP validation, service selection, step progression
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
      zipDebounceDelay: 800,
    },

    // State
    state: {
      currentStep: 1,
      totalSteps: 6,
      isProcessing: false,
      selectedServices: [], // Changed back to array to support multiple services
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
      validatedArea: null,
      debugMode: false,
    },

    // Initialize
    init: function () {
      if ($(".mobooking-booking-form-container").length === 0) {
        return;
      }

      this.state.debugMode =
        window.location.search.includes("debug=1") ||
        (typeof mobookingBooking !== "undefined" && mobookingBooking.debug);

      this.log("🚀 Fixed Booking Form initializing...");

      if (!this.validateConfig()) {
        return;
      }

      this.cacheElements();
      this.loadServicesData();
      this.attachEventListeners();
      this.initializeForm();
      this.log("✅ Fixed Booking Form initialized");
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

    // Cache DOM elements - FIXED
    cacheElements: function () {
      this.elements = {
        container: $(".mobooking-booking-form-container"),
        form: $("#mobooking-booking-form"),
        steps: $(".booking-step"),
        progressBar: $(".progress-fill"),
        progressSteps: $(".progress-steps .step"),

        // Step 1 - ZIP Code
        zipInput: $("#customer_zip_code"),
        zipInputGroup: $(".zip-input-group"),
        zipResult: $(".zip-result"),
        zipValidationIcon: null, // Will be created

        // Step 2 - Services - FIXED to support both checkbox and radio
        serviceCards: $(".service-card"),
        serviceInputs: $(
          'input[name="selected_services[]"], input[name="selected_service"]'
        ),
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

      // Create ZIP validation UI
      this.createZipValidationUI();
    },

    // Create ZIP validation UI - FIXED
    createZipValidationUI: function () {
      const $zipInput = this.elements.zipInput;

      // Only create if not already exists
      if ($zipInput.parent().hasClass("zip-input-wrapper")) {
        this.elements.zipValidationIcon = $zipInput
          .parent()
          .find(".zip-validation-icon");
        return;
      }

      // Create wrapper and validation icon
      $zipInput.wrap('<div class="zip-input-wrapper"></div>');
      const $wrapper = $zipInput.parent();
      $wrapper.append('<div class="zip-validation-icon"></div>');

      this.elements.zipValidationIcon = $wrapper.find(".zip-validation-icon");

      this.log("ZIP validation UI created");
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
            $card
              .find('input[type="checkbox"], input[type="radio"]')
              .data("has-options") === 1,
          description: $card.find(".service-description").text().trim(),
          duration: $card.find(".service-duration").text().trim(),
        };
      });

      this.log("Services data loaded:", this.state.servicesData);
    },

    // Attach event listeners - FIXED
    attachEventListeners: function () {
      const self = this;

      // ZIP Code validation with debouncing
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

      // Service selection - FIXED to handle both checkbox and radio
      $(document).on(
        "change",
        'input[name="selected_services[]"], input[name="selected_service"]',
        function () {
          self.handleServiceSelection($(this));
        }
      );

      // Manual navigation
      this.elements.nextBtns.on("click", function (e) {
        e.preventDefault();
        self.handleNextStep($(this));
      });

      this.elements.prevBtns.on("click", function (e) {
        e.preventDefault();
        self.handlePrevStep($(this));
      });

      // Service options change
      $(document).on(
        "change input",
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

      this.log("Event listeners attached");
    },

    // Initialize form
    initializeForm: function () {
      this.updateProgressBar();
      this.setMinDateTime();
      this.updateNavigationButtons();
      this.log("Form initialized");
    },

    // Enhanced ZIP code handling - FIXED
    handleZipInput: function () {
      const zipCode = this.elements.zipInput.val().trim();
      this.log("ZIP input changed:", zipCode);

      // Clear previous timer
      clearTimeout(this.state.zipDebounceTimer);

      // Reset validation state
      this.state.isZipValid = false;
      this.state.validatedArea = null;

      // Clear results
      this.elements.zipResult.empty();

      if (!zipCode) {
        this.updateZipValidationIcon("none");
        this.updateNavigationButtons();
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
        this.updateNavigationButtons();
        return;
      }

      // Show checking state immediately for valid format
      this.updateZipValidationIcon("checking");

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

    // Enhanced ZIP validation - FIXED
    validateZipCode: function () {
      const zipCode = this.elements.zipInput.val().trim();
      this.log("Validating ZIP code:", zipCode);

      if (!zipCode) {
        this.updateZipValidationIcon("error");
        this.showMessage(
          this.elements.zipResult,
          "Please enter a ZIP code",
          "error"
        );
        this.updateNavigationButtons();
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
        this.updateNavigationButtons();
        return;
      }

      this.updateZipValidationIcon("checking");

      const data = {
        action: "mobooking_check_zip_coverage",
        zip_code: zipCode,
        user_id: this.config.userId,
        nonce: this.config.nonces.booking,
      };

      this.log("Sending ZIP validation request:", data);

      $.ajax({
        url: this.config.ajaxUrl,
        type: "POST",
        data: data,
        timeout: 15000,
        success: (response) => {
          this.log("ZIP validation response:", response);

          if (response && response.success) {
            this.state.isZipValid = true;
            this.state.validatedArea = response.data || null;
            this.updateZipValidationIcon("success");

            let successMessage = "✓ Service available in your area!";
            if (this.state.validatedArea?.label) {
              successMessage = `✓ Service available in ${this.state.validatedArea.label}!`;
            }

            this.showMessage(
              this.elements.zipResult,
              successMessage,
              "success"
            );

            // CRITICAL FIX: Update navigation buttons after successful validation
            this.updateNavigationButtons();
          } else {
            this.state.isZipValid = false;
            this.state.validatedArea = null;
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
          this.state.validatedArea = null;
          this.updateZipValidationIcon("error");

          let errorMessage = "Unable to verify ZIP code. Please try again.";
          if (xhr.status === 0) {
            errorMessage = "Network error. Please check your connection.";
          } else if (xhr.status === 400) {
            errorMessage = "Invalid request. Please check your ZIP code.";
          }

          this.showMessage(this.elements.zipResult, errorMessage, "error");
          this.updateNavigationButtons();
        },
      });
    },

    // Update ZIP validation icon - FIXED
    updateZipValidationIcon: function (state) {
      if (!this.elements.zipValidationIcon) {
        this.log("Warning: ZIP validation icon not found");
        return;
      }

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

    // Enhanced service selection - FIXED
    handleServiceSelection: function ($input) {
      const serviceId = parseInt($input.val());
      const $serviceCard = $input.closest(".service-card");
      const isCheckbox = $input.attr("type") === "checkbox";

      this.log(
        "Service selection changed:",
        serviceId,
        "checked:",
        $input.is(":checked")
      );

      if (isCheckbox) {
        // Multiple selection with checkboxes
        if ($input.is(":checked")) {
          if (!this.state.selectedServices.includes(serviceId)) {
            this.state.selectedServices.push(serviceId);
            $serviceCard.addClass("selected");
          }
        } else {
          this.state.selectedServices = this.state.selectedServices.filter(
            (id) => id !== serviceId
          );
          $serviceCard.removeClass("selected");
        }
      } else {
        // Single selection with radio buttons
        this.elements.serviceCards.removeClass("selected");
        this.state.selectedServices = [serviceId];
        $serviceCard.addClass("selected");
      }

      // Add selection animation
      $serviceCard.addClass("selecting");
      setTimeout(() => $serviceCard.removeClass("selecting"), 600);

      this.updatePricing();
      this.updateNavigationButtons();

      this.log("Selected services:", this.state.selectedServices);

      // Show confirmation
      const serviceName =
        this.state.servicesData[serviceId]?.name || `Service ${serviceId}`;
      this.showNotification(
        isCheckbox
          ? $input.is(":checked")
            ? `Added: ${serviceName}`
            : `Removed: ${serviceName}`
          : `Selected: ${serviceName}`,
        "success"
      );
    },

    // Manual navigation handlers - FIXED
    handleNextStep: function ($button) {
      const $currentStep = $button.closest(".booking-step");
      const stepIndex = this.elements.steps.index($currentStep);
      const currentStep = stepIndex + 1;

      this.log("Next step requested from step", currentStep);
      this.nextStep(currentStep);
    },

    handlePrevStep: function ($button) {
      const $currentStep = $button.closest(".booking-step");
      const stepIndex = this.elements.steps.index($currentStep);
      const currentStep = stepIndex + 1;

      this.log("Previous step requested from step", currentStep);
      this.prevStep(currentStep);
    },

    // Manual navigation - FIXED
    nextStep: function (currentStep) {
      if (this.state.isProcessing) {
        this.log("Cannot proceed - form is processing");
        return;
      }

      this.log(
        `Attempting to go from step ${currentStep} to ${currentStep + 1}`
      );

      if (!this.validateStep(currentStep)) {
        this.log("Step validation failed for step", currentStep);
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
          this.log("Should not reach here - step 5 uses submit button");
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
      if (stepNumber < 1 || stepNumber > this.state.totalSteps) {
        this.log("Invalid step number:", stepNumber);
        return;
      }

      this.log(`Showing step ${stepNumber}`);

      this.state.currentStep = stepNumber;

      // Update step visibility
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

      // Focus management
      setTimeout(() => {
        const $activeStep = $(`.booking-step.step-${stepNumber}`);
        const $firstInput = $activeStep
          .find("input:visible, select:visible, textarea:visible")
          .first();
        if ($firstInput.length) {
          $firstInput.focus();
        }
      }, 300);
    },

    // Enhanced step validation - FIXED
    validateStep: function (stepNumber) {
      this.clearFieldErrors();
      this.log("Validating step", stepNumber);

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
          return true;
        default:
          return true;
      }
    },

    validateZipStep: function () {
      const zipCode = this.elements.zipInput.val().trim();
      this.log(
        "Validating ZIP step. ZIP:",
        zipCode,
        "Valid:",
        this.state.isZipValid
      );

      if (!zipCode) {
        this.showFieldError(this.elements.zipInput, "Please enter a ZIP code");
        this.showNotification("Please enter a ZIP code", "error");
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
      this.log(
        "Validating services step. Selected:",
        this.state.selectedServices
      );

      if (
        !this.state.selectedServices ||
        this.state.selectedServices.length === 0
      ) {
        this.showNotification("Please select at least one service", "error");
        this.elements.servicesGrid[0].scrollIntoView({ behavior: "smooth" });
        return false;
      }
      return true;
    },

    validateServiceOptionsStep: function () {
      // Service options validation
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
      }

      if (!isValid && errors.length > 0) {
        this.showNotification(`Please fill in: ${errors.join(", ")}`, "error");
      }

      return isValid;
    },

    // Enhanced navigation button updates - FIXED
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
            $nextBtn.prop("disabled", false).text("Continue to Services");
            this.log("Step 1: Next button enabled - ZIP is valid");
          } else {
            $nextBtn.prop("disabled", true).text("Enter Valid ZIP Code");
            this.log("Step 1: Next button disabled - ZIP not valid");
          }
          break;
        case 2:
          if (
            this.state.selectedServices &&
            this.state.selectedServices.length > 0
          ) {
            $nextBtn.prop("disabled", false).text("Continue to Options");
            this.log("Step 2: Next button enabled - services selected");
          } else {
            $nextBtn.prop("disabled", true).text("Select a Service");
            this.log("Step 2: Next button disabled - no services selected");
          }
          break;
        case 3:
          $nextBtn.prop("disabled", false).text("Continue to Details");
          break;
        case 4:
          $nextBtn.prop("disabled", false).text("Review Booking");
          break;
        case 5:
          $nextBtn.hide();
          break;
        default:
          $nextBtn.prop("disabled", false).text("Continue");
      }
    },

    // Enhanced pricing calculations - FIXED
    updatePricing: function () {
      let subtotal = 0;

      // Calculate base service prices
      this.state.selectedServices.forEach((serviceId) => {
        if (this.state.servicesData[serviceId]) {
          subtotal += this.state.servicesData[serviceId].price;
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
          shouldApply = $checked.length > 0;
        } else if ($input.is("select")) {
          shouldApply = $input.val() !== "";
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

    // Prepare service options - FIXED
    prepareServiceOptions: function () {
      this.log(
        "🔧 Preparing service options for services:",
        this.state.selectedServices
      );
      this.elements.optionsContainer.empty();

      if (
        !this.state.selectedServices ||
        this.state.selectedServices.length === 0
      ) {
        this.log("No services selected for options preparation");
        this.showNoOptionsMessage();
        return;
      }

      let hasAnyOptions = false;
      let optionsLoaded = 0;
      const totalServices = this.state.selectedServices.length;

      this.state.selectedServices.forEach((serviceId) => {
        const serviceData = this.state.servicesData[serviceId];
        if (serviceData && serviceData.hasOptions) {
          hasAnyOptions = true;
          this.loadServiceOptions(serviceId, () => {
            optionsLoaded++;
            if (optionsLoaded === totalServices) {
              this.log("All service options loaded");
            }
          });
        }
      });

      if (!hasAnyOptions) {
        this.log("No selected services have options");
        this.showNoOptionsMessage();
      }
    },

    // Load service options - FIXED
    loadServiceOptions: function (serviceId, callback) {
      this.log(`🔄 Loading options for service ${serviceId}`);

      const serviceData = this.state.servicesData[serviceId];
      if (!serviceData) {
        this.log(`Service data not found for ID: ${serviceId}`);
        if (callback) callback();
        return;
      }

      const data = {
        action: "mobooking_get_service_options",
        service_id: serviceId,
        nonce: this.config.nonces.booking,
      };

      $.ajax({
        url: this.config.ajaxUrl,
        type: "POST",
        data: data,
        timeout: 30000,
        success: (response) => {
          this.log(`Service options response for ${serviceId}:`, response);

          if (response && response.success) {
            if (response.data && Array.isArray(response.data.options)) {
              if (response.data.options.length > 0) {
                this.renderServiceOptions(
                  serviceId,
                  serviceData,
                  response.data.options
                );
              } else {
                this.log(`Service ${serviceId} has no options configured`);
              }
            }
          } else {
            this.log(
              `Failed to load options for service ${serviceId}:`,
              response
            );
            this.showOptionsError(
              serviceId,
              response?.data?.message || "Failed to load options"
            );
          }

          if (callback) callback();
        },
        error: (xhr, status, error) => {
          this.log(`AJAX error loading options for service ${serviceId}:`, {
            status: xhr.status,
            error: error,
          });

          this.showOptionsError(serviceId, `Error loading options: ${error}`);
          if (callback) callback();
        },
      });
    },

    // Render service options - FIXED
    renderServiceOptions: function (serviceId, serviceData, options) {
      this.log(
        `🎨 Rendering ${options.length} options for service ${serviceId}`
      );

      if (!options || options.length === 0) {
        return;
      }

      const $section = $(`
        <div class="service-options-section" data-service-id="${serviceId}">
            <div class="service-options-header">
                <h3 class="service-options-title">
                    ${serviceData.name} Options
                </h3>
                <p class="service-options-subtitle">Customize your ${
                  serviceData.name
                } service</p>
            </div>
            <div class="service-options-fields">
                ${this.generateOptionsHTML(options)}
            </div>
        </div>
      `);

      this.elements.optionsContainer.append($section);
      this.initializeOptionHandlers($section);
    },

    // Show no options message
    showNoOptionsMessage: function () {
      this.elements.optionsContainer.html(`
        <div class="no-options-message">
          <div class="no-options-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
            </svg>
          </div>
          <h3>No Additional Options Needed</h3>
          <p>Your selected services are ready to book. Click "Continue" to proceed.</p>
        </div>
      `);
    },

    // Generate options HTML
    generateOptionsHTML: function (options) {
      if (!Array.isArray(options) || options.length === 0) {
        return '<p class="no-options">No additional options for this service.</p>';
      }

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
          <div class="option-field" 
               data-option-id="${option.id}" 
               data-option-type="${option.type}"
               data-price-type="${option.price_type}" 
               data-price-impact="${option.price_impact}"
               data-service-id="${option.service_id}">
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

    // Generate option input
    generateOptionInput: function (option, isRequired) {
      const requiredAttr = isRequired ? 'required data-required="true"' : "";
      const placeholderAttr = option.placeholder
        ? `placeholder="${option.placeholder}"`
        : "";
      const optionId = `option_${option.id}`;

      switch (option.type) {
        case "text":
          return `<input type="text" id="${optionId}" name="${optionId}" 
                       ${requiredAttr} ${placeholderAttr} 
                       value="${option.default_value || ""}" 
                       class="option-input-field">`;

        case "textarea":
          return `<textarea id="${optionId}" name="${optionId}" 
                       ${requiredAttr} ${placeholderAttr} 
                       rows="${option.rows || 3}"
                       class="option-input-field">${
                         option.default_value || ""
                       }</textarea>`;

        case "number":
        case "quantity":
          return `<input type="number" id="${optionId}" name="${optionId}" 
                    ${requiredAttr} ${placeholderAttr}
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
                    step="${option.step || 1}" 
                    value="${option.default_value || ""}" 
                    class="option-input-field">`;

        case "select":
          const selectOptions = this.parseChoices(option.options);
          let selectHTML = `<select id="${optionId}" name="${optionId}" ${requiredAttr} class="option-input-field">`;

          if (!isRequired) {
            selectHTML += '<option value="">Choose an option...</option>';
          }

          selectOptions.forEach((choice) => {
            const selected =
              choice.value === option.default_value ? "selected" : "";
            selectHTML += `<option value="${choice.value}" ${selected}>
                             ${choice.label}${
              choice.price > 0
                ? " (+" + this.formatPrice(choice.price) + ")"
                : ""
            }
                           </option>`;
          });
          selectHTML += "</select>";
          return selectHTML;

        case "radio":
          const radioOptions = this.parseChoices(option.options);
          let radioHTML = '<div class="radio-group">';

          radioOptions.forEach((choice, index) => {
            const radioId = `${optionId}_${index}`;
            const checked =
              choice.value === option.default_value ? "checked" : "";

            radioHTML += `
              <label class="radio-label" for="${radioId}">
                  <input type="radio" id="${radioId}" 
                         name="${optionId}" value="${choice.value}" 
                         ${checked} ${requiredAttr} class="option-input-field">
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
          const checked = option.default_value == "1" ? "checked" : "";
          return `
            <label class="checkbox-label">
                <input type="checkbox" id="${optionId}" name="${optionId}" 
                       value="1" ${checked} class="option-input-field">
                <span class="checkbox-text">${
                  option.option_label || "Yes"
                }</span>
            </label>
          `;

        default:
          return `<input type="text" id="${optionId}" name="${optionId}" 
                       ${requiredAttr} ${placeholderAttr} 
                       value="${
                         option.default_value || ""
                       }" class="option-input-field">`;
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
      const self = this;

      $section.find(".option-input-field").off(".optionHandler");

      $section
        .find(".option-input-field")
        .on("change.optionHandler input.optionHandler", function () {
          self.updatePricing();
        });
    },

    // Show options error
    showOptionsError: function (serviceId, message) {
      const serviceData = this.state.servicesData[serviceId];
      const serviceName = serviceData
        ? serviceData.name
        : `Service ${serviceId}`;

      const $errorSection = $(`
        <div class="service-options-section error" data-service-id="${serviceId}">
            <div class="service-options-header">
                <h3 class="service-options-title">${serviceName} Options</h3>
            </div>
            <div class="options-error">
                <p class="error-message">⚠️ ${message}</p>
                <button type="button" class="retry-options-btn" data-service-id="${serviceId}">
                    Try Again
                </button>
            </div>
        </div>
      `);

      this.elements.optionsContainer.append($errorSection);

      $errorSection.find(".retry-options-btn").on("click", () => {
        $errorSection.remove();
        this.loadServiceOptions(serviceId);
      });
    },

    // Collect service options data
    collectServiceOptions: function () {
      const optionsData = {};

      this.state.selectedServices.forEach((serviceId) => {
        optionsData[serviceId] = {};

        $(
          `.service-options-section[data-service-id="${serviceId}"] .option-field`
        ).each(function () {
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

    // Set minimum date/time
    setMinDateTime: function () {
      const tomorrow = new Date();
      tomorrow.setDate(tomorrow.getDate() + 1);
      tomorrow.setHours(8, 0, 0, 0);

      const minDateTime = tomorrow.toISOString().slice(0, 16);
      this.elements.customerForm.date.attr("min", minDateTime);
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

    // Build order summary
    buildOrderSummary: function () {
      this.buildServiceSummary();
      this.buildCustomerSummary();
      this.updatePricing();
      this.showDiscountSection();
    },

    // Build service summary
    buildServiceSummary: function () {
      let html = "";

      this.state.selectedServices.forEach((serviceId) => {
        const serviceData = this.state.servicesData[serviceId];
        if (serviceData) {
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
        }
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
        ${
          this.state.validatedArea && this.state.validatedArea.label
            ? `<br><small>(${this.state.validatedArea.label})</small>`
            : ""
        }
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

    // Apply discount code
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
        return;
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
        error: () => {
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

    // Submit booking
    submitBooking: function () {
      if (this.state.isProcessing) return;

      if (!this.validateStep(5)) {
        return;
      }

      this.state.isProcessing = true;
      this.setLoading(this.elements.confirmBtn, true);

      const formData = new FormData(this.elements.form[0]);
      formData.append("action", "mobooking_save_booking");
      formData.append("nonce", this.config.nonces.booking);

      // Add selected services
      this.state.selectedServices.forEach((serviceId) => {
        formData.append("selected_services[]", serviceId);
      });

      $.ajax({
        url: this.config.ajaxUrl,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        timeout: 60000,
        success: (response) => {
          if (response && response.success) {
            $(".reference-number").text("#" + response.data.id);
            this.showStep(6);
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
        error: (xhr) => {
          let errorMessage = "An error occurred. Please try again.";
          if (xhr.status === 0) {
            errorMessage = "Network error. Please check your connection.";
          } else if (xhr.status >= 500) {
            errorMessage = "Server error. Please try again in a few minutes.";
          }
          this.showNotification(errorMessage, "error");
        },
        complete: () => {
          this.state.isProcessing = false;
          this.setLoading(this.elements.confirmBtn, false);
        },
      });
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
          `<div class="message ${type}"><span class="message-text">${message}</span></div>`
        )
        .show();
    },

    showNotification: function (message, type = "info") {
      $(".booking-notification").remove();

      const colors = {
        success: "#22c55e",
        error: "#ef4444",
        warning: "#f59e0b",
        info: "#3b82f6",
      };

      const notification = $(`
        <div class="booking-notification notification-${type}" style="
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

  // Add enhanced styles
  if (!document.getElementById("fixed-booking-styles")) {
    $("<style>")
      .attr("id", "fixed-booking-styles")
      .prop("type", "text/css")
      .html(
        `
        @keyframes slideIn {
          from { transform: translateX(100%); opacity: 0; }
          to { transform: translateX(0); opacity: 1; }
        }
        
        .zip-input-wrapper {
          position: relative;
          display: flex;
          align-items: center;
        }
        
        .zip-validation-icon {
          position: absolute;
          right: 1rem;
          display: flex;
          align-items: center;
          justify-content: center;
          width: 1.5rem;
          height: 1.5rem;
          pointer-events: none;
        }
        
        .zip-validation-icon.success {
          color: #10b981;
          font-weight: bold;
          font-size: 18px;
        }
        
        .zip-validation-icon.error {
          color: #ef4444;
          font-weight: bold;
          font-size: 18px;
        }
        
        .zip-validation-icon .spinner {
          width: 16px;
          height: 16px;
          border: 2px solid #d1d5db;
          border-top-color: #3b82f6;
          border-radius: 50%;
          animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
          to { transform: rotate(360deg); }
        }
        
        .service-card {
          cursor: pointer;
          transition: all 0.3s ease;
          border: 2px solid #e5e7eb;
          border-radius: 8px;
          padding: 1.5rem;
        }
        
        .service-card:hover {
          border-color: #3b82f6;
          transform: translateY(-2px);
          box-shadow: 0 8px 25px rgba(59, 130, 246, 0.15);
        }
        
        .service-card.selected {
          border-color: #3b82f6;
          background: rgba(59, 130, 246, 0.05);
          transform: translateY(-2px);
          box-shadow: 0 8px 25px rgba(59, 130, 246, 0.25);
        }
        
        .service-card.selecting {
          animation: cardPulse 0.6s ease-out;
        }
        
        @keyframes cardPulse {
          0% { transform: scale(1); }
          50% { transform: scale(1.05); }
          100% { transform: scale(1.02); }
        }
        
        .no-options-message {
          text-align: center;
          padding: 3rem 2rem;
          background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));
          border: 2px dashed rgba(16, 185, 129, 0.3);
          border-radius: 12px;
          color: #059669;
        }
        
        .no-options-icon {
          width: 4rem;
          height: 4rem;
          background: #10b981;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          margin: 0 auto 1.5rem;
          color: white;
        }
        
        .no-options-icon svg {
          width: 2rem;
          height: 2rem;
        }
        
        .btn-primary:disabled {
          background: #9ca3af !important;
          cursor: not-allowed !important;
          transform: none !important;
          box-shadow: none !important;
        }
        
        .btn-primary:not(:disabled):hover {
          transform: translateY(-2px);
          box-shadow: 0 8px 25px rgba(59, 130, 246, 0.25);
        }
        
        .zip-result .message.success {
          background: rgba(16, 185, 129, 0.1);
          color: #065f46;
          border: 1px solid rgba(16, 185, 129, 0.3);
          padding: 12px 16px;
          border-radius: 8px;
          font-weight: 500;
          display: flex;
          align-items: center;
          gap: 8px;
        }
        
        .zip-result .message.error {
          background: rgba(239, 68, 68, 0.1);
          color: #991b1b;
          border: 1px solid rgba(239, 68, 68, 0.3);
          padding: 12px 16px;
          border-radius: 8px;
          font-weight: 500;
          display: flex;
          align-items: center;
          gap: 8px;
        }
        
        .field-error {
          color: #ef4444;
          font-size: 0.75rem;
          margin-top: 0.25rem;
          display: flex;
          align-items: center;
          gap: 0.25rem;
        }
        
        .field-error::before {
          content: '⚠';
          font-size: 0.875rem;
        }
        
        .error {
          border-color: #ef4444 !important;
          box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2) !important;
        }
      `
      )
      .appendTo("head");
  }
})(jQuery);
