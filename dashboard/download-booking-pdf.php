<?php
// dashboard/download-booking-pdf.php - PDF Download Script

// Attempt to load WordPress environment
$wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

if (file_exists($wp_load_path)) {
    require_once($wp_load_path);
} else {
    $wp_load_path_alt = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php';
    if (file_exists($wp_load_path_alt)) {
        require_once($wp_load_path_alt);
    } else {
        $wp_load_path_alt_2 = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/wp-load.php';
        if (file_exists($wp_load_path_alt_2)) {
            require_once($wp_load_path_alt_2);
        } else {
            error_log("MoBooking PDF Download: wp-load.php not found.");
            die('WordPress environment could not be loaded.');
        }
    }
}

if (!function_exists('wp_get_current_user_id') || !function_exists('__')) {
    error_log("MoBooking PDF Download: WordPress core functions not available.");
    die('WordPress core functions are not available.');
}

$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

if (!$booking_id) {
    wp_die(__('Invalid Booking ID provided.', 'mobooking'));
}

// TODO: Implement proper user permission checks.
// Example: if (!current_user_can('view_booking_pdf', $booking_id)) { wp_die(__('You do not have permission to view this PDF.', 'mobooking')); }


// Load TCPDF library
$tcpdf_path = dirname(__FILE__) . '/../lib/tcpdf/tcpdf.php';
if (file_exists($tcpdf_path)) {
    require_once($tcpdf_path);
} else {
    error_log("MoBooking PDF Download: TCPDF library not found at " . $tcpdf_path);
    wp_die(__('TCPDF library not found. Cannot generate PDF.', 'mobooking'));
}

// Check for required classes
if (!class_exists('\MoBooking\Bookings\Manager')) {
    error_log("MoBooking PDF Download: Class \MoBooking\Bookings\Manager not found.");
    wp_die(__('Booking manager class not found.', 'mobooking'));
}
if (!class_exists('\MoBooking\Services\ServicesManager')) {
    error_log("MoBooking PDF Download: Class \MoBooking\Services\ServicesManager not found.");
    wp_die(__('Services manager class not found.', 'mobooking'));
}

$bookings_manager = new \MoBooking\Bookings\Manager();
$booking = $bookings_manager->get_booking($booking_id); // Assuming this also checks user permissions or is admin-side

if (!$booking) {
    wp_die(sprintf(__('Booking with ID %d not found or permission denied.', 'mobooking'), $booking_id));
}

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor(get_bloginfo('name'));
$pdf->SetTitle(__('Booking Details', 'mobooking') . ' - #' . $booking->id);
$pdf->SetSubject(__('Booking Confirmation', 'mobooking'));
$pdf->SetKeywords('Booking, PDF, #' . $booking->id . ', ' . get_bloginfo('name'));

// Set header and footer to false (no default header/footer)
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Set some language-dependent strings (optional)
// Requires $l array to be defined, typically from a TCPDF lang file.
$lang_file_path = dirname(__FILE__).'/../lib/tcpdf/examples/lang/eng.php'; // Adjust path if needed
if (@file_exists($lang_file_path)) {
    require_once($lang_file_path);
    if (isset($l) && is_array($l)) { // Check if $l is defined and is an array
        $pdf->setLanguageArray($l);
    }
}

// Set font
$pdf->SetFont('helvetica', '', 10);

// Add a page
$pdf->AddPage();

// --- HTML Content ---
$html = '<style>';
$html .= 'body { font-family: helvetica, sans-serif; color: #333333; }'; // Base font and color
$html .= 'h1 { font-size: 18pt; margin-bottom: 10px; color: #222222; }';
$html .= 'h2 { font-size: 14pt; margin-bottom: 8px; color: #333333; border-bottom: 1px solid #eeeeee; padding-bottom: 3px;}';
$html .= 'h3 { font-size: 11pt; margin-bottom: 5px; color: #444444; }';
$html .= 'p { margin-bottom: 6px; line-height: 1.4; }';
$html .= 'ul { margin-bottom: 8px; padding-left: 20px; list-style-type: disc; }';
$html .= 'li { margin-bottom: 4px; }';
$html .= 'hr { border: 0; height: 1px; background: #dddddd; margin: 15px 0; }';
$html .= '.meta-info { font-size: 0.8em; color: #555555; text-align: right; margin-top: 15px;}';
$html .= '.price-summary table { width: 100%; border-collapse: collapse; margin-top: 5px; }';
$html .= '.price-summary th, .price-summary td { padding: 6px 8px; border: 1px solid #eeeeee; text-align: right; }';
$html .= '.price-summary th { background-color: #f9f9f9; text-align: left; font-weight: bold; }';
$html .= '.total-row td { font-weight: bold; font-size: 1.1em; background-color: #f0f0f0; }';
$html .= '.section { margin-bottom: 15px; }';
$html .= '.booking-id { font-family: monospace; background-color: #f0f0f0; padding: 2px 4px; border-radius: 3px;}';
$html .= '</style>';

