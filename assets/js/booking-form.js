/**
 * MoBooking Enhanced Frontend Booking Form Handler - COMPLETE VERSION
 * Features: ZIP validation, single service selection, enhanced UI, auto-progression, debug mode
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
      settings: mobookingBooking?.settings || {},
      zipDebounceDelay: 800,
    },

    // State
    state: {
      currentStep: 1,
      totalSteps: 6,
      isProcessing: false,
      selectedService: null, // Changed to single service selection
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

    // Cache DOM elements - Enhanced for new UI
    cacheElements: function () {
      this.elements = {
        container: $(".mobooking-booking-form-container"),
        form: $("#mobooking-booking-form"),
        steps: $(".booking-step"),
        progressBar: $(".progress-fill"),
        progressSteps: $(".progress-steps .step"),

        // Step 1 - ZIP Code - Enhanced
        zipInput: $("#customer_zip_code"),
        zipInputGroup: $(".zip-input-group"),
        zipResult: $(".zip-result"),
        zipValidationIcon: null,
        zipContinueBtn: null, // Will be created

        // Step 2 - Services - Changed to single selection
        serviceCards: $(".service-card"),
        serviceInputs: $('input[name="selected_services[]"]'),
        servicesGrid: $(".services-grid"),

        // Step 3 - Service Options - Enhanced
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

      // Create enhanced ZIP validation UI
      this.createEnhancedZipUI();
    },

    // Create enhanced ZIP validation UI
    createEnhancedZipUI: function () {
      const $zipInput = this.elements.zipInput;
      const $zipInputGroup = this.elements.zipInputGroup;

      // Only create if not already exists
      if ($zipInput.parent().hasClass("zip-input-wrapper")) {
        this.elements.zipValidationIcon = $zipInput
          .parent()
          .find(".zip-validation-icon");
        this.elements.zipContinueBtn = $zipInputGroup.find(".zip-continue-btn");
        return;
      }

      // Create enhanced wrapper
      $zipInput.wrap('<div class="zip-input-wrapper"></div>');
      const $wrapper = $zipInput.parent();

      // Add validation icon
      $wrapper.append('<div class="zip-validation-icon"></div>');

      // Add compact continue button within the ZIP section
      $zipInputGroup.append(`
        <div class="zip-action-section">
          <button type="button" class="zip-continue-btn btn-primary" disabled>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M5 12h14M12 5l7 7-7 7"/>
            </svg>
            <span class="btn-text">Continue to Services</span>
          </button>
        </div>
      `);

      // Add area display section
      $zipInputGroup.append('<div class="zip-area-display"></div>');

      this.elements.zipValidationIcon = $wrapper.find(".zip-validation-icon");
      this.elements.zipContinueBtn = $zipInputGroup.find(".zip-continue-btn");

      this.log("Enhanced ZIP UI created");
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

    // Attach event listeners - Enhanced
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

      // ZIP continue button
      $(document).on("click", ".zip-continue-btn", function (e) {
        e.preventDefault();
        self.nextStep(1);
      });

      // Service selection - Changed to single selection
      $(document).on(
        "change",
        'input[name="selected_services[]"]',
        function () {
          self.handleServiceSelection($(this));
        }
      );

      // Service card clicks for better UX
      $(document).on("click", ".service-card", function (e) {
        if (!$(e.target).is('input[type="checkbox"]')) {
          const $checkbox = $(this).find('input[type="checkbox"]');
          $checkbox
            .prop("checked", !$checkbox.is(":checked"))
            .trigger("change");
        }
      });

      // Enhanced option handlers
      $(document).on("click", ".quantity-btn", function () {
        self.handleQuantityChange($(this));
      });

      $(document).on("click", ".custom-dropdown-trigger", function () {
        self.toggleCustomDropdown($(this));
      });

      $(document).on("click", ".custom-dropdown-option", function () {
        self.selectDropdownOption($(this));
      });

      // Close dropdowns when clicking outside
      $(document).on("click", function (e) {
        if (!$(e.target).closest(".custom-dropdown").length) {
          $(".custom-dropdown").removeClass("open");
        }
      });

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
        ".option-field input, .option-field textarea",
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

      // Check ZIP validation setting
      if (!this.config.settings.enableZipValidation) {
        this.log("ZIP validation disabled - skipping ZIP step");
        this.state.isZipValid = true;
        this.state.currentStep = 2;
        this.showStep(2);
      }

      this.log("Form initialized");
    },

    // Enhanced ZIP code handling
    handleZipInput: function () {
      // If ZIP validation is disabled, always allow continuation
      if (!this.config.settings.enableZipValidation) {
        this.state.isZipValid = true;
        this.updateZipValidationIcon("success");
        this.updateNavigationButtons();
        return;
      }

      const zipCode = this.elements.zipInput.val().trim();
      this.log("ZIP input changed:", zipCode);

      // Clear previous timer
      clearTimeout(this.state.zipDebounceTimer);

      // Reset validation state
      this.state.isZipValid = false;
      this.state.validatedArea = null;

      // Clear results
      this.elements.zipResult.empty();
      $(".zip-area-display").empty();

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
      if (!this.config.settings.enableZipValidation) {
        return;
      }

      const zipCode = this.elements.zipInput.val().trim();
      if (zipCode && !this.state.isZipValid) {
        clearTimeout(this.state.zipDebounceTimer);
        this.validateZipCode();
      }
    },

    // Enhanced ZIP validation
    validateZipCode: function () {
      // If ZIP validation is disabled, always pass
      if (!this.config.settings.enableZipValidation) {
        this.state.isZipValid = true;
        this.updateZipValidationIcon("success");
        this.showAreaDisplay("ZIP validation disabled");
        this.updateNavigationButtons();
        return;
      }

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
            let areaDisplay = `ZIP ${zipCode}`;

            if (this.state.validatedArea?.label) {
              successMessage = `✓ Service available in ${this.state.validatedArea.label}!`;
              areaDisplay = `${this.state.validatedArea.label} (${zipCode})`;
            }

            this.showMessage(
              this.elements.zipResult,
              successMessage,
              "success"
            );

            this.showAreaDisplay(areaDisplay);
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

    // Show area display
    showAreaDisplay: function (areaText) {
      $(".zip-area-display").html(`
        <div class="area-badge">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
            <circle cx="12" cy="10" r="3"></circle>
          </svg>
          <span>${areaText}</span>
        </div>
      `);
    },

    // Update ZIP validation icon
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

    // Helper function to load service data from DOM if missing
    loadServiceDataFromDOM: function (serviceId, $serviceCard) {
      if (!$serviceCard || $serviceCard.length === 0) {
        $serviceCard = $(`.service-card[data-service-id="${serviceId}"]`);
      }

      if ($serviceCard.length > 0) {
        this.state.servicesData[serviceId] = {
          id: serviceId,
          name: $serviceCard.find("h3").text().trim() || `Service ${serviceId}`,
          price: parseFloat($serviceCard.data("service-price")) || 0,
          hasOptions:
            $serviceCard.find('input[type="checkbox"]').data("has-options") ===
            1,
          description: $serviceCard.find(".service-description").text().trim(),
          duration: $serviceCard.find(".service-duration").text().trim(),
        };

        this.log(
          "Loaded service data from DOM:",
          this.state.servicesData[serviceId]
        );
      } else {
        this.log("⚠️ Could not find service card for ID:", serviceId);
      }
    },

    // Enhanced version of handleServiceSelection to ensure state is properly set
    handleServiceSelection: function ($input) {
      const serviceId = parseInt($input.val());
      const $serviceCard = $input.closest(".service-card");

      this.log(
        "Service selection changed:",
        serviceId,
        "checked:",
        $input.is(":checked")
      );

      // Clear all previous selections
      this.elements.serviceCards.removeClass("selected");
      this.elements.serviceInputs.not($input).prop("checked", false);

      // Set new selection
      if ($input.is(":checked")) {
        this.state.selectedService = serviceId;
        $serviceCard.addClass("selected");

        // Ensure the service data is available
        if (!this.state.servicesData[serviceId]) {
          this.log(
            "⚠️ Service data not found for ID:",
            serviceId,
            "Loading from DOM..."
          );
          this.loadServiceDataFromDOM(serviceId, $serviceCard);
        }
      } else {
        this.state.selectedService = null;
      }

      // Add selection animation
      $serviceCard.addClass("selecting");
      setTimeout(() => $serviceCard.removeClass("selecting"), 600);

      this.updatePricing();
      this.updateNavigationButtons();

      this.log("Selected service updated to:", this.state.selectedService);
      this.log("Current services data:", this.state.servicesData);

      // Show confirmation
      if (this.state.selectedService) {
        const serviceName =
          this.state.servicesData[serviceId]?.name || `Service ${serviceId}`;
        this.showNotification(`Selected: ${serviceName}`, "success");
      }
    },

    // Enhanced quantity button handler
    handleQuantityChange: function ($btn) {
      const $input = $btn.siblings('input[type="number"]');
      const isIncrement = $btn.hasClass("quantity-increment");
      const currentValue = parseInt($input.val()) || 0;
      const min = parseInt($input.attr("min")) || 0;
      const max = parseInt($input.attr("max")) || 999;
      const step = parseInt($input.attr("step")) || 1;

      let newValue = currentValue;

      if (isIncrement) {
        newValue = Math.min(max, currentValue + step);
      } else {
        newValue = Math.max(min, currentValue - step);
      }

      $input.val(newValue).trigger("change");

      // Update button states
      $btn.siblings(".quantity-decrement").prop("disabled", newValue <= min);
      $btn.siblings(".quantity-increment").prop("disabled", newValue >= max);
    },

    // Custom dropdown handlers
    toggleCustomDropdown: function ($trigger) {
      const $dropdown = $trigger.closest(".custom-dropdown");
      $(".custom-dropdown").not($dropdown).removeClass("open");
      $dropdown.toggleClass("open");
    },

    selectDropdownOption: function ($option) {
      const $dropdown = $option.closest(".custom-dropdown");
      const $trigger = $dropdown.find(".custom-dropdown-trigger");
      const $hiddenInput = $dropdown.find("input[type='hidden']");

      const value = $option.data("value");
      const text = $option.text();

      $trigger.find(".dropdown-selected-text").text(text);
      $hiddenInput.val(value).trigger("change");

      $dropdown.removeClass("open");

      // Update selected state
      $option.siblings().removeClass("selected");
      $option.addClass("selected");
    },

    // Manual navigation handlers
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

    // Manual navigation
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

    // Enhanced step validation
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
      // If ZIP validation is disabled, always pass
      if (!this.config.settings.enableZipValidation) {
        return true;
      }

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

    // Enhanced validateServicesStep to ensure selection is properly detected
    validateServicesStep: function () {
      this.log(
        "Validating services step. Selected:",
        this.state.selectedService
      );

      // First try to get from state
      if (!this.state.selectedService) {
        // Try to get from DOM as fallback
        const $selectedCheckbox = $(
          'input[name="selected_services[]"]:checked'
        );
        if ($selectedCheckbox.length > 0) {
          this.state.selectedService = parseInt($selectedCheckbox.val());
          this.log(
            "Found selected service from DOM during validation:",
            this.state.selectedService
          );
        }
      }

      if (!this.state.selectedService) {
        this.showNotification("Please select a service", "error");
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
        const $input = $field.find(
          "input, select, textarea, .custom-dropdown input[type='hidden']"
        );
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

    // Enhanced navigation button updates
    updateNavigationButtons: function () {
      const $currentStep = $(`.booking-step.step-${this.state.currentStep}`);
      const $nextBtn = $currentStep.find(".next-step");
      const $prevBtn = $currentStep.find(".prev-step");
      const $zipContinueBtn = this.elements.zipContinueBtn;

      // Update previous button
      if (this.state.currentStep <= 1) {
        $prevBtn.hide();
      } else {
        $prevBtn.show();
      }

      // Update next button based on step validation
      switch (this.state.currentStep) {
        case 1:
          // Handle ZIP continue button
          if ($zipContinueBtn && $zipContinueBtn.length) {
            if (
              this.state.isZipValid ||
              !this.config.settings.enableZipValidation
            ) {
              $zipContinueBtn
                .prop("disabled", false)
                .find(".btn-text")
                .text("Continue to Services");
              this.log("Step 1: ZIP continue button enabled");
            } else {
              $zipContinueBtn
                .prop("disabled", true)
                .find(".btn-text")
                .text("Enter Valid ZIP Code");
              this.log("Step 1: ZIP continue button disabled");
            }
          }

          // Regular next button
          if (
            this.state.isZipValid ||
            !this.config.settings.enableZipValidation
          ) {
            $nextBtn.prop("disabled", false).text("Continue to Services");
          } else {
            $nextBtn.prop("disabled", true).text("Enter Valid ZIP Code");
          }
          break;
        case 2:
          if (this.state.selectedService) {
            $nextBtn.prop("disabled", false).text("Continue to Options");
            this.log("Step 2: Next button enabled - service selected");
          } else {
            $nextBtn.prop("disabled", true).text("Select a Service");
            this.log("Step 2: Next button disabled - no service selected");
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

    // Enhanced pricing calculations
    updatePricing: function () {
      let subtotal = 0;

      // Calculate base service price (single service)
      if (
        this.state.selectedService &&
        this.state.servicesData[this.state.selectedService]
      ) {
        subtotal = this.state.servicesData[this.state.selectedService].price;
      }

      // Calculate option pricing
      $(".option-field").each(function () {
        const $field = $(this);
        const priceType = $field.data("price-type") || "fixed";
        const priceImpact = parseFloat($field.data("price-impact")) || 0;

        if (priceImpact === 0) return;

        const $input = $field.find(
          "input, textarea, .custom-dropdown input[type='hidden']"
        );
        let shouldApply = false;
        let multiplier = 1;

        if ($input.is('input[type="checkbox"]')) {
          shouldApply = $input.is(":checked");
        } else if ($input.is('input[type="radio"]')) {
          const $checked = $field.find('input[type="radio"]:checked');
          shouldApply = $checked.length > 0;
        } else if (
          $input.is("input[type='hidden']") &&
          $input.closest(".custom-dropdown").length
        ) {
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

    // Prepare service options
    prepareServiceOptions: function () {
      this.log(
        "🔧 Preparing service options for service:",
        this.state.selectedService
      );
      this.elements.optionsContainer.empty();

      if (!this.state.selectedService) {
        this.log("No service selected for options preparation");
        this.showNoOptionsMessage();
        return;
      }

      const serviceData = this.state.servicesData[this.state.selectedService];
      if (serviceData && serviceData.hasOptions) {
        this.loadServiceOptions(this.state.selectedService);
      } else {
        this.log("Selected service has no options");
        this.showNoOptionsMessage();
      }
    },

    // Load service options
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
                this.showNoOptionsMessage();
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

    // Render service options with enhanced UI
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
               ${this.generateEnhancedOptionsHTML(options)}
           </div>
       </div>
     `);

      this.elements.optionsContainer.append($section);
      this.initializeEnhancedOptionHandlers($section);
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
         <p>Your selected service is ready to book. Click "Continue" to proceed.</p>
       </div>
     `);
    },

    // Generate enhanced options HTML
    generateEnhancedOptionsHTML: function (options) {
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
         <div class="option-field enhanced-option" 
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
             <div class="enhanced-option-input">
                 ${this.generateEnhancedOptionInput(option, isRequired)}
             </div>
         </div>
       `;
      });

      return html;
    },

    // Generate enhanced option input with better styling
    generateEnhancedOptionInput: function (option, isRequired) {
      const requiredAttr = isRequired ? 'required data-required="true"' : "";
      const placeholderAttr = option.placeholder
        ? `placeholder="${option.placeholder}"`
        : "";
      const optionId = `option_${option.id}`;

      switch (option.type) {
        case "text":
          return `
           <div class="enhanced-text-input">
             <input type="text" id="${optionId}" name="${optionId}" 
                    ${requiredAttr} ${placeholderAttr} 
                    value="${option.default_value || ""}" 
                    class="enhanced-input">
           </div>`;

        case "textarea":
          return `
           <div class="enhanced-textarea-input">
             <textarea id="${optionId}" name="${optionId}" 
                      ${requiredAttr} ${placeholderAttr} 
                      rows="${option.rows || 3}"
                      class="enhanced-textarea">${
                        option.default_value || ""
                      }</textarea>
           </div>`;

        case "number":
        case "quantity":
          return `
           <div class="enhanced-quantity-input">
             <button type="button" class="quantity-btn quantity-decrement" ${
               option.min_value !== null &&
               parseFloat(option.default_value || 0) <= option.min_value
                 ? "disabled"
                 : ""
             }>
               <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                 <path d="M5 12h14"/>
               </svg>
             </button>
             <input type="number" id="${optionId}" name="${optionId}" 
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
                   class="enhanced-number-input">
             <button type="button" class="quantity-btn quantity-increment" ${
               option.max_value !== null &&
               parseFloat(option.default_value || 0) >= option.max_value
                 ? "disabled"
                 : ""
             }>
               <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                 <path d="M12 5v14M5 12h14"/>
               </svg>
             </button>
             ${
               option.unit
                 ? `<span class="input-unit">${option.unit}</span>`
                 : ""
             }
           </div>`;

        case "select":
          const selectOptions = this.parseChoices(option.options);
          const defaultText = isRequired
            ? "Choose an option..."
            : "Select (optional)";
          const defaultValue = option.default_value || "";

          let optionsHTML = "";
          selectOptions.forEach((choice) => {
            const selected = choice.value === defaultValue ? "selected" : "";
            optionsHTML += `
             <div class="custom-dropdown-option ${selected}" data-value="${
              choice.value
            }">
               ${choice.label}${
              choice.price > 0 ? ` (+${this.formatPrice(choice.price)})` : ""
            }
             </div>`;
          });

          const selectedOption = selectOptions.find(
            (opt) => opt.value === defaultValue
          );
          const displayText = selectedOption
            ? selectedOption.label
            : defaultText;

          return `
           <div class="custom-dropdown enhanced-dropdown">
             <input type="hidden" id="${optionId}" name="${optionId}" value="${defaultValue}" ${requiredAttr}>
             <div class="custom-dropdown-trigger">
               <span class="dropdown-selected-text">${displayText}</span>
               <svg class="dropdown-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                 <path d="M6 9l6 6 6-6"/>
               </svg>
             </div>
             <div class="custom-dropdown-menu">
               ${
                 !isRequired
                   ? `<div class="custom-dropdown-option" data-value="">${defaultText}</div>`
                   : ""
               }
               ${optionsHTML}
             </div>
           </div>`;

        case "radio":
          const radioOptions = this.parseChoices(option.options);
          let radioHTML = '<div class="enhanced-radio-group">';

          radioOptions.forEach((choice, index) => {
            const radioId = `${optionId}_${index}`;
            const checked =
              choice.value === option.default_value ? "checked" : "";

            radioHTML += `
             <div class="enhanced-radio-option">
               <input type="radio" id="${radioId}" 
                      name="${optionId}" value="${choice.value}" 
                      ${checked} ${requiredAttr} class="enhanced-radio-input">
               <label for="${radioId}" class="enhanced-radio-label">
                 <div class="radio-indicator"></div>
                 <div class="radio-content">
                   <span class="radio-text">${choice.label}</span>
                   ${
                     choice.price > 0
                       ? `<span class="choice-price">+${this.formatPrice(
                           choice.price
                         )}</span>`
                       : ""
                   }
                 </div>
               </label>
             </div>
           `;
          });
          radioHTML += "</div>";
          return radioHTML;

        case "checkbox":
          const checked = option.default_value == "1" ? "checked" : "";
          return `
           <div class="enhanced-checkbox-input">
             <input type="checkbox" id="${optionId}" name="${optionId}" 
                    value="1" ${checked} class="enhanced-checkbox" ${requiredAttr}>
             <label for="${optionId}" class="enhanced-checkbox-label">
               <div class="checkbox-indicator">
                 <svg class="checkbox-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                   <path d="M20 6L9 17l-5-5"/>
                 </svg>
               </div>
               <span class="checkbox-text">${
                 option.option_label || "Yes"
               }</span>
             </label>
           </div>
         `;

        default:
          return `
           <div class="enhanced-text-input">
             <input type="text" id="${optionId}" name="${optionId}" 
                    ${requiredAttr} ${placeholderAttr} 
                    value="${option.default_value || ""}" 
                    class="enhanced-input">
           </div>`;
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

    // Initialize enhanced option handlers
    initializeEnhancedOptionHandlers: function ($section) {
      const self = this;

      $section
        .find(
          ".enhanced-input, .enhanced-textarea, .enhanced-number-input, .enhanced-checkbox, .enhanced-radio-input"
        )
        .off(".optionHandler");

      $section
        .find(
          ".enhanced-input, .enhanced-textarea, .enhanced-number-input, .enhanced-checkbox, .enhanced-radio-input"
        )
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

    // Fixed collectServiceOptions function - COMPLETE VERSION
    collectServiceOptions: function () {
      const optionsData = {};

      this.log(
        "🔧 Collecting service options. Selected service:",
        this.state.selectedService
      );
      this.log("🔧 Services data:", this.state.servicesData);

      // Enhanced validation - check if we have a selected service
      if (!this.state.selectedService) {
        this.log(
          "⚠️ No service selected - collecting options for all services with data"
        );

        // Fallback: try to get selected service from DOM
        const $selectedCheckbox = $(
          'input[name="selected_services[]"]:checked'
        );
        if ($selectedCheckbox.length > 0) {
          this.state.selectedService = parseInt($selectedCheckbox.val());
          this.log(
            "🔧 Found selected service from DOM:",
            this.state.selectedService
          );
        } else {
          // If still no service, check service cards
          const $selectedCard = $(".service-card.selected");
          if ($selectedCard.length > 0) {
            this.state.selectedService = parseInt(
              $selectedCard.data("service-id")
            );
            this.log(
              "🔧 Found selected service from card:",
              this.state.selectedService
            );
          }
        }
      }

      // If we still don't have a selected service, return empty options
      if (!this.state.selectedService) {
        this.log(
          "⚠️ No service selected after all attempts - returning empty options"
        );
        this.state.serviceOptions = {};
        this.elements.serviceOptionsField.val("{}");
        return;
      }

      // Ensure the service exists in our services data
      if (!this.state.servicesData[this.state.selectedService]) {
        this.log(
          "⚠️ Selected service not found in services data:",
          this.state.selectedService
        );
        this.state.serviceOptions = {};
        this.elements.serviceOptionsField.val("{}");
        return;
      }

      // Initialize options for the selected service
      optionsData[this.state.selectedService] = {};

      // Find options container for the selected service
      const $serviceOptionsSection = $(
        `.service-options-section[data-service-id="${this.state.selectedService}"]`
      );

      if ($serviceOptionsSection.length === 0) {
        this.log(
          "🔧 No options section found for service:",
          this.state.selectedService
        );
        // This is okay - the service might not have options
        this.state.serviceOptions = optionsData;
        this.elements.serviceOptionsField.val(JSON.stringify(optionsData));
        return;
      }

      this.log("🔧 Found options section, collecting options...");

      // Collect options from the service options section
      $serviceOptionsSection.find(".option-field").each(function () {
        const $field = $(this);
        const optionId = $field.data("option-id");

        if (!optionId) {
          console.warn("Option field missing option-id:", $field);
          return; // Skip this field
        }

        // Find the input/select/textarea within this option field
        const $input = $field.find(
          'input, select, textarea, .custom-dropdown input[type="hidden"]'
        );

        if ($input.length === 0) {
          console.warn("No input found for option:", optionId);
          return; // Skip this field
        }

        let value = null;

        // Handle different input types
        if ($input.is('input[type="checkbox"]')) {
          value = $input.is(":checked") ? "1" : "0";
        } else if ($input.is('input[type="radio"]')) {
          const $checkedRadio = $field.find('input[type="radio"]:checked');
          value = $checkedRadio.length > 0 ? $checkedRadio.val() : "";
        } else if ($input.hasClass("enhanced-radio-input")) {
          // Handle enhanced radio inputs
          const $checkedRadio = $field.find(".enhanced-radio-input:checked");
          value = $checkedRadio.length > 0 ? $checkedRadio.val() : "";
        } else if (
          $input.is('input[type="hidden"]') &&
          $input.closest(".custom-dropdown").length
        ) {
          // Handle custom dropdown
          value = $input.val() || "";
        } else {
          // Handle text, number, textarea, etc.
          value = $input.val() || "";
        }

        // Store the value
        optionsData[this.state.selectedService][optionId] = value;

        console.log(`Collected option ${optionId}:`, value);
      });

      // Update state and hidden field
      this.state.serviceOptions = optionsData;
      this.elements.serviceOptionsField.val(JSON.stringify(optionsData));

      this.log("✅ Service options collected:", optionsData);
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

    // Build service summary (updated for single service)
    buildServiceSummary: function () {
      let html = "";

      if (this.state.selectedService) {
        const serviceData = this.state.servicesData[this.state.selectedService];
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
               ${this.buildServiceOptionsHTML(this.state.selectedService)}
             </div>
             <div class="service-price">${this.formatPrice(
               serviceData.price
             )}</div>
           </div>
         `;
        }
      }

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
        const $input = $optionField.find(
          "input, textarea, .custom-dropdown input[type='hidden']"
        );

        if ($input.is('input[type="checkbox"]') && value === "1") {
          displayValue = "Yes";
        } else if ($input.closest(".custom-dropdown").length) {
          const $selectedOption = $optionField.find(
            `.custom-dropdown-option[data-value="${value}"]`
          );
          if ($selectedOption.length) {
            displayValue = $selectedOption
              .text()
              .replace(/\(.*\)/, "")
              .trim();
          }
        } else if ($input.is('input[type="radio"]')) {
          const $selected = $optionField.find(
            `input[value="${value}"]:checked`
          );
          if ($selected.length) {
            displayValue = $selected.parent().find(".radio-text").text();
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

    // Submit booking (updated for single service)
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

      // Add selected service (single service)
      if (this.state.selectedService) {
        formData.append("selected_services[]", this.state.selectedService);
      }

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

    // Format price with currency
    formatPrice: function (price) {
      const symbol = this.config.currency.symbol || "$";
      const position = this.config.currency.position || "left";
      const formattedPrice = parseFloat(price).toFixed(2);

      if (position === "right") {
        return formattedPrice + symbol;
      }
      return symbol + formattedPrice;
    },

    // Enhanced notification system
    showNotification: function (message, type = "info") {
      $(".booking-notification").remove();

      const colors = {
        success: "#22c55e",
        error: "#ef4444",
        warning: "#f59e0b",
        info: "#3b82f6",
      };

      const icons = {
        success: "✓",
        error: "✗",
        warning: "⚠",
        info: "ℹ",
      };

      const notification = $(`
   <div class="booking-notification ${type}">
     <div class="notification-content">
       <span class="notification-icon">${icons[type]}</span>
       <span class="notification-message">${message}</span>
       <button class="notification-close" aria-label="Close">×</button>
     </div>
   </div>
 `);

      notification.css("background-color", colors[type]);
      $("body").append(notification);

      // Show notification with animation
      setTimeout(() => notification.addClass("show"), 100);

      // Auto-hide after 5 seconds (except for errors)
      if (type !== "error") {
        setTimeout(() => {
          notification.removeClass("show");
          setTimeout(() => notification.remove(), 300);
        }, 5000);
      }

      // Manual close
      notification.find(".notification-close").on("click", function () {
        notification.removeClass("show");
        setTimeout(() => notification.remove(), 300);
      });

      this.log(`Notification shown: ${type} - ${message}`);
    },

    // Debug and error handling
    handleError: function (error, context) {
      this.log("Error in", context, ":", error);

      if (this.state.debugMode) {
        console.error("MoBooking Error:", error);
      }

      let userMessage = "An unexpected error occurred. Please try again.";

      if (error.status === 0) {
        userMessage =
          "Network connection error. Please check your internet connection.";
      } else if (error.status === 400) {
        userMessage = "Invalid request. Please refresh the page and try again.";
      } else if (error.status === 403) {
        userMessage = "Permission denied. Please refresh the page.";
      } else if (error.status >= 500) {
        userMessage = "Server error. Please try again in a few minutes.";
      }

      this.showNotification(userMessage, "error");
    },

    // Cleanup methods
    destroy: function () {
      // Remove event listeners
      $(document).off(".bookingForm");
      this.elements.form.off(".bookingForm");

      // Clear timers
      if (this.state.zipDebounceTimer) {
        clearTimeout(this.state.zipDebounceTimer);
      }

      // Remove notifications
      $(".booking-notification").remove();

      this.log("BookingForm destroyed");
    },

    // Accessibility enhancements
    enhanceAccessibility: function () {
      // Add ARIA labels and descriptions
      this.elements.zipInput.attr({
        "aria-describedby": "zip-help",
        "aria-required": "true",
      });

      // Add keyboard navigation for service cards
      this.elements.serviceCards
        .attr("tabindex", "0")
        .on("keydown", function (e) {
          if (e.key === "Enter" || e.key === " ") {
            e.preventDefault();
            $(this)
              .find('input[type="radio"]')
              .prop("checked", true)
              .trigger("change");
          }
        });

      // Enhance focus management
      this.elements.steps.each(function (index) {
        $(this).attr("aria-hidden", index !== 0 ? "true" : "false");
      });

      // Add progress announcements for screen readers
      this.elements.progressSteps.each(function (index) {
        $(this).attr("aria-label", `Step ${index + 1} of 6`);
      });

      this.log("Accessibility enhancements applied");
    },

    // Performance optimizations
    optimizePerformance: function () {
      // Debounce resize events
      let resizeTimer;
      $(window).on("resize.bookingForm", () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
          this.handleResize();
        }, 250);
      });

      // Lazy load service options only when needed
      this.lazyLoadOptions = true;

      // Optimize DOM queries by caching more elements
      this.cacheAdditionalElements();

      this.log("Performance optimizations applied");
    },

    cacheAdditionalElements: function () {
      // Cache frequently accessed elements
      this.cachedElements = {
        body: $("body"),
        window: $(window),
        document: $(document),
      };
    },

    handleResize: function () {
      // Handle responsive adjustments
      const isMobile = window.innerWidth < 768;

      if (isMobile) {
        this.elements.container.addClass("mobile-layout");
      } else {
        this.elements.container.removeClass("mobile-layout");
      }
    },

    // Form data persistence (optional)
    saveFormData: function () {
      if (!window.localStorage) return;

      const formData = {
        step: this.state.currentStep,
        zipCode: this.elements.zipInput.val(),
        selectedService: this.state.selectedService,
        customerData: this.state.customerData,
        timestamp: Date.now(),
      };

      try {
        localStorage.setItem("mobooking_form_data", JSON.stringify(formData));
        this.log("Form data saved to localStorage");
      } catch (e) {
        this.log("Failed to save form data:", e);
      }
    },

    loadFormData: function () {
      if (!window.localStorage) return;

      try {
        const saved = localStorage.getItem("mobooking_form_data");
        if (!saved) return;

        const formData = JSON.parse(saved);
        const age = Date.now() - formData.timestamp;

        // Only restore data if less than 1 hour old
        if (age > 3600000) {
          localStorage.removeItem("mobooking_form_data");
          return;
        }

        // Restore form state
        if (formData.zipCode) {
          this.elements.zipInput.val(formData.zipCode);
        }

        if (formData.customerData) {
          Object.entries(formData.customerData).forEach(([key, value]) => {
            if (this.elements.customerForm[key]) {
              this.elements.customerForm[key].val(value);
            }
          });
        }

        this.log("Form data restored from localStorage");
      } catch (e) {
        this.log("Failed to load form data:", e);
        localStorage.removeItem("mobooking_form_data");
      }
    },

    clearFormData: function () {
      if (window.localStorage) {
        localStorage.removeItem("mobooking_form_data");
      }
    },

    // Analytics integration (optional)
    trackEvent: function (action, step, data = {}) {
      if (typeof gtag === "function") {
        gtag("event", action, {
          event_category: "booking_form",
          event_label: `step_${step}`,
          custom_map: data,
        });
      }

      if (typeof fbq === "function") {
        fbq("track", "CustomEvent", {
          action: action,
          step: step,
          ...data,
        });
      }

      this.log("Event tracked:", action, step, data);
    },

    // Final initialization with error handling
    safeInit: function () {
      try {
        this.init();
      } catch (error) {
        console.error("MoBooking initialization failed:", error);
        this.showNotification(
          "Booking form failed to load. Please refresh the page.",
          "error"
        );
      }
    },
  };

  // Auto-initialize when DOM is ready
  $(document).ready(function () {
    if ($(".mobooking-booking-form-container").length > 0) {
      BookingForm.safeInit();

      // Apply performance optimizations
      BookingForm.optimizePerformance();

      // Enhance accessibility
      BookingForm.enhanceAccessibility();

      // Load any saved form data
      BookingForm.loadFormData();
    }
  });

  // Handle page unload
  $(window).on("beforeunload", function () {
    if (
      BookingForm.state.currentStep > 1 &&
      BookingForm.state.currentStep < 6
    ) {
      BookingForm.saveFormData();
    }
  });

  // Handle page visibility changes
  document.addEventListener("visibilitychange", function () {
    if (document.hidden) {
      BookingForm.saveFormData();
    }
  });

  // Clean up on page unload
  $(window).on("unload", function () {
    BookingForm.destroy();
  });

  // Make BookingForm globally available for debugging
  window.MoBookingForm = BookingForm;

  // Additional CSS for enhanced notifications
  const notificationCSS = `
<style>
.booking-notification {
 position: fixed;
 top: 20px;
 right: 20px;
 z-index: 9999;
 min-width: 300px;
 max-width: 500px;
 border-radius: 8px;
 box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
 transform: translateX(100%);
 opacity: 0;
 transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
 backdrop-filter: blur(8px);
}

.booking-notification.show {
 transform: translateX(0);
 opacity: 1;
}

.notification-content {
 display: flex;
 align-items: center;
 padding: 16px 20px;
 color: white;
 font-weight: 500;
 gap: 12px;
}

.notification-icon {
 font-size: 18px;
 font-weight: bold;
 flex-shrink: 0;
}

.notification-message {
 flex: 1;
 line-height: 1.4;
}

.notification-close {
 background: rgba(255, 255, 255, 0.2);
 border: none;
 color: white;
 width: 24px;
 height: 24px;
 border-radius: 50%;
 cursor: pointer;
 display: flex;
 align-items: center;
 justify-content: center;
 font-size: 18px;
 flex-shrink: 0;
 transition: background-color 0.2s ease;
}

.notification-close:hover {
 background: rgba(255, 255, 255, 0.3);
}

.mobile-layout .booking-notification {
 left: 10px;
 right: 10px;
 max-width: none;
}

@media (max-width: 480px) {
 .booking-notification {
   left: 10px;
   right: 10px;
   max-width: none;
 }
 
 .notification-content {
   padding: 14px 16px;
   font-size: 14px;
 }
}

/* Enhanced loading spinner */
.spinner {
 width: 16px;
 height: 16px;
 border: 2px solid rgba(255, 255, 255, 0.3);
 border-top-color: currentColor;
 border-radius: 50%;
 animation: spin 1s linear infinite;
}

