/**
 * Complete Working Service Form Handler
 * Handles both service data and options with proper debugging and fallback mechanisms
 */
(function ($) {
  "use strict";

  console.log("=== Service Form Handler Loading ===");

  // Cache DOM elements
  const elements = {
    serviceForm: $("#unified-service-form"),
    serviceName: $("#service-name"),
    servicePrice: $("#service-price"),
    serviceDuration: $("#service-duration"),
    saveButton: $("#save-service-button"),
    optionsContainer: $("#service-options-container"),
    addOptionButton: $(".add-new-option-btn"),
    tabButtons: $(".tab-button"),
    tabPanes: $(".tab-pane"),
    deleteButtons: $(".delete-service-btn"),
    confirmationModal: $("#confirmation-modal"),
    confirmDeleteBtn: $(".confirm-delete-btn"),
    cancelDeleteBtn: $(".cancel-delete-btn"),
    modalClose: $(".modal-close"),
    selectImageBtn: $(".select-image"),
    imagePreview: $(".image-preview"),
    iconItems: $(".icon-item"),
    iconSelect: $("#service-icon"),
    iconPreview: $(".icon-preview"),
  };

  // Application state
  const state = {
    nextOptionIndex: 0,
    deleteTarget: null,
    deleteType: null,
    deleteElement: null,
    isSubmitting: false,
  };

  // Option type definitions
  const optionTypes = {
    checkbox: "Checkbox",
    text: "Text Input",
    number: "Number Input",
    select: "Dropdown Select",
    radio: "Radio Buttons",
    textarea: "Text Area",
    quantity: "Quantity Selector",
  };

  // Initialize the form handler
  function init() {
    console.log("Initializing Service Form Handler...");

    // Only initialize if the form exists
    if (elements.serviceForm.length === 0) {
      console.log("Service form not found, skipping initialization");
      return;
    }

    // Set initial option index
    state.nextOptionIndex =
      elements.optionsContainer.find(".option-card").length;
    console.log("Initial option count:", state.nextOptionIndex);

    attachEventListeners();
    initSortables();
    initMediaUploader();
    updateAllOptionIndices();

    console.log("Service Form Handler initialized successfully");
  }

  // Attach all event listeners
  function attachEventListeners() {
    console.log("Attaching event listeners...");

    // Form submission
    elements.serviceForm.on("submit", handleFormSubmit);

    // Tab switching
    elements.tabButtons.on("click", function () {
      switchTab($(this).data("tab"));
    });

    // Add new option
    elements.addOptionButton.on("click", addNewOption);

    // Option card interactions (using event delegation)
    elements.optionsContainer.on("click", ".edit-option-btn", function (e) {
      e.preventDefault();
      toggleOptionDetails($(this).closest(".option-card"));
    });

    elements.optionsContainer.on("click", ".remove-option-btn", function (e) {
      e.preventDefault();
      removeOption($(this).closest(".option-card"));
    });

    // Option type change
    elements.optionsContainer.on("change", ".option-type-select", function () {
      updateOptionTypeFields($(this).closest(".option-card"), $(this).val());
    });

    // Price type change
    elements.optionsContainer.on("change", ".price-type-select", function () {
      updatePriceFields($(this).closest(".option-card"), $(this).val());
    });

    // Choice management for select/radio options
    elements.optionsContainer.on("click", ".add-choice-btn", function (e) {
      e.preventDefault();
      addChoiceRow($(this).prev(".choices-list"));
    });

    elements.optionsContainer.on("click", ".remove-choice-btn", function (e) {
      e.preventDefault();
      removeChoiceRow($(this));
    });

    // Update option name in header when changed
    elements.optionsContainer.on("input", 'input[name$="[name]"]', function () {
      const optionCard = $(this).closest(".option-card");
      const newName = $(this).val() || "New Option";
      optionCard.find(".option-name").text(newName);
    });

    // Service deletion
    elements.deleteButtons.on("click", function () {
      showDeleteConfirmation("service", $(this).data("id"));
    });

    // Confirmation modal
    elements.confirmDeleteBtn.on("click", handleDeleteConfirmation);
    elements.cancelDeleteBtn.on("click", hideConfirmationModal);
    elements.modalClose.on("click", hideConfirmationModal);

    // Icon selection
    elements.iconItems.on("click", function () {
      selectIcon($(this).data("icon"));
    });

    console.log("Event listeners attached");
  }

  // Initialize sortable functionality
  function initSortables() {
    if ($.fn.sortable && elements.optionsContainer.length) {
      elements.optionsContainer.sortable({
        handle: ".option-drag-handle",
        items: ".option-card",
        placeholder: "option-card-placeholder",
        axis: "y",
        opacity: 0.8,
        tolerance: "pointer",
        update: function () {
          updateAllOptionIndices();
        },
      });
      console.log("Options sortable initialized");
    }
  }

  // Initialize media uploader
  function initMediaUploader() {
    if (
      !elements.selectImageBtn.length ||
      typeof wp === "undefined" ||
      !wp.media
    ) {
      return;
    }

    let mediaUploader;

    elements.selectImageBtn.on("click", function (e) {
      e.preventDefault();

      if (mediaUploader) {
        mediaUploader.open();
        return;
      }

      mediaUploader = wp.media({
        title: "Choose Image",
        button: { text: "Select" },
        multiple: false,
      });

      mediaUploader.on("select", function () {
        const attachment = mediaUploader
          .state()
          .get("selection")
          .first()
          .toJSON();
        $("#service-image").val(attachment.url);
        elements.imagePreview.html('<img src="' + attachment.url + '" alt="">');
      });

      mediaUploader.open();
    });

    console.log("Media uploader initialized");
  }

  // Switch between tabs
  function switchTab(tabId) {
    elements.tabButtons.removeClass("active");
    elements.tabButtons.filter('[data-tab="' + tabId + '"]').addClass("active");
    elements.tabPanes.removeClass("active");
    $("#" + tabId).addClass("active");
  }

  // Select an icon
  function selectIcon(icon) {
    elements.iconSelect.val(icon);
    elements.iconPreview.html('<span class="dashicons ' + icon + '"></span>');
  }

  // Handle form submission
  function handleFormSubmit(e) {
    e.preventDefault();

    if (state.isSubmitting) {
      console.log("Form submission already in progress");
      return;
    }

    console.log("=== FORM SUBMISSION STARTED ===");

    // Validate form
    if (!validateForm()) {
      console.log("Form validation failed");
      return;
    }

    state.isSubmitting = true;
    showLoading(elements.saveButton);

    // Prepare and submit form data
    submitFormData();
  }

  // Validate the form
  function validateForm() {
    console.log("Validating form...");

    let isValid = true;
    clearErrors();

    // Validate service name
    if (!elements.serviceName.val().trim()) {
      showError(elements.serviceName, "Service name is required");
      isValid = false;
    }

    // Validate price
    const price = parseFloat(elements.servicePrice.val());
    if (isNaN(price) || price <= 0) {
      showError(elements.servicePrice, "Price must be greater than zero");
      isValid = false;
    }

    // Validate duration
    const duration = parseInt(elements.serviceDuration.val());
    if (isNaN(duration) || duration < 15) {
      showError(
        elements.serviceDuration,
        "Duration must be at least 15 minutes"
      );
      isValid = false;
    }

    // Validate options
    elements.optionsContainer.find(".option-card").each(function () {
      const optionCard = $(this);
      const nameField = optionCard.find('input[name$="[name]"]');
      const type = optionCard.find('select[name$="[type]"]').val();

      if (!nameField.val().trim()) {
        showError(nameField, "Option name is required");
        // Open the option details if closed
        const details = optionCard.find(".option-card-details");
        if (!details.is(":visible")) {
          details.show();
        }
        isValid = false;
      }

      // For select/radio, validate choices
      if (
        (type === "select" || type === "radio") &&
        optionCard.find(".choices-list").length
      ) {
        let hasValidChoice = false;
        optionCard.find(".choice-row").each(function () {
          if ($(this).find("input").first().val().trim()) {
            hasValidChoice = true;
          }
        });

        if (!hasValidChoice) {
          const choicesContainer = optionCard.find(".choices-container");
          showError(
            choicesContainer,
            "At least one choice with a value is required"
          );
          isValid = false;
        }
      }
    });

    console.log("Form validation result:", isValid);
    return isValid;
  }

  // Submit form data
  function submitFormData() {
    console.log("Preparing form data for submission...");

    const serviceData = collectServiceData();
    const optionsData = collectOptionsData();

    console.log("Service data:", serviceData);
    console.log("Options data:", optionsData);

    // Try unified save first
    const unifiedData = Object.assign({}, serviceData, {
      action: "mobooking_save_unified_service",
      options: optionsData,
    });

    console.log("Attempting unified save...");

    $.ajax({
      url: getAjaxUrl(),
      type: "POST",
      data: unifiedData,
      success: function (response) {
        console.log("Unified save response:", response);
        handleSaveSuccess(response, "unified");
      },
      error: function (xhr, status, error) {
        console.log("Unified save failed, trying fallback method...");
        console.error("Unified save error:", error);

        // Fallback to two-step save
        fallbackSave(serviceData, optionsData);
      },
    });
  }

  // Fallback save method
  function fallbackSave(serviceData, optionsData) {
    console.log("Using fallback save method...");

    // First save the service
    $.ajax({
      url: getAjaxUrl(),
      type: "POST",
      data: Object.assign({}, serviceData, {
        action: "mobooking_save_service_ajax",
      }),
      success: function (serviceResponse) {
        console.log("Service save response:", serviceResponse);

        if (serviceResponse.success) {
          const serviceId = serviceResponse.data.id;

          if (optionsData.length > 0) {
            // Now save options
            saveOptionsData(serviceId, optionsData);
          } else {
            handleSaveSuccess(serviceResponse, "service-only");
          }
        } else {
          handleSaveError(
            serviceResponse.data.message || "Failed to save service"
          );
        }
      },
      error: function (xhr, status, error) {
        console.error("Service save error:", error);
        handleSaveError("Error saving service: " + error);
      },
    });
  }

  // Save options data
  function saveOptionsData(serviceId, optionsData) {
    console.log("Saving options for service ID:", serviceId);

    $.ajax({
      url: getAjaxUrl(),
      type: "POST",
      data: {
        action: "mobooking_debug_options_save",
        service_nonce: getNonce(),
        service_id: serviceId,
        options: optionsData,
      },
      success: function (optionsResponse) {
        console.log("Options save response:", optionsResponse);

        if (optionsResponse.success) {
          handleSaveSuccess(optionsResponse, "with-options");
        } else {
          handleSaveError(
            "Options failed to save: " + optionsResponse.data.message
          );
        }
      },
      error: function (xhr, status, error) {
        console.error("Options save error:", error);
        console.log("Response text:", xhr.responseText);

        // Try direct save as last resort
        directSaveOptions(serviceId, optionsData);
      },
    });
  }

  // Direct save options (last resort)
  function directSaveOptions(serviceId, optionsData) {
    console.log("Attempting direct options save...");

    $.ajax({
      url: getAjaxUrl(),
      type: "POST",
      data: {
        action: "mobooking_direct_save_service_options",
        service_nonce: getNonce(),
        id: serviceId,
        options: JSON.stringify(optionsData),
      },
      success: function (response) {
        console.log("Direct save response:", response);

        if (response.success) {
          handleSaveSuccess(response, "direct");
        } else {
          handleSaveError("All save methods failed: " + response.data.message);
        }
      },
      error: function (xhr, status, error) {
        console.error("Direct save error:", error);
        handleSaveError(
          "All save methods failed. Please check your database configuration."
        );
      },
    });
  }

  // Handle successful save
  function handleSaveSuccess(response, method) {
    console.log("Save successful using method:", method);

    state.isSubmitting = false;
    hideLoading(elements.saveButton);

    showNotification("Service and options saved successfully!", "success");

    // Redirect or reload
    setTimeout(function () {
      const serviceId = response.data.id || $("#service-id").val();
      if (!$("#service-id").val() && serviceId) {
        // New service - redirect to edit page
        window.location.href =
          window.location.pathname + "?view=edit&service_id=" + serviceId;
      } else {
        // Existing service - reload page
        window.location.reload();
      }
    }, 1500);
  }

  // Handle save error
  function handleSaveError(message) {
    console.error("Save error:", message);

    state.isSubmitting = false;
    hideLoading(elements.saveButton);
    showNotification("Error: " + message, "error");
  }

  // Collect service data from form
  function collectServiceData() {
    return {
      service_nonce: getNonce(),
      id: $("#service-id").val(),
      service_id: $("#service-id").val(), // For backward compatibility
      name: elements.serviceName.val(),
      description: $("#service-description").val(),
      price: elements.servicePrice.val(),
      duration: elements.serviceDuration.val(),
      icon: elements.iconSelect.val(),
      category: $("#service-category").val(),
      image_url: $("#service-image").val(),
      status: $("#service-status").val(),
    };
  }

  // Collect options data from form
  function collectOptionsData() {
    const options = [];

    elements.optionsContainer.find(".option-card").each(function (index) {
      const card = $(this);
      const type = card.find('select[name$="[type]"]').val() || "checkbox";

      const option = {
        name: card.find('input[name$="[name]"]').val(),
        description: card.find('input[name$="[description]"]').val() || "",
        type: type,
        is_required: card.find('select[name$="[is_required]"]').val() || "0",
        price_type: card.find('select[name$="[price_type]"]').val() || "fixed",
        price_impact: card.find('input[name$="[price_impact]"]').val() || "0",
        display_order: index,
      };

      // Add type-specific fields
      switch (type) {
        case "checkbox":
          option.default_value =
            card.find('select[name$="[default_value]"]').val() || "0";
          option.option_label =
            card.find('input[name$="[option_label]"]').val() || "";
          break;

        case "select":
        case "radio":
          option.options = formatChoicesAsString(card.find(".choices-list"));
          option.default_value =
            card.find('input[name$="[default_value]"]').val() || "";
          break;

        case "number":
        case "quantity":
          option.min_value =
            card.find('input[name$="[min_value]"]').val() || "";
          option.max_value =
            card.find('input[name$="[max_value]"]').val() || "";
          option.default_value =
            card.find('input[name$="[default_value]"]').val() || "";
          option.step = card.find('input[name$="[step]"]').val() || "1";
          option.unit = card.find('input[name$="[unit]"]').val() || "";
          break;

        case "text":
          option.default_value =
            card.find('input[name$="[default_value]"]').val() || "";
          option.placeholder =
            card.find('input[name$="[placeholder]"]').val() || "";
          option.min_length =
            card.find('input[name$="[min_length]"]').val() || "";
          option.max_length =
            card.find('input[name$="[max_length]"]').val() || "";
          break;

        case "textarea":
          option.default_value =
            card.find('textarea[name$="[default_value]"]').val() || "";
          option.placeholder =
            card.find('input[name$="[placeholder]"]').val() || "";
          option.rows = card.find('input[name$="[rows]"]').val() || "3";
          option.max_length =
            card.find('input[name$="[max_length]"]').val() || "";
          break;
      }

      if (option.name && option.name.trim()) {
        options.push(option);
      }
    });

    return options;
  }

  // Format choices as string for storage
  function formatChoicesAsString(choicesList) {
    const choices = [];

    choicesList.find(".choice-row").each(function () {
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
  }

  // Add new option
  function addNewOption() {
    console.log("Adding new option...");

    const optionIndex = state.nextOptionIndex++;
    const optionHtml = createOptionCardHtml(optionIndex);

    elements.optionsContainer.append(optionHtml);

    // Initialize the new option
    const newCard = elements.optionsContainer.find(".option-card").last();
    initChoicesSortable(newCard.find(".choices-list"));

    // Show the details by default for new options
    newCard.find(".option-card-details").show();

    // Scroll to the new option
    $("html, body").animate(
      {
        scrollTop: newCard.offset().top - 100,
      },
      500
    );
  }

  // Create option card HTML
  function createOptionCardHtml(index) {
    return `
            <div class="option-card" data-option-index="${index}">
                <div class="option-card-header">
                    <div class="option-drag-handle">
                        <span class="dashicons dashicons-menu"></span>
                    </div>
                    <div class="option-title">
                        <span class="option-name">New Option</span>
                        <span class="option-type">Checkbox</span>
                    </div>
                    <div class="option-actions">
                        <button type="button" class="button button-small edit-option-btn">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button type="button" class="button button-small remove-option-btn">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
                <div class="option-card-details" style="display: none;">
                    <div class="option-form">
                        <div class="form-row">
                            <div class="form-group half">
                                <label>Option Name <span class="required">*</span></label>
                                <input type="text" name="options[${index}][name]" value="New Option" required>
                            </div>
                            <div class="form-group half">
                                <label>Option Type</label>
                                <select name="options[${index}][type]" class="option-type-select">
                                    ${Object.keys(optionTypes)
                                      .map(
                                        (key) =>
                                          `<option value="${key}">${optionTypes[key]}</option>`
                                      )
                                      .join("")}
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group half">
                                <label>Required?</label>
                                <select name="options[${index}][is_required]">
                                    <option value="0">Optional</option>
                                    <option value="1">Required</option>
                                </select>
                            </div>
                            <div class="form-group half">
                                <label>Description</label>
                                <input type="text" name="options[${index}][description]" value="">
                            </div>
                        </div>
                        
                        <div class="option-type-fields">
                            <!-- Default checkbox fields -->
                            <div class="form-row">
                                <div class="form-group half">
                                    <label>Default Value</label>
                                    <select name="options[${index}][default_value]">
                                        <option value="0">Unchecked</option>
                                        <option value="1">Checked</option>
                                    </select>
                                </div>
                                <div class="form-group half">
                                    <label>Option Label</label>
                                    <input type="text" name="options[${index}][option_label]" value="">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row price-impact-section">
                            <div class="form-group half">
                                <label>Price Impact Type</label>
                                <select name="options[${index}][price_type]" class="price-type-select">
                                    <option value="fixed">Fixed Amount</option>
                                    <option value="percentage">Percentage</option>
                                    <option value="multiply">Multiply by Value</option>
                                    <option value="none">No Price Impact</option>
                                </select>
                            </div>
                            <div class="form-group half price-impact-value">
                                <label>Price Impact Value</label>
                                <input type="number" name="options[${index}][price_impact]" value="0" step="0.01">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
  }

  // Toggle option details
  function toggleOptionDetails(optionCard) {
    const details = optionCard.find(".option-card-details");

    if (details.is(":visible")) {
      details.slideUp(200);
    } else {
      // Close other open details
      $(".option-card-details").not(details).slideUp(200);
      details.slideDown(200);
    }
  }

  // Remove option
  function removeOption(optionCard) {
    const optionId = optionCard.find('input[name$="[id]"]').val();

    if (optionId) {
      showDeleteConfirmation("option", optionId, optionCard);
    } else {
      // Just remove new options
      optionCard.fadeOut(300, function () {
        $(this).remove();
        updateAllOptionIndices();
      });
    }
  }

  // Update option type fields
  function updateOptionTypeFields(optionCard, type) {
    const fieldsContainer = optionCard.find(".option-type-fields");
    const optionIndex = optionCard.data("option-index");

    // Update type display in header
    optionCard.find(".option-type").text(optionTypes[type] || type);

    // Clear current fields
    fieldsContainer.empty();

    // Add type-specific fields
    let fieldsHtml = "";

    switch (type) {
      case "checkbox":
        fieldsHtml = `
                    <div class="form-row">
                        <div class="form-group half">
                            <label>Default Value</label>
                            <select name="options[${optionIndex}][default_value]">
                                <option value="0">Unchecked</option>
                                <option value="1">Checked</option>
                            </select>
                        </div>
                        <div class="form-group half">
                            <label>Option Label</label>
                            <input type="text" name="options[${optionIndex}][option_label]" value="">
                        </div>
                    </div>
                `;
        break;

      case "select":
      case "radio":
        fieldsHtml = `
                    <div class="form-group">
                        <label>Choices</label>
                        <div class="choices-container">
                            <div class="choices-list"></div>
                            <button type="button" class="add-choice-btn button-secondary">Add Choice</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Default Value</label>
                        <input type="text" name="options[${optionIndex}][default_value]" value="" 
                               placeholder="Enter the value of the default choice">
                    </div>
                `;
        break;

      // Add other types as needed...
    }

    fieldsContainer.html(fieldsHtml);

    // Initialize choices for select/radio
    if (type === "select" || type === "radio") {
      const choicesList = fieldsContainer.find(".choices-list");
      addChoiceRow(choicesList); // Add initial choice
      initChoicesSortable(choicesList);
    }
  }

  // Add choice row
  function addChoiceRow(choicesList) {
    const choiceIndex = choicesList.find(".choice-row").length;

    const choiceHtml = `
            <div class="choice-row">
                <div class="choice-drag-handle">
                    <span class="dashicons dashicons-menu"></span>
                </div>
                <div class="choice-value">
                    <input type="text" placeholder="Value" value="">
                </div>
                <div class="choice-label">
                    <input type="text" placeholder="Label" value="">
                </div>
                <div class="choice-price">
                    <input type="number" placeholder="0.00" value="0" step="0.01">
                </div>
                <div class="choice-actions">
                    <button type="button" class="remove-choice-btn">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            </div>
        `;

    choicesList.append(choiceHtml);
  }

  // Remove choice row
  function removeChoiceRow(button) {
    const choiceRow = button.closest(".choice-row");
    const choicesList = choiceRow.parent();

    if (choicesList.find(".choice-row").length <= 1) {
      showNotification("You must have at least one choice", "warning");
      return;
    }

    choiceRow.remove();
  }

  // Initialize choices sortable
  function initChoicesSortable(choicesList) {
    if ($.fn.sortable && choicesList.length) {
      choicesList.sortable({
        handle: ".choice-drag-handle",
        items: ".choice-row",
        placeholder: "choice-row-placeholder",
        axis: "y",
        opacity: 0.8,
      });
    }
  }

  // Update price fields
  function updatePriceFields(optionCard, priceType) {
    const valueContainer = optionCard.find(".price-impact-value");

    if (priceType === "none") {
      valueContainer.hide();
    } else {
      valueContainer.show();
    }
  }

  // Update all option indices
  function updateAllOptionIndices() {
    elements.optionsContainer.find(".option-card").each(function (index) {
      const card = $(this);
      card.attr("data-option-index", index);

      // Update all input names
      card.find("input, select, textarea").each(function () {
        const input = $(this);
        const name = input.attr("name");

        if (name && name.includes("options[")) {
          const newName = name.replace(
            /options\[\d+\]/,
            "options[" + index + "]"
          );
          input.attr("name", newName);
        }
      });
    });

    state.nextOptionIndex =
      elements.optionsContainer.find(".option-card").length;
  }

  // Show/hide loading state
  function showLoading(button) {
    button.prop("disabled", true);

    const normalState = button.find(".normal-state");
    const loadingState = button.find(".loading-state");

    if (normalState.length && loadingState.length) {
      normalState.hide();
      loadingState.show();
    } else {
      button.data("original-text", button.text());
      button.text("Saving...");
    }
  }

  function hideLoading(button) {
    button.prop("disabled", false);

    const normalState = button.find(".normal-state");
    const loadingState = button.find(".loading-state");

    if (normalState.length && loadingState.length) {
      normalState.show();
      loadingState.hide();
    } else if (button.data("original-text")) {
      button.text(button.data("original-text"));
    }
  }

  // Show error
  function showError(element, message) {
    element.addClass("has-error");

    // Remove existing error
    element.next(".field-error").remove();

    // Add error message
    element.after('<div class="field-error">' + message + "</div>");
  }

  // Clear errors
  function clearErrors() {
    $(".has-error").removeClass("has-error");
    $(".field-error").remove();
  }

  // Show notification
  function showNotification(message, type = "info") {
    $("#mobooking-notification").remove();

    const colors = {
      success: "#4CAF50",
      error: "#f44336",
      warning: "#ff9800",
      info: "#2196F3",
    };

    const notification = $(`
            <div id="mobooking-notification" style="
                position: fixed; 
                top: 20px; 
                right: 20px; 
                background: ${colors[type]}; 
                color: white; 
                padding: 15px 20px; 
                border-radius: 5px; 
                z-index: 9999;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                max-width: 300px;
                font-weight: 500;
            ">${message}</div>
        `);

    $("body").append(notification);

    setTimeout(function () {
      notification.fadeOut(300, function () {
        $(this).remove();
      });
    }, 4000);
  }

  // Delete confirmation
  function showDeleteConfirmation(type, id, element) {
    state.deleteType = type;
    state.deleteTarget = id;
    state.deleteElement = element;

    elements.confirmationModal.show();
  }

  function hideConfirmationModal() {
    elements.confirmationModal.hide();
    state.deleteType = null;
    state.deleteTarget = null;
    state.deleteElement = null;
  }

  function handleDeleteConfirmation() {
    if (state.deleteType === "service") {
      deleteService(state.deleteTarget);
    } else if (state.deleteType === "option") {
      deleteOption(state.deleteTarget, state.deleteElement);
    }
  }

  function deleteService(serviceId) {
    $.ajax({
      url: getAjaxUrl(),
      type: "POST",
      data: {
        action: "mobooking_delete_service",
        id: serviceId,
        nonce: getNonce(),
      },
      success: function (response) {
        if (response.success) {
          showNotification("Service deleted successfully", "success");
          setTimeout(function () {
            window.location.href = window.location.pathname + "?view=list";
          }, 1000);
        } else {
          showNotification("Error deleting service", "error");
        }
        hideConfirmationModal();
      },
      error: function () {
        showNotification("Error deleting service", "error");
        hideConfirmationModal();
      },
    });
  }

  function deleteOption(optionId, optionElement) {
    if (optionElement) {
      optionElement.fadeOut(300, function () {
        $(this).remove();
        updateAllOptionIndices();
      });
    }
    hideConfirmationModal();
  }

  // Utility functions
  function getAjaxUrl() {
    return (
      (typeof mobookingData !== "undefined" && mobookingData.ajaxUrl) ||
      (typeof mobooking_data !== "undefined" && mobooking_data.ajax_url) ||
      "/wp-admin/admin-ajax.php"
    );
  }

  function getNonce() {
    return (
      (typeof mobookingData !== "undefined" && mobookingData.serviceNonce) ||
      (typeof mobooking_data !== "undefined" && mobooking_data.nonce) ||
      ""
    );
  }

  // Initialize when document is ready
  $(document).ready(function () {
    init();
  });
})(jQuery);
