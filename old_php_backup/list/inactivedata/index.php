<?php
// --- 1. Error Reporting Configuration ---
error_reporting(E_ALL);
ini_set('display_errors', 0); 
ini_set('log_errors', 1);     

// --- 2. Security Enhancements & Session Management ---
session_start();

header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

if (!isset($_SESSION["user_id"])) {
    header("Location: /login.php");
    exit();
}

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
    die("Critical Error: Database connection (\$conn) is missing.");
}

// --- 4. Auto Bug Detection & Database Logging ---
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
    <title>BMSSY SERVICE | Inactive Beneficiaries</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

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
            --color-success: #146c2e;
            --color-error: #b3261e;
            --color-error-bg: #f9dedc;
            --app-radius-lg: 24px;
            --app-radius-md: 12px;
            --sidebar-width: 280px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: var(--md-sys-color-background); color: var(--md-sys-color-on-surface); }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: rgba(0, 0, 0, 0.2); border-radius: 4px; }

        /* --- Scaffold & Layout --- */
        .app-scaffold { display: flex; height: 100vh; width: 100vw; overflow: hidden; }
        .sidebar-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 90; opacity: 0; visibility: hidden; transition: 0.3s; }
        .sidebar-overlay.active { opacity: 1; visibility: visible; }
        .app-main { flex: 1; display: flex; flex-direction: column; height: 100%; margin-left: var(--sidebar-width); transition: 0.3s; }

        /* --- Topbar --- */
        .app-topbar { height: 72px; padding: 0 24px; display: flex; align-items: center; justify-content: space-between; background: var(--md-sys-color-surface); z-index: 50; box-shadow: 0 1px 2px rgba(0,0,0,0.02); }
        .topbar-left { display: flex; align-items: center; gap: 16px; }
        .page-title { font-size: 20px; font-weight: 600; }
        .menu-btn { background: none; border: none; width: 40px; height: 40px; border-radius: 50%; display: none; align-items: center; justify-content: center; cursor: pointer; color: var(--md-sys-color-on-surface-variant); }
        .user-profile { display: flex; align-items: center; gap: 12px; }
        .user-info { text-align: right; }
        .user-name { font-size: 14px; font-weight: 600; }
        .user-role { font-size: 12px; color: var(--md-sys-color-on-surface-variant); }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--md-sys-color-primary-container); color: var(--md-sys-color-primary); display: flex; align-items: center; justify-content: center; font-weight: 700; }

        /* --- Content & Cards --- */
        .content-scroll { flex: 1; overflow-y: auto; padding: 16px; }
        .md-card { background: var(--md-sys-color-surface); border-radius: var(--app-radius-lg); padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); margin-bottom: 24px; }
        .card-header { font-size: 18px; font-weight: 700; margin-bottom: 20px; color: var(--md-sys-color-primary); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
        
        /* --- Responsive Filter Bar --- */
        .filter-bar { display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px; }
        .filter-group { display: flex; flex-direction: column; gap: 12px; width: 100%; }
        .search-wrapper { position: relative; width: 100%; }
        .search-wrapper i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--md-sys-color-outline); }
        .search-input { width: 100%; border: 2px solid var(--md-sys-color-background); border-radius: 100px; padding: 12px 16px 12px 44px; font-size: 14px; outline: none; transition: 0.2s; background: var(--md-sys-color-background); height: 48px; }
        .search-input:focus { border-color: var(--md-sys-color-primary); background: var(--md-sys-color-surface); }
        .filter-select { width: 100%; border: 2px solid var(--md-sys-color-background); border-radius: 100px; padding: 0 16px; font-size: 14px; outline: none; background: var(--md-sys-color-background); cursor: pointer; height: 48px; }

        /* --- Responsive Buttons --- */
        .action-btn { background: var(--md-sys-color-primary); color: white; border: none; padding: 0 20px; border-radius: 100px; font-weight: 600; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-size: 14px; height: 48px; width: 100%; }
        .action-btn:hover { opacity: 0.9; transform: scale(0.98); }
        .action-btn:disabled { opacity: 0.6; cursor: not-allowed; }
        .action-btn.export { background: var(--color-success); }
        .action-btn.export:hover { background: #0f5223; }
        .action-btn.secondary { background: var(--md-sys-color-primary-container); color: var(--md-sys-color-primary); }
        .action-btn.secondary:hover { background: #c2e7ff; }

        @media (min-width: 768px) {
            .content-scroll { padding: 24px; }
            .md-card { padding: 32px; }
            .filter-bar { flex-direction: row; align-items: center; justify-content: space-between; }
            .filter-group { flex-direction: row; flex: 1; }
            .search-wrapper { flex: 2; }
            .filter-select { flex: 1; }
            .action-btn { width: auto; height: 42px; padding: 0 24px; } /* Slimmer on desktop */
            .search-input, .filter-select { height: 42px; }
        }

        /* --- Loading Bar --- */
        .loading-container { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 150px; }
        .progress-wrapper { width: 100%; max-width: 400px; height: 6px; background: var(--md-sys-color-background); border-radius: 100px; overflow: hidden; margin-top: 12px; }
        .progress-bar { height: 100%; background: var(--color-error); width: 0%; transition: width 0.3s ease; }

        /* --- Custom DataTables (Desktop View) --- */
        .table-container { opacity: 0; transition: opacity 0.5s ease; display: none; width: 100%; overflow-x: auto;}
        .table-container.show { opacity: 1; display: block; }
        table.dataTable { border-collapse: collapse !important; width: 100% !important; margin-top: 16px !important; }
        table.dataTable thead th { background: var(--md-sys-color-background); font-weight: 600; color: var(--md-sys-color-on-surface-variant); text-transform: uppercase; font-size: 12px; border-bottom: none !important; padding: 16px; white-space: nowrap; }
        table.dataTable tbody td { padding: 16px; border-bottom: 1px solid var(--md-sys-color-background); vertical-align: middle; font-size: 14px; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { border-radius: 100px !important; border: none !important; background: transparent !important; color: var(--md-sys-color-on-surface) !important; margin: 0 4px; padding: 4px 12px !important;}
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: var(--md-sys-color-primary-container) !important; color: var(--md-sys-color-primary) !important; font-weight: 700; }
        .dataTables_filter { display: none; }

        /* --- Native MD3 Modal --- */
        .md-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; display: none; align-items: center; justify-content: center; backdrop-filter: blur(2px); padding: 16px; }
        .md-modal-overlay.show { display: flex; }
        .md-modal { background: var(--md-sys-color-surface); width: 100%; max-width: 500px; border-radius: var(--app-radius-lg); padding: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); animation: popIn 0.3s cubic-bezier(0.2,0,0,1); max-height: 90vh; overflow-y: auto; }
        @keyframes popIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .md-modal-title { font-size: 18px; font-weight: 700; margin-bottom: 24px; color: var(--color-success); display: flex; justify-content: space-between; align-items: center; }
        .close-btn { background: var(--md-sys-color-background); border: none; font-size: 16px; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; color: var(--md-sys-color-on-surface-variant); display: flex; align-items: center; justify-content: center;}
        
        .form-grid { display: grid; grid-template-columns: 1fr; gap: 16px; margin-bottom: 16px; }
        @media (min-width: 600px) { .form-grid { grid-template-columns: 1fr 1fr; } }
        
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 8px; color: var(--md-sys-color-on-surface-variant); }
        .md-input-readonly { border: none; background: var(--md-sys-color-background); border-radius: 8px; padding: 12px 16px; width: 100%; font-size: 14px; font-weight: 600; color: var(--md-sys-color-on-surface-variant); cursor: not-allowed; }
        .md-input-error { border: 1px solid rgba(179, 38, 30, 0.2); background: var(--color-error-bg); color: var(--color-error); font-weight: 500; line-height: 1.5; }

        .md-modal-actions { display: flex; flex-direction: column-reverse; gap: 12px; margin-top: 32px; padding-top: 16px; border-top: 1px solid var(--md-sys-color-background); }
        @media (min-width: 600px) {
            .md-modal-actions { flex-direction: row; justify-content: flex-end; }
        }
        
        .btn-text { background: none; border: none; font-weight: 600; color: var(--md-sys-color-on-surface-variant); cursor: pointer; padding: 0 20px; height: 48px; border-radius: 100px; transition: 0.2s; width: 100%;}
        @media (min-width: 600px) { .btn-text { width: auto; height: 42px; } }
        .btn-text:hover { background: var(--md-sys-color-background); }

        /* --- Mobile Responsive Table (Card View) --- */
        @media (max-width: 768px) {
            .app-main { margin-left: 0; }
            .menu-btn { display: flex; }
            .user-info { display: none; }

            /* Force table to not be like a table anymore */
            .table-container table, .table-container thead, .table-container tbody, .table-container th, .table-container td, .table-container tr { 
                display: block; width: 100%; 
            }
            
            /* Hide table headers (but not display: none;, for accessibility) */
            .table-container thead tr { position: absolute; top: -9999px; left: -9999px; }
            
            /* Card design for rows */
            .table-container tr { border: 1px solid var(--md-sys-color-outline); border-radius: 12px; margin-bottom: 16px; padding: 12px; background: var(--md-sys-color-surface); box-shadow: 0 2px 4px rgba(0,0,0,0.02);}
            
            /* Flex layout for cells */
            .table-container td { border: none; border-bottom: 1px solid var(--md-sys-color-background); position: relative; padding: 12px 0; display: flex; flex-direction: column; align-items: flex-start; gap: 4px; text-align: left; }
            .table-container td:last-child { border-bottom: 0; padding-bottom: 0; }
            
            /* Data Labels generated via ::before using HTML data-label attribute */
            .table-container td::before { 
                content: attr(data-label); 
                font-weight: 700; 
                color: var(--md-sys-color-on-surface-variant); 
                font-size: 11px; 
                text-transform: uppercase; 
                letter-spacing: 0.5px;
            }
        }
    </style>
