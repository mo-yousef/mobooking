console.log("Dashboard script loaded");

/**
 * Enhanced Service Manager Fix
 *
 * This script completely overrides the service editing functionality
 * by using direct database access endpoints.
 */

jQuery(document).ready(function ($) {
  // Only apply on the services page
  if (!$(".services-section").length) {
    return;
  }

  console.log("Enhanced MoBooking Service Manager Fix loaded");

  // Override the edit service click handler
  $(document).off("click", ".edit-service");
  $(document).on("click", ".edit-service", function (e) {
    e.preventDefault();

    // Get service ID
    const serviceId = $(this).data("id");

    // Set global variables used by main script
    window.serviceFormMode = "edit";
    window.currentServiceId = serviceId;

    // Set modal title
    $("#modal-title").text("Edit Service");

    // Show loading indicator
    $("#service-editor-modal").addClass("loading");
    $("#service-editor-modal").fadeIn(300);

    // Use direct database access endpoint
    $.ajax({
      url: getAjaxUrl(),
      type: "POST",
      data: {
        action: "mobooking_direct_get_service",
        id: serviceId,
        nonce: getNonce(),
      },
      success: function (response) {
        console.log("Service data response:", response);

        if (response.success && response.data.service) {
          const service = response.data.service;
          window.currentServiceName = service.name;

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
            response.data.message || "Error loading service data",
            "error"
          );
          $("#service-editor-modal").removeClass("loading");
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error:", error);
        console.log("Status:", status);
        console.log("Response:", xhr.responseText);
        showNotification("Error loading service. Please try again.", "error");
        $("#service-editor-modal").removeClass("loading");
      },
    });
  });

  /**
   * Load service options using direct access endpoint
   */
  function loadServiceOptions(serviceId) {
    $.ajax({
      url: getAjaxUrl(),
      type: "POST",
      data: {
        action: "mobooking_direct_get_service_options",
        service_id: serviceId,
        nonce: getNonce(),
      },
      success: function (response) {
        console.log("Options response:", response);

        if (response.success) {
          // Update options list
          populateOptionsList(response.data.options || []);
        } else {
          console.warn("Error loading options:", response);
        }

        // Remove loading indicator
        $("#service-editor-modal").removeClass("loading");
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error:", error);
        console.log("Status:", status);
        console.log("Response:", xhr.responseText);

        // Remove loading indicator and display empty options list
        $("#service-editor-modal").removeClass("loading");
        $(".options-list").html(
          '<div class="options-list-empty">No options configured yet or error loading options.</div>'
        );
      },
    });
  }

  /**
   * Populate options list with data
   */
  function populateOptionsList(options) {
    // Clear options list
    $(".options-list").empty();

    if (!options || options.length === 0) {
      $(".options-list").html(
        '<div class="options-list-empty">No options configured yet. Add your first option to customize this service.</div>'
      );
      if (typeof disableSortable === "function") {
        disableSortable();
      }
    } else {
      // Populate options list
      const optionsHTML = options
        .map((option, index) => {
          return `
                        <div class="option-item" data-id="${
                          option.id
                        }" data-order="${option.display_order || index}">
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

      // Initialize sortable if we have options and the function exists
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
   * Helper function to get nonce
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
});

// Add debug helpers
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
