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
    <title>BMSSY SERVICE | All SSIN Data</title>

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
        .md-card { background: var(--md-sys-color-surface); border-radius: var(--app-radius-lg); padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); margin-bottom: 24px; position: relative; }
        .card-header { font-size: 18px; font-weight: 700; margin-bottom: 20px; color: var(--md-sys-color-primary); display: flex; align-items: center; justify-content: space-between; }
        
        .btn-refresh { background: var(--md-sys-color-primary-container); color: var(--md-sys-color-primary); border: none; padding: 8px 16px; border-radius: 100px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px; }
        .btn-refresh:hover { background: #c2e7ff; }

        /* Loading Progress Bar */
        .loading-container { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 200px; }
        .progress-wrapper { width: 100%; max-width: 400px; height: 6px; background: var(--md-sys-color-background); border-radius: 100px; overflow: hidden; margin-top: 12px; }
        .progress-bar { height: 100%; background: var(--md-sys-color-primary); width: 0%; transition: width 0.3s ease; }
        .progress-text { font-size: 14px; font-weight: 600; color: var(--md-sys-color-on-surface-variant); }

        /* Custom DataTables Styling */
        .table-container { opacity: 0; transition: opacity 0.5s ease; display: none; }
        .table-container.show { opacity: 1; display: block; }
        table.dataTable { border-collapse: collapse !important; width: 100% !important; margin-top: 16px !important; }
        table.dataTable thead th { background: var(--md-sys-color-background); font-weight: 600; color: var(--md-sys-color-on-surface-variant); text-transform: uppercase; font-size: 12px; border-bottom: none !important; padding: 16px; }
        table.dataTable tbody td { padding: 16px; border-bottom: 1px solid var(--md-sys-color-background); vertical-align: middle; font-size: 14px; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { border-radius: 100px !important; border: none !important; background: transparent !important; color: var(--md-sys-color-on-surface) !important; margin: 0 4px; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: var(--md-sys-color-primary-container) !important; color: var(--md-sys-color-primary) !important; font-weight: 700; }
        .dataTables_wrapper .dataTables_filter input { border: 2px solid var(--md-sys-color-background); border-radius: 8px; padding: 8px 16px; outline: none; transition: 0.2s; margin-left: 8px; }
        .dataTables_wrapper .dataTables_filter input:focus { border-color: var(--md-sys-color-primary); }

        /* Action Buttons */
        .action-btn { background: var(--md-sys-color-primary); color: white; border: none; padding: 8px 16px; border-radius: 100px; font-weight: 600; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px; font-size: 13px; }
        .action-btn:hover { background: #0842a0; }
        .action-btn:disabled { opacity: 0.6; cursor: not-allowed; }

        /* Native MD3 Modal */
        .md-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; display: none; align-items: center; justify-content: center; backdrop-filter: blur(2px); }
        .md-modal-overlay.show { display: flex; }
        .md-modal { background: var(--md-sys-color-surface); width: 90%; max-width: 450px; border-radius: var(--app-radius-lg); padding: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); animation: popIn 0.3s cubic-bezier(0.2,0,0,1); }
        @keyframes popIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .md-modal-title { font-size: 18px; font-weight: 700; margin-bottom: 20px; color: var(--md-sys-color-primary); display: flex; justify-content: space-between; align-items: center; }
        .close-btn { background: none; border: none; font-size: 20px; cursor: pointer; color: var(--md-sys-color-on-surface-variant); }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--md-sys-color-on-surface); }
        .md-input-sm { border: 2px solid var(--md-sys-color-background); border-radius: 8px; padding: 10px 14px; outline: none; width: 100%; transition: 0.2s; font-size: 14px; background: var(--md-sys-color-background); }
        .md-input-sm:focus { border-color: var(--md-sys-color-primary); background: var(--md-sys-color-surface); }
        .md-modal-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--md-sys-color-background); }
        .btn-text { background: none; border: none; font-weight: 600; color: var(--md-sys-color-on-surface-variant); cursor: pointer; padding: 8px 16px; border-radius: 100px; transition: 0.2s; }
        .btn-text:hover { background: var(--md-sys-color-background); }

        @media (max-width: 1024px) {
            .app-main { margin-left: 0; }
            .menu-btn { display: flex; }
        }
    </style>