</head>
<body>

    <div class="app-scaffold">
        
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <script>window.PAGE_IDENTIFIER = '/list/inactivedata';</script>
        <?php include('../../inc/sideber.php'); ?>

        <main class="app-main">
            
            <header class="app-topbar">
                <div class="topbar-left">
                    <button class="menu-btn" id="menuBtn">
                        <svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
                    </button>
                    <h1 class="page-title">Inactive List</h1>
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
                    
                    <div class="filter-bar">
                        <div class="filter-group">
                            <div class="search-wrapper">
                                <i class="fas fa-search"></i>
                                <input type="text" id="searchBox" class="search-input" placeholder="Search Name, SSIN...">
                            </div>
                            <select id="filterType" class="filter-select">
                                <option value="">All Categories</option>
                                <option value="142">Others (142)</option>
                                <option value="242">Construction (242)</option>
                            </select>
                        </div>
                        <button id="exportExcel" class="action-btn export">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                    </div>

                    <div class="card-header" style="border-top: 1px solid var(--md-sys-color-background); padding-top: 24px;">
                        <div style="color: var(--color-error);"><i class="fas fa-user-slash mr-2"></i> Inactive Beneficiaries</div>
                        <button onclick="reloadData()" class="action-btn secondary" style="width: auto;">
                            <i class="fas fa-sync-alt"></i> <span class="hidden md:inline">Refresh</span>
                        </button>
                    </div>

                    <div id="loadingState" class="loading-container">
                        <div class="progress-text">Loading Data...</div>
                        <div class="progress-wrapper">
                            <div id="progressBar" class="progress-bar"></div>
                        </div>
                    </div>

                    <div id="tableContainer" class="table-container">
                        <table id="inactiveBeneficiariesTable" class="display nowrap">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>SSIN</th>
                                    <th>Date of 60</th>
                                    <th>Phone</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="inactiveBeneficiaryTable">
                                </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <div class="md-modal-overlay" id="activateModal">
        <div class="md-modal">
            <div class="md-modal-title">
                <span><i class="fas fa-check-circle"></i> Activate Beneficiary</span>
                <button onclick="closeActivateModal()" class="close-btn"><i class="fas fa-times"></i></button>
            </div>
            
            <input type="hidden" id="activateId">
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Name</label>
                    <input type="text" id="activateName" class="md-input-readonly" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">SSIN</label>
                    <input type="text" id="activateSSIN" class="md-input-readonly" readonly>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Phone No</label>
                <input type="text" id="activatePhone" class="md-input-readonly" readonly>
            </div>
            
            <div class="form-group">
                <label class="form-label" style="color: var(--color-error);">Rejection Reason</label>
                <textarea id="activateReason" class="md-input-readonly md-input-error" rows="3" readonly></textarea>
            </div>
            
            <div class="md-modal-actions">
                <button type="button" class="btn-text" onclick="closeActivateModal()">Cancel</button>
                <button type="button" class="action-btn" id="confirmActivate" style="background: var(--color-success);">
                    <span id="loadingSpinner" style="display:none; margin-right:6px;"><i class="fas fa-spinner fa-spin"></i></span>
                    Confirm Activation
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

        // --- Modal Logic ---
        function openActivateModal() { document.getElementById('activateModal').classList.add('show'); }
        function closeActivateModal() { document.getElementById('activateModal').classList.remove('show'); }

        // --- AJAX Data Fetching Logic ---
        function reloadData() {
            // UI Reset
            document.getElementById('tableContainer').classList.remove('show');
            document.getElementById('loadingState').style.display = 'flex';
            document.getElementById('progressBar').style.width = '30%';

            let filter = $("#filterType").val();
            let search = $("#searchBox").val();

            $.ajax({
                url: "fetch_inactive_beneficiaries.php",
                type: "GET",
                data: { filter_type: filter, search_query: search },
                success: function (response) {
                    $('#progressBar').css('width', '100%');
                    
                    setTimeout(() => {
                        if ($.fn.DataTable.isDataTable('#inactiveBeneficiariesTable')) {
                            $('#inactiveBeneficiariesTable').DataTable().destroy();
                        }

                        $("#inactiveBeneficiaryTable").html(response);

                        $('#inactiveBeneficiariesTable').DataTable({
                            responsive: false, // We handle mobile responsiveness via CSS Cards
                            lengthChange: false,
                            autoWidth: false,
                            dom: 'rt<"flex flex-col md:flex-row justify-between items-center mt-4 gap-4"ip>',
                            pageLength: 15
                        });

                        document.getElementById('loadingState').style.display = 'none';
                        document.getElementById('tableContainer').classList.add('show');
                    }, 400);
                },
                error: function() {
                    Swal.fire('Error', 'Failed to load data', 'error');
                    document.getElementById('loadingState').style.display = 'none';
                }
            });
        }

        $(document).ready(function () {
            // Initial Load
            reloadData();

            // Filter Change
            $("#filterType").change(function () { reloadData(); });

            // Debounced Search
            let timeout = null;
            $("#searchBox").on("keyup", function () {
                clearTimeout(timeout);
                timeout = setTimeout(reloadData, 500);
            });

            // Export Excel
            $("#exportExcel").click(function () {
                let filter = $("#filterType").val();
                let search = $("#searchBox").val();
                window.location.href = "export_excel.php?filter_type=" + encodeURIComponent(filter) + "&search_query=" + encodeURIComponent(search);
            });

            // Open Activate Modal
            $(document).on("click", ".activateBtn", function () {
                $("#activateId").val($(this).data("id"));
                $("#activateName").val($(this).data("name"));
                $("#activateSSIN").val($(this).data("ssin"));
                $("#activatePhone").val($(this).data("phone"));
                $("#activateReason").val($(this).data("reason"));
                openActivateModal();
            });

            // Confirm Activation
            $("#confirmActivate").click(function () {
                let id = $("#activateId").val();
                if (!id) return Swal.fire("Error", "Invalid beneficiary ID!", "error");

                let $btn = $(this);
                $btn.prop("disabled", true);
                $("#loadingSpinner").show();

                $.ajax({
                    url: "activate_beneficiary.php",
                    type: "POST",
                    data: { id: id },
                    success: function (response) {
                        if (response.trim() === "success") {
                            Swal.fire({
                                title: "Activated!", 
                                text: "Beneficiary is now active.", 
                                icon: "success",
                                background: 'var(--md-sys-color-surface)',
                                color: 'var(--md-sys-color-on-surface)'
                            }).then(() => {
                                closeActivateModal();
                                reloadData();
                            });
                        } else {
                            Swal.fire({
                                title: "Error", 
                                text: "Activation failed: " + response, 
                                icon: "error",
                                background: 'var(--md-sys-color-surface)',
                                color: 'var(--md-sys-color-on-surface)'
                            });
                        }
                    },
                    error: function () {
                        Swal.fire("Error", "Server error! Try again later.", "error");
                    },
                    complete: function () {
                        $btn.prop("disabled", false);
                        $("#loadingSpinner").hide();
                    }
                });
            });
        });
    </script>
</body>
</html>
<?php
if (isset($conn)) {
    $conn->close();
}
?>