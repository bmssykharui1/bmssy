<?php
// Start an output buffer to catch stray spaces or warnings
ob_start();

// Turn off on-screen errors for PDF generation to prevent header corruption
error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('Asia/Kolkata');
session_start();
if (!isset($_SESSION["user_id"])) {
    die("Unauthorized Access.");
}

require_once '../../database/index.php'; 

// Require FPDF from the fpdf/ folder
require('fpdf/fpdf.php');

// Get parameters
$date_type = isset($_GET['date_type']) && $_GET['date_type'] === 'created_at' ? 'created_at' : 'date_of_collection';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

if (empty($from_date) || empty($to_date)) {
    die("Please select both From and To dates.");
}

// Adjust to_date for created_at to include the full day
$query_to_date = $date_type === 'created_at' ? $to_date . ' 23:59:59' : $to_date;

// Fetch Data
$stmt = $conn->prepare("SELECT * FROM form4_entries WHERE $date_type BETWEEN ? AND ? ORDER BY $date_type ASC");
$stmt->bind_param("ss", $from_date, $query_to_date);
$stmt->execute();
$result = $stmt->get_result();

// Initialize PDF 
// P = Portrait, mm = millimeters, A4 = Page Size
$pdf = new FPDF('P', 'mm', 'A4');
// Disable AutoPageBreak because we will manage it manually to insert Page Totals
$pdf->SetAutoPageBreak(false); 

// Column widths for Portrait A4 (Total width = 190mm)
// 8 + 22 + 46 + 14 + 14 + 38 + 26 + 22 = 190
$w = array(8, 22, 46, 14, 14, 38, 26, 22); 

// Helper function to draw headers on every new page
function drawHeaders($pdf, $w) {
    // 1. Main Title
    $pdf->SetFont('Arial', 'B', 14); 
    $pdf->Cell(0, 8, "West Bengal Building & Others Construction Worker's Welfare Fund", 0, 1, 'C');

    // 2. Sub Title 1
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 6, "Registration/Subscription", 0, 1, 'C');

    // 3. Sub Title 2 (Underline Removed)
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 6, "Particulars of Benificiary Worker's", 0, 1, 'C');

    $pdf->Ln(4); // Space before table

    // TABLE HEADERS
    $pdf->SetFont('Arial', 'B', 7.5); 
    $pdf->SetFillColor(230, 230, 230);
    $pdf->Cell($w[0], 8, 'SL NO', 1, 0, 'C', true);
    $pdf->Cell($w[1], 8, 'Reg. No', 1, 0, 'C', true);
    $pdf->Cell($w[2], 8, 'Name of Benificiary', 1, 0, 'C', true);
    $pdf->Cell($w[3], 8, 'Book No', 1, 0, 'C', true);
    $pdf->Cell($w[4], 8, 'Receipt', 1, 0, 'C', true);
    $pdf->Cell($w[5], 8, 'For the Month Of', 1, 0, 'C', true);
    $pdf->Cell($w[6], 8, 'Date of Collection', 1, 0, 'C', true);
    $pdf->Cell($w[7], 8, 'Amount', 1, 1, 'C', true);
}

// Draw headers for the first page
$pdf->AddPage();
drawHeaders($pdf, $w);

// ==========================================
// TABLE DATA
// ==========================================
$pdf->SetFont('Arial', '', 7.5); 
$sl_no = 1;
$row_height = 6; 
$page_subtotal = 0;
$grand_total = 0;

while ($row = $result->fetch_assoc()) {
    // Check if we are near the bottom of the page (275mm limit)
    // If yes, print the PAGE TOTAL, add a new page, and redraw headers
    if ($pdf->GetY() + $row_height > 275) {
        // Print Page Subtotal
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetFillColor(245, 245, 245);
        $pdf->Cell(array_sum(array_slice($w, 0, 7)), 8, 'TOTAL:', 1, 0, 'R', true);
        $pdf->Cell($w[7], 8, number_format($page_subtotal, 2), 1, 1, 'R', true);
        
        // Reset Page Subtotal and create new page
        $page_subtotal = 0;
        $pdf->AddPage();
        drawHeaders($pdf, $w);
        $pdf->SetFont('Arial', '', 7.5);
    }

    // 1. Format Date of Collection to DD/MM/YYYY
    $formatted_date = date('d/m/Y', strtotime($row['date_of_collection']));
    
    // 2. Format "For the Month Of" to DD/MM/YYYY - DD/MM/YYYY
    $month_parts = explode(' - ', $row['for_month']);
    if (count($month_parts) == 2) {
        $formatted_for_month = date('d/m/Y', strtotime($month_parts[0])) . ' - ' . date('d/m/Y', strtotime($month_parts[1]));
    } else {
        // Fallback just in case
        $formatted_for_month = date('d/m/Y', strtotime($row['for_month']));
    }

    $amount = number_format($row['amount'], 2);
    $page_subtotal += $row['amount'];
    $grand_total += $row['amount'];

    // Convert the name safely
    $encoded_name = mb_convert_encoding($row['beneficiary_name'], 'ISO-8859-1', 'UTF-8');

    // Truncate name slightly if it's too long so it doesn't break the layout
    if($pdf->GetStringWidth($encoded_name) > ($w[2]-2)) {
        while($pdf->GetStringWidth($encoded_name . '...') > ($w[2]-2)) {
            $encoded_name = substr($encoded_name, 0, -1);
        }
        $encoded_name .= '...';
    }

    // Print Row
    $pdf->Cell($w[0], $row_height, $sl_no, 1, 0, 'C');
    $pdf->Cell($w[1], $row_height, $row['reg_no'], 1, 0, 'C');
    $pdf->Cell($w[2], $row_height, $encoded_name, 1, 0, 'L');
    $pdf->Cell($w[3], $row_height, $row['book_no'], 1, 0, 'C');
    $pdf->Cell($w[4], $row_height, $row['receipt_no'], 1, 0, 'C');
    $pdf->Cell($w[5], $row_height, $formatted_for_month, 1, 0, 'C');
    $pdf->Cell($w[6], $row_height, $formatted_date, 1, 0, 'C');
    $pdf->Cell($w[7], $row_height, $amount, 1, 1, 'R');
    
    $sl_no++;
}

// If no data
if ($sl_no === 1) {
    $pdf->Cell(array_sum($w), 10, 'No records found for the selected date range.', 1, 1, 'C');
} else {
    // Print Final PAGE TOTAL for the last page
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetFillColor(245, 245, 245);
    $pdf->Cell(array_sum(array_slice($w, 0, 7)), 8, 'TOTAL:', 1, 0, 'R', true);
    $pdf->Cell($w[7], 8, number_format($page_subtotal, 2), 1, 1, 'R', true);
}

// Clean the output buffer to ensure absolutely NO whitespace or errors 
// are sent to the browser before the PDF renders.
ob_end_clean();

// Output the PDF to the browser
$pdf->Output('I', 'Form4_Export_' . date('Ymd_His') . '.pdf');
?>