/**
 * MoBooking Frontend Booking Form Handler - COMPLETE FIXED VERSION
 * Handles multi-step booking form with enhanced error handling and debugging
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
    },

    // State
    state: {
      currentStep: 1,
      totalSteps: 5,
      isProcessing: false,
      selectedServices: [],
      serviceOptions: {},
      pricing: {
        subtotal: 0,
        discount: 0,
        total: 0,
      },
      customerData: {},
      isZipValid: false,
      debugMode: false,
    },

    // Initialize
    init: function () {
      if ($(".mobooking-booking-form-container").length === 0) {
        return;
      }

      // Enable debug mode for troubleshooting
      this.state.debugMode =
        window.location.search.includes("debug=1") ||
        (typeof console !== "undefined" && console.log);

      this.log("🚀 Booking Form initializing...");
      this.log("Config:", this.config);

      // Validate configuration
      if (!this.validateConfig()) {
        return;
      }

      this.cacheElements();
      this.attachEventListeners();
      this.initializeForm();
      this.log("✅ Booking Form initialized");
    },

    // Validate configuration
    validateConfig: function () {
      const issues = [];

      if (!this.config.ajaxUrl) {
        issues.push("AJAX URL not configured");
      }

      if (!this.config.userId || this.config.userId === "0") {
        issues.push("User ID not configured");
      }

      if (!this.config.nonces || !this.config.nonces.booking) {
        issues.push("Security nonce not configured");
      }

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

        // Step 1 - ZIP Code
        zipInput: $("#customer_zip_code"),
        checkZipBtn: $(".check-zip-btn"),
        zipResult: $(".zip-result"),

        // Step 2 - Services
        serviceCards: $(".service-card"),
        serviceCheckboxes: $('input[name="selected_services[]"]'),

        // Step 3 - Options
        optionsContainer: $(".service-options-container"),
        optionsTemplate: $("#service-options-template"),

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
    },

    // Attach event listeners
    attachEventListeners: function () {
      const self = this;

      // ZIP Code validation
      this.elements.checkZipBtn.on("click", function (e) {
        e.preventDefault();
        self.checkZipCode();
      });

      this.elements.zipInput.on("keypress", function (e) {
        if (e.which === 13) {
          e.preventDefault();
          self.checkZipCode();
        }
      });

      // Service selection
      this.elements.serviceCheckboxes.on("change", function () {
        self.handleServiceSelection($(this));
      });

      // Navigation
      this.elements.nextBtns.on("click", function () {
        const currentStep = $(this).closest(".booking-step").index() + 1;
        self.nextStep(currentStep);
      });

      this.elements.prevBtns.on("click", function () {
        const currentStep = $(this).closest(".booking-step").index() + 1;
        self.prevStep(currentStep);
      });

      // Service options change
      $(document).on(
        "change",
        ".option-field input, .option-field select, .option-field textarea",
        function () {
          self.updatePricing();
        }
      );

      // Discount code
      this.elements.applyDiscountBtn.on("click", function () {
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

      // Customer info changes
      Object.values(this.elements.customerForm).forEach((field) => {
        field.on("change", function () {
          self.updateCustomerData();
        });
      });
    },

    // Initialize form
    initializeForm: function () {
      this.updateProgressBar();
      this.setMinDateTime();
    },

    // Set minimum date/time for service
    setMinDateTime: function () {
      const tomorrow = new Date();
      tomorrow.setDate(tomorrow.getDate() + 1);
      tomorrow.setHours(8, 0, 0, 0);

      const minDateTime = tomorrow.toISOString().slice(0, 16);
      this.elements.customerForm.date.attr("min", minDateTime);
    },

    // Check ZIP code availability - COMPLETELY FIXED
    checkZipCode: function () {
      const zipCode = this.elements.zipInput.val().trim();

      this.log("ZIP check requested for:", zipCode);

      if (!zipCode) {
        this.showMessage(
          this.elements.zipResult,
          "Please enter a ZIP code",
          "error"
        );
        return;
      }

      // Validate ZIP code format
      const zipRegex = /^\d{5}(-\d{4})?$/;
      if (!zipRegex.test(zipCode)) {
        this.showMessage(
          this.elements.zipResult,
          "Please enter a valid ZIP code (e.g., 12345 or 12345-6789)",
          "error"
        );
        return;
      }

      this.setLoading(this.elements.checkZipBtn, true);

      const data = {
        action: "mobooking_check_zip_coverage",
        zip_code: zipCode,
        user_id: this.config.userId,
        nonce: this.config.nonces.booking,
      };

      this.log("Sending ZIP check request:", data);

      // FIXED: Enhanced AJAX error handling
      $.ajax({
        url: this.config.ajaxUrl,
        type: "POST",
        data: data,
        timeout: 30000, // 30 second timeout
        success: (response) => {
          this.log("ZIP check response:", response);

          // Handle both old and new response formats
          if (response && response.success) {
            this.state.isZipValid = true;
            this.showMessage(
              this.elements.zipResult,
              response.data?.message || "Service available in your area!",
              "success"
            );

            // Auto-advance after 2 seconds
            setTimeout(() => {
              this.nextStep(1);
            }, 2000);
          } else {
            this.state.isZipValid = false;
            const errorMessage =
              response?.data?.message ||
              response?.message ||
              "Sorry, we don't service this area yet.";
            this.showMessage(this.elements.zipResult, errorMessage, "error");
          }
        },
        error: (xhr, status, error) => {
          this.log("ZIP check error details:");
          this.log("- Status:", xhr.status);
          this.log("- Status Text:", xhr.statusText);
          this.log("- Response Text:", xhr.responseText);
          this.log("- Error:", error);
          this.log("- AJAX Status:", status);

          let errorMessage = "Error checking ZIP code availability.";

          // Provide specific error messages based on status
          switch (xhr.status) {
            case 0:
              errorMessage = "Network error. Please check your connection.";
              break;
            case 400:
              errorMessage = "Invalid request. Please try again.";
              break;
            case 403:
              errorMessage = "Access denied. Please refresh the page.";
              break;
            case 404:
              errorMessage = "Service not found. Please contact support.";
              break;
            case 500:
              errorMessage = "Server error. Please try again later.";
              break;
            default:
              if (xhr.responseText && xhr.responseText !== "0") {
                try {
                  const errorData = JSON.parse(xhr.responseText);
                  errorMessage =
                    errorData.data?.message ||
                    errorData.message ||
                    errorMessage;
                } catch (e) {
                  // If response is not JSON, use status text
                  errorMessage = xhr.statusText || errorMessage;
                }
              }
          }

          this.showMessage(this.elements.zipResult, errorMessage, "error");

          // Show debug info if in debug mode
          if (this.state.debugMode) {
            this.showMessage(
              this.elements.zipResult,
              `Debug: ${xhr.status} - ${xhr.responseText}`,
              "error"
            );
          }
        },
        complete: () => {
          this.setLoading(this.elements.checkZipBtn, false);
        },
      });
    },

    // Handle service selection
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
    },

    // Navigation methods
    nextStep: function (currentStep) {
      if (this.state.isProcessing) return;

      if (!this.validateStep(currentStep)) {
        return;
      }

      switch (currentStep) {
        case 1:
          if (!this.state.isZipValid) {
            this.showNotification(
              "Please check ZIP code availability first",
              "error"
            );
            return;
          }
          break;
        case 2:
          this.loadServiceOptions();
          break;
        case 3:
          this.collectServiceOptions();
          break;
        case 4:
          this.updateCustomerData();
          this.buildOrderSummary();
          break;
      }

      this.showStep(currentStep + 1);
    },

    prevStep: function (currentStep) {
      if (this.state.isProcessing) return;
      this.showStep(currentStep - 1);
    },

    showStep: function (stepNumber) {
      if (stepNumber < 1 || stepNumber > this.state.totalSteps + 1) return;

      this.state.currentStep = stepNumber;

      this.elements.steps.removeClass("active");
      $(`.booking-step.step-${stepNumber}`).addClass("active");

      this.updateProgressBar();
      this.updateProgressSteps();

      this.elements.container[0].scrollIntoView({ behavior: "smooth" });
    },

    // Validation
    validateStep: function (stepNumber) {
      switch (stepNumber) {
        case 1:
          const zipCode = this.elements.zipInput.val().trim();
          if (!zipCode) {
            this.showNotification("Please enter a ZIP code", "error");
            return false;
          }
          return this.state.isZipValid;

        case 2:
          if (this.state.selectedServices.length === 0) {
            this.showNotification(
              "Please select at least one service",
              "error"
            );
            return false;
          }
          return true;

        case 3:
          return this.validateServiceOptions();

        case 4:
          return this.validateCustomerForm();

        default:
          return true;
      }
    },

    validateServiceOptions: function () {
      let isValid = true;

      $(
        '.option-field input[data-required="true"], .option-field select[required], .option-field textarea[required]'
      ).each(function () {
        const $field = $(this);
        const value = $field.val();

        if (!value || (typeof value === "string" && value.trim() === "")) {
          isValid = false;
          $field.addClass("error");
        } else {
          $field.removeClass("error");
        }
      });

      if (!isValid) {
        this.showNotification("Please fill in all required fields", "error");
      }

      return isValid;
    },

    validateCustomerForm: function () {
      let isValid = true;
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      Object.entries(this.elements.customerForm).forEach(([key, $field]) => {
        if ($field.prop("required")) {
          const value = $field.val().trim();
          if (!value) {
            isValid = false;
            $field.addClass("error");
          } else {
            $field.removeClass("error");
          }
        }
      });

      const email = this.elements.customerForm.email.val().trim();
      if (email && !emailRegex.test(email)) {
        isValid = false;
        this.elements.customerForm.email.addClass("error");
        this.showNotification("Please enter a valid email address", "error");
      }

      if (!isValid && !email) {
        this.showNotification("Please fill in all required fields", "error");
      }

      return isValid;
    },

    // Load service options for selected services
    loadServiceOptions: function () {
      this.elements.optionsContainer.empty();

      const servicesWithOptions = this.state.selectedServices.filter(
        (serviceId) => {
          const $checkbox = $(`input[value="${serviceId}"]`);
          return $checkbox.data("has-options") === 1;
        }
      );

      if (servicesWithOptions.length === 0) {
        this.showStep(4);
        return;
      }

      servicesWithOptions.forEach((serviceId) => {
        const $optionsSection = this.elements.optionsTemplate
          .find(`[data-service-id="${serviceId}"]`)
          .clone();
        this.elements.optionsContainer.append($optionsSection);
      });

      this.initializeOptionHandlers();
    },

    initializeOptionHandlers: function () {
      $('.option-field input[type="range"]').on("input", function () {
        const $this = $(this);
        const $display = $this.siblings(".range-display");
        $display.text($this.val());
      });
    },

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
    },

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

    buildOrderSummary: function () {
      this.buildServicesSummary();
      this.buildCustomerSummary();
      this.showDiscountSection();
    },

    buildServicesSummary: function () {
      let html = "";

      this.state.selectedServices.forEach((serviceId) => {
        const $serviceCard = $(`.service-card[data-service-id="${serviceId}"]`);
        const serviceName = $serviceCard.find("h3").text();
        const servicePrice = parseFloat($serviceCard.data("service-price"));

        html += `<div class="service-summary-item">
                    <div class="service-info">
                        <h4>${serviceName}</h4>`;

        if (this.state.serviceOptions[serviceId]) {
          const optionsHtml = this.buildOptionsHtml(serviceId);
          if (optionsHtml) {
            html += `<div class="service-options">${optionsHtml}</div>`;
          }
        }

        html += `</div>
                    <div class="service-price">${this.formatPrice(
                      servicePrice
                    )}</div>
                </div>`;
      });

      $(".selected-services-list").html(html);
    },

    buildOptionsHtml: function (serviceId) {
      let html = "";
      const options = this.state.serviceOptions[serviceId];

      if (!options) return html;

      Object.entries(options).forEach(([optionId, value]) => {
        if (!value || value === "0") return;

        const $optionField = $(`.option-field[data-option-id="${optionId}"]`);
        const optionName = $optionField
          .find(".option-label")
          .text()
          .replace("*", "")
          .trim();

        html += `<div class="option-summary">
                    <span class="option-name">${optionName}:</span>
                    <span class="option-value">${value}</span>
                </div>`;
      });

      return html;
    },

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
        { hour: "2-digit", minute: "2-digit" }
      )}
            `);

      $(".customer-info").html(`
                <div><strong>Name:</strong> ${data.name}</div>
                <div><strong>Email:</strong> ${data.email}</div>
                ${
                  data.phone
                    ? `<div><strong>Phone:</strong> ${data.phone}</div>`
                    : ""
                }
                ${
                  data.notes
                    ? `<div><strong>Notes:</strong> ${data.notes}</div>`
                    : ""
                }
            `);
    },

    showDiscountSection: function () {
      $(".discount-section").show();
    },

    // Pricing calculations
    updatePricing: function () {
      let subtotal = 0;

      this.state.selectedServices.forEach((serviceId) => {
        const $serviceCard = $(`.service-card[data-service-id="${serviceId}"]`);
        const servicePrice =
          parseFloat($serviceCard.data("service-price")) || 0;
        subtotal += servicePrice;
      });

      // Calculate options pricing
      $(".option-field").each(function () {
        const $field = $(this);
        const priceType = $field.data("price-type");
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
              return;
            }
          }
        } else if ($input.is("select")) {
          const $selected = $input.find("option:selected");
          if ($selected.val()) {
            shouldApply = true;
            const choicePrice = parseFloat($selected.data("price")) || 0;
            if (choicePrice > 0) {
              subtotal += choicePrice;
              return;
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
          if (priceType === "percentage") {
            subtotal += (subtotal * priceImpact) / 100;
          } else if (priceType === "multiply") {
            subtotal += priceImpact * multiplier;
          } else {
            subtotal += priceImpact;
          }
        }
      });

      this.state.pricing.subtotal = Math.max(0, subtotal);
      this.state.pricing.total =
        this.state.pricing.subtotal - this.state.pricing.discount;

      this.updatePricingDisplay();
    },

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
      } else {
        $(".pricing-summary .discount").hide();
      }
    },

    // Discount handling - FIXED
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
            this.state.pricing.discount = response.data.discount_amount || 0;
            this.elements.discountAmountField.val(this.state.pricing.discount);
            this.updatePricing();
            this.showMessage(
              $(".discount-message"),
              response.data.message || "Discount applied!",
              "success"
            );
            this.elements.applyDiscountBtn
              .text("Applied")
              .prop("disabled", true);
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
            "Error applying discount code",
            "error"
          );
        },
        complete: () => {
          this.setLoading(this.elements.applyDiscountBtn, false);
        },
      });
    },

    // Submit booking - FIXED with enhanced error handling
    submitBooking: function () {
      if (this.state.isProcessing) return;

      this.state.isProcessing = true;
      this.setLoading(this.elements.confirmBtn, true);

      // Collect all form data
      const formData = new FormData(this.elements.form[0]);
      formData.append("action", "mobooking_save_booking");
      formData.append("nonce", this.config.nonces.booking);

      this.log("Submitting booking with data:", Object.fromEntries(formData));

      $.ajax({
        url: this.config.ajaxUrl,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        timeout: 60000, // 60 second timeout for booking submission
        success: (response) => {
          this.log("Booking submission response:", response);
          if (response && response.success) {
            $(".reference-number").text("#" + response.data.id);
            this.showStep(6); // Success step
            this.showNotification(
              response.data.message || "Booking confirmed!",
              "success"
            );
          } else {
            this.showNotification(
              response?.data?.message ||
                response?.message ||
                "Booking failed. Please try again.",
              "error"
            );
          }
        },
        error: (xhr, status, error) => {
          this.log("Booking submission error:", xhr, status, error);
          this.log("Response text:", xhr.responseText);

          let errorMessage = "An error occurred. Please try again.";

          // Provide more specific error messages
          if (xhr.status === 0) {
            errorMessage =
              "Network error. Please check your connection and try again.";
          } else if (xhr.status >= 500) {
            errorMessage = "Server error. Please try again in a few minutes.";
          } else if (xhr.responseText && xhr.responseText !== "0") {
            try {
              const errorData = JSON.parse(xhr.responseText);
              errorMessage =
                errorData.data?.message || errorData.message || errorMessage;
            } catch (e) {
              // Response is not JSON
              errorMessage = "Booking submission failed. Please try again.";
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

    setLoading: function ($btn, loading) {
      if (loading) {
        $btn.addClass("loading").prop("disabled", true);
      } else {
        $btn.removeClass("loading").prop("disabled", false);
      }
    },

    showMessage: function ($container, message, type) {
      $container.show().html(`
                <div class="message ${type}">
                    <span class="message-text">${message}</span>
                </div>
            `);
    },

    showNotification: function (message, type = "info") {
      const $notification = $(`
                <div class="booking-notification ${type}">
                    ${message}
                </div>
            `);

      $("body").append($notification);

      setTimeout(() => {
        $notification.addClass("show");
      }, 100);

      setTimeout(() => {
        $notification.removeClass("show");
        setTimeout(() => $notification.remove(), 300);
      }, 4000);
    },

    formatPrice: function (amount) {
      const symbol = this.config.currency.symbol;
      const formatted = parseFloat(amount).toFixed(2);

      return this.config.currency.position === "right"
        ? formatted + symbol
        : symbol + formatted;
    },
  };

  // Initialize when document is ready
  $(document).ready(function () {
    BookingForm.init();
  });
})(jQuery);
