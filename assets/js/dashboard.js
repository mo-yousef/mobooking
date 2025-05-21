/**
 * Dashboard script with enhanced service options support
 */
jQuery(document).ready(function ($) {
  console.log("Dashboard script loaded");

  // Only apply service fixes on the services page
  if ($(".services-section").length > 0) {
    console.log("Enhanced MoBooking Service Options Fix loaded");

    // Override the edit service click handler if needed
    if (!window.originalEditServiceHandler) {
      window.originalEditServiceHandler = true;

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
            showNotification(
              "Error loading service. Please try again.",
              "error"
            );
            $("#service-editor-modal").removeClass("loading");
          },
        });
      });
    }

    // Enhanced loadServiceOptions function
    window.loadServiceOptions = function (serviceId) {
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
    };

    // Fallback method to load options
    window.fallbackLoadOptions = function (serviceId) {
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
    };

    // Function to populate the options list
    window.populateOptionsList = function (options) {
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
    };
  }

  // Add button and form event delegations
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

  // Helper functions to ensure they're in the global scope
  window.getAjaxUrl = function () {
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
  };

  window.getNonce = function () {
    // Check both possible nonce locations
    if (typeof mobooking_data !== "undefined" && mobooking_data.nonce) {
      return mobooking_data.nonce;
    } else if (
      typeof mobooking_services !== "undefined" &&
      mobooking_services.nonce
    ) {
      return mobooking_services.nonce;
    }

    console.error(
      "Nonce not found in either mobooking_data or mobooking_services"
    );
    return "";
  };

  window.getOptionNonce = function () {
    // Check for dedicated option nonce first
    if (typeof mobooking_data !== "undefined" && mobooking_data.option_nonce) {
      return mobooking_data.option_nonce;
    }
    // Fall back to service nonce
    return getNonce();
  };

  // Add debug function if not present
  if (typeof window.debugAjax !== "function") {
    window.debugAjax = function (action, requestData, responseData, isError) {
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
    };
  }

  // Add notification helper if not present
  if (typeof window.showNotification !== "function") {
    window.showNotification = function (message, type = "info") {
      // Create notification element if it doesn't exist
      if ($("#mobooking-notification").length === 0) {
        $("body").append('<div id="mobooking-notification"></div>');
      }

      const notification = $("#mobooking-notification");
      notification.attr("class", "").addClass("notification-" + type);
      notification.html(message);
      notification.fadeIn(300).delay(3000).fadeOut(300);
    };
  }
});
