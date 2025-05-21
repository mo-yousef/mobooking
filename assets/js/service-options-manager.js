/**
 * Enhanced Service Options Manager for MoBooking
 * Unified interface for managing services and their options
 */

jQuery(document).ready(function ($) {
  // Check if we're on the services page
  if (!$(".services-section").length) {
    return;
  }

  console.log("Service Options Manager loaded");

  // Initialize variables
  let currentServiceId = null;
  let currentServiceName = null;
  let optionsChanged = false;
  let optionsSortableInitialized = false;
  let choicesSortableInitialized = false;
  let serviceFormMode = "add"; // 'add' or 'edit'

  // ========================= UTILITY FUNCTIONS =========================

  /**
   * Helper function to show notifications
   */
  function showNotification(message, type = "info") {
    // Create notification element if it doesn't exist
    if ($("#mobooking-notification").length === 0) {
      $("body").append('<div id="mobooking-notification"></div>');
    }

    const notification = $("#mobooking-notification");
    notification.attr("class", "").addClass("notification-" + type);
    notification.html(message);
    notification.fadeIn(300).delay(3000).fadeOut(300);
  }

  /**
   * Helper function to safely get labels from mobooking_data.labels
   */
  function getLabel(key, fallback) {
    if (
      typeof mobooking_data !== "undefined" &&
      mobooking_data.labels &&
      mobooking_data.labels[key]
    ) {
      return mobooking_data.labels[key];
    }
    return fallback;
  }

  /**
   * Helper function to get AJAX URL
   */
  function getAjaxUrl() {
    if (typeof mobooking_data !== "undefined" && mobooking_data.ajax_url) {
      return mobooking_data.ajax_url;
    } else if (
      typeof mobooking_services !== "undefined" &&
      mobooking_services.ajax_url
    ) {
      return mobooking_services.ajax_url;
    }

    console.error("AJAX URL not found in localized data");
    return "/wp-admin/admin-ajax.php";
  }

  /**
   * Helper function to get service nonce
   */
  function getNonce() {
    if (typeof mobooking_data !== "undefined" && mobooking_data.nonce) {
      return mobooking_data.nonce;
    } else if (
      typeof mobooking_services !== "undefined" &&
      mobooking_services.nonce
    ) {
      return mobooking_services.nonce;
    }

    console.error("Nonce not found in localized data");
    return "";
  }

  /**
   * Helper function to get option nonce
   */
  function getOptionNonce() {
    // Check for dedicated option nonce first
    if (typeof mobooking_data !== "undefined" && mobooking_data.option_nonce) {
      return mobooking_data.option_nonce;
    } else if (
      typeof mobooking_services !== "undefined" &&
      mobooking_services.option_nonce
    ) {
      return mobooking_services.option_nonce;
    }

    // Fall back to service nonce
    return getNonce();
  }

  // Tab switching
  $(".tab-button").on("click", function () {
    var targetTab = $(this).data("tab");

    // Update active tab button
    $(".tab-button").removeClass("active");
    $(this).addClass("active");

    // Show target tab content
    $(".tab-pane").removeClass("active");
    $("#" + targetTab).addClass("active");
  });

  // Service filter
  $("#service-filter").on("change", function () {
    var category = $(this).val();

    if (category === "") {
      $(".service-card").show();
    } else {
      $(".service-card").hide();
      $('.service-card[data-category="' + category + '"]').show();
    }
  });

  // Initialize the media uploader
  let mediaUploader;

  $(".select-image").on("click", function (e) {
    e.preventDefault();

    // If the uploader object has already been created, reopen the dialog
    if (mediaUploader) {
      mediaUploader.open();
      return;
    }

    // Create the media uploader
    mediaUploader = wp.media.frames.file_frame = wp.media({
      title: "Choose Image",
      button: {
        text: "Select",
      },
      multiple: false,
    });

    // When an image is selected, run a callback
    mediaUploader.on("select", function () {
      var attachment = mediaUploader.state().get("selection").first().toJSON();
      $("#service-image").val(attachment.url);
      $(".image-preview").html('<img src="' + attachment.url + '" alt="">');
    });

    // Open the uploader dialog
    mediaUploader.open();
  });

  // Preview selected icon
  $("#service-icon").on("change", function () {
    var iconClass = $(this).val();
    if (iconClass) {
      $(".icon-preview").html(
        '<span class="dashicons ' + iconClass + '"></span>'
      );
    } else {
      $(".icon-preview").empty();
    }
  });

  // ========================= SERVICE MANAGEMENT =========================

  /**
   * Reset the service form to default state
   */
  function resetServiceForm() {
    $("#unified-service-form")[0].reset();
    $("#service-id").val("");
    $(".image-preview").empty();
    $(".icon-preview").empty();
    $(".options-list").html(
      '<div class="options-list-empty">No options configured yet. Add your first option to customize this service.</div>'
    );

    // Reset the option editor
    $(".option-form-container").hide();
    $(".no-option-selected").show();
    $("#option-id").val("");

    // Reset tabs
    $('.tab-button[data-tab="basic-info"]').click();
  }

  /**
   * Open add new service modal
   */
  $(".add-new-service").on("click", function () {
    serviceFormMode = "add";
    currentServiceId = null;
    currentServiceName = null;

    $("#modal-title").text("Add New Service");
    resetServiceForm();

    // Show the modal
    $("#service-editor-modal").fadeIn(300);
  });

  /**
   * Open edit service modal
   */
  $(document).on("click", ".edit-service", function () {
    serviceFormMode = "edit";
    currentServiceId = $(this).data("id");

    // Set modal title
    $("#modal-title").text("Edit Service");

    // Show loading indicator
    $("#service-editor-modal").addClass("loading");
    $("#service-editor-modal").fadeIn(300);

    // Get service data via AJAX
    $.ajax({
      url: getAjaxUrl(),
      type: "POST",
      data: {
        action: "mobooking_direct_get_service",
        id: currentServiceId,
        nonce: getNonce(),
      },
      success: function (response) {
        if (response.success) {
          var service = response.data.service;
          currentServiceName = service.name;

          // Fill the form with service data
          $("#service-id").val(service.id);
          $("#service-name").val(service.name);
          $("#service-description").val(service.description);
          $("#service-price").val(service.price);
          $("#service-duration").val(service.duration);
          $("#service-category").val(service.category);
          $("#service-icon").val(service.icon);
          $("#service-image").val(service.image_url || "");
          $("#service-status").val(service.status || "active");

          // Preview image if available
          if (service.image_url) {
            $(".image-preview").html(
              '<img src="' + service.image_url + '" alt="">'
            );
          } else {
            $(".image-preview").empty();
          }

          // Preview icon if available
          if (service.icon) {
            $(".icon-preview").html(
              '<span class="dashicons ' + service.icon + '"></span>'
            );
          } else {
            $(".icon-preview").empty();
          }

          // Load service options
          loadServiceOptions(service.id);
        } else {
          showNotification(
            response.data.message ||
              getLabel("error_loading", "Error loading service data"),
            "error"
          );
          $("#service-editor-modal").removeClass("loading");
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error:", error);
        showNotification("Error loading service. Please try again.", "error");
        $("#service-editor-modal").removeClass("loading");
      },
    });
  });

  /**
   * Submit service form
   */
  $("#unified-service-form").on("submit", function (e) {
    e.preventDefault();

    // Validate form data
    var serviceName = $("#service-name").val().trim();
    var servicePrice = parseFloat($("#service-price").val());
    var serviceDuration = parseInt($("#service-duration").val());

    if (!serviceName) {
      showNotification("Service name is required", "error");
      $("#service-name").focus();
      return;
    }

    if (isNaN(servicePrice) || servicePrice <= 0) {
      showNotification("Service price must be greater than zero", "error");
      $("#service-price").focus();
      return;
    }

    if (isNaN(serviceDuration) || serviceDuration < 15) {
      showNotification("Service duration must be at least 15 minutes", "error");
      $("#service-duration").focus();
      return;
    }

    // Show loading indicator
    $("#service-editor-modal").addClass("loading");

    // Prepare form data as an object for better debugging
    var formObj = {
      action: "mobooking_save_service",
      nonce: getNonce(),
      id: $("#service-id").val(),
      name: serviceName,
      description: $("#service-description").val(),
      price: servicePrice,
      duration: serviceDuration,
      category: $("#service-category").val(),
      icon: $("#service-icon").val(),
      image_url: $("#service-image").val(),
      status: $("#service-status").val() || "active",
    };

    // Log what we're sending
    console.log("Submitting service data:", formObj);

    // Submit via AJAX
    $.ajax({
      url: getAjaxUrl(),
      type: "POST",
      data: formObj,
      success: function (response) {
        debugAjax("save_service", formObj, response);

        if (response.success) {
          showNotification(
            getLabel("success_save", "Service saved successfully"),
            "success"
          );

          // If adding a new service, switch to edit mode with the new service ID
          if (serviceFormMode === "add") {
            serviceFormMode = "edit";
            currentServiceId = response.data.id;
            $("#service-id").val(currentServiceId);
            currentServiceName = serviceName;
            $("#modal-title").text("Edit Service: " + serviceName);

            // Switch to options tab
            $('.tab-button[data-tab="options"]').click();

            // Remove loading indicator
            $("#service-editor-modal").removeClass("loading");
          } else {
            // If editing, just reload the page to show updated services
            setTimeout(function () {
              location.reload();
            }, 1000);
          }
        } else {
          showNotification(
            response.data.message || "Error saving service",
            "error"
          );
          $("#service-editor-modal").removeClass("loading");
        }
      },
      error: function (xhr, status, error) {
        debugAjax("save_service", formObj, { xhr, status, error }, true);
        console.error("AJAX Error:", error);
        showNotification("Error saving service. Please try again.", "error");
        $("#service-editor-modal").removeClass("loading");
      },
    });
  });

  /**
   * Open delete confirmation modal
   */
  $(document).on("click", ".delete-service", function () {
    var serviceId = $(this).data("id");
    $(".confirm-delete").data("id", serviceId);
    $("#delete-modal").fadeIn(300);
  });

  /**
   * Confirm delete service
   */
  $(".confirm-delete").on("click", function () {
    var serviceId = $(this).data("id");

    // Show loading indicator
    $("#delete-modal").addClass("loading");

    // Submit delete request via AJAX
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
          showNotification(
            getLabel("success_delete", "Service deleted successfully"),
            "success"
          );

          // Reload page to show updated services
          setTimeout(function () {
            location.reload();
          }, 1000);
        } else {
          showNotification(
            response.data.message || "Error deleting service",
            "error"
          );
          $("#delete-modal").removeClass("loading");
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error:", error);
        showNotification("Error deleting service. Please try again.", "error");
        $("#delete-modal").removeClass("loading");
      },
    });
  });

  // ========================= SERVICE OPTIONS MANAGEMENT =========================

  /**
   * Load service options
   */
  function loadServiceOptions(serviceId) {
    if (!serviceId) {
      console.error("No service ID provided to loadServiceOptions");
      return;
    }

    // Show loading indicator
    $("#service-editor-modal").addClass("loading");

    // First try the direct database access endpoint
    $.ajax({
      url: getAjaxUrl(),
      type: "POST",
      data: {
        action: "mobooking_direct_get_service_options",
        service_id: serviceId,
        nonce: getNonce(),
      },
      success: function (response) {
        console.log("Options loaded:", response);

        if (response.success && response.data && response.data.options) {
          populateOptionsList(response.data.options);
        } else {
          // If direct access fails, try the regular endpoint
          fallbackLoadOptions(serviceId);
        }

        // Remove loading indicator
        $("#service-editor-modal").removeClass("loading");
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error loading options:", error);
        // Try fallback method
        fallbackLoadOptions(serviceId);
        $("#service-editor-modal").removeClass("loading");
      },
    });
  }

  // Fallback method to load options
  function fallbackLoadOptions(serviceId) {
    $.ajax({
      url: getAjaxUrl(),
      type: "POST",
      data: {
        action: "mobooking_get_service_options",
        service_id: serviceId,
        nonce: getNonce(),
      },
      success: function (response) {
        if (response.success && response.data && response.data.options) {
          populateOptionsList(response.data.options);
        } else {
          // If all fails, show empty state
          $(".options-list").html(
            '<div class="options-list-empty">No options configured yet. Add your first option to customize this service.</div>'
          );
        }
      },
      error: function (xhr, status, error) {
        console.error("Fallback AJAX Error:", error);
        $(".options-list").html(
          '<div class="options-list-empty">Error loading options. Please refresh the page and try again.</div>'
        );
      },
    });
  }

  // Function to populate the options list
  function populateOptionsList(options) {
    // Clear options list
    $(".options-list").empty();

    if (!options || options.length === 0) {
      $(".options-list").html(
        '<div class="options-list-empty">No options configured yet. Add your first option to customize this service.</div>'
      );
      // Disable sortable if it exists
      if (typeof disableSortable === "function") {
        disableSortable();
      }
    } else {
      // Populate options list
      const optionsHTML = options
        .map((option, index) => {
          return `
            <div class="option-item" data-id="${option.id}" data-order="${
            option.display_order || index
          }">
              <div class="option-drag-handle">
                <span class="dashicons dashicons-menu"></span>
              </div>
              <div class="option-content">
                <span class="option-name">${option.name}</span>
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
      if (typeof initOptionsSortable === "function") {
        initOptionsSortable();
      }
    }
  }

  /**
   * Helper function to get option type label
   */
  function getOptionTypeLabel(type) {
    const labels = {
      checkbox: "Checkbox",
      number: "Number Input",
      select: "Dropdown",
      text: "Text Input",
      textarea: "Text Area",
      radio: "Radio Buttons",
      quantity: "Quantity",
      "": "Unknown",
    };

    return labels[type] || type || "Unknown";
  }

  /**
   * Helper function to generate option preview
   */
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
        preview = '<div class="preview-text">Option</div>';
    }

    // Add price info if applicable
    if (option.price_impact && option.price_impact != 0) {
      const sign = option.price_impact > 0 ? "+" : "";
      let priceDisplay = "";

      if (option.price_type === "percentage") {
        priceDisplay = `${sign}${option.price_impact}%`;
      } else if (option.price_type === "fixed") {
        priceDisplay = `${sign}$${Math.abs(
          parseFloat(option.price_impact)
        ).toFixed(2)}`;
      } else if (option.price_type === "multiply") {
        priceDisplay = `×${option.price_impact}`;
      }

      if (priceDisplay) {
        preview += `<div class="price-indicator">${priceDisplay}</div>`;
      }
    }

    return preview;
  }

  /**
   * Function to initialize sortable options
   */
  function initOptionsSortable() {
    if (
      !optionsSortableInitialized &&
      $(".options-list .option-item").length > 1
    ) {
      $(".options-list").sortable({
        handle: ".option-drag-handle",
        placeholder: "option-item-placeholder",
        axis: "y",
        opacity: 0.8,
        tolerance: "pointer",
        start: function (event, ui) {
          ui.item.addClass("sorting");
          ui.placeholder.height(ui.item.outerHeight());
        },
        stop: function (event, ui) {
          ui.item.removeClass("sorting");
        },
        update: function (event, ui) {
          optionsChanged = true;
          updateOptionsOrder();
        },
      });
      optionsSortableInitialized = true;
      $(".options-list").addClass("sortable-enabled");
    }
  }

  /**
   * Function to disable sortable
   */
  function disableSortable() {
    if (optionsSortableInitialized) {
      try {
        $(".options-list").sortable("destroy");
      } catch (e) {
        console.log("Sortable already destroyed");
      }
      optionsSortableInitialized = false;
      $(".options-list").removeClass("sortable-enabled");
    }
  }

  /**
   * Function to update options order
   */
  function updateOptionsOrder() {
    const orderData = [];

    $(".options-list .option-item").each(function (index) {
      orderData.push({
        id: $(this).data("id"),
        order: index,
      });
    });

    $.ajax({
      url: getAjaxUrl(),
      type: "POST",
      data: {
        action: "mobooking_update_options_order",
        service_id: currentServiceId,
        order_data: JSON.stringify(orderData),
        nonce: getNonce(),
      },
      success: function (response) {
        if (response.success) {
          showNotification(
            getLabel("options_order", "Options order updated"),
            "success"
          );
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

  /**
   * Handle clicking on an option in the list
   */
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
    $("#service-editor-modal").addClass("loading");

    // Get option data
    $.ajax({
      url: getAjaxUrl(),
      type: "POST",
      data: {
        action: "mobooking_get_service_option",
        id: optionId,
        nonce: getNonce(),
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
          $(".option-form-container").show();

          // Highlight selected option
          $(".option-item").removeClass("active");
          $(`.option-item[data-id="${optionId}"]`).addClass("active");
        } else {
          showNotification(
            response.data.message || "Error loading option",
            "error"
          );
        }

        // Remove loading indicator
        $("#service-editor-modal").removeClass("loading");
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error:", error);
        showNotification(
          "Error loading option data. Please try again.",
          "error"
        );
        $("#service-editor-modal").removeClass("loading");
      },
    });
  });

  /**
   * Add new option button
   */
  $(document).on("click", ".add-option-button", function () {
    // Deselect any selected option
    const optionItems = document.querySelectorAll(".option-item");
    optionItems.forEach((item) => item.classList.remove("active"));

    // Show new option form
    const templateHtml = document.getElementById(
      "option-form-template"
    ).innerHTML;
    const optionFormContainer = document.querySelector(
      ".option-form-container"
    );
    const noOptionSelected = document.querySelector(".no-option-selected");

    // Create new option form with proper data
    let formHtml = templateHtml
      .replace("{id}", "")
      .replace("{title}", "Add New Option")
      .replace("{name}", "")
      .replace("{description}", "")
      .replace(/{type_selected_[^}]+}/g, "")
      .replace("{type_selected_checkbox}", "selected")
      .replace("{required_selected_0}", "selected")
      .replace("{required_selected_1}", "")
      .replace(/{price_type_selected_[^}]+}/g, "")
      .replace("{price_type_selected_fixed}", "selected")
      .replace("{price_impact}", "0")
      .replace("{delete_button_visibility}", 'style="display: none;"');

    // Update the DOM
    if (optionFormContainer) {
      optionFormContainer.innerHTML = formHtml;
      optionFormContainer.style.display = "block";
    }
    if (noOptionSelected) {
      noOptionSelected.style.display = "none";
    }

    // Initialize form handlers and fields
    initOptionFormHandlers();
    generateDynamicFields("checkbox");
    updatePriceFields("fixed");
  });

  /**
   * Handle option type change
   */
  $(document).on("change", "#option-type", function () {
    const optionType = $(this).val();
    if (optionType) {
      generateDynamicFields(optionType);
    }
  });

  /**
   * Handle price type change
   */
  $(document).on("change", "#option-price-type", function () {
    updatePriceFields($(this).val());
  });

  /**
   * Function to update price fields based on selected price type
   */
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

  /**
   * Function to generate dynamic fields based on option type
   */
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
            (optionData.min_value !== null ? optionData.min_value : 0) +
            '">' +
            "</div>" +
            '<div class="form-group half">' +
            '<label for="option-max-value">Maximum Value</label>' +
            '<input type="number" id="option-max-value" name="max_value" value="' +
            (optionData.max_value !== null ? optionData.max_value : "") +
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
        // Parse existing options
        let choicesArray = [];
        if (optionData.options) {
          choicesArray = parseOptionsString(optionData.options);
        }

        dynamicFields.append(
          '<div class="form-group">' +
            "<label>Choices</label>" +
            '<div class="choices-container">' +
            '<div class="choices-header">' +
            '<div class="choice-value">Value</div>' +
            '<div class="choice-label">Label</div>' +
            '<div class="choice-price">Price Impact</div>' +
            '<div class="choice-actions"></div>' +
            "</div>" +
            '<div class="choices-list"></div>' +
            '<div class="add-choice-container">' +
            '<button type="button" class="button add-choice">Add Choice</button>' +
            "</div>" +
            "</div>" +
            '<input type="hidden" id="option-choices" name="options">' +
            "</div>" +
            '<div class="form-group">' +
            '<label for="option-default-value">Default Value</label>' +
            '<input type="text" id="option-default-value" name="default_value" value="' +
            (optionData.default_value || "") +
            '">' +
            '<p class="field-hint">Enter the value (not the label) of the default choice</p>' +
            "</div>"
        );

        // Populate choices
        const choicesList = dynamicFields.find(".choices-list");
        if (choicesArray.length === 0) {
          // Add a blank choice if none exist
          addChoiceRow(choicesList);
        } else {
          // Add each choice
          choicesArray.forEach((choice) => {
            addChoiceRow(choicesList, choice.value, choice.label, choice.price);
          });
        }

        // Make choices sortable
        initChoicesSortable();

        // Update the hidden choices field
        updateOptionsField();
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
            (optionData.min_length !== null ? optionData.min_length : "") +
            '" min="0">' +
            "</div>" +
            '<div class="form-group half">' +
            '<label for="option-max-length">Maximum Length</label>' +
            '<input type="number" id="option-max-length" name="max_length" value="' +
            (optionData.max_length !== null ? optionData.max_length : "") +
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
            '<div class="form-group half">' +
            '<label for="option-max-length">Maximum Length</label>' +
            '<input type="number" id="option-max-length" name="max_length" value="' +
            (optionData.max_length !== null ? optionData.max_length : "") +
            '" min="0">' +
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
            (optionData.min_value !== null ? optionData.min_value : 0) +
            '" min="0">' +
            "</div>" +
            '<div class="form-group half">' +
            '<label for="option-max-value">Maximum Quantity</label>' +
            '<input type="number" id="option-max-value" name="max_value" value="' +
            (optionData.max_value !== null ? optionData.max_value : "") +
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

  /**
   * Function to parse options string into array of objects
   */
  function parseOptionsString(optionsString) {
    const options = [];
    if (!optionsString) return options;

    const lines = optionsString.split("\n");

    lines.forEach((line) => {
      if (!line.trim()) return;

      const parts = line.split("|");
      const value = parts[0]?.trim() || "";
      const labelPriceParts = parts[1]?.split(":") || [""];

      const label = labelPriceParts[0]?.trim() || "";
      const price = parseFloat(labelPriceParts[1] || 0) || 0;

      if (value) {
        options.push({
          value,
          label,
          price,
        });
      }
    });

    return options;
  }

  /**
   * Function to serialize choices to string format
   */
  function serializeChoices() {
    const choices = [];

    $(".choice-row").each(function () {
      const value = $(this).find(".choice-value-input").val().trim();
      const label = $(this).find(".choice-label-input").val().trim();
      const price = parseFloat($(this).find(".choice-price-input").val()) || 0;

      if (value) {
        choices.push(`${value}|${label}${price ? ":" + price : ""}`);
      }
    });

    return choices.join("\n");
  }

  /**
   * Function to add a new choice row
   */
  function addChoiceRow(container, value = "", label = "", price = 0) {
    const row = $(`
            <div class="choice-row">
                <div class="choice-drag-handle">
                    <span class="dashicons dashicons-menu"></span>
                </div>
                <div class="choice-value">
                    <input type="text" class="choice-value-input" value="${value}" placeholder="value">
                </div>
                <div class="choice-label">
                    <input type="text" class="choice-label-input" value="${label}" placeholder="Display Label">
                </div>
                <div class="choice-price">
                    <input type="number" class="choice-price-input" value="${price}" step="0.01" placeholder="0.00">
                </div>
                <div class="choice-actions">
                    <button type="button" class="button-link remove-choice">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            </div>
        `);

    container.append(row);

    // Focus on the value input for new choices
    if (!value) {
      row.find(".choice-value-input").focus();
    }

    return row;
  }

  /**
   * Function to initialize choices sortable
   */
  function initChoicesSortable() {
    if (!choicesSortableInitialized) {
      $(".choices-list").sortable({
        handle: ".choice-drag-handle",
        placeholder: "choice-row-placeholder",
        axis: "y",
        opacity: 0.8,
        tolerance: "pointer",
        start: function (event, ui) {
          ui.placeholder.height(ui.item.outerHeight());
        },
        update: function () {
          // When order changes, update the hidden input
          updateOptionsField();
        },
      });
      choicesSortableInitialized = true;
    }
  }

  /**
   * Add new choice button handler
   */
  $(document).on("click", ".add-choice", function () {
    const choicesList = $(this)
      .closest(".choices-container")
      .find(".choices-list");
    addChoiceRow(choicesList);
    updateOptionsField();
  });

  /**
   * Remove choice button handler
   */
  $(document).on("click", ".remove-choice", function () {
    const choiceRow = $(this).closest(".choice-row");

    // Don't remove if it's the only choice
    if ($(".choice-row").length <= 1) {
      showNotification(
        getLabel("at_least_one", "You must have at least one choice"),
        "warning"
      );
      return;
    }

    // Remove the row
    choiceRow.remove();
    updateOptionsField();
  });

  /**
   * Update options field when choice inputs change
   */
  $(document).on(
    "input",
    ".choice-value-input, .choice-label-input, .choice-price-input",
    function () {
      updateOptionsField();
    }
  );

  /**
   * Function to update the hidden options field
   */
  function updateOptionsField() {
    const serialized = serializeChoices();
    $("#option-choices").val(serialized);
  }

  /**
   * Initialize event handlers for option form
   */
  function initOptionFormHandlers() {
    // Option type change
    const optionType = document.getElementById("option-type");
    if (optionType) {
      optionType.addEventListener("change", function () {
        generateDynamicFields(this.value);
      });
    }

    // Price type change
    const priceType = document.getElementById("option-price-type");
    if (priceType) {
      priceType.addEventListener("change", function () {
        updatePriceFields(this.value);
      });
    }

    // Cancel button
    const cancelButton = document.querySelector(".cancel-option");
    if (cancelButton) {
      cancelButton.addEventListener("click", function () {
        const optionFormContainer = document.querySelector(
          ".option-form-container"
        );
        const noOptionSelected = document.querySelector(".no-option-selected");
        const optionItems = document.querySelectorAll(".option-item");

        if (optionFormContainer) optionFormContainer.style.display = "none";
        if (noOptionSelected) noOptionSelected.style.display = "flex";
        optionItems.forEach((item) => item.classList.remove("active"));
      });
    }

    // Delete button
    const deleteButton = document.querySelector(".delete-option");
    if (deleteButton) {
      deleteButton.addEventListener("click", function () {
        if (
          confirm(
            "Are you sure you want to delete this option? This action cannot be undone."
          )
        ) {
          const optionForm = document.getElementById("option-form");
          if (optionForm) {
            const formData = new FormData(optionForm);
            formData.append("action", "mobooking_delete_option_ajax");

            // Show loading state
            this.innerHTML = '<span class="spinner-icon"></span> Deleting...';
            this.disabled = true;

            fetch(getAjaxUrl(), {
              method: "POST",
              body: formData,
              credentials: "same-origin",
            })
              .then((response) => {
                if (!response.ok) {
                  throw new Error(
                    "Server returned " +
                      response.status +
                      " " +
                      response.statusText
                  );
                }
                return response.json();
              })
              .then((data) => {
                if (data.success) {
                  showNotification(data.data.message, "success");

                  // Reload the options list
                  setTimeout(() => {
                    window.location.reload();
                  }, 1000);
                } else {
                  // Reset button state
                  this.innerHTML =
                    '<span class="dashicons dashicons-trash"></span> Delete';
                  this.disabled = false;

                  showNotification(
                    data.data?.message || "Error deleting option",
                    "error"
                  );
                }
              })
              .catch((error) => {
                // Reset button state
                this.innerHTML =
                  '<span class="dashicons dashicons-trash"></span> Delete';
                this.disabled = false;

                console.error("Error deleting option:", error);
                showNotification(
                  "An error occurred while deleting the option",
                  "error"
                );
              });
          }
        }
      });
    }

    // Save button
    const saveOptionButton = document.querySelector(".save-option");
    if (saveOptionButton) {
      saveOptionButton.addEventListener("click", function (e) {
        e.preventDefault();

        const optionForm = document.getElementById("option-form");
        if (!optionForm) return;

        // Basic validation
        const nameField = optionForm.querySelector('input[name="name"]');
        const typeField = optionForm.querySelector('select[name="type"]');

        if (!nameField || !nameField.value.trim()) {
          showNotification("Option name is required", "error");
          if (nameField) nameField.focus();
          return;
        }

        if (!typeField || !typeField.value) {
          showNotification("Option type is required", "error");
          if (typeField) typeField.focus();
          return;
        }

        // For select/radio, handle choices
        if (typeField.value === "select" || typeField.value === "radio") {
          // Collect all choices and format them properly
          const choices = [];
          optionForm.querySelectorAll(".choice-row").forEach((row) => {
            const value = row.querySelector(".choice-value-input").value.trim();
            const label = row.querySelector(".choice-label-input").value.trim();
            const price = row.querySelector(".choice-price-input").value;

            if (value) {
              if (price > 0) {
                choices.push(`${value}|${label}:${price}`);
              } else {
                choices.push(`${value}|${label}`);
              }
            }
          });

          // Make sure we have at least one choice
          if (choices.length === 0) {
            showNotification(
              "At least one choice is required for " +
                typeField.value +
                " options",
              "error"
            );
            return;
          }

          // Add choices to form data
          const choicesField = document.createElement("input");
          choicesField.type = "hidden";
          choicesField.name = "options";
          choicesField.value = choices.join("\n");
          optionForm.appendChild(choicesField);
        }

        // Show loading state
        this.innerHTML = '<span class="spinner-icon"></span> Saving...';
        this.disabled = true;

        // Create FormData for submission
        const formData = new FormData(optionForm);

        // IMPORTANT: Use the correct AJAX action
        formData.append("action", "mobooking_save_option_ajax");

        // Include current service ID if not already in form
        if (!formData.has("service_id")) {
          formData.append("service_id", currentServiceId);
        }

        // Add nonce
        formData.append("option_nonce", getOptionNonce());

        // Log what we're sending for debugging
        console.log("Submitting option data:", Object.fromEntries(formData));

        // Submit via fetch API
        fetch(getAjaxUrl(), {
          method: "POST",
          body: formData,
          credentials: "same-origin",
        })
          .then((response) => {
            if (!response.ok) {
              throw new Error(
                "Server returned " + response.status + " " + response.statusText
              );
            }
            return response.json();
          })
          .then((data) => {
            console.log("Save option response:", data);

            // Reset button state
            this.innerHTML = "Save Option";
            this.disabled = false;

            if (data.success) {
              showNotification(
                data.data.message || "Option saved successfully",
                "success"
              );

              // Reload the page to reflect changes
              setTimeout(() => {
                window.location.reload();
              }, 1000);
            } else {
              showNotification(
                data.data?.message || "Error saving option",
                "error"
              );
            }
          })
          .catch((error) => {
            // Reset button state
            this.innerHTML = "Save Option";
            this.disabled = false;

            console.error("Error saving option:", error);
            showNotification(
              "An error occurred while saving the option",
              "error"
            );
          });
      });
    }
  }

  // Filter options with search
  $("#options-search").on("input", function () {
    const searchTerm = $(this).val().toLowerCase();

    if (!searchTerm) {
      $(".option-item").show();
      return;
    }

    $(".option-item").each(function () {
      const optionName = $(this).find(".option-name").text().toLowerCase();
      const optionType = $(this).find(".option-type").text().toLowerCase();

      if (optionName.includes(searchTerm) || optionType.includes(searchTerm)) {
        $(this).show();
      } else {
        $(this).hide();
      }
    });
  });

  // ========================= MODAL MANAGEMENT =========================

  /**
   * Close modals
   */
  $(".modal-close, .cancel-service, .cancel-delete").on("click", function () {
    $(this).closest(".mobooking-modal").fadeOut(300);
  });

  /**
   * ESC key to close modals
   */
  $(document).keydown(function (e) {
    if (e.keyCode === 27) {
      // ESC key
      $(".mobooking-modal:visible").fadeOut(300);
    }
  });
});

/**
 * Enhanced AJAX handler for saving service options
 */
$(document).on("click", ".save-option", function (e) {
  e.preventDefault();

  const optionForm = document.getElementById("option-form");
  if (!optionForm) return;

  // Basic validation
  const nameField = optionForm.querySelector('input[name="name"]');
  const typeField = optionForm.querySelector('select[name="type"]');

  if (!nameField || !nameField.value.trim()) {
    showNotification("Option name is required", "error");
    if (nameField) nameField.focus();
    return;
  }

  if (!typeField || !typeField.value) {
    showNotification("Option type is required", "error");
    if (typeField) typeField.focus();
    return;
  }

  // For select/radio, handle choices
  if (typeField.value === "select" || typeField.value === "radio") {
    // Collect all choices and format them properly
    const choices = [];
    optionForm.querySelectorAll(".choice-row").forEach((row) => {
      const value = row.querySelector(".choice-value-input").value.trim();
      const label = row.querySelector(".choice-label-input").value.trim();
      const price = row.querySelector(".choice-price-input").value;

      if (value) {
        if (price > 0) {
          choices.push(`${value}|${label}:${price}`);
        } else {
          choices.push(`${value}|${label}`);
        }
      }
    });

    // Make sure we have at least one choice
    if (choices.length === 0) {
      showNotification(
        "At least one choice is required for " + typeField.value + " options",
        "error"
      );
      return;
    }

    // Add choices to form data
    const choicesField = document.createElement("input");
    choicesField.type = "hidden";
    choicesField.name = "options";
    choicesField.value = choices.join("\n");
    optionForm.appendChild(choicesField);
  }

  // Show loading state
  this.innerHTML = '<span class="spinner-icon"></span> Saving...';
  this.disabled = true;

  // Create FormData for submission
  const formData = new FormData(optionForm);

  // IMPORTANT: Use the correct AJAX action
  formData.append("action", "mobooking_save_option_ajax");

  // Include current service ID if not already in form
  if (!formData.has("service_id")) {
    formData.append("service_id", currentServiceId);
  }

  // Add nonce
  formData.append("option_nonce", getOptionNonce());

  // Log what we're sending for debugging
  console.log("Submitting option data:", Object.fromEntries(formData));

  // Submit via fetch API
  fetch(getAjaxUrl(), {
    method: "POST",
    body: formData,
    credentials: "same-origin",
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error(
          "Server returned " + response.status + " " + response.statusText
        );
      }
      return response.json();
    })
    .then((data) => {
      console.log("Save option response:", data);

      // Reset button state
      this.innerHTML = "Save Option";
      this.disabled = false;

      if (data.success) {
        showNotification(
          data.data.message || "Option saved successfully",
          "success"
        );

        // Reload the page to reflect changes
        setTimeout(() => {
          window.location.reload();
        }, 1000);
      } else {
        showNotification(data.data?.message || "Error saving option", "error");
      }
    })
    .catch((error) => {
      // Reset button state
      this.innerHTML = "Save Option";
      this.disabled = false;

      console.error("Error saving option:", error);
      showNotification("An error occurred while saving the option", "error");
    });
});
/**
 * Debug AJAX calls
 */
function debugAjax(action, requestData, responseData, isError) {
  console.group("AJAX Debug: " + action);
  console.log("Request:", requestData);
  console.log("Response:", responseData);
  console.groupEnd();

  if (isError) {
    // Show in UI
    const debugOutput = document.getElementById("debug-output");
    if (debugOutput) {
      debugOutput.style.display = "block";
      const debugContent = document.getElementById("debug-content");
      if (debugContent) {
        debugContent.textContent = JSON.stringify(
          {
            action: action,
            request: requestData,
            response: responseData,
          },
          null,
          2
        );
      }
    }
  }
}
