<?php
// dashboard/invoice-template.php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Ensure booking and company settings are available (passed from pdf-helper.php)
global $current_booking, $current_company_settings;

if (!isset($current_booking) || !isset($current_company_settings)) {
    echo 'Booking data or company settings not available.';
    return;
}

$booking = $current_booking;
$company_settings = $current_company_settings;

// Helper to safely get array values
function mobooking_get_setting($array, $key, $default = '') {
    return isset($array[$key]) ? esc_html($array[$key]) : esc_html($default);
}

$company_logo_url = mobooking_get_setting($company_settings, 'logo_url');
$company_name = mobooking_get_setting($company_settings, 'company_name', 'Your Company Name');
$company_address = mobooking_get_setting($company_settings, 'business_address', 'Your Company Address');
$company_email = mobooking_get_setting($company_settings, 'business_email', 'your@company.email');
$company_phone = mobooking_get_setting($company_settings, 'phone', 'Your Company Phone');

// Invoice specific details
$invoice_number = 'INV-' . $booking->id;
$invoice_date = date_i18n(get_option('date_format'), time()); // Current date for invoice
$service_date_obj = new DateTime($booking->service_date);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo esc_html($invoice_number); ?></title>
    <style>
        body {
            font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #fff; /* Ensure background is white for PDF */
        }
        .invoice-container {
            width: 100%;
            max-width: 800px; /* Typical A4 width */
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        .company-details {
            text-align: left;
        }
        .company-details img {
            max-width: 150px;
            max-height: 75px;
            margin-bottom: 10px;
        }
        .company-details h1 {
            margin: 0;
            font-size: 1.8em;
            color: #333;
        }
        .invoice-info {
            text-align: right;
        }
        .invoice-info h2 {
            margin: 0;
            font-size: 1.5em;
            color: #555;
        }
        .client-details {
            margin-bottom: 30px;
        }
        .client-details h3 {
            margin-bottom: 5px;
            color: #555;
        }
        .booking-details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .booking-details-table th, .booking-details-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .booking-details-table th {
            background-color: #f9f9f9;
            font-weight: bold;
        }
        .booking-details-table .service-name {
            font-weight: bold;
        }
        .booking-details-table .service-options ul {
            padding-left: 15px;
            margin: 5px 0 0 0;
            font-size: 0.9em;
            color: #555;
        }
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 30px;
        }
        .totals-table {
            width: 50%;
            max-width: 350px;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 8px 12px;
            border: 1px solid #ddd;
        }
        .totals-table .label {
            font-weight: bold;
            text-align: right;
        }
        .totals-table .amount {
            text-align: right;
        }
        .totals-table .grand-total .label,
        .totals-table .grand-total .amount {
            font-size: 1.2em;
            font-weight: bold;
        }
        .invoice-footer {
            text-align: center;
            font-size: 0.9em;
            color: #777;
            padding-top: 20px;
            border-top: 1px solid #eee;
            margin-top: 30px;
        }
        /* Utility classes for PDF rendering if needed */
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }

        /* Flexbox for header might not work perfectly in all PDF renderers,
           consider tables for layout if issues arise with TCPDF */
        .invoice-header > div {
            width: 48%; /* Approximate for two columns */
        }

    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <div class="company-details">
                <?php if ($company_logo_url): ?>
                    <img src="<?php echo esc_url($company_logo_url); ?>" alt="<?php echo $company_name; ?> Logo">
                <?php endif; ?>
                <h1><?php echo $company_name; ?></h1>
                <p>
                    <?php echo nl2br($company_address); ?><br>
                    <?php echo $company_email; ?><br>
                    <?php echo $company_phone; ?>
                </p>
            </div>
            <div class="invoice-info">
                <h2>INVOICE</h2>
                <p>
                    <strong>Invoice #:</strong> <?php echo esc_html($invoice_number); ?><br>
                    <strong>Date:</strong> <?php echo esc_html($invoice_date); ?><br>
                    <strong>Booking ID:</strong> #<?php echo esc_html($booking->id); ?><br>
                    <strong>Service Date:</strong> <?php echo esc_html($service_date_obj->format(get_option('date_format') . ' ' . get_option('time_format'))); ?>
                </p>
            </div>
        </div>

        <div class="client-details">
            <h3>Bill To:</h3>
            <p>
                <strong><?php echo esc_html($booking->customer_name); ?></strong><br>
                <?php echo nl2br(esc_html($booking->customer_address)); ?><br>
                <?php echo esc_html($booking->zip_code); ?><br>
                <?php echo esc_html($booking->customer_email); ?>
                <?php if (!empty($booking->customer_phone)): ?>
                    <br><?php echo esc_html($booking->customer_phone); ?>
                <?php endif; ?>
            </p>
        </div>

        <table class="booking-details-table">
            <thead>
                <tr>
                    <th>Service / Item</th>
                    <th>Details</th>
                    <th class="text-right">Price</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $services_manager = new \MoBooking\Services\ServicesManager();
                $booking_services_data = $booking->services; // Assuming this is an array of service IDs or objects
                $booking_service_options_data = (array) $booking->service_options; // Assuming this is an array/object

                $calculated_subtotal = 0;

                // This part needs to align with how services and their options are stored in $booking
                // For example, if $booking->services is an array of service IDs:
                $service_items = array();
                if (is_array($booking_services_data)) {
                    foreach ($booking_services_data as $svc_id_or_obj) {
                        $service_obj = null;
                        $service_price = 0;
                        if (is_object($svc_id_or_obj) && isset($svc_id_or_obj->id)) { // If it's already a rich object from get_booking_with_details
                             $service_obj = $svc_id_or_obj;
                             $service_price = isset($service_obj->unit_price) ? floatval($service_obj->unit_price) : (isset($service_obj->price) ? floatval($service_obj->price) : 0);
                        } elseif (is_numeric($svc_id_or_obj)) { // If it's just an ID
                            $service_obj = $services_manager->get_service(absint($svc_id_or_obj));
                            $service_price = $service_obj ? floatval($service_obj->price) : 0;
                        }

                        if ($service_obj) {
                            $item_name = isset($service_obj->service_name) ? $service_obj->service_name : $service_obj->name;
                            $item_description = isset($service_obj->service_description) ? $service_obj->service_description : $service_obj->description;

                            $service_items[] = [
                                'name' => $item_name,
                                'description' => $item_description,
                                'price' => $service_price,
                                'options' => [] // Placeholder for options related to this service
                            ];
                            $calculated_subtotal += $service_price;
                        }
                    }
                }

                // Add selected options to their respective services
                // This logic assumes $booking->service_options contains option details with price impact
                if (!empty($booking_service_options_data)) {
                    foreach ($booking_service_options_data as $opt_data) {
                        if (is_object($opt_data) && isset($opt_data->service_option_id)) {
                             // Find which service this option belongs to (might need service_id on the option_data or a more complex mapping)
                             // For now, let's assume options are listed separately or we improve this mapping later
                            $option_name = isset($opt_data->option_name) ? $opt_data->option_name : 'Option ID ' . $opt_data->service_option_id;
                            $option_value = esc_html($opt_data->option_value);
                            $option_price_impact = isset($opt_data->price_impact) ? floatval($opt_data->price_impact) : 0;

                            // Simple display for now, might need to associate with a service if multiple services
                             $service_items[] = [
                                'name' => $option_name,
                                'description' => 'Value: ' . $option_value,
                                'price' => $option_price_impact,
                                'is_option' => true
                            ];
                            $calculated_subtotal += $option_price_impact;
                        }
                    }
                }


                if (empty($service_items)) : ?>
                    <tr>
                        <td colspan="3">No services listed for this booking.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($service_items as $item) : ?>
                        <tr>
                            <td class="service-name"><?php echo esc_html($item['name']); ?></td>
                            <td>
                                <?php if(!empty($item['description']) && !(isset($item['is_option']) && $item['is_option'])): ?>
                                    <?php echo esc_html($item['description']); ?>
                                <?php elseif (isset($item['is_option']) && $item['is_option']): ?>
                                     <?php echo esc_html($item['description']); ?>
                                <?php endif; ?>
                                <?php if (!empty($item['options'])) : ?>
                                    <div class="service-options">
                                        <ul>
                                            <?php foreach ($item['options'] as $option_line) : ?>
                                                <li><?php echo esc_html($option_line); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="text-right"><?php echo function_exists('wc_price') ? wc_price($item['price']) : esc_html(mobooking_format_price($item['price'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="totals-section">
            <table class="totals-table">
                <tbody>
                    <tr>
                        <td class="label">Subtotal:</td>
                        <td class="amount"><?php echo function_exists('wc_price') ? wc_price($calculated_subtotal) : esc_html(mobooking_format_price($calculated_subtotal)); ?></td>
                    </tr>
                    <?php if (isset($booking->discount_amount) && floatval($booking->discount_amount) > 0): ?>
                        <tr>
                            <td class="label">Discount <?php echo !empty($booking->discount_code) ? '(' . esc_html($booking->discount_code) . ')' : ''; ?>:</td>
                            <td class="amount">-<?php echo function_exists('wc_price') ? wc_price($booking->discount_amount) : esc_html(mobooking_format_price($booking->discount_amount)); ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr class="grand-total">
                        <td class="label">Total:</td>
                        <td class="amount"><?php echo function_exists('wc_price') ? wc_price($booking->total_price) : esc_html(mobooking_format_price($booking->total_price)); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="invoice-footer">
            <p>Thank you for your business!</p>
            <p><?php echo $company_name; ?> - <?php echo home_url(); ?></p>
        </div>
    </div>
</body>
</html>
