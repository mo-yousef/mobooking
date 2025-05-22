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

/**
 * Debug Service Options JavaScript
 * Add this to your services page to debug the options saving process
 */
jQuery(document).ready(function ($) {
  console.log("=== MOBOOKING DEBUG SCRIPT LOADED ===");

  // Add debug buttons to the page
  if ($(".services-section").length > 0) {
    var debugPanel = $(`
            <div id="mobooking-debug-panel" style="
                position: fixed; 
                top: 100px; 
                right: 20px; 
                background: #fff; 
                padding: 15px; 
                border: 2px solid #007cba; 
                border-radius: 5px; 
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                z-index: 9999;
                max-width: 300px;
                font-size: 12px;
            ">
                <h4 style="margin: 0 0 10px 0; color: #007cba;">Debug Panel</h4>
                <button id="debug-db-structure" class="button button-small" style="margin-bottom: 5px; width: 100%;">Test DB Structure</button>
                <button id="debug-collect-options" class="button button-small" style="margin-bottom: 5px; width: 100%;">Show Options Data</button>
                <button id="debug-test-save" class="button button-small" style="margin-bottom: 5px; width: 100%;">Test Option Save</button>
                <button id="debug-close" class="button button-small" style="width: 100%;">Close Debug</button>
                <div id="debug-output" style="
                    margin-top: 10px; 
                    padding: 10px; 
                    background: #f0f0f0; 
                    border-radius: 3px; 
                    max-height: 200px; 
                    overflow-y: auto; 
                    font-family: monospace; 
                    font-size: 11px;
                    display: none;
                "></div>
            </div>
        `);

    $("body").append(debugPanel);

    // Debug button events
    $("#debug-db-structure").on("click", function () {
      console.log("Testing database structure...");
      $("#debug-output").html("Testing database structure...").show();

      $.ajax({
        url: mobookingData.ajaxUrl,
        type: "POST",
        data: {
          action: "mobooking_test_db_structure",
        },
        success: function (response) {
          console.log("DB Structure Response:", response);
          if (response.success) {
            $("#debug-output").html(
              "✅ DB Structure OK<br>Columns: " +
                response.data.columns.join(", ")
            );
          } else {
            $("#debug-output").html(
              "❌ DB Structure Error: " + response.data.message
            );
          }
        },
        error: function (xhr, status, error) {
          console.error("DB Structure Test Error:", error);
          $("#debug-output").html("❌ AJAX Error: " + error);
        },
      });
    });

    $("#debug-collect-options").on("click", function () {
      console.log("Collecting options data...");
      $("#debug-output").show();

      var optionsData = [];

      // Method 1: Try to collect from option cards
      $(".option-card").each(function (index) {
        var card = $(this);
        var option = {
          index: index,
          name: card.find('input[name$="[name]"]').val(),
          type: card.find('select[name$="[type]"]').val(),
          description: card.find('input[name$="[description]"]').val(),
          is_required: card.find('select[name$="[is_required]"]').val(),
          price_type: card.find('select[name$="[price_type]"]').val(),
          price_impact: card.find('input[name$="[price_impact]"]').val(),
        };

        optionsData.push(option);
        console.log("Found option " + index + ":", option);
      });

      if (optionsData.length === 0) {
        // Method 2: Try to collect from service options container
        $("#service-options-container .option-card").each(function (index) {
          var card = $(this);
          var option = {
            index: index,
            name: card.find('input[type="text"]').first().val(),
            type: card.find("select").first().val(),
            card_html: card.html().substring(0, 200) + "...",
          };

          optionsData.push(option);
          console.log("Found option (method 2) " + index + ":", option);
        });
      }

      var output = "Options found: " + optionsData.length + "<br>";
      optionsData.forEach(function (option, index) {
        output +=
          index +
          ": " +
          (option.name || "NO NAME") +
          " (" +
          (option.type || "NO TYPE") +
          ")<br>";
      });

      if (optionsData.length === 0) {
        output += "❌ No options found!<br>";
        output +=
          "Available option cards: " + $(".option-card").length + "<br>";
        output += "Available forms: " + $("form").length + "<br>";

        // Show all inputs that might be options
        var allInputs = [];
        $('input[name*="option"]').each(function () {
          allInputs.push($(this).attr("name") + " = " + $(this).val());
        });

        if (allInputs.length > 0) {
          output += "Option-related inputs found:<br>" + allInputs.join("<br>");
        }
      }

      $("#debug-output").html(output);
    });

    $("#debug-test-save").on("click", function () {
      var serviceId = $("#service-id").val();

      if (!serviceId) {
        $("#debug-output").html("❌ No service ID found").show();
        return;
      }

      console.log("Testing option save for service ID:", serviceId);
      $("#debug-output").html("Testing option save...").show();

      $.ajax({
        url: mobookingData.ajaxUrl,
        type: "POST",
        data: {
          action: "mobooking_test_option_save",
          service_id: serviceId,
        },
        success: function (response) {
          console.log("Test Save Response:", response);
          if (response.success) {
            $("#debug-output").html(
              "✅ Test option saved successfully!<br>Option ID: " +
                response.data.option_id
            );
          } else {
            $("#debug-output").html(
              "❌ Test save failed: " + response.data.message
            );
          }
        },
        error: function (xhr, status, error) {
          console.error("Test Save Error:", error);
          $("#debug-output").html("❌ AJAX Error: " + error);
        },
      });
    });

    $("#debug-close").on("click", function () {
      $("#mobooking-debug-panel").remove();
    });
  }

  // Intercept the original form submission to debug
  $(document).on("submit", "#unified-service-form", function (e) {
    console.log("=== FORM SUBMISSION INTERCEPTED ===");

    // Don't prevent the default, but log what we're sending
    var formData = new FormData(this);

    console.log("Form action:", $(this).attr("action"));
    console.log("Form method:", $(this).attr("method"));

    // Log all form data
    var formEntries = {};
    for (var pair of formData.entries()) {
      formEntries[pair[0]] = pair[1];
    }
    console.log("Form data being sent:", formEntries);

    // Specifically look for options data
    var optionsFound = [];
    for (var key in formEntries) {
      if (key.includes("options[")) {
        optionsFound.push(key + " = " + formEntries[key]);
      }
    }

    if (optionsFound.length > 0) {
      console.log("Options data found in form:", optionsFound);
    } else {
      console.log("❌ NO OPTIONS DATA FOUND IN FORM!");
    }

    // Let the form continue submitting
    return true;
  });

  // Debug: Watch for any AJAX calls
  $(document).ajaxSend(function (event, xhr, settings) {
    if (settings.url && settings.url.includes("admin-ajax.php")) {
      console.log("AJAX Request being sent:", {
        url: settings.url,
        data: settings.data,
        type: settings.type,
      });
    }
  });

  $(document).ajaxComplete(function (event, xhr, settings) {
    if (settings.url && settings.url.includes("admin-ajax.php")) {
      console.log("AJAX Response received:", {
        status: xhr.status,
        responseText: xhr.responseText.substring(0, 500),
      });
    }
  });

  console.log("=== DEBUG SCRIPT READY ===");
});