</head>
<body>

    <div class="app-scaffold">
        
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <script>window.PAGE_IDENTIFIER = '/list/alldata';</script>
        <?php include('../../inc/sideber.php'); ?>

        <main class="app-main">
            
            <header class="app-topbar">
                <div class="topbar-left">
                    <button class="menu-btn" id="menuBtn">
                        <svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
                    </button>
                    <h1 class="page-title">All SSIN Data</h1>
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
                        <div><i class="fas fa-users mr-2"></i> Beneficiary List</div>
                        <button onclick="reloadData()" class="btn-refresh">
                            <i class="fas fa-sync-alt"></i> Refresh Data
                        </button>
                    </div>

                    <div id="loadingState" class="loading-container">
                        <div class="progress-text">Fetching Data... <span id="progressText">0%</span></div>
                        <div class="progress-wrapper">
                            <div id="progressBar" class="progress-bar"></div>
                        </div>
                        <div style="font-size: 12px; color: var(--md-sys-color-outline); margin-top: 8px;">Please wait while records are loaded</div>
                    </div>

                    <div id="tableContainer" class="table-container">
                        <table id="example1" class="display nowrap">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>SSIN</th>
                                    <th>Date of Attaining 60</th>
                                    <th>Phone No</th>
                                    <th>Last Update</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="beneficiaryTable">
                                </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <div class="md-modal-overlay" id="updateModal">
        <div class="md-modal">
            <div class="md-modal-title">
                <span>Update Beneficiary</span>
                <button onclick="closeUpdateModal()" class="close-btn"><i class="fas fa-times"></i></button>
            </div>
            
            <input type="hidden" id="updateId">
            
            <div class="form-group">
                <label class="form-label" for="updateName">Name</label>
                <input type="text" id="updateName" class="md-input-sm">
            </div>
            <div class="form-group">
                <label class="form-label" for="updateSSIN">SSIN</label>
                <input type="text" id="updateSSIN" class="md-input-sm">
            </div>
            <div class="form-group">
                <label class="form-label" for="updateDOB">Date of Attaining 60</label>
                <input type="date" id="updateDOB" class="md-input-sm">
            </div>
            <div class="form-group">
                <label class="form-label" for="updatePhone">Phone No</label>
                <input type="text" id="updatePhone" class="md-input-sm">
            </div>
            
            <div class="md-modal-actions">
                <button type="button" class="btn-text" onclick="closeUpdateModal()">Cancel</button>
                <button type="button" class="action-btn" id="saveUpdate">
                    <i class="fas fa-save"></i> Save Changes
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
        function openUpdateModal() {
            document.getElementById('updateModal').classList.add('show');
        }
        function closeUpdateModal() {
            document.getElementById('updateModal').classList.remove('show');
        }

        // --- AJAX Data Fetching Logic ---
        let tableInstance = null;

        function reloadData() {
            // Reset UI to loading state
            document.getElementById('tableContainer').classList.remove('show');
            document.getElementById('loadingState').style.display = 'flex';
            document.getElementById('progressBar').style.width = '0%';
            document.getElementById('progressText').innerText = '0%';
            
            $.ajax({
                url: "fetch_beneficiaries.php",
                type: "GET",
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    xhr.addEventListener("progress", function(evt) {
                        if (evt.lengthComputable) {
                            var percentComplete = Math.round((evt.loaded / evt.total) * 100);
                            $('#progressBar').css('width', percentComplete + '%');
                            $('#progressText').text(percentComplete + '%');
                        } else {
                            $('#progressBar').css('width', '80%');
                            $('#progressText').text('Loading...');
                        }
                    }, false);
                    return xhr;
                },
                success: function(response) {
                    $('#progressBar').css('width', '100%');
                    $('#progressText').text('100%');

                    setTimeout(() => {
                        // Destroy old DataTable instance if it exists
                        if ($.fn.DataTable.isDataTable('#example1')) {
                            $('#example1').DataTable().destroy();
                        }

                        $("#beneficiaryTable").html(response);

                        // Initialize DataTables
                        tableInstance = $('#example1').DataTable({
                            responsive: true,
                            lengthChange: false,
                            autoWidth: false,
                            language: { search: "", searchPlaceholder: "Search records..." },
                            pageLength: 25
                        });

                        // Switch views
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

        $(document).ready(function() {
            // Initial load
            reloadData();

            // Open Modal event delegation (since rows are dynamically loaded)
            $(document).on("click", ".updateBtn", function() {
                $("#updateId").val($(this).data("id"));
                $("#updateName").val($(this).data("name"));
                $("#updateSSIN").val($(this).data("ssin"));
                $("#updateDOB").val($(this).data("dob"));
                $("#updatePhone").val($(this).data("phone"));
                openUpdateModal();
            });

            // Save Update Logic
            $("#saveUpdate").click(function() {
                let id = $("#updateId").val();
                let name = $("#updateName").val();
                let ssin = $("#updateSSIN").val();
                let dob = $("#updateDOB").val();
                let phone = $("#updatePhone").val();
                let $btn = $(this);
                let originalText = $btn.html();

                $btn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);

                $.ajax({
                    url: "update_beneficiary.php",
                    type: "POST",
                    data: { id, name, ssin, dob, phone },
                    success: function(response) {
                        if (response.trim() === "success") {
                            Swal.fire({
                                title: "Updated!", 
                                text: "Beneficiary updated successfully.", 
                                icon: "success",
                                background: 'var(--md-sys-color-surface)',
                                color: 'var(--md-sys-color-on-surface)'
                            });
                            closeUpdateModal();
                            reloadData();
                        } else {
                            Swal.fire({
                                title: "Error", 
                                text: "Update failed: " + response, 
                                icon: "error",
                                background: 'var(--md-sys-color-surface)',
                                color: 'var(--md-sys-color-on-surface)'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire("Error", "AJAX request failed", "error");
                    },
                    complete: function() {
                        $btn.html(originalText).prop('disabled', false);
                    }
                });
            });
        });
    </script>
</body>
</html>
<?php
// Close MySQLi connection securely at the very end
if (isset($conn)) {
    $conn->close();
}
?>