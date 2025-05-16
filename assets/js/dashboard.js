/**
 * Enhanced Service Options Manager for MoBooking
 * Provides a modern, intuitive interface for managing service options
 * with sortable functionality and improved UI/UX
 *
 * Features:
 * - Drag and drop sorting of service options
 * - Visual previews of each option type
 * - Improved UI with compact design
 * - Real-time feedback and notifications
 * - Dynamic forms based on option type
 */

jQuery(document).ready(function ($) {
  // Check if we're on the services page
  if (!$(".services-section").length) {
    return;
  }

  // Initialize variables
  let currentServiceId = null;
  let currentServiceName = null;
  let optionsChanged = false;
  let sortableInitialized = false;

  // ==== SERVICE OPTIONS MANAGEMENT ====

  // Open manage options modal
  $(document).on("click", ".manage-options", function () {
    currentServiceId = $(this).data("id");
    currentServiceName = $(this).data("name");

    // Reset state
    optionsChanged = false;

    // Set modal title
    $("#options-modal-title .service-name").text(currentServiceName);

    // Set service ID in the form
    $("#option-service-id").val(currentServiceId);

    // Reset option form
    $("#option-form").hide();
    $("#option-id").val("");
    $("#option-form")[0].reset();
    $(".no-option-selected").show();

    // Show loading indicator
    $("#options-modal").addClass("loading");
    $("#options-modal").fadeIn();

    // Load service options
    loadServiceOptions(currentServiceId);
  });

  // Function to load service options
  function loadServiceOptions(serviceId) {
    $.ajax({
      url: mobooking_services.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_get_service_options",
        service_id: serviceId,
        nonce: mobooking_services.nonce,
      },
      success: function (response) {
        if (response.success) {
          // Clear options list
          $(".options-list").empty();

          if (response.data.options.length === 0) {
            $(".options-list").html(
              '<div class="options-list-empty">No options configured yet. Add your first option to customize this service.</div>'
            );
            disableSortable();
          } else {
            // Populate options list
            const optionsHTML = response.data.options
              .map((option, index) => {
                return `
                                <div class="option-item" data-id="${
                                  option.id
                                }" data-order="${
                  option.display_order || index
                }">
                                    <div class="option-drag-handle">
                                        <span class="dashicons dashicons-menu"></span>
                                    </div>
                                    <div class="option-content">
                                        <span class="option-name">${
                                          option.name
                                        }</span>
                                        <div class="option-meta">
                                            <span class="option-type">${getOptionTypeLabel(
                                              option.type
                                            )}</span>
                                            ${
                                              option.is_required == 1
                                                ? '<span class="option-required">Required</span>'
                                                : ""
                                            }
                                        </div>
                                    </div>
                                    <div class="option-preview">
                                        ${generateOptionPreview(option)}
                                    </div>
                                </div>
                            `;
              })
              .join("");

            $(".options-list").html(optionsHTML);

            // Initialize sortable if we have options
            initSortable();
          }
        } else {
          showNotification(
            response.data.message || "Error loading options",
            "error"
          );
        }

        // Remove loading indicator
        $("#options-modal").removeClass("loading");
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error:", error);
        showNotification(
          "Error loading service options. Please try again.",
          "error"
        );
        $("#options-modal").removeClass("loading");
      },
    });
  }

  // Function to initialize sortable
  function initSortable() {
    if (!sortableInitialized && $(".options-list .option-item").length > 0) {
      $(".options-list").sortable({
        handle: ".option-drag-handle",
        placeholder: "option-item-placeholder",
        axis: "y",
        update: function (event, ui) {
          optionsChanged = true;
          updateOptionsOrder();
        },
      });
      sortableInitialized = true;
      $(".options-list").addClass("sortable-enabled");
    }
  }

  // Function to disable sortable
  function disableSortable() {
    if (sortableInitialized) {
      $(".options-list").sortable("destroy");
      sortableInitialized = false;
      $(".options-list").removeClass("sortable-enabled");
    }
  }

  // Function to update options order
  function updateOptionsOrder() {
    const orderData = [];

    $(".options-list .option-item").each(function (index) {
      orderData.push({
        id: $(this).data("id"),
        order: index,
      });
    });

    $.ajax({
      url: mobooking_services.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_update_options_order",
        service_id: currentServiceId,
        order_data: JSON.stringify(orderData),
        nonce: mobooking_services.nonce,
      },
      success: function (response) {
        if (response.success) {
          showNotification("Options order updated", "success");
        } else {
          showNotification(
            response.data.message || "Error updating order",
            "error"
          );
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error:", error);
        showNotification("Error updating options order", "error");
      },
    });
  }

  // Helper function to get option type label
  function getOptionTypeLabel(type) {
    const labels = {
      checkbox: "Checkbox",
      number: "Number Input",
      select: "Dropdown",
      text: "Text Input",
      textarea: "Text Area",
      radio: "Radio Buttons",
      quantity: "Quantity",
    };

    return labels[type] || type;
  }

  // Helper function to generate option preview
  function generateOptionPreview(option) {
    let preview = "";

    switch (option.type) {
      case "checkbox":
        preview = `<div class="preview-checkbox"><input type="checkbox" ${
          option.default_value == "1" ? "checked" : ""
        } disabled /></div>`;
        break;

      case "select":
        preview =
          '<div class="preview-select"><select disabled><option>Options...</option></select></div>';
        break;

      case "radio":
        preview =
          '<div class="preview-radio"><span class="radio-dot"></span></div>';
        break;

      case "number":
      case "quantity":
        preview = '<div class="preview-number">123</div>';
        break;

      case "text":
        preview = '<div class="preview-text">Text</div>';
        break;

      case "textarea":
        preview = '<div class="preview-textarea">Text Area</div>';
        break;

      default:
        preview = "";
    }

    // Add price info
    if (option.price_impact && option.price_impact != 0) {
      const sign = option.price_impact > 0 ? "+" : "";
      let priceDisplay = "";

      if (option.price_type === "percentage") {
        priceDisplay = `${sign}${option.price_impact}%`;
      } else if (option.price_type === "fixed") {
        priceDisplay = `${sign}$${Math.abs(option.price_impact).toFixed(2)}`;
      } else if (option.price_type === "multiply") {
        priceDisplay = `×${option.price_impact}`;
      }

      if (priceDisplay) {
        preview += `<div class="price-indicator">${priceDisplay}</div>`;
      }
    }

    return preview;
  }

  // Handle clicking on an option in the list
  $(document).on("click", ".option-item", function (e) {
    // Don't react if clicking on the drag handle
    if (
      $(e.target).hasClass("option-drag-handle") ||
      $(e.target).closest(".option-drag-handle").length
    ) {
      return;
    }

    const optionId = $(this).data("id");

    // Show loading indicator
    $("#options-modal").addClass("loading");

    // Get option data
    $.ajax({
      url: mobooking_services.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_get_service_option",
        id: optionId,
        nonce: mobooking_services.nonce,
      },
      success: function (response) {
        if (response.success) {
          const option = response.data.option;

          // Fill the form with option data
          $("#option-id").val(option.id);
          $("#option-name").val(option.name);
          $("#option-type").val(option.type);
          $("#option-required").val(option.is_required);
          $("#option-description").val(option.description);
          $("#option-price-type").val(option.price_type || "fixed");
          $("#option-price-impact").val(option.price_impact || 0);

          // Generate type-specific fields
          generateDynamicFields(option.type, option);

          // Show delete button
          $(".delete-option").show();

          // Show the form
          $(".no-option-selected").hide();
          $("#option-form").show();

          // Highlight selected option
          $(".option-item").removeClass("active");
          $('.option-item[data-id="' + optionId + '"]').addClass("active");
        } else {
          showNotification(
            response.data.message || "Error loading option",
            "error"
          );
        }

        // Remove loading indicator
        $("#options-modal").removeClass("loading");
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error:", error);
        showNotification(
          "Error loading option data. Please try again.",
          "error"
        );
        $("#options-modal").removeClass("loading");
      },
    });
  });

  // Add new option button
  $(".add-new-option").on("click", function () {
    // Reset form
    $("#option-id").val("");
    $("#option-form")[0].reset();

    // Set service ID from the modal
    // (Already set when opening the options modal)

    // Generate empty dynamic fields for default type
    generateDynamicFields("checkbox");

    // Hide delete button for new options
    $(".delete-option").hide();

    // Show the form
    $(".no-option-selected").hide();
    $("#option-form").show();

    // Deselect any selected option
    $(".option-item").removeClass("active");
  });

  // Handle option type change
  $("#option-type").on("change", function () {
    const optionType = $(this).val();
    if (optionType) {
      generateDynamicFields(optionType);
    }
  });

  // Handle price type change
  $("#option-price-type").on("change", function () {
    updatePriceFields($(this).val());
  });

  // Function to update price fields based on selected price type
  function updatePriceFields(priceType) {
    const valueField = $(".price-impact-value");

    valueField.show();

    if (priceType === "custom") {
      valueField.find("label").text("Formula");
      valueField
        .find("input")
        .attr("type", "text")
        .attr("placeholder", "price + (value * 5)");
    } else if (priceType === "none") {
      valueField.hide();
    } else if (priceType === "percentage") {
      valueField.find("label").text("Percentage (%)");
      valueField.find("input").attr("type", "number").attr("placeholder", "10");
    } else if (priceType === "multiply") {
      valueField.find("label").text("Multiplier");
      valueField
        .find("input")
        .attr("type", "number")
        .attr("step", "0.1")
        .attr("placeholder", "1.5");
    } else {
      valueField.find("label").text("Amount ($)");
      valueField
        .find("input")
        .attr("type", "number")
        .attr("step", "0.01")
        .attr("placeholder", "9.99");
    }
  }

  // Function to generate dynamic fields based on option type
  function generateDynamicFields(optionType, optionData) {
    const dynamicFields = $(".dynamic-fields");
    dynamicFields.empty();

    optionData = optionData || {};

    switch (optionType) {
      case "checkbox":
        dynamicFields.append(
          '<div class="form-row">' +
            '<div class="form-group half">' +
            '<label for="option-default-value">Default Value</label>' +
            '<select id="option-default-value" name="default_value">' +
            '<option value="0" ' +
            (optionData.default_value == "0" ? "selected" : "") +
            ">Unchecked</option>" +
            '<option value="1" ' +
            (optionData.default_value == "1" ? "selected" : "") +
            ">Checked</option>" +
            "</select>" +
            "</div>" +
            '<div class="form-group half">' +
            '<label for="option-label">Option Label</label>' +
            '<input type="text" id="option-label" name="option_label" value="' +
            (optionData.option_label || "") +
            '" placeholder="Check this box to add...">' +
            "</div>" +
            "</div>"
        );
        break;

      case "number":
        dynamicFields.append(
          '<div class="form-row">' +
            '<div class="form-group half">' +
            '<label for="option-min-value">Minimum Value</label>' +
            '<input type="number" id="option-min-value" name="min_value" value="' +
            (optionData.min_value || 0) +
            '">' +
            "</div>" +
            '<div class="form-group half">' +
            '<label for="option-max-value">Maximum Value</label>' +
            '<input type="number" id="option-max-value" name="max_value" value="' +
            (optionData.max_value || "") +
            '">' +
            "</div>" +
            "</div>" +
            '<div class="form-row">' +
            '<div class="form-group half">' +
            '<label for="option-default-value">Default Value</label>' +
            '<input type="number" id="option-default-value" name="default_value" value="' +
            (optionData.default_value || "") +
            '">' +
            "</div>" +
            '<div class="form-group half">' +
            '<label for="option-placeholder">Placeholder</label>' +
            '<input type="text" id="option-placeholder" name="placeholder" value="' +
            (optionData.placeholder || "") +
            '">' +
            "</div>" +
            "</div>" +
            '<div class="form-row">' +
            '<div class="form-group half">' +
            '<label for="option-step">Step</label>' +
            '<input type="number" id="option-step" name="step" value="' +
            (optionData.step || "1") +
            '" step="0.01">' +
            "</div>" +
            '<div class="form-group half">' +
            '<label for="option-unit">Unit Label</label>' +
            '<input type="text" id="option-unit" name="unit" value="' +
            (optionData.unit || "") +
            '" placeholder="sq ft, hours, etc.">' +
            "</div>" +
            "</div>"
        );
        break;

      case "select":
      case "radio":
        dynamicFields.append(
          '<div class="form-group">' +
            '<label for="option-choices">Choices (one per line, format: value|label)</label>' +
            '<textarea id="option-choices" name="options" rows="4">' +
            (optionData.options || "") +
            "</textarea>" +
            '<p class="field-hint">Example: small|Small Size (10-20 sq ft)</p>' +
            "</div>" +
            '<div class="form-group">' +
            '<label for="option-default-value">Default Value</label>' +
            '<input type="text" id="option-default-value" name="default_value" value="' +
            (optionData.default_value || "") +
            '">' +
            '<p class="field-hint">Enter the value (not the label) of the default choice</p>' +
            "</div>"
        );
        break;

      case "text":
        dynamicFields.append(
          '<div class="form-row">' +
            '<div class="form-group half">' +
            '<label for="option-default-value">Default Value</label>' +
            '<input type="text" id="option-default-value" name="default_value" value="' +
            (optionData.default_value || "") +
            '">' +
            "</div>" +
            '<div class="form-group half">' +
            '<label for="option-placeholder">Placeholder</label>' +
            '<input type="text" id="option-placeholder" name="placeholder" value="' +
            (optionData.placeholder || "") +
            '">' +
            "</div>" +
            "</div>" +
            '<div class="form-row">' +
            '<div class="form-group half">' +
            '<label for="option-min-length">Minimum Length</label>' +
            '<input type="number" id="option-min-length" name="min_length" value="' +
            (optionData.min_length || "") +
            '" min="0">' +
            "</div>" +
            '<div class="form-group half">' +
            '<label for="option-max-length">Maximum Length</label>' +
            '<input type="number" id="option-max-length" name="max_length" value="' +
            (optionData.max_length || "") +
            '" min="0">' +
            "</div>" +
            "</div>"
        );
        break;

      case "textarea":
        dynamicFields.append(
          '<div class="form-group">' +
            '<label for="option-default-value">Default Value</label>' +
            '<textarea id="option-default-value" name="default_value" rows="2">' +
            (optionData.default_value || "") +
            "</textarea>" +
            "</div>" +
            '<div class="form-group">' +
            '<label for="option-placeholder">Placeholder</label>' +
            '<input type="text" id="option-placeholder" name="placeholder" value="' +
            (optionData.placeholder || "") +
            '">' +
            "</div>" +
            '<div class="form-row">' +
            '<div class="form-group half">' +
            '<label for="option-rows">Rows</label>' +
            '<input type="number" id="option-rows" name="rows" value="' +
            (optionData.rows || "3") +
            '" min="2">' +
            "</div>" +
            "</div>"
        );
        break;

      case "quantity":
        dynamicFields.append(
          '<div class="form-row">' +
            '<div class="form-group half">' +
            '<label for="option-min-value">Minimum Quantity</label>' +
            '<input type="number" id="option-min-value" name="min_value" value="' +
            (optionData.min_value || 0) +
            '" min="0">' +
            "</div>" +
            '<div class="form-group half">' +
            '<label for="option-max-value">Maximum Quantity</label>' +
            '<input type="number" id="option-max-value" name="max_value" value="' +
            (optionData.max_value || "") +
            '" min="0">' +
            "</div>" +
            "</div>" +
            '<div class="form-row">' +
            '<div class="form-group half">' +
            '<label for="option-default-value">Default Quantity</label>' +
            '<input type="number" id="option-default-value" name="default_value" value="' +
            (optionData.default_value || 0) +
            '" min="0">' +
            "</div>" +
            '<div class="form-group half">' +
            '<label for="option-step">Step</label>' +
            '<input type="number" id="option-step" name="step" value="' +
            (optionData.step || "1") +
            '" min="1">' +
            "</div>" +
            "</div>" +
            '<div class="form-row">' +
            '<div class="form-group half">' +
            '<label for="option-unit">Unit Label</label>' +
            '<input type="text" id="option-unit" name="unit" value="' +
            (optionData.unit || "") +
            '" placeholder="items, people, etc.">' +
            "</div>" +
            "</div>"
        );
        break;
    }

    // Update price fields
    updatePriceFields(optionData.price_type || "fixed");
  }

  // Cancel option editing
  $(".cancel-option").on("click", function () {
    $("#option-form").hide();
    $(".no-option-selected").show();
    $(".option-item").removeClass("active");
  });

  // Submit option form
  $("#option-form").on("submit", function (e) {
    e.preventDefault();

    // Show loading indicator
    $("#options-modal").addClass("loading");

    // Prepare form data
    let formData = $(this).serialize();
    formData +=
      "&action=mobooking_save_service_option&nonce=" + mobooking_services.nonce;

    // Submit via AJAX
    $.ajax({
      url: mobooking_services.ajax_url,
      type: "POST",
      data: formData,
      success: function (response) {
        if (response.success) {
          // Reload options list
          loadServiceOptions($("#option-service-id").val());

          // Hide the form
          $("#option-form").hide();
          $(".no-option-selected").show();

          // Show success message
          showNotification("Option saved successfully", "success");
        } else {
          showNotification(
            response.data.message || "Error saving option",
            "error"
          );
          $("#options-modal").removeClass("loading");
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error:", error);
        showNotification("Error saving option. Please try again.", "error");
        $("#options-modal").removeClass("loading");
      },
    });
  });

  // Delete option button click
  $(".delete-option").on("click", function () {
    const optionId = $("#option-id").val();

    if (!optionId) {
      return;
    }

    if (
      !confirm(
        "Are you sure you want to delete this option? This action cannot be undone."
      )
    ) {
      return;
    }

    // Show loading indicator
    $("#options-modal").addClass("loading");

    // Submit delete request via AJAX
    $.ajax({
      url: mobooking_services.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_delete_service_option",
        id: optionId,
        nonce: mobooking_services.nonce,
      },
      success: function (response) {
        if (response.success) {
          // Reload options list
          loadServiceOptions($("#option-service-id").val());

          // Hide the form
          $("#option-form").hide();
          $(".no-option-selected").show();

          // Show success message
          showNotification("Option deleted successfully", "success");
        } else {
          showNotification(
            response.data.message || "Error deleting option",
            "error"
          );
          $("#options-modal").removeClass("loading");
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error:", error);
        showNotification("Error deleting option. Please try again.", "error");
        $("#options-modal").removeClass("loading");
      },
    });
  });

  // Close options modal - check if changes need to be saved
  $(document).on("click", "#options-modal .modal-close", function () {
    if (optionsChanged) {
      const confirmMessage =
        "You have unsaved changes to the options order. Save changes before closing?";

      if (confirm(confirmMessage)) {
        updateOptionsOrder();
      }
    }

    $("#options-modal").fadeOut();
  });

  // Helper function to show notification
  function showNotification(message, type = "info") {
    // Create notification element if it doesn't exist
    if ($("#mobooking-notification").length === 0) {
      $("body").append('<div id="mobooking-notification"></div>');
    }

    const notification = $("#mobooking-notification");
    notification.attr("class", "notification-" + type);
    notification.text(message);
    notification.fadeIn().delay(3000).fadeOut();
  }
});