@keyframes spin {
 to {
   transform: rotate(360deg);
 }
}

/* Enhanced option styling */
.enhanced-option {
 background: white;
 border: 1px solid #e5e7eb;
 border-radius: 8px;
 padding: 20px;
 margin-bottom: 16px;
 transition: all 0.2s ease;
}

.enhanced-option:hover {
 border-color: #d1d5db;
 box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.enhanced-option:focus-within {
 border-color: var(--booking-primary);
 box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Enhanced quantity controls */
.enhanced-quantity-input {
 display: flex;
 align-items: center;
 gap: 8px;
 max-width: 200px;
}

.quantity-btn {
 width: 36px;
 height: 36px;
 border: 1px solid #d1d5db;
 background: white;
 border-radius: 6px;
 display: flex;
 align-items: center;
 justify-content: center;
 cursor: pointer;
 transition: all 0.2s ease;
}

.quantity-btn:hover:not(:disabled) {
 border-color: var(--booking-primary);
 background: rgba(59, 130, 246, 0.05);
}

.quantity-btn:disabled {
 opacity: 0.5;
 cursor: not-allowed;
}

.enhanced-number-input {
 flex: 1;
 text-align: center;
 min-width: 60px;
}

/* Enhanced dropdown styling */
.custom-dropdown {
 position: relative;
}

.custom-dropdown-trigger {
 display: flex;
 align-items: center;
 justify-content: space-between;
 padding: 12px 16px;
 border: 1px solid #d1d5db;
 border-radius: 6px;
 background: white;
 cursor: pointer;
 transition: all 0.2s ease;
}

.custom-dropdown-trigger:hover {
 border-color: var(--booking-primary);
}

.custom-dropdown.open .custom-dropdown-trigger {
 border-color: var(--booking-primary);
 box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.custom-dropdown-menu {
 position: absolute;
 top: 100%;
 left: 0;
 right: 0;
 background: white;
 border: 1px solid #d1d5db;
 border-radius: 6px;
 box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
 z-index: 1000;
 max-height: 200px;
 overflow-y: auto;
 opacity: 0;
 transform: translateY(-10px);
 pointer-events: none;
 transition: all 0.2s ease;
}

.custom-dropdown.open .custom-dropdown-menu {
 opacity: 1;
 transform: translateY(0);
 pointer-events: all;
}

.custom-dropdown-option {
 padding: 12px 16px;
 cursor: pointer;
 transition: background-color 0.2s ease;
}

.custom-dropdown-option:hover {
 background: rgba(59, 130, 246, 0.05);
}

.custom-dropdown-option.selected {
 background: rgba(59, 130, 246, 0.1);
 color: var(--booking-primary);
}

/* Area badge styling */
.area-badge {
 display: inline-flex;
 align-items: center;
 gap: 8px;
 padding: 8px 12px;
 background: rgba(16, 185, 129, 0.1);
 border: 1px solid rgba(16, 185, 129, 0.2);
 border-radius: 20px;
 color: #059669;
 font-size: 14px;
 font-weight: 500;
 margin-top: 12px;
}

.area-badge svg {
 width: 16px;
 height: 16px;
}

/* ZIP continue button styling */
.zip-continue-btn {
 margin-top: 16px;
 width: 100%;
 display: flex;
 align-items: center;
 justify-content: center;
 gap: 8px;
 padding: 12px 24px;
 font-weight: 600;
 transition: all 0.2s ease;
}

.zip-continue-btn svg {
 width: 18px;
 height: 18px;
}

.zip-continue-btn:disabled {
 opacity: 0.6;
 cursor: not-allowed;
}

/* Enhanced radio and checkbox styling */
.enhanced-radio-option, .enhanced-checkbox-input {
 margin-bottom: 12px;
}

.enhanced-radio-label, .enhanced-checkbox-label {
 display: flex;
 align-items: center;
 gap: 12px;
 padding: 12px;
 border: 1px solid #e5e7eb;
 border-radius: 6px;
 cursor: pointer;
 transition: all 0.2s ease;
}

.enhanced-radio-label:hover, .enhanced-checkbox-label:hover {
 border-color: var(--booking-primary);
 background: rgba(59, 130, 246, 0.02);
}

.enhanced-radio-input:checked + .enhanced-radio-label,
.enhanced-checkbox:checked + .enhanced-checkbox-label {
 border-color: var(--booking-primary);
 background: rgba(59, 130, 246, 0.05);
}

.radio-indicator, .checkbox-indicator {
 width: 20px;
 height: 20px;
 border: 2px solid #d1d5db;
 border-radius: 50%;
 display: flex;
 align-items: center;
 justify-content: center;
 transition: all 0.2s ease;
}

.checkbox-indicator {
 border-radius: 4px;
}

.enhanced-radio-input:checked + .enhanced-radio-label .radio-indicator,
.enhanced-checkbox:checked + .enhanced-checkbox-label .checkbox-indicator {
 border-color: var(--booking-primary);
 background: var(--booking-primary);
}

.checkbox-check {
 width: 12px;
 height: 12px;
 stroke: white;
 opacity: 0;
 transition: opacity 0.2s ease;
}

.enhanced-checkbox:checked + .enhanced-checkbox-label .checkbox-check {
 opacity: 1;
}

.radio-indicator::after {
 content: '';
 width: 8px;
 height: 8px;
 border-radius: 50%;
 background: white;
 opacity: 0;
 transition: opacity 0.2s ease;
}

.enhanced-radio-input:checked + .enhanced-radio-label .radio-indicator::after {
 opacity: 1;
}

/* Responsive enhancements */
@media (max-width: 768px) {
 .enhanced-quantity-input {
   max-width: 160px;
 }
 
 .quantity-btn {
   width: 32px;
   height: 32px;
 }
 
 .custom-dropdown-menu {
   max-height: 150px;
 }
 
 .enhanced-radio-label, .enhanced-checkbox-label {
   padding: 10px;
 }
}
</style>
`;

  // Inject CSS
  $("head").append(notificationCSS);
})(jQuery);
