<?php
// --- 1. Error Reporting Configuration ---
error_reporting(E_ALL);
ini_set('display_errors', 0); // Hide errors from users
ini_set('log_errors', 1);     // Log them locally as a fallback

// --- 2. Security Enhancements & Session Management ---
session_start();

// Basic HTTP Security Headers
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

// Authentication Check
if (!isset($_SESSION["user_id"])) {
    header("Location: /login.php");
    exit();
}

// Session Fixation Protection
if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
} else if (time() - $_SESSION['CREATED'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['CREATED'] = time();
}

$user_name = isset($_SESSION["user_name"]) ? $_SESSION["user_name"] : "Admin User";
date_default_timezone_set("Asia/Kolkata");

// --- 3. Database Connection ---
require_once '../../database/index.php';

if (!isset($conn)) {
    die("Critical Error: Database connection (\$conn) is missing. Check your database/index.php file.");
}

// --- 4. Auto Bug Detection & Database Logging (MySQLi Version) ---
try {
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS system_bug_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            error_level VARCHAR(50),
            error_message TEXT,
            file_name VARCHAR(255),
            line_number INT,
            request_url VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $conn->query($createTableSQL);

    function customErrorHandler($errno, $errstr, $errfile, $errline) {
        global $conn;
        if ($conn) {
            $url = $_SERVER['REQUEST_URI'] ?? 'Unknown';
            $level = "PHP Error [$errno]";
            $stmt = $conn->prepare("INSERT INTO system_bug_logs (error_level, error_message, file_name, line_number, request_url) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sssis", $level, $errstr, $errfile, $errline, $url);
                $stmt->execute();
            }
        }
        return true; 
    }
    set_error_handler("customErrorHandler");

    function customExceptionHandler($exception) {
        global $conn;
        if ($conn) {
            $url = $_SERVER['REQUEST_URI'] ?? 'Unknown';
            $level = "PHP Exception";
            $msg = $exception->getMessage();
            $file = $exception->getFile();
            $line = $exception->getLine();
            
            $stmt = $conn->prepare("INSERT INTO system_bug_logs (error_level, error_message, file_name, line_number, request_url) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sssis", $level, $msg, $file, $line, $url);
                $stmt->execute();
            }
        }
    }
    set_exception_handler("customExceptionHandler");

    function customFatalErrorHandler() {
        global $conn;
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            if ($conn) {
                $url = $_SERVER['REQUEST_URI'] ?? 'Unknown';
                $level = "PHP Fatal Error [" . $error['type'] . "]";
                $stmt = $conn->prepare("INSERT INTO system_bug_logs (error_level, error_message, file_name, line_number, request_url) VALUES (?, ?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("sssis", $level, $error['message'], $error['file'], $error['line'], $url);
                    $stmt->execute();
                }
            }
        }
    }
    register_shutdown_function("customFatalErrorHandler");

} catch (Exception $e) {
    error_log("Bug Logger Setup failed: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#ffffff">
    <title>BMSSY SERVICE | PF Update List</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>

    <style>
        /* =========================================
           Material Design 3 Core Variables
           ========================================= */
        :root {
            --md-sys-color-background: #f6f8fa;
            --md-sys-color-surface: #ffffff;
            --md-sys-color-primary: #0b57d0;
            --md-sys-color-on-primary: #ffffff;
            --md-sys-color-primary-container: #d3e3fd;
            --md-sys-color-on-surface: #1f1f1f;
            --md-sys-color-on-surface-variant: #44474e;
            --md-sys-color-outline: #74777f;
            --app-radius-lg: 24px;
            --app-radius-md: 12px;
            --sidebar-width: 280px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: var(--md-sys-color-background); color: var(--md-sys-color-on-surface); }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: rgba(0, 0, 0, 0.2); border-radius: 4px; }

        /* Scaffold */
        .app-scaffold { display: flex; height: 100vh; width: 100vw; overflow: hidden; }
        .sidebar-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 90; opacity: 0; visibility: hidden; transition: 0.3s; }
        .sidebar-overlay.active { opacity: 1; visibility: visible; }
        .app-main { flex: 1; display: flex; flex-direction: column; height: 100%; margin-left: var(--sidebar-width); transition: 0.3s; }

        /* Topbar */
        .app-topbar { height: 72px; padding: 0 24px; display: flex; align-items: center; justify-content: space-between; background: var(--md-sys-color-surface); z-index: 50; box-shadow: 0 1px 2px rgba(0,0,0,0.02); }
        .topbar-left { display: flex; align-items: center; gap: 16px; }
        .page-title { font-size: 20px; font-weight: 600; }
        .menu-btn { background: none; border: none; width: 40px; height: 40px; border-radius: 50%; display: none; align-items: center; justify-content: center; cursor: pointer; color: var(--md-sys-color-on-surface-variant); }
        .user-profile { display: flex; align-items: center; gap: 12px; }
        .user-info { text-align: right; }
        .user-name { font-size: 14px; font-weight: 600; }
        .user-role { font-size: 12px; color: var(--md-sys-color-on-surface-variant); }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--md-sys-color-primary-container); color: var(--md-sys-color-primary); display: flex; align-items: center; justify-content: center; font-weight: 700; }

        /* Content */
        .content-scroll { flex: 1; overflow-y: auto; padding: 24px; }
        .md-card { background: var(--md-sys-color-surface); border-radius: var(--app-radius-lg); padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); margin-bottom: 24px; }
        .card-header { font-size: 18px; font-weight: 700; margin-bottom: 20px; color: var(--md-sys-color-primary); display: flex; align-items: center; justify-content: space-between; }
        
        /* Filter Section */
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 16px; align-items: end; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-label { font-size: 13px; font-weight: 600; color: var(--md-sys-color-on-surface-variant); }
        .md-input { border: 2px solid var(--md-sys-color-background); border-radius: 8px; padding: 10px 14px; outline: none; width: 100%; transition: 0.2s; font-size: 14px; background: var(--md-sys-color-background); }
        .md-input:focus { border-color: var(--md-sys-color-primary); background: var(--md-sys-color-surface); }
        
        .action-btn { background: var(--md-sys-color-primary); color: white; border: none; padding: 10px 20px; border-radius: 100px; font-weight: 600; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-size: 14px; height: 42px; }
        .action-btn:hover { opacity: 0.9; transform: scale(0.98); }
        .action-btn.secondary { background: var(--md-sys-color-primary-container); color: var(--md-sys-color-primary); }
        .action-btn.secondary:hover { background: #c2e7ff; }

        /* Custom DataTables Styling */
        table.dataTable { border-collapse: collapse !important; width: 100% !important; margin-top: 16px !important; }
        table.dataTable thead th { background: var(--md-sys-color-background); font-weight: 600; color: var(--md-sys-color-on-surface-variant); text-transform: uppercase; font-size: 12px; border-bottom: none !important; padding: 16px; }
        table.dataTable tbody td { padding: 16px; border-bottom: 1px solid var(--md-sys-color-background); vertical-align: middle; font-size: 14px; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { border-radius: 100px !important; border: none !important; background: transparent !important; color: var(--md-sys-color-on-surface) !important; margin: 0 4px; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: var(--md-sys-color-primary-container) !important; color: var(--md-sys-color-primary) !important; font-weight: 700; }
        .dataTables_wrapper .dataTables_filter input { border: 2px solid var(--md-sys-color-background); border-radius: 8px; padding: 8px 16px; outline: none; transition: 0.2s; margin-left: 8px; }
        .dataTables_wrapper .dataTables_filter input:focus { border-color: var(--md-sys-color-primary); }

        /* Native MD3 Modal for PDF Preview */
        .md-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; display: none; align-items: center; justify-content: center; backdrop-filter: blur(2px); }
        .md-modal-overlay.show { display: flex; }
        .md-modal-large { background: var(--md-sys-color-surface); width: 95%; max-width: 1000px; height: 90vh; border-radius: var(--app-radius-lg); display: flex; flex-direction: column; box-shadow: 0 10px 30px rgba(0,0,0,0.2); animation: popIn 0.3s cubic-bezier(0.2,0,0,1); overflow: hidden; }
        @keyframes popIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .md-modal-header { padding: 20px 24px; border-bottom: 1px solid var(--md-sys-color-background); display: flex; justify-content: space-between; align-items: center; font-size: 18px; font-weight: 700; color: var(--md-sys-color-primary); }
        .md-modal-body { flex: 1; overflow-y: auto; padding: 24px; background: #f9f9f9; }
        .md-modal-footer { padding: 16px 24px; border-top: 1px solid var(--md-sys-color-background); display: flex; justify-content: flex-end; gap: 12px; }
        
        .close-btn { background: none; border: none; font-size: 20px; cursor: pointer; color: var(--md-sys-color-on-surface-variant); transition: color 0.2s; }
        .close-btn:hover { color: var(--color-error); }
        
        /* Preview Table Styles */
        .preview-table { width: 100%; border-collapse: collapse; font-size: 12px; background: white; border: 1px solid #000; }
        .preview-table th, .preview-table td { border: 1px solid #000; padding: 6px; text-align: center; }
        .preview-table th { background: #e0e0e0; font-weight: bold; }

        @media (max-width: 1024px) {
            .app-main { margin-left: 0; }
            .menu-btn { display: flex; }
        }
    </style>
</head>
<body>

    <div class="app-scaffold">
        
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <script>window.PAGE_IDENTIFIER = '/list/pfupdate';</script>
        <?php include('../../inc/sideber.php'); ?>

        <main class="app-main">
            
            <header class="app-topbar">
                <div class="topbar-left">
                    <button class="menu-btn" id="menuBtn">
                        <svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
                    </button>
                    <div>
                        <h1 class="page-title">PF Update List</h1>
                        <p style="font-size: 12px; color: var(--md-sys-color-on-surface-variant);" id="currentMonthYear">Loading date...</p>
                    </div>
                </div>
                <div class="user-profile">
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="user-role">Administrator</div>
                    </div>
                    <div class="user-avatar"><?php echo htmlspecialchars(strtoupper(substr($user_name, 0, 1)), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </header>

            <div class="content-scroll">
                
                <div class="md-card">
                    <div class="card-header">
                        <span><i class="fas fa-filter"></i> Filter Records</span>
                        <button onclick="showPdfPreview()" class="action-btn secondary">
                            <i class="fas fa-file-pdf"></i> Preview PDF
                        </button>
                    </div>

                    <form method="GET" id="filterForm">
                        <div class="filter-grid">
                            <div class="form-group">
                                <label class="form-label">Period From</label>
                                <input type="date" name="period_form" id="period_form" class="md-input" value="<?php echo htmlspecialchars($_GET['period_form'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Period To</label>
                                <input type="date" name="period_to" id="period_to" class="md-input" value="<?php echo htmlspecialchars($_GET['period_to'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Type</label>
                                <select name="filter_type" id="filter_type" class="md-input">
                                    <option value="" <?php echo empty($_GET['filter_type']) ? 'selected' : ''; ?>>All</option>
                                    <option value="142" <?php echo (isset($_GET['filter_type']) && $_GET['filter_type'] == '142') ? 'selected' : ''; ?>>Others (142)</option>
                                    <option value="242" <?php echo (isset($_GET['filter_type']) && $_GET['filter_type'] == '242') ? 'selected' : ''; ?>>Construction (242)</option>
                                </select>
                            </div>
                            <button type="submit" class="action-btn" style="width: 100%;">
                                <i class="fas fa-search"></i> Apply Filter
                            </button>
                        </div>
                    </form>
                </div>

                <div class="md-card" style="<?php echo (isset($_GET['period_form']) || isset($_GET['period_to']) || isset($_GET['filter_type'])) ? '' : 'display:none;'; ?>">
                    <div class="card-header">
                        <span><i class="fas fa-list"></i> Filtered Results</span>
                    </div>
                     <table id="example1" class="display nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>SSIN</th>
                                <th>Date of 60</th>
                                <th>Period From</th>
                                <th>Period To</th>
                                <th>Update Date</th>
                            </tr>
                        </thead>
                        <tbody id="beneficiaryTable">
                            <?php
                            // Load data based on filters
                            if (isset($_GET['period_form']) || isset($_GET['period_to']) || isset($_GET['filter_type'])) {
                                // Important: We assume 'fetch_beneficiaries.php' checks these GET params and outputs <tr> elements
                                include 'fetch_beneficiaries.php';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </main>
    </div>

    <div id="pdfPopup" class="md-modal-overlay">
        <div class="md-modal-large">
            <div class="md-modal-header">
                <span><i class="fas fa-file-invoice"></i> PDF Statement Preview</span>
                <button onclick="closePopup()" class="close-btn"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="md-modal-body">
                <div style="text-align: center; margin-bottom: 20px;">
                    <p style="font-size: 16px; font-weight: bold; margin-bottom: 4px;">Statement of recording Government Grant (Construction/ Transport/ Others)</p>
                    <p style="font-size: 14px;">Code of LWFC: 4207112, Quarter: __________, Year: 2023 – 2024.</p>
                </div>

                <table id="example11" class="preview-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>SSIN</th>
                            <th>Date of Attaining 60</th>
                            <th>Period From</th>
                            <th>Period To</th>
                            <th>Update Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (isset($_GET['period_form']) || isset($_GET['period_to']) || isset($_GET['filter_type'])) {
                            include 'fetch_beneficiaries.php';
                        }
                        ?>
                    </tbody>
                </table>

                <div style="margin-top: 40px; font-size: 12px;">
                    <p style="color: #666; margin-bottom: 20px;">*Strike out whichever is not applicable. Certified that I have made all the relevant entries...</p>
                    <div style="display: flex; justify-content: flex-end;">
                        <div style="text-align: center; font-weight: bold;">
                            _________________________ <br><br>
                            Signature of the CA/SLO<br>
                            Name: MAMATA JANA<br>
                            Code No.: 4207112
                        </div>
                    </div>
                </div>
            </div>

            <div class="md-modal-footer">
                <button onclick="closePopup()" class="btn-text">Close</button>
                <button onclick="generatePDF()" class="action-btn">
                    <i class="fas fa-download"></i> Download PDF
                </button>
            </div>
        </div>
    </div>

    <script>
        // --- Sidebar Logic ---
        const menuBtn = document.getElementById('menuBtn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        function toggleSidebar() {
            if(sidebar && overlay) {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('active');
            }
        }
        if(menuBtn) menuBtn.addEventListener('click', toggleSidebar);
        if(overlay) overlay.addEventListener('click', toggleSidebar);
        
        document.addEventListener("DOMContentLoaded", () => {
            const currentDashLink = document.querySelector(`a[href="${window.PAGE_IDENTIFIER}"]`);
            if (currentDashLink) {
                currentDashLink.classList.add('active');
                const submenuWrapper = currentDashLink.closest('.sb-submenu-wrapper');
                if (submenuWrapper) {
                    submenuWrapper.classList.add('open');
                    const parentBtn = submenuWrapper.previousElementSibling;
                    if (parentBtn) {
                        parentBtn.setAttribute('aria-expanded', 'true');
                        parentBtn.classList.add('active'); 
                    }
                }
            }
        });

        // --- Date Init Logic ---
        document.addEventListener("DOMContentLoaded", function () {
            const date = new Date();
            document.getElementById('currentMonthYear').textContent = date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            
            function getLocalDate(year, month, day) {
                let d = new Date(year, month, day);
                d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
                return d.toISOString().split('T')[0];
            }
            let today = new Date();
            if(!document.getElementById('period_form').value) {
                document.getElementById('period_form').value = getLocalDate(today.getFullYear(), today.getMonth(), 1);
            }
            if(!document.getElementById('period_to').value) {
                document.getElementById('period_to').value = getLocalDate(today.getFullYear(), today.getMonth() + 1, 0);
            }
        });

        // --- DataTable Init ---
        $(function () {
            if ($('#example1 tbody tr').length > 0) {
                $("#example1").DataTable({
                    responsive: true,
                    lengthChange: false,
                    autoWidth: false,
                    language: { search: "", searchPlaceholder: "Search records..." }
                });
            }
        });

        // --- Modal Logic ---
        function showPdfPreview() {
            document.getElementById('pdfPopup').classList.add('show');
        }
        function closePopup() {
            document.getElementById('pdfPopup').classList.remove('show');
        }

        // ============================================
        // jsPDF Generation Logic (Adapted to your specific table structure)
        // ============================================
        function generatePDF() {
            // Check if there's actually data to print
            if ($('#example11 tbody tr').length === 0 || $('#example11 tbody tr td').length === 1) {
                Swal.fire('No Data', 'There is no data to generate a PDF for.', 'warning');
                return;
            }

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({
                orientation: "portrait",
                unit: "mm",
                format: "a4"
            });

            const pageWidth = doc.internal.pageSize.getWidth();
            const pageHeight = doc.internal.pageSize.getHeight();

            const headers = [
                [
                    { content: "SL NO", rowSpan: 2, styles: { halign: 'center', valign: 'middle' } },
                    { content: "Name of the beneficiary", rowSpan: 2, styles: { halign: 'center', valign: 'middle' } },
                    { content: "Date of attaining 60 years of age", rowSpan: 2, styles: { halign: 'center', valign: 'middle' } },
                    { content: "Approved SSIN", rowSpan: 2, styles: { halign: 'center', valign: 'middle' } },
                    { content: "Month of the Government Grant", colSpan: 2, styles: { halign: 'center', valign: 'middle' } },
                    { content: "Date of entry in the Pass Book", rowSpan: 2, styles: { halign: 'center', valign: 'middle' } }
                ],
                [
                    { content: "PERIOD FROM", styles: { halign: 'center', valign: 'middle' } },
                    { content: "PERIOD TO", styles: { halign: 'center', valign: 'middle' } }
                ]
            ];

            const data = [];
            const uniqueSet = new Set();
            let rowIndex = 1;
            let minYear = 9999;
            let maxYear = 0;

            // Scrape #example11 (The hidden modal table)
            $('#example11 tbody tr').each(function () {
                const cells = $(this).find('td');
                if (cells.length > 0) {
                    const name = $(cells[0]).text().trim();
                    const ssin = $(cells[1]).text().trim();
                    const dob = $(cells[2]).text().trim();
                    const fromDate = $(cells[3]).text().trim();
                    const toDate = $(cells[4]).text().trim();
                    const updated = $(cells[5]).text().trim();

                    if(fromDate && toDate) {
                        const fromYearParts = fromDate.split("-");
                        const toYearParts = toDate.split("-");
                        
                        if(fromYearParts.length === 3) {
                            const fYear = parseInt(fromYearParts[2]);
                            if (!isNaN(fYear) && fYear < minYear) minYear = fYear;
                        }
                        if(toYearParts.length === 3) {
                            const tYear = parseInt(toYearParts[2]);
                            if (!isNaN(tYear) && tYear > maxYear) maxYear = tYear;
                        }
                    }

                    const row = [name, dob, ssin, fromDate, toDate, updated];
                    const rowKey = row.join('|');
                    if (!uniqueSet.has(rowKey)) {
                        uniqueSet.add(rowKey);
                        data.push([rowIndex.toString(), ...row]);
                        rowIndex++;
                    }
                }
            });

            if(minYear === 9999) minYear = "____";
            if(maxYear === 0) maxYear = "____";

            const title1 = "Statement of recording Government Grant (Construction/ Transport/ Others)";
            const title2 = `Code of LWFC: 4207112, Quarter: __________, Year: ${minYear} – ${maxYear}.`;
            const footerText = "*Strike out whichever is not applicable. Certified that I have made all the relevant entries up to the quarter ending on___________________In the Passbook of the beneficiaries and all the data recorded in the above statement is in consonance with the entries made by me.";
            const signatureText = "_________________________\nSignature of the CA/SLO\nName: MAMATA JANA\nCode No.: 4207112";

            function addFooter(yPosition) {
                const footerY = yPosition + 10;
                doc.setFont("helvetica", "normal");
                doc.setFontSize(10);
                const wrappedFooter = doc.splitTextToSize(footerText, pageWidth - 20);
                doc.text(wrappedFooter, 10, footerY);

                const footerHeight = wrappedFooter.length * 5;
                const signatureX = pageWidth - 15;
                doc.text(signatureText, signatureX, footerY + footerHeight + 5, { align: "right" });
            }

            doc.autoTable({
                startY: 35,
                head: headers,
                body: data,
                theme: 'grid',
                styles: { font: "helvetica", fontSize: 9, fontStyle: 'bold', cellPadding: 2, lineWidth: 0.3, lineColor: [0, 0, 0] },
                headStyles: { font: "helvetica", fontSize: 8, fontStyle: 'bold', halign: 'center', valign: 'middle', lineWidth: 0.5, lineColor: [0, 0, 0], fillColor: [204, 153, 255], textColor: [0, 0, 0] },
                bodyStyles: { halign: 'center', valign: 'middle', fontStyle: 'bold', lineWidth: 0.3, lineColor: [0, 0, 0], textColor: [0, 0, 0] },
                margin: { top: 10, bottom: 50, left: 10, right: 10 },
                tableWidth: 'auto',
                didDrawPage: function (data) {
                    const pageNumber = doc.internal.getNumberOfPages();
                    if (pageNumber === 1) {
                        doc.setFont("helvetica", "bold");
                        doc.setFontSize(12);
                        doc.text(title1, pageWidth / 2, 20, { align: "center" });
                        doc.text(title2, pageWidth / 2, 28, { align: "center" });
                    }

                    const totalPages = doc.internal.getNumberOfPages();
                    if (pageNumber === totalPages) {
                        const yAfterTable = data.cursor.y;
                        addFooter(yAfterTable);
                    }
                }
            });

            // Filename generation
            const now = new Date().toLocaleString("en-US", { timeZone: "Asia/Kolkata" });
            const dateInKolkata = new Date(now);
            dateInKolkata.setMonth(dateInKolkata.getMonth() - 1);

            const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            const reportMonth = monthNames[dateInKolkata.getMonth()];
            const reportYear = dateInKolkata.getFullYear();

            let reportType = "UNKNOWN";
            if (data.length > 0) {
                // data format: [SL, Name, DOB, SSIN, From, To, Updated]
                const firstSSIN = data[0][3]; 
                if (firstSSIN && firstSSIN.toString().startsWith("142")) {
                    reportType = "OTHERS";
                } else if (firstSSIN && firstSSIN.toString().startsWith("242")) {
                    reportType = "CONSTRUCTION";
                }
            }

            const fileName = `${reportMonth} ${reportYear} - ${reportType} PF MONTHLY REPORT.pdf`;
            doc.save(fileName);
            closePopup();
        }
    </script>
</body>
</html>
<?php
if (isset($conn)) {
    $conn->close();
}
?>