$html .= '<div class="section">';
$html .= '<h1>' . __('Booking Details', 'mobooking') . '</h1>';
$html .= '<p><strong>' . __('Booking ID:', 'mobooking') . '</strong> <span class="booking-id">#' . esc_html($booking->id) . '</span></p>';
$html .= '<p><strong>' . __('Status:', 'mobooking') . '</strong> ' . esc_html(ucfirst($booking->status)) . '</p>';
$html .= '</div><hr>';

$html .= '<div class="section">';
$html .= '<h2>' . __('Customer Information', 'mobooking') . '</h2>';
$html .= '<p><strong>' . __('Name:', 'mobooking') . '</strong> ' . esc_html($booking->customer_name) . '</p>';
$html .= '<p><strong>' . __('Email:', 'mobooking') . '</strong> ' . esc_html($booking->customer_email) . '</p>';
if (!empty($booking->customer_phone)) {
    $html .= '<p><strong>' . __('Phone:', 'mobooking') . '</strong> ' . esc_html($booking->customer_phone) . '</p>';
}
if (!empty($booking->customer_address)) {
    $html .= '<p><strong>' . __('Address:', 'mobooking') . '</strong> ' . nl2br(esc_html($booking->customer_address)) . '</p>';
}
if (!empty($booking->zip_code)) {
    $html .= '<p><strong>' . __('ZIP Code:', 'mobooking') . '</strong> ' . esc_html($booking->zip_code) . '</p>';
}
$html .= '</div><hr>';

$html .= '<div class="section">';
$html .= '<h2>' . __('Service Details', 'mobooking') . '</h2>';
if (!empty($booking->service_date)) {
    try {
        // Ensure the date is treated as UTC or the site's timezone, then display appropriately
        $service_date = new DateTime($booking->service_date, new DateTimeZone(get_option('timezone_string', 'UTC')));
        $html .= '<p><strong>' . __('Date:', 'mobooking') . '</strong> ' . esc_html($service_date->format(get_option('date_format', 'F j, Y'))) . '</p>';
        $html .= '<p><strong>' . __('Time:', 'mobooking') . '</strong> ' . esc_html($service_date->format(get_option('time_format', 'g:i A'))) . ' (' . esc_html($service_date->format('T')) . ')</p>';
    } catch (Exception $e) {
        $html .= '<p><strong>' . __('Date/Time:', 'mobooking') . '</strong> ' . esc_html($booking->service_date) . ' (' . __('Could not parse date', 'mobooking') . ')</p>';
        error_log("MoBooking PDF: Error parsing service_date '{$booking->service_date}' for booking {$booking->id}: " . $e->getMessage());
    }
} else {
     $html .= '<p>' . __('Service date not specified.', 'mobooking') . '</p>';
}

if (!empty($booking->services) && (is_array($booking->services) || is_object($booking->services))) {
    $services_manager = new \MoBooking\Services\ServicesManager();
    $html .= '<h3>' . __('Services Booked:', 'mobooking') . '</h3><ul>';
    foreach ($booking->services as $service_id_or_obj) {
        // Handle cases where $service_id_or_obj might be just an ID or a full service object from the booking data
        $s_id = is_object($service_id_or_obj) && property_exists($service_id_or_obj, 'id') ? $service_id_or_obj->id : intval($service_id_or_obj);
        $service = $services_manager->get_service($s_id); // Assumes get_service can fetch by ID

        if ($service) {
            $price_display = property_exists($service, 'price') ? (function_exists('wc_price') ? wc_price($service->price) : esc_html(number_format(floatval($service->price), 2))) : __('N/A', 'mobooking');
            $html .= '<li>' . esc_html($service->name) . ' (' . $price_display . ')</li>';
        } else {
            $html .= '<li>' . sprintf(__('Service ID %s not found or could not be loaded.', 'mobooking'), esc_html($s_id)) . '</li>';
        }
    }
    $html .= '</ul>';
} else {
    $html .= '<p>' . __('No services listed for this booking.', 'mobooking') . '</p>';
}
$html .= '</div><hr>';

