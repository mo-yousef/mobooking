/**
 * Unified Service Form Handler
 * Handles both service data and options in a single form submission
 */
(function ($) {
  "use strict";

  // Cache DOM elements
  const elements = {
    // Main form elements
    serviceForm: $("#unified-service-form"),
    serviceName: $("#service-name"),
    servicePrice: $("#service-price"),
    serviceDuration: $("#service-duration"),
    saveButton: $("#save-service-button"),
    deleteButtons: $(".delete-service-btn"),

    // Options elements
    optionsContainer: $("#service-options-container"),
    addOptionButton: $(".add-new-option-btn"),
    newOptionTemplate: $("#new-option-template"),

    // Tab navigation
    tabButtons: $(".tab-button"),
    tabPanes: $(".tab-pane"),

    // Confirmation modal
    confirmationModal: $("#confirmation-modal"),
    confirmDeleteBtn: $(".confirm-delete-btn"),
    cancelDeleteBtn: $(".cancel-delete-btn"),
    modalClose: $(".modal-close"),

    // Media elements
    selectImageBtn: $(".select-image"),
    imagePreview: $(".image-preview"),
    iconItems: $(".icon-item"),
    iconSelect: $("#service-icon"),
    iconPreview: $(".icon-preview"),
  };

  // Application state
  const state = {
    nextOptionIndex: elements.optionsContainer
      ? elements.optionsContainer.find(".option-card").length
      : 0,
    deleteTarget: null,
    deleteType: null, // 'service' or 'option'
  };

  // Initialize the form handler
  function init() {
    // Only initialize if the form exists on this page
    if (elements.serviceForm.length === 0) {
      return;
    }

    attachEventListeners();
    initSortables();
    initMediaUploader();

    console.log(
      "Service Form Handler initialized with " +
        state.nextOptionIndex +
        " existing options"
    );
  }

  // Attach all event listeners
  function attachEventListeners() {
    // Form submission
    elements.serviceForm.on("submit", handleFormSubmit);

    // Tab switching
    elements.tabButtons.on("click", function () {
      switchTab($(this).data("tab"));
    });

    // Adding new option
    if (elements.addOptionButton.length) {
      elements.addOptionButton.on("click", addNewOption);
    }

    // Option card event delegation
    if (elements.optionsContainer) {
      // Edit option button
      elements.optionsContainer.on("click", ".edit-option-btn", function (e) {
        e.preventDefault();
        toggleOptionDetails($(this).closest(".option-card"));
      });

      // Remove option button
      elements.optionsContainer.on("click", ".remove-option-btn", function (e) {
        e.preventDefault();
        const optionCard = $(this).closest(".option-card");
        removeOption(optionCard);
      });

      // Option type change
      elements.optionsContainer.on(
        "change",
        ".option-type-select",
        function () {
          updateOptionTypeFields(
            $(this).closest(".option-card"),
            $(this).val()
          );
        }
      );

      // Price type change
      elements.optionsContainer.on("change", ".price-type-select", function () {
        updatePriceFields($(this).closest(".option-card"), $(this).val());
      });

      // Add choice button for select/radio options
      elements.optionsContainer.on("click", ".add-choice-btn", function (e) {
        e.preventDefault();
        const choicesList = $(this).prev(".choices-list");
        addChoiceRow(choicesList);
      });

      // Remove choice button for select/radio options
      elements.optionsContainer.on("click", ".remove-choice-btn", function (e) {
        e.preventDefault();
        const choiceRow = $(this).closest(".choice-row");
        const choicesList = choiceRow.parent(".choices-list");

        // Don't remove if it's the only choice
        if (choicesList.children(".choice-row").length <= 1) {
          showNotification("You must have at least one choice", "warning");
          return;
        }

        choiceRow.remove();
      });
    }

    // Service deletion buttons
    elements.deleteButtons.on("click", function () {
      showDeleteConfirmation("service", $(this).data("id"));
    });

    // Confirmation modal events
    elements.confirmDeleteBtn.on("click", handleDeleteConfirmation);
    elements.cancelDeleteBtn.on("click", hideConfirmationModal);
    elements.modalClose.on("click", hideConfirmationModal);

    // Icon selection
    elements.iconItems.on("click", function () {
      selectIcon($(this).data("icon"));
    });
  }

  // Initialize sortable elements
  function initSortables() {
    // Make options sortable if jQuery UI is available
    if ($.fn.sortable && elements.optionsContainer) {
      elements.optionsContainer.sortable({
        handle: ".option-drag-handle",
        items: ".option-card",
        placeholder: "option-card-placeholder",
        axis: "y",
        opacity: 0.8,
        tolerance: "pointer",
        update: function () {
          // Update option indices after sorting
          updateOptionIndices();
        },
      });

      // Make choice rows sortable within each option
      $(".choices-list").each(function () {
        $(this).sortable({
          handle: ".choice-drag-handle",
          items: ".choice-row",
          placeholder: "choice-row-placeholder",
          axis: "y",
          opacity: 0.8,
        });
      });
    }
  }

  // Switch between tabs
  function switchTab(tabId) {
    elements.tabButtons.removeClass("active");
    elements.tabButtons.filter('[data-tab="' + tabId + '"]').addClass("active");

    elements.tabPanes.removeClass("active");
    $("#" + tabId).addClass("active");
  }

  // Initialize media uploader
  function initMediaUploader() {
    if (!elements.selectImageBtn.length) return;

    let mediaUploader;

    elements.selectImageBtn.on("click", function (e) {
      e.preventDefault();

      // Create media frame or open existing one
      if (mediaUploader) {
        mediaUploader.open();
        return;
      }

      // Create new media uploader
      mediaUploader = wp.media({
        title: "Choose Image",
        button: {
          text: "Select",
        },
        multiple: false,
      });

      // Handle selection
      mediaUploader.on("select", function () {
        const attachment = mediaUploader
          .state()
          .get("selection")
          .first()
          .toJSON();

        // Update image URL field
        $("#service-image").val(attachment.url);

        // Update preview
        elements.imagePreview.html('<img src="' + attachment.url + '" alt="">');
      });

      // Open the uploader
      mediaUploader.open();
    });
  }

  // Select an icon
  function selectIcon(icon) {
    elements.iconSelect.val(icon);
    elements.iconPreview.html('<span class="dashicons ' + icon + '"></span>');
  }

  // Add a new option to the form
  function addNewOption() {
    if (!elements.newOptionTemplate || !elements.optionsContainer) {
      console.error("Option template or container missing");
      return;
    }

    // Get template and replace placeholder index
    const template = elements.newOptionTemplate.html();
    const newOption = $(template.replace(/{index}/g, state.nextOptionIndex));

    // Add to container and increment index
    elements.optionsContainer.append(newOption);
    state.nextOptionIndex++;

    // Initialize the new option card
    initOptionCard(newOption);

    // Scroll to the new option
    $("html, body").animate(
      {
        scrollTop: newOption.offset().top - 100,
      },
      500
    );
  }

  // Initialize a newly added option card
  function initOptionCard(optionCard) {
    // Make choice rows sortable if it's a select/radio type
    const choicesList = optionCard.find(".choices-list");
    if (choicesList.length && $.fn.sortable) {
      choicesList.sortable({
        handle: ".choice-drag-handle",
        items: ".choice-row",
        placeholder: "choice-row-placeholder",
        axis: "y",
        opacity: 0.8,
      });
    }
  }

  // Toggle option details visibility
  function toggleOptionDetails(optionCard) {
    const details = optionCard.find(".option-card-details");

    if (details.is(":visible")) {
      details.slideUp(200);
    } else {
      // Close any open option details first
      $(".option-card-details").not(details).slideUp(200);
      details.slideDown(200);
    }
  }

  // Remove an option
  function removeOption(optionCard) {
    // Check if this is an existing option with an ID
    const optionId = optionCard.find('input[name$="[id]"]').val();

    if (optionId) {
      // Show confirmation for existing options
      showDeleteConfirmation("option", optionId, optionCard);
    } else {
      // Just remove new options without confirmation
      optionCard.fadeOut(300, function () {
        $(this).remove();
        updateOptionIndices();
      });
    }
  }

  // Update option indices after removing or reordering
  function updateOptionIndices() {
    elements.optionsContainer.find(".option-card").each(function (index) {
      const optionCard = $(this);
      const currentIndex = optionCard.data("option-index");

      // Skip if this option already has the correct index
      if (currentIndex === index) {
        return;
      }

      // Update the data attribute
      optionCard.attr("data-option-index", index);

      // Update all input names to use the new index
      optionCard.find("input, select, textarea").each(function () {
        const input = $(this);
        const name = input.attr("name");

        if (name) {
          const newName = name.replace(
            /options\[\d+\]/,
            "options[" + index + "]"
          );
          input.attr("name", newName);
        }
      });
    });
  }

  // Update type-specific fields when option type changes
  function updateOptionTypeFields(optionCard, type) {
    const fieldsContainer = optionCard.find(".option-type-fields");
    const optionIndex = optionCard.data("option-index");

    // Update the displayed type in header
    optionCard.find(".option-type").text(getOptionTypeLabel(type));

    // Clear current fields
    fieldsContainer.empty();

    // Build new fields based on type
    let fieldsHtml = "";

    switch (type) {
      case "checkbox":
        fieldsHtml = `
                    <div class="form-row">
                        <div class="form-group half">
                            <label>${translations.defaultValue}</label>
                            <select name="options[${optionIndex}][default_value]">
                                <option value="0">${translations.unchecked}</option>
                                <option value="1">${translations.checked}</option>
                            </select>
                        </div>
                        <div class="form-group half">
                            <label>${translations.optionLabel}</label>
                            <input type="text" name="options[${optionIndex}][option_label]" value="">
                        </div>
                    </div>
                `;
        break;

      case "select":
      case "radio":
        fieldsHtml = `
                    <div class="form-group">
                        <label>${translations.choices}</label>
                        <div class="choices-container">
                            <div class="choices-list"></div>
                            <button type="button" class="add-choice-btn button-secondary">
                                ${translations.addChoice}
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>${translations.defaultValue}</label>
                        <input type="text" name="options[${optionIndex}][default_value]" value="" 
                               placeholder="${translations.enterDefaultValue}">
                    </div>
                `;
        break;

      case "number":
      case "quantity":
        fieldsHtml = `
                    <div class="form-row">
                        <div class="form-group half">
                            <label>${translations.minValue}</label>
                            <input type="number" name="options[${optionIndex}][min_value]" value="0" step="any">
                        </div>
                        <div class="form-group half">
                            <label>${translations.maxValue}</label>
                            <input type="number" name="options[${optionIndex}][max_value]" value="" step="any">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group half">
                            <label>${translations.defaultValue}</label>
                            <input type="number" name="options[${optionIndex}][default_value]" value="0" step="any">
                        </div>
                        <div class="form-group half">
                            <label>${translations.step}</label>
                            <input type="number" name="options[${optionIndex}][step]" value="1" step="any">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>${translations.unitLabel}</label>
                        <input type="text" name="options[${optionIndex}][unit]" value="" 
                               placeholder="${translations.unitPlaceholder}">
                    </div>
                `;
        break;

      case "text":
        fieldsHtml = `
                    <div class="form-row">
                        <div class="form-group half">
                            <label>${translations.defaultValue}</label>
                            <input type="text" name="options[${optionIndex}][default_value]" value="">
                        </div>
                        <div class="form-group half">
                            <label>${translations.placeholder}</label>
                            <input type="text" name="options[${optionIndex}][placeholder]" value="">
                        </div>
                    </div>
                `;
        break;

      case "textarea":
        fieldsHtml = `
                    <div class="form-group">
                        <label>${translations.defaultValue}</label>
                        <textarea name="options[${optionIndex}][default_value]" rows="3"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group half">
                            <label>${translations.placeholder}</label>
                            <input type="text" name="options[${optionIndex}][placeholder]" value="">
                        </div>
                        <div class="form-group half">
                            <label>${translations.rows}</label>
                            <input type="number" name="options[${optionIndex}][rows]" value="3" min="2">
                        </div>
                    </div>
                `;
        break;
    }

    // Add the fields to the container
    fieldsContainer.html(fieldsHtml);

    // Add initial choice row for select/radio
    if (type === "select" || type === "radio") {
      const choicesList = fieldsContainer.find(".choices-list");
      addChoiceRow(choicesList);

      // Make choices sortable
      if ($.fn.sortable) {
        choicesList.sortable({
          handle: ".choice-drag-handle",
          items: ".choice-row",
          placeholder: "choice-row-placeholder",
          axis: "y",
          opacity: 0.8,
        });
      }
    }
  }

  // Add a new choice row for select/radio options
  function addChoiceRow(choicesList) {
    if (!choicesList || !choicesList.length) {
      return;
    }

    const optionCard = choicesList.closest(".option-card");
    const optionIndex = optionCard.data("option-index");
    const choiceIndex = choicesList.children(".choice-row").length;

    const newRow = $(`
            <div class="choice-row">
                <div class="choice-drag-handle">
                    <span class="dashicons dashicons-menu"></span>
                </div>
                <div class="choice-value">
                    <input type="text" name="options[${optionIndex}][choices][${choiceIndex}][value]" 
                           value="" placeholder="${translations.value}">
                </div>
                <div class="choice-label">
                    <input type="text" name="options[${optionIndex}][choices][${choiceIndex}][label]" 
                           value="" placeholder="${translations.label}">
                </div>
                <div class="choice-price">
                    <input type="number" name="options[${optionIndex}][choices][${choiceIndex}][price]" 
                           value="0" step="0.01" placeholder="0.00">
                </div>
                <div class="choice-actions">
                    <button type="button" class="remove-choice-btn">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            </div>
        `);

    choicesList.append(newRow);

    // Focus on the first input
    newRow.find("input").first().focus();
  }

  // Update price fields when price type changes
  function updatePriceFields(optionCard, priceType) {
    const valueContainer = optionCard.find(".price-impact-value");
    if (!valueContainer.length) return;

    const valueField = valueContainer.find("input");
    const valueLabel = valueContainer.find("label");

    // Hide value field if type is 'none'
    valueContainer.toggle(priceType !== "none");

    if (!priceType || priceType === "none") return;

    // Update label and field attributes based on type
    if (priceType === "fixed") {
      valueLabel.text(translations.amountDollars);
      valueField.attr("step", "0.01");
      valueField.attr("placeholder", "9.99");
    } else if (priceType === "percentage") {
      valueLabel.text(translations.percentagePercent);
      valueField.attr("step", "1");
      valueField.attr("placeholder", "10");
    } else if (priceType === "multiply") {
      valueLabel.text(translations.multiplier);
      valueField.attr("step", "0.1");
      valueField.attr("placeholder", "1.5");
    }
  }

  // Get display label for option types
  function getOptionTypeLabel(type) {
    const labels = {
      checkbox: translations.checkboxLabel,
      number: translations.numberLabel,
      select: translations.selectLabel,
      text: translations.textLabel,
      textarea: translations.textareaLabel,
      radio: translations.radioLabel,
      quantity: translations.quantityLabel,
    };

    return labels[type] || type;
  }

  // Handle form submission
  function handleFormSubmit(e) {
    e.preventDefault();

    // Validate the form
    if (!validateForm()) {
      return;
    }

    // Show loading state
    showLoading(elements.saveButton);

    // Prepare the options data (we need to reformat choices for select/radio)
    prepareOptionsData();

    // Get form data
    const formData = new FormData(elements.serviceForm[0]);

    // Add action for AJAX handling
    formData.append("action", "mobooking_save_unified_service");

    // Send the form data
    $.ajax({
      url: mobookingData.ajaxUrl,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.success) {
          showNotification(
            response.data.message || mobookingData.messages.serviceSuccess,
            "success"
          );

          // Navigate to edit page if this was a new service
          if (!formData.get("id")) {
            setTimeout(function () {
              window.location.href =
                window.location.pathname +
                "?view=edit&service_id=" +
                response.data.id;
            }, 1000);
          } else {
            // Reload the current page after a delay
            setTimeout(function () {
              window.location.reload();
            }, 1000);
          }
        } else {
          showNotification(
            response.data.message || mobookingData.messages.serviceError,
            "error"
          );
          hideLoading(elements.saveButton);
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error:", error);
        showNotification(mobookingData.messages.serviceError, "error");
        hideLoading(elements.saveButton);
      },
    });
  }

  // Prepare options data for submission
  function prepareOptionsData() {
    // For select/radio options, we need to convert choices array to options string
    elements.optionsContainer.find(".option-card").each(function () {
      const optionCard = $(this);
      const type = optionCard.find(".option-type-select").val();

      if (type === "select" || type === "radio") {
        const choicesList = optionCard.find(".choices-list");
        const optionsString = formatChoicesAsString(choicesList);

        // Create a hidden field to store the formatted options string
        if (optionsString) {
          const optionIndex = optionCard.data("option-index");
          optionCard.append(
            `<input type="hidden" name="options[${optionIndex}][options]" value="${optionsString}">`
          );
        }
      }
    });
  }

  // Format choices as a string for storage
  function formatChoicesAsString(choicesList) {
    const choices = [];

    choicesList.find(".choice-row").each(function () {
      const value = $(this).find("input:first").val().trim();
      const label = $(this).find("input:nth(1)").val().trim();
      const price = parseFloat($(this).find("input:last").val()) || 0;

      if (value) {
        if (price > 0) {
          choices.push(`${value}|${label}:${price}`);
        } else {
          choices.push(`${value}|${label}`);
        }
      }
    });

    return choices.join("\n");
  }

  // Validate the form before submission
  function validateForm() {
    let isValid = true;

    // Clear previous errors
    clearErrors();

    // Validate service name
    if (!elements.serviceName.val().trim()) {
      showError(elements.serviceName, "name-error", translations.nameRequired);
      isValid = false;
    }

    // Validate price
    const price = parseFloat(elements.servicePrice.val());
    if (isNaN(price) || price <= 0) {
      showError(
        elements.servicePrice,
        "price-error",
        translations.priceRequired
      );
      isValid = false;
    }

    // Validate duration
    const duration = parseInt(elements.serviceDuration.val());
    if (isNaN(duration) || duration < 15) {
      showError(
        elements.serviceDuration,
        "duration-error",
        translations.durationRequired
      );
      isValid = false;
    }

    // Validate options
    elements.optionsContainer.find(".option-card").each(function () {
      const optionCard = $(this);
      const nameField = optionCard.find('input[name$="[name]"]');

      if (!nameField.val().trim()) {
        nameField.addClass("has-error");
        nameField.after(
          '<div class="field-error active">Option name is required</div>'
        );

        // Open the option details if closed
        const details = optionCard.find(".option-card-details");
        if (!details.is(":visible")) {
          details.slideDown(200);
        }

        isValid = false;
      }

      // For select/radio, validate that there's at least one choice with a value
      const type = optionCard.find(".option-type-select").val();
      if (type === "select" || type === "radio") {
        let hasValidChoice = false;
        optionCard.find(".choice-row").each(function () {
          if ($(this).find("input:first").val().trim()) {
            hasValidChoice = true;
          }
        });

        if (!hasValidChoice) {
          const choicesList = optionCard.find(".choices-list");
          choicesList.after(
            '<div class="field-error active">At least one choice with a value is required</div>'
          );
          isValid = false;
        }
      }
    });

    // If there are errors, scroll to the first one
    if (!isValid) {
      const firstError = $(".field-error.active").first();
      if (firstError.length) {
        $("html, body").animate(
          {
            scrollTop: firstError.offset().top - 100,
          },
          500
        );
      }
    }

    return isValid;
  }

  // Show error for a field
  function showError(element, errorId, message) {
    const errorElement = $("#" + errorId);
    element.closest(".form-group").addClass("has-error");

    if (errorElement.length) {
      errorElement.text(message);
      errorElement.addClass("active");
    } else {
      element.after('<div class="field-error active">' + message + "</div>");
    }
  }

  // Clear all form errors
  function clearErrors() {
    $(".has-error").removeClass("has-error");
    $(".field-error").remove();
  }

  // Show loading indicator
  function showLoading(button) {
    const normalState = button.find(".normal-state");
    const loadingState = button.find(".loading-state");

    if (normalState.length && loadingState.length) {
      normalState.hide();
      loadingState.show();
    }

    button.prop("disabled", true);
  }

  // Hide loading indicator
  function hideLoading(button) {
    const normalState = button.find(".normal-state");
    const loadingState = button.find(".loading-state");

    if (normalState.length && loadingState.length) {
      normalState.show();
      loadingState.hide();
    }

    button.prop("disabled", false);
  }

  // Show notification
  function showNotification(message, type = "info") {
    // Create notification element if it doesn't exist
    if (!$("#notification-message").length) {
      $("#notification-container").append(
        '<div id="notification-message" class="notification"></div>'
      );
    }

    // Update notification
    const notification = $("#notification-message");
    notification
      .removeClass()
      .addClass("notification notification-" + type)
      .text(message)
      .fadeIn(300);

    // Auto-hide after delay
    setTimeout(function () {
      notification.fadeOut(300);
    }, 4000);
  }

  // Show delete confirmation modal
  function showDeleteConfirmation(type, id, element) {
    state.deleteType = type;
    state.deleteTarget = id;
    state.deleteElement = element;

    // Update confirmation message
    let message = mobookingData.messages.deleteConfirm;
    if (type === "service") {
      message = translations.confirmDeleteService;
    } else if (type === "option") {
      message = translations.confirmDeleteOption;
    }

    $("#confirmation-message").text(message);

    // Show modal
    elements.confirmationModal.css("display", "flex");
  }

  // Hide confirmation modal
  function hideConfirmationModal() {
    elements.confirmationModal.hide();
    state.deleteType = null;
    state.deleteTarget = null;
    state.deleteElement = null;
  }

  // Handle delete confirmation
  function handleDeleteConfirmation() {
    if (!state.deleteType || !state.deleteTarget) {
      hideConfirmationModal();
      return;
    }

    // Show loading in confirmation button
    showLoading(elements.confirmDeleteBtn);

    if (state.deleteType === "service") {
      deleteService(state.deleteTarget);
    } else if (state.deleteType === "option") {
      deleteOption(state.deleteTarget, state.deleteElement);
    }
  }

  // Delete a service
  function deleteService(serviceId) {
    $.ajax({
      url: mobookingData.ajaxUrl,
      type: "POST",
      data: {
        action: "mobooking_delete_service",
        id: serviceId,
        nonce: mobookingData.serviceNonce,
      },
      success: function (response) {
        if (response.success) {
          showNotification(translations.serviceDeleted, "success");

          // Redirect to service list
          setTimeout(function () {
            window.location.href = window.location.pathname + "?view=list";
          }, 1000);
        } else {
          showNotification(
            response.data.message || translations.errorDeleting,
            "error"
          );
          hideConfirmationModal();
          hideLoading(elements.confirmDeleteBtn);
        }
      },
      error: function () {
        showNotification(translations.errorDeleting, "error");
        hideConfirmationModal();
        hideLoading(elements.confirmDeleteBtn);
      },
    });
  }

  // Delete an option
  function deleteOption(optionId, optionElement) {
    // For existing options with IDs, we'll send an AJAX request
    // For new options (no ID), we'll just remove them from the DOM
    if (optionId) {
      $.ajax({
        url: mobookingData.ajaxUrl,
        type: "POST",
        data: {
          action: "mobooking_delete_service_option",
          id: optionId,
          nonce: mobookingData.serviceNonce,
        },
        success: function (response) {
          if (response.success) {
            showNotification(translations.optionDeleted, "success");

            // Remove from DOM if we have the element
            if (optionElement) {
              optionElement.fadeOut(300, function () {
                $(this).remove();
                updateOptionIndices();
              });
            }

            hideConfirmationModal();
            hideLoading(elements.confirmDeleteBtn);
          } else {
            showNotification(
              response.data.message || translations.errorDeleting,
              "error"
            );
            hideConfirmationModal();
            hideLoading(elements.confirmDeleteBtn);
          }
        },
        error: function () {
          showNotification(translations.errorDeleting, "error");
          hideConfirmationModal();
          hideLoading(elements.confirmDeleteBtn);
        },
      });
    } else if (optionElement) {
      // Just remove the element from the DOM for new options
      optionElement.fadeOut(300, function () {
        $(this).remove();
        updateOptionIndices();
      });

      hideConfirmationModal();
      hideLoading(elements.confirmDeleteBtn);
    }
  }

  // Translation strings
  const translations = {
    nameRequired: "Service name is required",
    priceRequired: "Price must be greater than zero",
    durationRequired: "Duration must be at least 15 minutes",
    confirmDeleteService:
      "Are you sure you want to delete this service? This will also delete all options associated with it.",
    confirmDeleteOption: "Are you sure you want to delete this option?",
    serviceDeleted: "Service deleted successfully",
    optionDeleted: "Option deleted successfully",
    errorDeleting: "Error deleting. Please try again.",

    // Option fields translations
    defaultValue: "Default Value",
    unchecked: "Unchecked",
    checked: "Checked",
    optionLabel: "Option Label",
    choices: "Choices",
    addChoice: "Add Choice",
    enterDefaultValue: "Enter the value of the default choice",
    minValue: "Minimum Value",
    maxValue: "Maximum Value",
    step: "Step",
    unitLabel: "Unit Label",
    unitPlaceholder: "e.g., hours, sq ft",
    placeholder: "Placeholder",
    rows: "Rows",
    amountDollars: "Amount ($)",
    percentagePercent: "Percentage (%)",
    multiplier: "Multiplier",

    // Option type labels
    checkboxLabel: "Checkbox",
    numberLabel: "Number Input",
    selectLabel: "Dropdown Select",
    textLabel: "Text Input",
    textareaLabel: "Text Area",
    radioLabel: "Radio Buttons",
    quantityLabel: "Quantity Selector",

    // Field labels
    value: "Value",
    label: "Label",
  };

  // Initialize the form handler when the document is ready
  $(init);
})(jQuery);
