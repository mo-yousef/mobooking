<?php
// includes/pdf-helper.php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Ensure TCPDF is available. Path might need adjustment.
// This assumes TCPDF's main class file is tcpdf.php directly under lib/tcpdf/
if (file_exists(ABSPATH . 'wp-content/plugins/mobooking/lib/tcpdf/tcpdf.php')) {
    // require_once(ABSPATH . 'wp-content/plugins/mobooking/lib/tcpdf/tcpdf.php');
    // For now, we will not require the file as it's a placeholder.
    // In a real scenario with actual TCPDF library, uncomment the line above.
} else {
    // Log error or handle missing library
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('MoBooking Error: TCPDF library not found.');
    }
    // Optionally, you could fall back to a simpler, non-PDF invoice or show an error.
}

/**
 * Generates PDF content for an invoice.
 *
 * @param object $booking Booking object.
 * @param array $company_settings Company settings.
 * @param string $invoice_template_path Path to the HTML invoice template.
 * @return string|false PDF content as a string, or false on failure.
 */
function mobooking_generate_invoice_pdf_output($booking, $company_settings, $invoice_template_path) {
    if (!class_exists('TCPDF')) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking Error: TCPDF class does not exist. Cannot generate PDF.');
        }
        // Try to include the placeholder again, in case it wasn't loaded.
        // In a real scenario, this would be the actual TCPDF include.
        if (file_exists(ABSPATH . 'wp-content/plugins/mobooking/lib/tcpdf/tcpdf.php')) {
            // require_once(ABSPATH . 'wp-content/plugins/mobooking/lib/tcpdf/tcpdf.php');
        } else {
            return false; // TCPDF library is essential.
        }
        // If after attempting to include, class still doesn't exist, then fail.
        if (!class_exists('TCPDF')) {
             if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking Error: TCPDF class still not found after include attempt.');
            }
            return false;
        }
    }

    // Get HTML content from the template
    ob_start();
    if (file_exists($invoice_template_path)) {
        // Make booking and company settings available to the template
        $current_booking = $booking;
        $current_company_settings = $company_settings;
        include $invoice_template_path;
    } else {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking Error: Invoice template file not found at ' . $invoice_template_path);
        }
        ob_end_clean();
        return false;
    }
    $html_content = ob_get_clean();

    if (empty($html_content)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking Error: Invoice template is empty or failed to load.');
        }
        return false;
    }

    // Create new PDF document
    // Note: In a real scenario, TCPDF would be properly instantiated and used here.
    // Since tcpdf.php is a placeholder, these lines will cause errors if not guarded.
    // For now, we'll simulate PDF generation by returning the HTML content
    // wrapped with a simple message.

    // $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    // $pdf->SetCreator(PDF_CREATOR);
    // $pdf->SetAuthor($company_settings['company_name'] ?? 'MoBooking');
    // $pdf->SetTitle('Invoice #' . $booking->id);
    // $pdf->SetSubject('Invoice for Booking #' . $booking->id);
    // $pdf->setPrintHeader(false);
    // $pdf->setPrintFooter(false);
    // $pdf->AddPage();
    // $pdf->writeHTML($html_content, true, false, true, false, '');
    // return $pdf->Output('invoice_' . $booking->id . '.pdf', 'S'); // 'S' returns as string

    // Placeholder for PDF generation:
    $simulated_pdf_content = "--- SIMULATED PDF CONTENT ---" . PHP_EOL;
    $simulated_pdf_content .= "Invoice for Booking ID: " . ($booking->id ?? 'N/A') . PHP_EOL;
    $simulated_pdf_content .= "Company: " . ($company_settings['company_name'] ?? 'N/A') . PHP_EOL;
    $simulated_pdf_content .= "--- HTML CONTENT START ---" . PHP_EOL;
    $simulated_pdf_content .= $html_content;
    $simulated_pdf_content .= "--- HTML CONTENT END ---" . PHP_EOL;

    return $simulated_pdf_content; // Returning the HTML content for now.
}

?>
