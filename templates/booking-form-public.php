<?php
/**
 * Fixed render_booking_form function with proper service structure
 * Add this to your Bookings Manager class to replace the existing render_booking_form method
 */
private function render_booking_form($user_id, $services, $atts) {
    try {
        if (!class_exists('\MoBooking\Services\ServiceOptionsManager')) {
            throw new Exception('ServiceOptionsManager class not found');
        }
        
        $options_manager = new \MoBooking\Services\ServiceOptionsManager();
        
        ob_start();
        ?>
        <div class="mobooking-booking-form-container">
            <!-- Enhanced Progress Indicator -->
            <div class="booking-progress">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 16.66%;"></div>
                </div>
                <div class="progress-steps">
                    <div class="step active">
                        <div class="step-number">1</div>
                        <div class="step-label"><?php _e('Services', 'mobooking'); ?></div>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-label"><?php _e('Options', 'mobooking'); ?></div>
                    </div>
                    <div class="step">
                        <div class="step-number">4</div>
                        <div class="step-label"><?php _e('Details', 'mobooking'); ?></div>
                    </div>
                    <div class="step">
                        <div class="step-number">5</div>
                        <div class="step-label"><?php _e('Review', 'mobooking'); ?></div>
                    </div>
                    <div class="step">
                        <div class="step-number">6</div>
                        <div class="step-label"><?php _e('Complete', 'mobooking'); ?></div>
                    </div>
                </div>
            </div>
            
            <form id="mobooking-booking-form" class="booking-form">
                <!-- Hidden fields -->
                <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
                <input type="hidden" name="total_price" id="total_price" value="0">
                <input type="hidden" name="discount_amount" id="discount_amount" value="0">
                <input type="hidden" name="service_options_data" id="service_options_data" value="">
                <?php wp_nonce_field('mobooking-booking-nonce', 'nonce'); ?>
                
                <!-- Step 1: ZIP Code -->
                <div class="booking-step step-1 active">
                    <div class="step-header">
                        <h2><?php _e('Check Service Availability', 'mobooking'); ?></h2>
                        <p><?php _e('Enter your ZIP code to see if we service your area', 'mobooking'); ?></p>
                    </div>
                    
                    <div class="zip-input-group">
                        <label for="customer_zip_code"><?php _e('ZIP Code', 'mobooking'); ?></label>
                        <input type="text" id="customer_zip_code" name="zip_code" class="zip-input" 
                               placeholder="<?php _e('Enter ZIP code', 'mobooking'); ?>" required
                               pattern="[0-9]{5}(-[0-9]{4})?" 
                               title="<?php _e('Please enter a valid ZIP code (e.g., 12345 or 12345-6789)', 'mobooking'); ?>">
                        <p class="zip-help"><?php _e('Enter your ZIP code to check service availability', 'mobooking'); ?></p>
                    </div>
                    
                    <div class="zip-result"></div>
                    
                    <div class="step-actions">
                        <button type="button" class="btn-primary next-step" disabled>
                            <?php _e('Enter ZIP Code', 'mobooking'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Step 2: Services -->
                <div class="booking-step step-2">
                    <div class="step-header">
                        <h2><?php _e('Select Services', 'mobooking'); ?></h2>
                        <p><?php _e('Choose the services you need', 'mobooking'); ?></p>
                    </div>
                    
                    <div class="services-grid services-container">
                        <?php foreach ($services as $service) : 
                            $service_options = $options_manager->get_service_options($service->id);
                            $has_options = !empty($service_options);
                        ?>
                            <div class="service-card" data-service-id="<?php echo esc_attr($service->id); ?>" data-service-price="<?php echo esc_attr($service->price); ?>">
                                <div class="service-header">
                                    <div class="service-visual">
                                        <?php if (!empty($service->image_url)) : ?>
                                            <div class="service-image">
                                                <img src="<?php echo esc_url($service->image_url); ?>" alt="<?php echo esc_attr($service->name); ?>">
                                            </div>
                                        <?php elseif (!empty($service->icon)) : ?>
                                            <div class="service-icon">
                                                <span class="dashicons <?php echo esc_attr($service->icon); ?>"></span>
                                            </div>
                                        <?php else : ?>
                                            <div class="service-icon service-icon-default">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                                                </svg>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="service-content">
                                            <h3><?php echo esc_html($service->name); ?></h3>
                                            <?php if (!empty($service->description)) : ?>
                                                <p class="service-description"><?php echo esc_html($service->description); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="service-selector">
                                        <!-- FIXED: Use consistent checkbox naming for multiple selection -->
                                        <input type="checkbox" name="selected_services[]" value="<?php echo esc_attr($service->id); ?>" 
                                               id="service_<?php echo esc_attr($service->id); ?>" 
                                               data-has-options="<?php echo $has_options ? 1 : 0; ?>">
                                        <label for="service_<?php echo esc_attr($service->id); ?>" class="service-checkbox"></label>
                                    </div>
                                </div>
                                
                                <div class="service-meta">
                                    <div class="service-price"><?php echo wc_price($service->price); ?></div>
                                    <div class="service-duration">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12,6 12,12 16,14"></polyline>
                                        </svg>
                                        <?php echo sprintf(_n('%d min', '%d mins', $service->duration, 'mobooking'), $service->duration); ?>
                                    </div>
                                </div>
                                
                                <?php if ($has_options) : ?>
                                    <div class="service-options-indicator">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="3"/>
                                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                                        </svg>
                                        <?php _e('Customizable options available', 'mobooking'); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="step-actions">
                        <button type="button" class="btn-secondary prev-step"><?php _e('Back', 'mobooking'); ?></button>
                        <button type="button" class="btn-primary next-step" disabled><?php _e('Select Services', 'mobooking'); ?></button>
                    </div>
                </div>
                
                <!-- Step 3: Service Options -->
                <div class="booking-step step-3">
                    <div class="step-header">
                        <h2><?php _e('Customize Your Services', 'mobooking'); ?></h2>
                        <p><?php _e('Configure your selected services', 'mobooking'); ?></p>
                    </div>
                    
                    <div class="service-options-container">
                        <!-- Service options will be loaded dynamically -->
                    </div>
                    
                    <div class="step-actions">
                        <button type="button" class="btn-secondary prev-step"><?php _e('Back', 'mobooking'); ?></button>
                        <button type="button" class="btn-primary next-step"><?php _e('Continue', 'mobooking'); ?></button>
                    </div>
                </div>
                
                <!-- Step 4: Customer Information -->
                <div class="booking-step step-4">
                    <div class="step-header">
                        <h2><?php _e('Your Information', 'mobooking'); ?></h2>
                        <p><?php _e('Please provide your contact details', 'mobooking'); ?></p>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="customer_name"><?php _e('Full Name', 'mobooking'); ?> *</label>
                            <input type="text" id="customer_name" name="customer_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="customer_email"><?php _e('Email Address', 'mobooking'); ?> *</label>
                            <input type="email" id="customer_email" name="customer_email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="customer_phone"><?php _e('Phone Number', 'mobooking'); ?></label>
                            <input type="tel" id="customer_phone" name="customer_phone">
                        </div>
                        
                        <div class="form-group">
                            <label for="service_date"><?php _e('Preferred Date & Time', 'mobooking'); ?> *</label>
                            <input type="datetime-local" id="service_date" name="service_date" required>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="customer_address"><?php _e('Service Address', 'mobooking'); ?> *</label>
                            <textarea id="customer_address" name="customer_address" rows="3" required 
                                      placeholder="<?php _e('Enter the full address where service will be provided', 'mobooking'); ?>"></textarea>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="booking_notes"><?php _e('Special Instructions', 'mobooking'); ?></label>
                            <textarea id="booking_notes" name="booking_notes" rows="3" 
                                      placeholder="<?php _e('Any special instructions or requests...', 'mobooking'); ?>"></textarea>
                        </div>
                    </div>
                    
                    <div class="step-actions">
                        <button type="button" class="btn-secondary prev-step"><?php _e('Back', 'mobooking'); ?></button>
                        <button type="button" class="btn-primary next-step"><?php _e('Review Booking', 'mobooking'); ?></button>
                    </div>
                </div>
                
                <!-- Step 5: Review & Confirm -->
                <div class="booking-step step-5">
                    <div class="step-header">
                        <h2><?php _e('Review Your Booking', 'mobooking'); ?></h2>
                        <p><?php _e('Please review your booking details before confirming', 'mobooking'); ?></p>
                    </div>
                    
                    <div class="booking-summary">
                        <div class="summary-section">
                            <h3><?php _e('Selected Services', 'mobooking'); ?></h3>
                            <div class="selected-services-list">
                                <!-- Services will be populated by JavaScript -->
                            </div>
                        </div>
                        
                        <div class="summary-section">
                            <h3><?php _e('Service Details', 'mobooking'); ?></h3>
                            <div class="service-address"></div>
                            <div class="service-datetime"></div>
                        </div>
                        
                        <div class="summary-section">
                            <h3><?php _e('Contact Information', 'mobooking'); ?></h3>
                            <div class="customer-info"></div>
                        </div>
                        
                        <div class="summary-section discount-section">
                            <h3><?php _e('Discount Code', 'mobooking'); ?></h3>
                            <div class="discount-input-group">
                                <input type="text" id="discount_code" name="discount_code" placeholder="<?php _e('Enter discount code', 'mobooking'); ?>">
                                <button type="button" class="apply-discount-btn"><?php _e('Apply', 'mobooking'); ?></button>
                            </div>
                            <div class="discount-message"></div>
                        </div>
                        
                        <div class="summary-section">
                            <h3><?php _e('Pricing', 'mobooking'); ?></h3>
                            <div class="pricing-summary">
                                <div class="pricing-line">
                                    <span class="label"><?php _e('Subtotal', 'mobooking'); ?></span>
                                    <span class="amount subtotal">$0.00</span>
                                </div>
                                <div class="pricing-line discount" style="display: none;">
                                    <span class="label"><?php _e('Discount', 'mobooking'); ?></span>
                                    <span class="amount">-$0.00</span>
                                </div>
                                <div class="pricing-line total">
                                    <span class="label"><?php _e('Total', 'mobooking'); ?></span>
                                    <span class="amount">$0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="step-actions">
                        <button type="button" class="btn-secondary prev-step"><?php _e('Back', 'mobooking'); ?></button>
                        <button type="submit" class="btn-primary confirm-booking-btn">
                            <span class="btn-text"><?php _e('Confirm Booking', 'mobooking'); ?></span>
                            <span class="btn-loading"><?php _e('Processing...', 'mobooking'); ?></span>
                        </button>
                    </div>
                </div>
                
                <!-- Step 6: Success -->
                <div class="booking-step step-6 step-success">
                    <div class="success-content">
                        <div class="success-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                            </svg>
                        </div>
                        
                        <h2><?php _e('Booking Confirmed!', 'mobooking'); ?></h2>
                        <p class="success-message"><?php _e('Thank you for your booking. We\'ll contact you shortly to confirm the details.', 'mobooking'); ?></p>
                        
                        <div class="booking-reference">
                            <strong><?php _e('Your booking reference:', 'mobooking'); ?></strong>
                            <span class="reference-number">#0000</span>
                        </div>
                        
                        <div class="next-steps">
                            <p><?php _e('What happens next?', 'mobooking'); ?></p>
                            <ul>
                                <li><?php _e('You\'ll receive a confirmation email shortly', 'mobooking'); ?></li>
                                <li><?php _e('We\'ll contact you to confirm the appointment details', 'mobooking'); ?></li>
                                <li><?php _e('Our team will arrive at the scheduled time', 'mobooking'); ?></li>
                            </ul>
                        </div>
                        
                        <div class="success-actions">
                            <button type="button" class="btn-primary new-booking-btn" onclick="location.reload();">
                                <?php _e('Book Another Service', 'mobooking'); ?>
                            </button>
                            <button type="button" class="btn-secondary print-booking-btn" onclick="window.print();">
                                <?php _e('Print Confirmation', 'mobooking'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <style>
        /* Enhanced styles for the fixed booking form */
        .zip-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .zip-input {
            flex: 1;
            padding: 0.75rem 3rem 0.75rem 1rem;
            border: 2px solid var(--booking-gray-300);
            border-radius: var(--booking-radius);
            font-size: 1rem;
            transition: var(--booking-transition);
            background: white;
        }
        
        .zip-input:focus {
            outline: none;
            border-color: var(--booking-primary);
            box-shadow: 0 0 0 3px rgb(59 130 246 / 0.1);
        }
        
        .zip-input.error {
            border-color: var(--booking-error);
            box-shadow: 0 0 0 3px rgb(239 68 68 / 0.1);
        }
        
        .zip-validation-icon {
            position: absolute;
            right: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 1.5rem;
            height: 1.5rem;
            pointer-events: none;
        }
        
        .zip-validation-icon.checking {
            color: var(--booking-gray-400);
        }
        
        .zip-validation-icon.success {
            color: var(--booking-success);
            font-weight: bold;
            font-size: 18px;
        }
        
        .zip-validation-icon.error {
            color: var(--booking-error);
            font-weight: bold;
            font-size: 18px;
        }
        
        .zip-validation-icon .spinner {
            width: 1rem;
            height: 1rem;
            border: 2px solid var(--booking-gray-300);
            border-top-color: var(--booking-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .service-checkbox {
            width: 1.5rem;
            height: 1.5rem;
            border: 2px solid var(--booking-gray-300);
            border-radius: 0.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--booking-transition);
            background: white;
            position: relative;
        }
        
        .service-card input[type="checkbox"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
        
        .service-card input[type="checkbox"]:checked + .service-checkbox {
            background: var(--booking-primary);
            border-color: var(--booking-primary);
            transform: scale(1.1);
        }
        
        .service-card input[type="checkbox"]:checked + .service-checkbox::after {
            content: "✓";
            color: white;
            font-weight: bold;
            font-size: 0.875rem;
        }
        
        .service-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid var(--booking-gray-200);
            border-radius: var(--booking-radius);
            padding: 1.5rem;
            background: white;
        }
        
        .service-card:hover {
            border-color: var(--booking-primary);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.15);
        }
        
        .service-card.selected {
            border-color: var(--booking-primary);
            background: rgba(59, 130, 246, 0.05);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.25);
        }
        
        .service-card.selecting {
            animation: cardPulse 0.6s ease-out;
        }
        
        @keyframes cardPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1.02); }
        }
        
        .field-error {
            color: var(--booking-error);
            font-size: 0.75rem;
            margin-top: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .field-error::before {
            content: '⚠';
            font-size: 0.875rem;
        }
        
        .error {
            border-color: var(--booking-error) !important;
            box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2) !important;
        }
        
        .btn-primary:disabled {
            background: var(--booking-gray-400) !important;
            cursor: not-allowed !important;
            transform: none !important;
            box-shadow: none !important;
        }
        
        .btn-primary:not(:disabled):hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.25);
        }
        
        .success-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }
        
        @media (max-width: 768px) {
            .success-actions {
                flex-direction: column;
            }
        }
        </style>
        <?php
        return ob_get_clean();
        
    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking: Exception in render_booking_form: ' . $e->getMessage());
        }
        return '<p class="mobooking-error">' . __('Error rendering booking form.', 'mobooking') . '</p>';
    }
}><?php _e('Location', 'mobooking'); ?></div>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <div class="step-label"