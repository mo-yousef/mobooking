/**
 * Unified Service Manager
 *
 * A comprehensive JavaScript solution for managing services and their options
 * with improved reliability and user experience.
 */
document.addEventListener("DOMContentLoaded", function () {
  "use strict";

  // Cache DOM elements
  const elements = {
    // Service related elements
    serviceForm: document.getElementById("unified-service-form"),
    serviceId: document.getElementById("service-id"),
    serviceName: document.getElementById("service-name"),
    servicePrice: document.getElementById("service-price"),
    serviceDuration: document.getElementById("service-duration"),
    saveServiceBtn: document.getElementById("save-service-button"),
    deleteServiceBtns: document.querySelectorAll(".delete-service-btn"),

    // Options elements
    optionsList: document.querySelector(".options-list"),
    optionForm: document.getElementById("option-form"),
    optionFormContainer: document.querySelector(".option-form-container"),
    noOptionSelected: document.querySelector(".no-option-selected"),
    addOptionBtns: document.querySelectorAll(".add-option-button"),
    optionType: document.getElementById("option-type"),
    dynamicFields: document.getElementById("dynamic-fields"),
    saveOptionBtn: document.querySelector(".save-option-btn"),
    cancelOptionBtn: document.querySelector(".cancel-option-btn"),
    deleteOptionBtn: document.querySelector(".delete-option-btn"),

    // Tab navigation
    tabButtons: document.querySelectorAll(".tab-button"),
    tabPanes: document.querySelectorAll(".tab-pane"),

    // Misc UI elements
    notificationContainer: document.getElementById("notification-container"),
    confirmationModal: document.getElementById("confirmation-modal"),
    confirmDeleteBtn: document.querySelector(".confirm-delete-btn"),
    cancelDeleteBtn: document.querySelector(".cancel-delete-btn"),

    // Media elements
    selectImageBtn: document.querySelector(".select-image"),
    imagePreview: document.querySelector(".image-preview"),
    iconItems: document.querySelectorAll(".icon-item"),
    iconSelect: document.getElementById("service-icon"),
    iconPreview: document.querySelector(".icon-preview"),
    optionsSearch: document.getElementById("options-search"),
  };

  // Application state
  const state = {
    currentServiceId: mobookingData?.currentServiceId || null,
    isEditing: !!mobookingData?.currentServiceId,
    currentOptionId: null,
    optionsChanged: false,
    optionsSortableInitialized: false,
    choicesSortableInitialized: false,
    deleteTarget: null,
    deleteType: null, // 'service' or 'option'
  };

  // Initialize the application
  function init() {
    attachEventListeners();
    initMediaUploader();

    // Initialize sortable if options exist
    if (elements.optionsList && elements.optionsList.children.length > 1) {
      initOptionsSortable();
    }
  }

  // Attach all event listeners
  function attachEventListeners() {
    // Service form submission
    if (elements.serviceForm) {
      elements.serviceForm.addEventListener("submit", handleServiceFormSubmit);
    }

    // Tab navigation
    elements.tabButtons.forEach((button) => {
      button.addEventListener("click", () => switchTab(button.dataset.tab));
    });

    // Service deletion
    elements.deleteServiceBtns.forEach((btn) => {
      btn.addEventListener("click", () =>
        showDeleteConfirmation("service", btn.dataset.id)
      );
    });

    // Add new option
    elements.addOptionBtns.forEach((btn) => {
      btn.addEventListener("click", showNewOptionForm);
    });

    // Cancel option editing
    if (elements.cancelOptionBtn) {
      elements.cancelOptionBtn.addEventListener("click", cancelOptionEditing);
    }

    // Delete option
    if (elements.deleteOptionBtn) {
      elements.deleteOptionBtn.addEventListener("click", () => {
        const optionId = elements.optionForm.querySelector("#option-id").value;
        if (optionId) {
          showDeleteConfirmation("option", optionId);
        }
      });
    }

    // Option type change
    if (elements.optionType) {
      elements.optionType.addEventListener("change", function () {
        generateDynamicFields(this.value);
      });
    }

    // Save option
    if (elements.saveOptionBtn) {
      elements.saveOptionBtn.addEventListener("click", handleOptionFormSubmit);
    }

    // Option item click
    if (elements.optionsList) {
      elements.optionsList.addEventListener("click", function (e) {
        const optionItem = e.target.closest(".option-item");
        // Don't handle clicks on drag handle
        if (optionItem && !e.target.closest(".option-drag-handle")) {
          loadOptionForm(optionItem.dataset.id);
        }
      });
    }

    // Option price type change
    const priceTypeField = document.getElementById("option-price-type");
    if (priceTypeField) {
      priceTypeField.addEventListener("change", function () {
        updatePriceFields(this.value);
      });
    }

    // Icon selection
    elements.iconItems.forEach((item) => {
      item.addEventListener("click", function () {
        const icon = this.dataset.icon;
        elements.iconSelect.value = icon;
        elements.iconPreview.innerHTML = `<span class="dashicons ${icon}"></span>`;
      });
    });

    // Filter options with search
    if (elements.optionsSearch) {
      elements.optionsSearch.addEventListener("input", filterOptions);
    }

    // Confirmation modal events
    if (elements.confirmDeleteBtn) {
      elements.confirmDeleteBtn.addEventListener(
        "click",
        handleDeleteConfirmation
      );
    }

    if (elements.cancelDeleteBtn) {
      elements.cancelDeleteBtn.addEventListener("click", hideConfirmationModal);
    }

    // Close modal when clicking on X
    const modalClose = document.querySelector(".modal-close");
    if (modalClose) {
      modalClose.addEventListener("click", hideConfirmationModal);
    }
  }

  // Switch between tabs
  function switchTab(tabId) {
    elements.tabButtons.forEach((btn) => {
      btn.classList.toggle("active", btn.dataset.tab === tabId);
    });

    elements.tabPanes.forEach((pane) => {
      pane.classList.toggle("active", pane.id === tabId);
    });
  }

  // Service Form Submission
  function handleServiceFormSubmit(e) {
    e.preventDefault();

    // Validate the form
    if (!validateServiceForm()) {
      return;
    }

    // Show loading state
    showLoading(elements.saveServiceBtn);

    // Get form data
    const formData = new FormData(elements.serviceForm);
    formData.append("action", "mobooking_save_service");
    formData.append("nonce", mobookingData.serviceNonce);

    // Submit the form via fetch API
    fetch(mobookingData.ajaxUrl, {
      method: "POST",
      body: formData,
      credentials: "same-origin",
    })
      .then((response) => response.json())
      .then((response) => {
        if (response.success) {
          showNotification(
            response.data.message || mobookingData.messages.serviceSuccess,
            "success"
          );

          // If this was a new service, update the URL and state
          if (!state.isEditing) {
            state.currentServiceId = response.data.id;
            state.isEditing = true;

            // Update form fields
            elements.serviceId.value = response.data.id;

            // Update URL without reloading the page
            const newUrl =
              window.location.pathname +
              "?view=edit&service_id=" +
              response.data.id +
              "&active_tab=options";
            window.history.pushState({}, "", newUrl);

            // Switch to options tab
            switchTab("options");
          } else {
            // If editing an existing service, reload to show updates
            setTimeout(() => {
              window.location.reload();
            }, 1000);
          }
        } else {
          showNotification(
            response.data.message || mobookingData.messages.serviceError,
            "error"
          );
        }
        hideLoading(elements.saveServiceBtn);
      })
      .catch((error) => {
        console.error("Error:", error);
        showNotification(mobookingData.messages.serviceError, "error");
        hideLoading(elements.saveServiceBtn);
      });
  }

  // Validate service form
  function validateServiceForm() {
    let isValid = true;

    // Clear previous errors
    clearErrors();

    // Validate service name
    if (!elements.serviceName.value.trim()) {
      showError(elements.serviceName, "name-error", "Service name is required");
      isValid = false;
    }

    // Validate price
    const price = parseFloat(elements.servicePrice.value);
    if (isNaN(price) || price <= 0) {
      showError(
        elements.servicePrice,
        "price-error",
        "Price must be greater than zero"
      );
      isValid = false;
    }

    // Validate duration
    const duration = parseInt(elements.serviceDuration.value);
    if (isNaN(duration) || duration < 15) {
      showError(
        elements.serviceDuration,
        "duration-error",
        "Duration must be at least 15 minutes"
      );
      isValid = false;
    }

    return isValid;
  }

  // Show error for a field
  function showError(element, errorId, message) {
    const errorElement = document.getElementById(errorId);
    element.parentNode.classList.add("has-error");

    if (errorElement) {
      errorElement.textContent = message;
      errorElement.classList.add("active");
    }
  }

  // Clear all form errors
  function clearErrors() {
    // Remove error classes
    document.querySelectorAll(".has-error").forEach((el) => {
      el.classList.remove("has-error");
    });

    // Hide error messages
    document.querySelectorAll(".field-error").forEach((el) => {
      el.textContent = "";
      el.classList.remove("active");
    });
  }

  // Show loading indicator
  function showLoading(button) {
    if (!button) return;

    const normalState = button.querySelector(".normal-state");
    const loadingState = button.querySelector(".loading-state");

    if (normalState && loadingState) {
      normalState.style.display = "none";
      loadingState.style.display = "inline-block";
    } else {
      // Fallback loading indicator
      const originalText = button.innerHTML;
      button.dataset.originalText = originalText;
      button.innerHTML = '<span class="loading-spinner"></span> Loading...';
    }

    button.disabled = true;
  }

  // Hide loading indicator
  function hideLoading(button) {
    if (!button) return;

    const normalState = button.querySelector(".normal-state");
    const loadingState = button.querySelector(".loading-state");

    if (normalState && loadingState) {
      normalState.style.display = "inline-block";
      loadingState.style.display = "none";
    } else if (button.dataset.originalText) {
      // Restore original text from fallback method
      button.innerHTML = button.dataset.originalText;
      delete button.dataset.originalText;
    }

    button.disabled = false;
  }

  // Show notification
  function showNotification(message, type = "info") {
    // Create notification element if it doesn't exist
    if (!document.getElementById("notification-message")) {
      const container = elements.notificationContainer || document.body;
      const notification = document.createElement("div");
      notification.id = "notification-message";
      notification.className = `notification notification-${type}`;
      container.appendChild(notification);
    }

    // Update notification
    const notificationElement = document.getElementById("notification-message");
    notificationElement.textContent = message;
    notificationElement.className = `notification notification-${type}`;

    // Smooth fade-in effect
    notificationElement.style.display = "block";
    notificationElement.style.opacity = "0";
    setTimeout(() => {
      notificationElement.style.opacity = "1";
    }, 10);

    // Auto-hide after delay
    setTimeout(() => {
      notificationElement.style.opacity = "0";
      setTimeout(() => {
        notificationElement.style.display = "none";
      }, 300);
    }, 4000);
  }

  // Initialize media uploader
  function initMediaUploader() {
    if (!elements.selectImageBtn) return;

    let mediaUploader;

    elements.selectImageBtn.addEventListener("click", function (e) {
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
        const imageField = document.getElementById("service-image");
        if (imageField) {
          imageField.value = attachment.url;
        }

        // Update preview
        if (elements.imagePreview) {
          elements.imagePreview.innerHTML = `<img src="${attachment.url}" alt="">`;
        }
      });

      // Open the uploader
      mediaUploader.open();
    });
  }

  // Initialize options sortable
  function initOptionsSortable() {
    if (
      !jQuery ||
      !jQuery.fn.sortable ||
      state.optionsSortableInitialized ||
      !elements.optionsList
    )
      return;

    jQuery(elements.optionsList).sortable({
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
      update: function () {
        state.optionsChanged = true;
        updateOptionsOrder();
      },
    });

    state.optionsSortableInitialized = true;
    elements.optionsList.classList.add("sortable-enabled");
  }

  // Update the order of options
  function updateOptionsOrder() {
    if (!elements.optionsList) return;

    const orderData = [];
    const options = elements.optionsList.querySelectorAll(".option-item");

    options.forEach((option, index) => {
      orderData.push({
        id: option.dataset.id,
        order: index,
      });
    });

    // Send the update to the server
    const formData = new FormData();
    formData.append("action", "mobooking_update_options_order");
    formData.append("service_id", state.currentServiceId);
    formData.append("order_data", JSON.stringify(orderData));
    formData.append("nonce", mobookingData.serviceNonce);

    fetch(mobookingData.ajaxUrl, {
      method: "POST",
      body: formData,
      credentials: "same-origin",
    })
      .then((response) => response.json())
      .then((response) => {
        if (response.success) {
          // Silently update order without showing notification
          state.optionsChanged = false;
        }
      })
      .catch((error) => {
        console.error("Error updating options order:", error);
      });
  }

  // Show new option form
  function showNewOptionForm() {
    // Deselect any active option
    const activeOptions = document.querySelectorAll(".option-item.active");
    activeOptions.forEach((option) => option.classList.remove("active"));

    // Reset the form
    if (elements.optionForm) {
      elements.optionForm.reset();

      // Set form title
      const titleEl = elements.optionForm.querySelector(".option-form-title");
      if (titleEl) {
        titleEl.textContent = "Add New Option";
      }

      // Clear the ID field
      const idField = elements.optionForm.querySelector("#option-id");
      if (idField) {
        idField.value = "";
      }

      // Hide delete button
      if (elements.deleteOptionBtn) {
        elements.deleteOptionBtn.style.display = "none";
      }

      // Set option type to checkbox by default and generate fields
      if (elements.optionType) {
        elements.optionType.value = "checkbox";
        generateDynamicFields("checkbox");
      }

      // Show the form
      elements.noOptionSelected.style.display = "none";
      elements.optionFormContainer.style.display = "block";
    }
  }

  // Load option form for editing
  function loadOptionForm(optionId) {
    if (!optionId || !elements.optionForm) return;

    // Show loading indicator
    elements.optionFormContainer.classList.add("loading");
    elements.noOptionSelected.style.display = "none";
    elements.optionFormContainer.style.display = "block";

    // Set current option ID
    state.currentOptionId = optionId;

    // Highlight the selected option
    const options = document.querySelectorAll(".option-item");
    options.forEach((option) => {
      option.classList.toggle("active", option.dataset.id === optionId);
    });

    // Get option data via fetch
    const formData = new FormData();
    formData.append("action", "mobooking_get_service_option");
    formData.append("id", optionId);
    formData.append("nonce", mobookingData.serviceNonce);

    fetch(mobookingData.ajaxUrl, {
      method: "POST",
      body: formData,
      credentials: "same-origin",
    })
      .then((response) => response.json())
      .then((response) => {
        if (response.success && response.data.option) {
          const option = response.data.option;

          // Fill the form
          elements.optionForm.querySelector("#option-id").value = option.id;
          elements.optionForm.querySelector("#option-name").value = option.name;
          elements.optionForm.querySelector("#option-description").value =
            option.description || "";
          elements.optionForm.querySelector("#option-type").value =
            option.type || "checkbox";
          elements.optionForm.querySelector("#option-required").value =
            option.is_required || "0";
          elements.optionForm.querySelector("#option-price-type").value =
            option.price_type || "fixed";
          elements.optionForm.querySelector("#option-price-impact").value =
            option.price_impact || "0";

          // Set form title
          const titleEl =
            elements.optionForm.querySelector(".option-form-title");
          if (titleEl) {
            titleEl.textContent = "Edit Option: " + option.name;
          }

          // Show delete button
          if (elements.deleteOptionBtn) {
            elements.deleteOptionBtn.style.display = "block";
          }

          // Generate type-specific fields
          generateDynamicFields(option.type, option);

          // Apply price configuration
          updatePriceFields(option.price_type || "fixed");
        } else {
          showNotification("Error loading option data", "error");
          cancelOptionEditing();
        }

        // Remove loading indicator
        elements.optionFormContainer.classList.remove("loading");
      })
      .catch((error) => {
        console.error("Error loading option:", error);
        showNotification("Error loading option data", "error");
        cancelOptionEditing();
        elements.optionFormContainer.classList.remove("loading");
      });
  }

  // Cancel option editing
  function cancelOptionEditing() {
    // Reset state
    state.currentOptionId = null;

    // Deselect any option
    const activeOptions = document.querySelectorAll(".option-item.active");
    activeOptions.forEach((option) => option.classList.remove("active"));

    // Hide the form, show the placeholder
    elements.optionFormContainer.style.display = "none";
    elements.noOptionSelected.style.display = "flex";
  }

  // Generate dynamic fields based on option type
  function generateDynamicFields(optionType, optionData = {}) {
    if (!elements.dynamicFields) return;

    const fieldContainer = elements.dynamicFields;
    fieldContainer.innerHTML = "";

    switch (optionType) {
      case "checkbox":
        fieldContainer.innerHTML = `
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="option-default-value">Default Value</label>
                            <select id="option-default-value" name="default_value">
                                <option value="0" ${
                                  optionData.default_value == "1"
                                    ? ""
                                    : "selected"
                                }>Unchecked</option>
                                <option value="1" ${
                                  optionData.default_value == "1"
                                    ? "selected"
                                    : ""
                                }>Checked</option>
                            </select>
                        </div>
                        <div class="form-group half">
                            <label for="option-label">Option Label</label>
                            <input type="text" id="option-label" name="option_label" value="${
                              optionData.option_label || ""
                            }" placeholder="Check this box to add...">
                        </div>
                    </div>
                `;
        break;

      case "number":
        fieldContainer.innerHTML = `
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="option-min-value">Minimum Value</label>
                            <input type="number" id="option-min-value" name="min_value" value="${
                              optionData.min_value !== null
                                ? optionData.min_value
                                : "0"
                            }">
                        </div>
                        <div class="form-group half">
                            <label for="option-max-value">Maximum Value</label>
                            <input type="number" id="option-max-value" name="max_value" value="${
                              optionData.max_value !== null
                                ? optionData.max_value
                                : ""
                            }">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="option-default-value">Default Value</label>
                            <input type="number" id="option-default-value" name="default_value" value="${
                              optionData.default_value || ""
                            }">
                        </div>
                        <div class="form-group half">
                            <label for="option-placeholder">Placeholder</label>
                            <input type="text" id="option-placeholder" name="placeholder" value="${
                              optionData.placeholder || ""
                            }">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="option-step">Step</label>
                            <input type="number" id="option-step" name="step" value="${
                              optionData.step || "1"
                            }" step="0.01">
                        </div>
                        <div class="form-group half">
                            <label for="option-unit">Unit Label</label>
                            <input type="text" id="option-unit" name="unit" value="${
                              optionData.unit || ""
                            }" placeholder="sq ft, hours, etc.">
                        </div>
                    </div>
                `;
        break;

      case "select":
      case "radio":
        // Parse existing choices from options string
        let choices = [];
        if (optionData.options) {
          choices = parseOptionsString(optionData.options);
        }

        // Create choices container
        const choicesContainer = document.createElement("div");
        choicesContainer.className = "form-group";
        choicesContainer.innerHTML = `
                    <label>Choices</label>
                    <div class="choices-container">
                        <div class="choices-header">
                            <div class="choice-value">Value</div>
                            <div class="choice-label">Label</div>
                            <div class="choice-price">Price Impact</div>
                            <div class="choice-actions"></div>
                        </div>
                        <div class="choices-list"></div>
                        <div class="add-choice-container">
                            <button type="button" class="button add-choice">Add Choice</button>
                        </div>
                    </div>
                    <input type="hidden" id="option-choices" name="options">
                `;

        // Add default value field
        const defaultValueGroup = document.createElement("div");
        defaultValueGroup.className = "form-group";
        defaultValueGroup.innerHTML = `
                    <label for="option-default-value">Default Value</label>
                    <input type="text" id="option-default-value" name="default_value" value="${
                      optionData.default_value || ""
                    }">
                    <p class="field-hint">Enter the value (not the label) of the default choice</p>
                `;

        // Add to container
        fieldContainer.appendChild(choicesContainer);
        fieldContainer.appendChild(defaultValueGroup);

        // Add choices to the list
        const choicesList = choicesContainer.querySelector(".choices-list");
        if (choices.length === 0) {
          // Add a blank choice if none exist
          addChoiceRow(choicesList);
        } else {
          // Add each choice
          choices.forEach((choice) => {
            addChoiceRow(choicesList, choice.value, choice.label, choice.price);
          });
        }

        // Set up choice-related events
        setupChoiceEvents(choicesContainer);
        break;

      case "text":
        fieldContainer.innerHTML = `
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="option-default-value">Default Value</label>
                            <input type="text" id="option-default-value" name="default_value" value="${
                              optionData.default_value || ""
                            }">
                        </div>
                        <div class="form-group half">
                            <label for="option-placeholder">Placeholder</label>
                            <input type="text" id="option-placeholder" name="placeholder" value="${
                              optionData.placeholder || ""
                            }">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="option-min-length">Minimum Length</label>
                            <input type="number" id="option-min-length" name="min_length" value="${
                              optionData.min_length !== null
                                ? optionData.min_length
                                : ""
                            }" min="0">
                        </div>
                        <div class="form-group half">
                            <label for="option-max-length">Maximum Length</label>
                            <input type="number" id="option-max-length" name="max_length" value="${
                              optionData.max_length !== null
                                ? optionData.max_length
                                : ""
                            }" min="0">
                        </div>
                    </div>
                `;
        break;

      case "textarea":
        fieldContainer.innerHTML = `
                    <div class="form-group">
                        <label for="option-default-value">Default Value</label>
                        <textarea id="option-default-value" name="default_value" rows="2">${
                          optionData.default_value || ""
                        }</textarea>
                    </div>
                    <div class="form-group">
                        <label for="option-placeholder">Placeholder</label>
                        <input type="text" id="option-placeholder" name="placeholder" value="${
                          optionData.placeholder || ""
                        }">
                    </div>
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="option-rows">Rows</label>
                            <input type="number" id="option-rows" name="rows" value="${
                              optionData.rows || "3"
                            }" min="2">
                        </div>
                        <div class="form-group half">
                            <label for="option-max-length">Maximum Length</label>
                            <input type="number" id="option-max-length" name="max_length" value="${
                              optionData.max_length !== null
                                ? optionData.max_length
                                : ""
                            }" min="0">
                        </div>
                    </div>
                `;
        break;

      case "quantity":
        fieldContainer.innerHTML = `
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="option-min-value">Minimum Quantity</label>
                            <input type="number" id="option-min-value" name="min_value" value="${
                              optionData.min_value !== null
                                ? optionData.min_value
                                : "0"
                            }" min="0">
                        </div>
                        <div class="form-group half">
                            <label for="option-max-value">Maximum Quantity</label>
                            <input type="number" id="option-max-value" name="max_value" value="${
                              optionData.max_value !== null
                                ? optionData.max_value
                                : ""
                            }" min="0">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="option-default-value">Default Quantity</label>
                            <input type="number" id="option-default-value" name="default_value" value="${
                              optionData.default_value || "0"
                            }" min="0">
                        </div>
                        <div class="form-group half">
                            <label for="option-step">Step</label>
                            <input type="number" id="option-step" name="step" value="${
                              optionData.step || "1"
                            }" min="1">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="option-unit">Unit Label</label>
                            <input type="text" id="option-unit" name="unit" value="${
                              optionData.unit || ""
                            }" placeholder="items, people, etc.">
                        </div>
                    </div>
                `;
        break;
    }
  }

  // Set up event listeners for choices
  function setupChoiceEvents(container) {
    if (!container) return;

    // Add choice button
    const addButton = container.querySelector(".add-choice");
    if (addButton) {
      addButton.addEventListener("click", function () {
        const choicesList = container.querySelector(".choices-list");
        addChoiceRow(choicesList);
        updateOptionsField();
      });
    }

    // Initialize sortable for choices
    const choicesList = container.querySelector(".choices-list");
    if (choicesList && jQuery && jQuery.fn.sortable) {
      jQuery(choicesList).sortable({
        handle: ".choice-drag-handle",
        placeholder: "choice-row-placeholder",
        axis: "y",
        opacity: 0.8,
        update: function () {
          updateOptionsField();
        },
      });
    }

    // Delegate events for removing choices and updating fields
    choicesList.addEventListener("click", function (e) {
      if (e.target.closest(".remove-choice")) {
        const choiceRow = e.target.closest(".choice-row");

        // Don't remove if it's the only choice
        if (choicesList.querySelectorAll(".choice-row").length <= 1) {
          showNotification("You must have at least one choice", "warning");
          return;
        }

        // Remove the row
        choiceRow.remove();
        updateOptionsField();
      }
    });

    // Listen for input changes to update the hidden field
    choicesList.addEventListener("input", function (e) {
      if (
        e.target.matches(
          ".choice-value-input, .choice-label-input, .choice-price-input"
        )
      ) {
        updateOptionsField();
      }
    });
  }

  // Add a new choice row
  function addChoiceRow(container, value = "", label = "", price = 0) {
    if (!container) return;

    const row = document.createElement("div");
    row.className = "choice-row";
    row.innerHTML = `
            <div class="choice-drag-handle">
                <span class="dashicons dashicons-menu"></span>
            </div>
            <div class="choice-value">
                <input type="text" class="choice-value-input" value="${escapeHtml(
                  value
                )}" placeholder="value">
            </div>
            <div class="choice-label">
                <input type="text" class="choice-label-input" value="${escapeHtml(
                  label
                )}" placeholder="Display Label">
            </div>
            <div class="choice-price">
                <input type="number" class="choice-price-input" value="${price}" step="0.01" placeholder="0.00">
            </div>
            <div class="choice-actions">
                <button type="button" class="button-link remove-choice">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
        `;

    container.appendChild(row);

    // Focus on the value input for new choices
    if (!value) {
      row.querySelector(".choice-value-input").focus();
    }

    return row;
  }

  // Update the hidden options field
  function updateOptionsField() {
    const form = document.getElementById("option-form");
    if (!form) return;

    const choiceRows = form.querySelectorAll(".choice-row");
    const choices = [];

    choiceRows.forEach((row) => {
      const value = row.querySelector(".choice-value-input").value.trim();
      const label = row.querySelector(".choice-label-input").value.trim();
      const price =
        parseFloat(row.querySelector(".choice-price-input").value) || 0;

      if (value) {
        if (price > 0) {
          choices.push(`${value}|${label}:${price}`);
        } else {
          choices.push(`${value}|${label}`);
        }
      }
    });

    const hiddenField = form.querySelector("#option-choices");
    if (hiddenField) {
      hiddenField.value = choices.join("\n");
    }
  }

  // Parse options string into array of objects
  function parseOptionsString(optionsString) {
    if (!optionsString) return [];

    const options = [];
    const lines = optionsString.split("\n");

    lines.forEach((line) => {
      if (!line.trim()) return;

      const parts = line.split("|");
      const value = parts[0]?.trim() || "";

      let label = "",
        price = 0;

      if (parts[1]) {
        const labelPriceParts = parts[1].split(":");
        label = labelPriceParts[0]?.trim() || "";
        price = parseFloat(labelPriceParts[1]) || 0;
      }

      if (value) {
        options.push({
          value,
          label: label || value,
          price,
        });
      }
    });

    return options;
  }

  // Update price fields based on selected price type
  function updatePriceFields(priceType) {
    const valueContainer = document.querySelector(".price-impact-value");
    if (!valueContainer) return;

    const valueField = valueContainer.querySelector("input");
    const valueLabel = valueContainer.querySelector("label");

    valueContainer.style.display = priceType === "none" ? "none" : "block";

    if (!priceType || priceType === "none") return;

    if (priceType === "fixed") {
      valueLabel.textContent = "Amount ($)";
      valueField.type = "number";
      valueField.step = "0.01";
      valueField.placeholder = "9.99";
    } else if (priceType === "percentage") {
      valueLabel.textContent = "Percentage (%)";
      valueField.type = "number";
      valueField.step = "1";
      valueField.placeholder = "10";
    } else if (priceType === "multiply") {
      valueLabel.textContent = "Multiplier";
      valueField.type = "number";
      valueField.step = "0.1";
      valueField.placeholder = "1.5";
    }
  }

  // Handle option form submission
  function handleOptionFormSubmit() {
    // Validate the form
    if (!validateOptionForm()) return;

    // Show loading
    showLoading(elements.saveOptionBtn);

    // Get the form data
    const formData = new FormData(elements.optionForm);

    // Add service ID if not present
    if (!formData.has("service_id")) {
      formData.append("service_id", state.currentServiceId);
    }

    // Add necessary action and nonce
    formData.append("action", "mobooking_save_service_option");
    formData.append("nonce", mobookingData.serviceNonce);

    // Send the data
    fetch(mobookingData.ajaxUrl, {
      method: "POST",
      body: formData,
      credentials: "same-origin",
    })
      .then((response) => response.json())
      .then((response) => {
        if (response.success) {
          showNotification(
            response.data.message || mobookingData.messages.optionSuccess,
            "success"
          );

          // Reload the page to show the updated option
          setTimeout(() => {
            window.location.reload();
          }, 1000);
        } else {
          showNotification(
            response.data.message || mobookingData.messages.optionError,
            "error"
          );
          hideLoading(elements.saveOptionBtn);
        }
      })
      .catch((error) => {
        console.error("Error saving option:", error);
        showNotification(mobookingData.messages.optionError, "error");
        hideLoading(elements.saveOptionBtn);
      });
  }

  // Validate option form
  function validateOptionForm() {
    let isValid = true;

    // Clear previous errors
    clearErrors();

    // Get form fields
    const nameField = document.getElementById("option-name");
    const typeField = document.getElementById("option-type");

    // Validate name
    if (!nameField || !nameField.value.trim()) {
      showError(nameField, "option-name-error", "Option name is required");
      isValid = false;
    }

    // Validate type
    if (!typeField || !typeField.value) {
      showError(typeField, "option-type-error", "Option type is required");
      isValid = false;
    }

    // For select/radio, validate that there's at least one choice
    if (
      typeField &&
      (typeField.value === "select" || typeField.value === "radio")
    ) {
      const choiceRows = document.querySelectorAll(".choice-row");
      let hasValidChoice = false;

      choiceRows.forEach((row) => {
        const value = row.querySelector(".choice-value-input")?.value.trim();
        if (value) {
          hasValidChoice = true;
        }
      });

      if (!hasValidChoice) {
        showNotification(
          "At least one choice with a value is required",
          "error"
        );
        isValid = false;
      }
    }

    return isValid;
  }

  // Filter options with search
  function filterOptions() {
    const searchTerm = elements.optionsSearch.value.toLowerCase();
    const options = document.querySelectorAll(".option-item");

    if (!searchTerm) {
      options.forEach((option) => {
        option.style.display = "flex";
      });
      return;
    }

    options.forEach((option) => {
      const optionName = option
        .querySelector(".option-name")
        .textContent.toLowerCase();
      const optionType = option
        .querySelector(".option-type")
        .textContent.toLowerCase();

      if (optionName.includes(searchTerm) || optionType.includes(searchTerm)) {
        option.style.display = "flex";
      } else {
        option.style.display = "none";
      }
    });
  }

  // Show delete confirmation modal
  function showDeleteConfirmation(type, id) {
    if (!elements.confirmationModal) return;

    // Set state for confirmation handler
    state.deleteType = type;
    state.deleteTarget = id;

    // Show modal
    elements.confirmationModal.style.display = "flex";
  }

  // Hide confirmation modal
  function hideConfirmationModal() {
    if (!elements.confirmationModal) return;

    // Reset state
    state.deleteType = null;
    state.deleteTarget = null;

    // Hide modal
    elements.confirmationModal.style.display = "none";
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
      deleteOption(state.deleteTarget);
    }
  }

  // Delete a service
  function deleteService(serviceId) {
    const formData = new FormData();
    formData.append("action", "mobooking_delete_service");
    formData.append("id", serviceId);
    formData.append("nonce", mobookingData.serviceNonce);

    fetch(mobookingData.ajaxUrl, {
      method: "POST",
      body: formData,
      credentials: "same-origin",
    })
      .then((response) => response.json())
      .then((response) => {
        if (response.success) {
          showNotification("Service deleted successfully", "success");

          // Redirect to the services list
          setTimeout(() => {
            window.location.href = window.location.pathname + "?view=list";
          }, 1000);
        } else {
          showNotification(
            response.data.message || "Error deleting service",
            "error"
          );
          hideConfirmationModal();
          hideLoading(elements.confirmDeleteBtn);
        }
      })
      .catch((error) => {
        console.error("Error deleting service:", error);
        showNotification("Error deleting service", "error");
        hideConfirmationModal();
        hideLoading(elements.confirmDeleteBtn);
      });
  }

  // Delete an option
  function deleteOption(optionId) {
    const formData = new FormData();
    formData.append("action", "mobooking_delete_service_option");
    formData.append("id", optionId);
    formData.append("nonce", mobookingData.serviceNonce);

    fetch(mobookingData.ajaxUrl, {
      method: "POST",
      body: formData,
      credentials: "same-origin",
    })
      .then((response) => response.json())
      .then((response) => {
        if (response.success) {
          showNotification("Option deleted successfully", "success");

          // Reload the page to show the updated options list
          setTimeout(() => {
            window.location.reload();
          }, 1000);
        } else {
          showNotification(
            response.data.message || "Error deleting option",
            "error"
          );
          hideConfirmationModal();
          hideLoading(elements.confirmDeleteBtn);
        }
      })
      .catch((error) => {
        console.error("Error deleting option:", error);
        showNotification("Error deleting option", "error");
        hideConfirmationModal();
        hideLoading(elements.confirmDeleteBtn);
      });
  }

  // Escape HTML for safe insertion
  function escapeHtml(text) {
    if (!text) return "";
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  // Initialize the application
  init();
});