$html .= '<div class="section price-summary">';
$html .= '<h2>' . __('Pricing Summary', 'mobooking') . '</h2>';
$html .= '<table>';
$subtotal = 0;
// Ensure properties exist before trying to use them.
$total_price = property_exists($booking, 'total_price') ? floatval($booking->total_price) : 0;
$discount_amount = property_exists($booking, 'discount_amount') ? floatval($booking->discount_amount) : 0;
$subtotal = $total_price + $discount_amount;

if (!property_exists($booking, 'total_price') || !property_exists($booking, 'discount_amount')) {
    error_log("MoBooking PDF: total_price or discount_amount missing on booking object ID {$booking->id}. Values assumed zero for PDF.");
}

$html .= '<tr><th>' . __('Subtotal:', 'mobooking') . '</th><td>' . (function_exists('wc_price') ? wc_price($subtotal) : esc_html(number_format($subtotal, 2))) . '</td></tr>';
if ($discount_amount > 0) {
    $html .= '<tr><th>' . __('Discount:', 'mobooking');
    if (!empty($booking->discount_code)) {
        $html .= ' (' . esc_html($booking->discount_code) . ')';
    }
    $html .= '</th><td>-' . (function_exists('wc_price') ? wc_price($discount_amount) : esc_html(number_format($discount_amount, 2))) . '</td></tr>';
}
$html .= '<tr class="total-row"><th>' . __('Total:', 'mobooking') . '</th><td>' . (function_exists('wc_price') ? wc_price($total_price) : esc_html(number_format($total_price, 2))) . '</td></tr>';
$html .= '</table></div><hr>';

if (!empty($booking->notes)) {
    $html .= '<div class="section">';
    $html .= '<h2>' . __('Special Instructions', 'mobooking') . '</h2>';
    $html .= '<p>' . nl2br(esc_html($booking->notes)) . '</p>';
    $html .= '</div><hr>';
}

$html .= '<div class="meta-info">';
if (!empty($booking->created_at)) {
    try {
        $created_date = new DateTime($booking->created_at, new DateTimeZone(get_option('timezone_string', 'UTC')));
        $html .= __('Booking Created:', 'mobooking') . ' ' . esc_html($created_date->format(get_option('date_format', 'M j, Y') . ' ' . get_option('time_format', 'g:i A'))) . '<br>';
    } catch (Exception $e) {
        $html .= __('Booking Created:', 'mobooking') . ' ' . esc_html($booking->created_at) . ' (' . __('Could not parse date', 'mobooking') . ')<br>';
        error_log("MoBooking PDF: Error parsing created_at '{$booking->created_at}' for booking {$booking->id}: " . $e->getMessage());
    }
}
if (!empty($booking->updated_at) && $booking->updated_at !== $booking->created_at) {
     try {
        $updated_date = new DateTime($booking->updated_at, new DateTimeZone(get_option('timezone_string', 'UTC')));
        $html .= __('Last Updated:', 'mobooking') . ' ' . esc_html($updated_date->format(get_option('date_format', 'M j, Y') . ' ' . get_option('time_format', 'g:i A'))) . '<br>';
    } catch (Exception $e) {
        $html .= __('Last Updated:', 'mobooking') . ' ' . esc_html($booking->updated_at) . ' (' . __('Could not parse date', 'mobooking') . ')<br>';
        error_log("MoBooking PDF: Error parsing updated_at '{$booking->updated_at}' for booking {$booking->id}: " . $e->getMessage());
    }
}
$html .= __('PDF Generated On:', 'mobooking') . ' ' . esc_html(date_i18n(get_option('date_format', 'M j, Y') . ' ' . get_option('time_format', 'g:i A'), current_time('timestamp'))) . '<br>';
$html .= '</div>';

// Write HTML content
$pdf->writeHTML($html, true, false, true, false, '');

// Clean any output buffering
if (ob_get_length()) {
    ob_end_clean();
}

// Close and output PDF document
// D: force download
$pdf_filename = apply_filters('mobooking_pdf_filename', 'booking_' . $booking->id . '_' . date('Ymd') . '.pdf', $booking);
$pdf->Output($pdf_filename, 'D');
exit;
?>
