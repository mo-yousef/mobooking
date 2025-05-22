/**
 * Services Handler for Separate Tables Architecture
 * Handles services and options with clean separation
 */
(function ($) {
  "use strict";

  console.log("=== Services Handler Loading (Separate Tables) ===");

  // Cache DOM elements
  const elements = {
    serviceForm: $("#service-form"),
    optionModal: $("#option-modal"),
    optionForm: $("#option-form"),
    addOptionBtn: $("#add-option-btn"),
    optionsContainer: $("#service-options-container"),
    confirmationModal: $("#confirmation-modal"),
    tabButtons: $(".tab-button"),
    tabPanes: $(".tab-pane"),
    deleteServiceBtns: $(".delete-service-btn"),
    selectImageBtn: $(".select-image"),
    imagePreview: $(".image-preview"),
    iconItems: $(".icon-item"),
    iconSelect: $("#service-icon"),
    iconPreview: $(".icon-preview"),
  };

  // Application state
  const state = {
    currentServiceId: mobookingServices?.currentServiceId || null,
    currentOptionId: null,
    deleteTarget: null,
    deleteType: null,
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

  // Initialize the handler
  function init() {
    console.log("Initializing Services Handler...");

    if (elements.serviceForm.length === 0) {
      console.log("Service form not found, skipping initialization");
      return;
    }

    attachEventListeners();
    initSortables();
    initMediaUploader();

    // Load options if editing a service
    if (state.currentServiceId) {
      loadServiceOptions(state.currentServiceId);
    }

    console.log("Services Handler initialized successfully");
  }

  // Attach all event listeners
  function attachEventListeners() {
    console.log("Attaching event listeners...");

    // Service form submission
    elements.serviceForm.on("submit", handleServiceSubmit);

    // Tab switching
    elements.tabButtons.on("click", function () {
      switchTab($(this).data("tab"));
    });

    // Add new option
    elements.addOptionBtn.on("click", showAddOptionModal);

    // Option form submission
    elements.optionForm.on("submit", handleOptionSubmit);

    // Edit option
    elements.optionsContainer.on("click", ".edit-option-btn", function () {
      const optionCard = $(this).closest(".option-card");
      editOption(optionCard.data("option-id"));
    });

    // Delete option
    elements.optionsContainer.on("click", ".delete-option-btn", function () {
      const optionId = $(this).data("option-id");
      showDeleteConfirmation("option", optionId);
    });

    // Service deletion
    elements.deleteServiceBtns.on("click", function () {
      showDeleteConfirmation("service", $(this).data("id"));
    });

    // Modal events
    $(".modal-close, .cancel-delete-btn, #cancel-option-btn").on(
      "click",
      hideModals
    );

    $(".confirm-delete-btn").on("click", handleDeleteConfirmation);

    // Option type change
    $("#option-type").on("change", function () {
      updateDynamicFields($(this).val());
    });

    // Price type change
    $("#option-price-type").on("change", function () {
      updatePriceImpactVisibility($(this).val());
    });

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
        update: function () {
          updateOptionsOrder();
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

  // Handle service form submission
  function handleServiceSubmit(e) {
    e.preventDefault();

    if (state.isSubmitting) {
      return;
    }

    console.log("=== SERVICE SUBMISSION STARTED ===");

    if (!validateServiceForm()) {
      return;
    }

    state.isSubmitting = true;
    showLoading($("#save-service-button"));

    const formData = new FormData(elements.serviceForm[0]);
    formData.append("action", "mobooking_save_service");

    $.ajax({
      url: getAjaxUrl(),
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        console.log("Service save response:", response);
        handleServiceSaveSuccess(response);
      },
      error: function (xhr, status, error) {
        console.error("Service save error:", error);
        handleServiceSaveError("Error saving service: " + error);
      },
    });
  }

  // Validate service form
  function validateServiceForm() {
    let isValid = true;
    clearErrors();

    const name = $("#service-name").val().trim();
    const price = parseFloat($("#service-price").val());
    const duration = parseInt($("#service-duration").val());

    if (!name) {
      showError($("#service-name"), "Service name is required");
      isValid = false;
    }

    if (isNaN(price) || price <= 0) {
      showError($("#service-price"), "Price must be greater than zero");
      isValid = false;
    }

    if (isNaN(duration) || duration < 15) {
      showError($("#service-duration"), "Duration must be at least 15 minutes");
      isValid = false;
    }

    return isValid;
  }

  // Handle successful service save
  function handleServiceSaveSuccess(response) {
    state.isSubmitting = false;
    hideLoading($("#save-service-button"));

    if (response.success) {
      showNotification("Service saved successfully!", "success");

      // Update service ID if this was a new service
      if (response.data.id && !state.currentServiceId) {
        state.currentServiceId = response.data.id;
        $("#service-id").val(response.data.id);
        $("#option-service-id").val(response.data.id);

        // Update URL and switch to options tab
        const newUrl =
          window.location.pathname +
          "?view=edit&service_id=" +
          response.data.id +
          "&active_tab=options";
        window.history.pushState({}, "", newUrl);
        switchTab("options");

        // Show add option button since we can now add options
        elements.addOptionBtn.show();
      }
    } else {
      handleServiceSaveError(
        response.data?.message || "Failed to save service"
      );
    }
  }

  // Handle service save error
  function handleServiceSaveError(message) {
    state.isSubmitting = false;
    hideLoading($("#save-service-button"));
    showNotification("Error: " + message, "error");
  }

  // Load service options
  function loadServiceOptions(serviceId) {
    if (!serviceId) {
      return;
    }

    console.log("Loading options for service:", serviceId);

    $.ajax({
      url: getAjaxUrl(),
      type: "POST",
      data: {
        action: "mobooking_get_service_options",
        service_id: serviceId,
        nonce: getNonce(),
      },
      success: function (response) {
        console.log("Options loaded:", response);

        if (response.success && response.data.options) {
          displayOptions(response.data.options);
        } else {
          showNoOptionsMessage();
        }
      },
      error: function (xhr, status, error) {
        console.error("Error loading options:", error);
        showNoOptionsMessage();
      },
    });
  }

  // Display options in the container
  function displayOptions(options) {
    elements.optionsContainer.empty();

    if (!options || options.length === 0) {
      showNoOptionsMessage();
      return;
    }

    options.forEach(function (option) {
      const optionCard = createOptionCard(option);
      elements.optionsContainer.append(optionCard);
    });

    // Update sortable
    if (elements.optionsContainer.hasClass("ui-sortable")) {
      elements.optionsContainer.sortable("refresh");
    }
  }

  // Create option card HTML
  function createOptionCard(option) {
    const optionTypeLabel = optionTypes[option.type] || option.type;
    const requiredBadge =
      option.is_required == 1
        ? '<span class="option-required">Required</span>'
        : "";

    return $(`
      <div class="option-card" data-option-id="${option.id}">
        <div class="option-card-header">
          <div class="option-drag-handle">
            <span class="dashicons dashicons-menu"></span>
          </div>
          <div class="option-title">
            <span class="option-name">${escapeHtml(option.name)}</span>
            <span class="option-type">${escapeHtml(optionTypeLabel)}</span>
            ${requiredBadge}
          </div>
          <div class="option-actions">
            <button type="button" class="button button-small edit-option-btn">
              <span class="dashicons dashicons-edit"></span>
            </button>
            <button type="button" class="button button-small delete-option-btn" data-option-id="${
              option.id
            }">
              <span class="dashicons dashicons-trash"></span>
            </button>
          </div>
        </div>
      </div>
    `);
  }

  // Show no options message
  function showNoOptionsMessage() {
    elements.optionsContainer.html(`
      <div class="no-options-message">
        <span class="dashicons dashicons-admin-generic"></span>
        <p>No options configured yet. Add your first option to customize this service.</p>
      </div>
    `);
  }

  // Show add option modal
  function showAddOptionModal() {
    if (!state.currentServiceId) {
      showNotification(
        "Please save the service first before adding options",
        "warning"
      );
      return;
    }

    state.currentOptionId = null;
    resetOptionForm();
    $("#option-modal-title").text("Add New Option");
    $("#delete-option-btn").hide();
    updateDynamicFields("checkbox");
    updatePriceImpactVisibility("fixed");
    elements.optionModal.show();
  }

  // Edit option
  function editOption(optionId) {
    if (!optionId) {
      return;
    }

    state.currentOptionId = optionId;

    $.ajax({
      url: getAjaxUrl(),
      type: "POST",
      data: {
        action: "mobooking_get_service_option",
        id: optionId,
        nonce: getNonce(),
      },
      success: function (response) {
        console.log("Option loaded:", response);

        if (response.success && response.data.option) {
          populateOptionForm(response.data.option);
          $("#option-modal-title").text("Edit Option");
          $("#delete-option-btn").show();
          elements.optionModal.show();
        } else {
          showNotification("Error loading option", "error");
        }
      },
      error: function (xhr, status, error) {
        console.error("Error loading option:", error);
        showNotification("Error loading option", "error");
      },
    });
  }

  // Reset option form
  function resetOptionForm() {
    elements.optionForm[0].reset();
    $("#option-id").val("");
    $("#option-service-id").val(state.currentServiceId);
    $("#option-dynamic-fields").empty();
  }

  // Populate option form with data
  function populateOptionForm(option) {
    $("#option-id").val(option.id);
    $("#option-service-id").val(option.service_id);
    $("#option-name").val(option.name);
    $("#option-description").val(option.description || "");
    $("#option-type").val(option.type);
    $("#option-required").val(option.is_required || "0");
    $("#option-price-type").val(option.price_type || "fixed");
    $("#option-price-impact").val(option.price_impact || "0");

    updateDynamicFields(option.type, option);
    updatePriceImpactVisibility(option.price_type || "fixed");
  }

  // Update dynamic fields based on option type
  function updateDynamicFields(type, optionData = {}) {
    const container = $("#option-dynamic-fields");
    container.empty();

    let fieldsHtml = "";

    switch (type) {
      case "checkbox":
        fieldsHtml = `
          <div class="form-row">
            <div class="form-group half">
              <label for="option-default-value">Default Value</label>
              <select id="option-default-value" name="default_value">
                <option value="0" ${
                  optionData.default_value != "1" ? "selected" : ""
                }>Unchecked</option>
                <option value="1" ${
                  optionData.default_value == "1" ? "selected" : ""
                }>Checked</option>
              </select>
            </div>
            <div class="form-group half">
              <label for="option-label">Option Label</label>
              <input type="text" id="option-label" name="option_label" value="${escapeHtml(
                optionData.option_label || ""
              )}" placeholder="Check this box to add...">
            </div>
          </div>
        `;
        break;

      case "select":
      case "radio":
        const choices = parseChoices(optionData.options || "");
        fieldsHtml = `
          <div class="form-group">
            <label>Choices</label>
            <div class="choices-container">
              <div class="choices-list" id="choices-list">
                ${choices
                  .map((choice, index) =>
                    createChoiceRow(choice.value, choice.label, choice.price)
                  )
                  .join("")}
              </div>
              <button type="button" class="button button-secondary" id="add-choice-btn">Add Choice</button>
            </div>
          </div>
          <div class="form-group">
            <label for="option-default-value">Default Value</label>
            <input type="text" id="option-default-value" name="default_value" value="${escapeHtml(
              optionData.default_value || ""
            )}" placeholder="Enter the value of the default choice">
          </div>
        `;
        break;

      case "number":
      case "quantity":
        fieldsHtml = `
          <div class="form-row">
            <div class="form-group half">
              <label for="option-min-value">Minimum Value</label>
              <input type="number" id="option-min-value" name="min_value" value="${
                optionData.min_value !== null ? optionData.min_value : ""
              }" step="any">
            </div>
            <div class="form-group half">
              <label for="option-max-value">Maximum Value</label>
              <input type="number" id="option-max-value" name="max_value" value="${
                optionData.max_value !== null ? optionData.max_value : ""
              }" step="any">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group half">
              <label for="option-default-value">Default Value</label>
              <input type="number" id="option-default-value" name="default_value" value="${
                optionData.default_value || ""
              }" step="any">
            </div>
            <div class="form-group half">
              <label for="option-step">Step</label>
              <input type="number" id="option-step" name="step" value="${
                optionData.step || "1"
              }" step="any">
            </div>
          </div>
          <div class="form-group">
            <label for="option-unit">Unit Label</label>
            <input type="text" id="option-unit" name="unit" value="${escapeHtml(
              optionData.unit || ""
            )}" placeholder="e.g., hours, sq ft">
          </div>
        `;
        break;

      case "text":
        fieldsHtml = `
          <div class="form-row">
            <div class="form-group half">
              <label for="option-default-value">Default Value</label>
              <input type="text" id="option-default-value" name="default_value" value="${escapeHtml(
                optionData.default_value || ""
              )}">
            </div>
            <div class="form-group half">
              <label for="option-placeholder">Placeholder</label>
              <input type="text" id="option-placeholder" name="placeholder" value="${escapeHtml(
                optionData.placeholder || ""
              )}">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group half">
              <label for="option-min-length">Minimum Length</label>
              <input type="number" id="option-min-length" name="min_length" value="${
                optionData.min_length !== null ? optionData.min_length : ""
              }" min="0">
            </div>
            <div class="form-group half">
              <label for="option-max-length">Maximum Length</label>
              <input type="number" id="option-max-length" name="max_length" value="${
                optionData.max_length !== null ? optionData.max_length : ""
              }" min="0">
            </div>
          </div>
        `;
        break;

      case "textarea":
        fieldsHtml = `
          <div class="form-group">
            <label for="option-default-value">Default Value</label>
            <textarea id="option-default-value" name="default_value" rows="3">${escapeHtml(
              optionData.default_value || ""
            )}</textarea>
          </div>
          <div class="form-row">
            <div class="form-group half">
              <label for="option-placeholder">Placeholder</label>
              <input type="text" id="option-placeholder" name="placeholder" value="${escapeHtml(
                optionData.placeholder || ""
              )}">
            </div>
            <div class="form-group half">
              <label for="option-rows">Rows</label>
              <input type="number" id="option-rows" name="rows" value="${
                optionData.rows || "3"
              }" min="2">
            </div>
          </div>
          <div class="form-group">
            <label for="option-max-length">Maximum Length</label>
            <input type="number" id="option-max-length" name="max_length" value="${
              optionData.max_length !== null ? optionData.max_length : ""
            }" min="0">
          </div>
        `;
        break;
    }

    container.html(fieldsHtml);

    // Attach events for choices if needed
    if (type === "select" || type === "radio") {
      attachChoiceEvents();
    }
  }

  // Parse choices from options string
  function parseChoices(optionsString) {
    if (!optionsString) {
      return [{ value: "", label: "", price: 0 }];
    }

    const choices = [];
    const lines = optionsString.split("\n");

    lines.forEach(function (line) {
      line = line.trim();
      if (!line) return;

      const parts = line.split("|");
      const value = parts[0]?.trim() || "";

      let label = value;
      let price = 0;

      if (parts[1]) {
        const labelPriceParts = parts[1].split(":");
        label = labelPriceParts[0]?.trim() || value;
        price = parseFloat(labelPriceParts[1]) || 0;
      }

      choices.push({ value, label, price });
    });

    return choices.length > 0 ? choices : [{ value: "", label: "", price: 0 }];
  }

  // Create choice row HTML
  function createChoiceRow(value = "", label = "", price = 0) {
    return `
      <div class="choice-row">
        <div class="choice-value">
          <input type="text" placeholder="Value" value="${escapeHtml(value)}">
        </div>
        <div class="choice-label">
          <input type="text" placeholder="Label" value="${escapeHtml(label)}">
        </div>
        <div class="choice-price">
          <input type="number" placeholder="0.00" value="${price}" step="0.01">
        </div>
        <div class="choice-actions">
          <button type="button" class="remove-choice-btn">
            <span class="dashicons dashicons-trash"></span>
          </button>
        </div>
      </div>
    `;
  }

  // Attach events for choices management
  function attachChoiceEvents() {
    $("#add-choice-btn").on("click", function () {
      $("#choices-list").append(createChoiceRow());
    });

    $(document).on("click", ".remove-choice-btn", function () {
      const choicesCount = $("#choices-list .choice-row").length;
      if (choicesCount <= 1) {
        showNotification("You must have at least one choice", "warning");
        return;
      }
      $(this).closest(".choice-row").remove();
    });
  }

  // Update price impact visibility
  function updatePriceImpactVisibility(priceType) {
    const group = $("#price-impact-group");
    if (priceType === "none") {
      group.hide();
    } else {
      group.show();
    }
  }

  // Handle option form submission
  function handleOptionSubmit(e) {
    e.preventDefault();

    if (!validateOptionForm()) {
      return;
    }

    console.log("=== OPTION SUBMISSION STARTED ===");

    showLoading(elements.optionForm.find('button[type="submit"]'));

    const formData = new FormData(elements.optionForm[0]);
    formData.append("action", "mobooking_save_service_option");

    // Process choices for select/radio types
    const optionType = $("#option-type").val();
    if (optionType === "select" || optionType === "radio") {
      const choices = collectChoices();
      formData.append("options", choices);
    }

    $.ajax({
      url: getAjaxUrl(),
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        console.log("Option save response:", response);
        handleOptionSaveSuccess(response);
      },
      error: function (xhr, status, error) {
        console.error("Option save error:", error);
        handleOptionSaveError("Error saving option: " + error);
      },
    });
  }

  // Validate option form
  function validateOptionForm() {
    let isValid = true;
    clearErrors();

    const name = $("#option-name").val().trim();
    if (!name) {
      showError($("#option-name"), "Option name is required");
      isValid = false;
    }

    const optionType = $("#option-type").val();
    if (optionType === "select" || optionType === "radio") {
      const choices = collectChoices();
      if (!choices || choices.trim() === "") {
        showNotification(
          "At least one choice with a value is required",
          "error"
        );
        isValid = false;
      }
    }

    return isValid;
  }

  // Collect choices data
  function collectChoices() {
    const choices = [];

    $("#choices-list .choice-row").each(function () {
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

  // Handle successful option save
  function handleOptionSaveSuccess(response) {
    hideLoading(elements.optionForm.find('button[type="submit"]'));

    if (response.success) {
      showNotification("Option saved successfully!", "success");
      hideModals();
      loadServiceOptions(state.currentServiceId);
    } else {
      handleOptionSaveError(response.data?.message || "Failed to save option");
    }
  }

  // Handle option save error
  function handleOptionSaveError(message) {
    hideLoading(elements.optionForm.find('button[type="submit"]'));
    showNotification("Error: " + message, "error");
  }

  // Update options order
  function updateOptionsOrder() {
    const orderData = [];

    elements.optionsContainer.find(".option-card").each(function (index) {
      orderData.push({
        id: $(this).data("option-id"),
        order: index,
      });
    });

    $.ajax({
      url: getAjaxUrl(),
      type: "POST",
      data: {
        action: "mobooking_update_options_order",
        service_id: state.currentServiceId,
        order_data: JSON.stringify(orderData),
        nonce: getNonce(),
      },
      success: function (response) {
        if (response.success) {
          console.log("Options order updated");
        }
      },
      error: function (xhr, status, error) {
        console.error("Error updating options order:", error);
      },
    });
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
    element.next(".field-error").remove();
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
  function showDeleteConfirmation(type, id) {
    state.deleteType = type;
    state.deleteTarget = id;
    elements.confirmationModal.show();
  }

  function hideModals() {
    $(".mobooking-modal").hide();
    state.deleteType = null;
    state.deleteTarget = null;
  }

  function handleDeleteConfirmation() {
    if (state.deleteType === "service") {
      deleteService(state.deleteTarget);
    } else if (state.deleteType === "option") {
      deleteOption(state.deleteTarget);
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
        hideModals();
      },
      error: function () {
        showNotification("Error deleting service", "error");
        hideModals();
      },
    });
  }

  function deleteOption(optionId) {
    $.ajax({
      url: getAjaxUrl(),
      type: "POST",
      data: {
        action: "mobooking_delete_service_option",
        id: optionId,
        nonce: getNonce(),
      },
      success: function (response) {
        if (response.success) {
          showNotification("Option deleted successfully", "success");
          loadServiceOptions(state.currentServiceId);
        } else {
          showNotification("Error deleting option", "error");
        }
        hideModals();
      },
      error: function () {
        showNotification("Error deleting option", "error");
        hideModals();
      },
    });
  }

  // Utility functions
  function getAjaxUrl() {
    return mobookingServices?.ajaxUrl || "/wp-admin/admin-ajax.php";
  }

  function getNonce() {
    return mobookingServices?.serviceNonce || "";
  }

  function escapeHtml(text) {
    if (!text) return "";
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  // Initialize when document is ready
  $(document).ready(function () {
    init();
  });
})(jQuery);
