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

// --- 5. Page Data Fetching ---
$sql = "SELECT * FROM ds_record ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#ffffff">
    <title>BMSSY SERVICE | DS List</title>

    <!-- FontAwesome & SweetAlert2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- jQuery & DataTables Core + Buttons -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>

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
        .card-header { font-size: 18px; font-weight: 700; margin-bottom: 20px; color: var(--md-sys-color-primary); display: flex; align-items: center; gap: 10px; }

        /* Custom DataTables Styling for MD3 */
        table.dataTable { border-collapse: collapse !important; width: 100% !important; margin-top: 16px !important; }
        table.dataTable thead th { background: var(--md-sys-color-background); font-weight: 600; color: var(--md-sys-color-on-surface-variant); text-transform: uppercase; font-size: 12px; border-bottom: none !important; padding: 16px; }
        table.dataTable tbody td { padding: 16px; border-bottom: 1px solid var(--md-sys-color-background); vertical-align: middle; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { border-radius: 100px !important; border: none !important; background: transparent !important; color: var(--md-sys-color-on-surface) !important; margin: 0 4px; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: var(--md-sys-color-primary-container) !important; color: var(--md-sys-color-primary) !important; font-weight: 700; }
        .dataTables_wrapper .dataTables_filter input { border: 2px solid var(--md-sys-color-background); border-radius: 8px; padding: 8px 16px; outline: none; transition: 0.2s; }
        .dataTables_wrapper .dataTables_filter input:focus { border-color: var(--md-sys-color-primary); }

        /* Custom DataTables Export Buttons MD3 Styling */
        .dt-buttons { margin-bottom: 16px; display: flex; gap: 8px; flex-wrap: wrap; }
        .dt-button {
            background: var(--md-sys-color-surface) !important;
            border: 1px solid var(--md-sys-color-outline) !important;
            color: var(--md-sys-color-on-surface) !important;
            padding: 6px 16px !important;
            border-radius: 100px !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            cursor: pointer;
            transition: all 0.2s ease !important;
            box-shadow: none !important;
        }
        .dt-button:hover {
            background: var(--md-sys-color-background) !important;
            color: var(--md-sys-color-primary) !important;
            border-color: var(--md-sys-color-primary) !important;
        }

        .badge { background: var(--md-sys-color-background); padding: 4px 10px; border-radius: 100px; font-size: 12px; font-weight: 600; color: var(--md-sys-color-on-surface-variant); border: 1px solid var(--md-sys-color-outline); display: inline-block; }
        .copy-icon { color: var(--md-sys-color-primary); cursor: pointer; margin-left: 8px; font-size: 14px; transition: 0.2s; }
        .copy-icon:hover { transform: scale(1.2); }

        @media (max-width: 1024px) {
            .app-main { margin-left: 0; }
            .menu-btn { display: flex; }
        }
    </style>
</head>
<body>

    <div class="app-scaffold">
        
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Sidebar Identifier -->
        <script>window.PAGE_IDENTIFIER = '/ds/ds_list';</script>
        <?php include('../../inc/sideber.php'); ?>

        <main class="app-main">
            
            <header class="app-topbar">
                <div class="topbar-left">
                    <button class="menu-btn" id="menuBtn">
                        <svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
                    </button>
                    <h1 class="page-title">Duare Sorkar List</h1>
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
                        <i class="fas fa-list-alt"></i> All DS Records
                    </div>

                    <table id="dsListTable" class="display nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>SSIN</th>
                                <th>Name</th>
                                <th>DS Number</th>
                                <th>DS Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): 
                                    $ssin = htmlspecialchars($row["ssin"], ENT_QUOTES, 'UTF-8');
                                    $dsno = htmlspecialchars($row["dsno"], ENT_QUOTES, 'UTF-8');
                                ?>
                                <tr>
                                    <td>
                                        <span style="font-weight: 600;"><?php echo $ssin; ?></span>
                                        <i class="fas fa-copy copy-icon" data-value="<?php echo $ssin; ?>" title="Copy SSIN"></i>
                                    </td>
                                    <td><?php echo htmlspecialchars($row["name"], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <span style="font-weight: 500;"><?php echo $dsno; ?></span>
                                        <i class="fas fa-copy copy-icon" data-value="<?php echo $dsno; ?>" title="Copy DS Number"></i>
                                    </td>
                                    <td>
                                        <span class="badge"><?php echo date("d-m-Y", strtotime($row["created_at"])); ?></span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </main>
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
                
                // Open parent dropdown if it exists
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

        // --- DataTables Initialization ---
        $(document).ready(function() {
            $('#dsListTable').DataTable({
                responsive: true,
                dom: '<"top"fB>rt<"bottom"ilp><"clear">', // Position search box and buttons at top
                buttons: [
                    { extend: 'copy', text: '<i class="fas fa-copy"></i> Copy' },
                    { extend: 'csv', text: '<i class="fas fa-file-csv"></i> CSV' },
                    { extend: 'excel', text: '<i class="fas fa-file-excel"></i> Excel' },
                    { extend: 'pdf', text: '<i class="fas fa-file-pdf"></i> PDF' },
                    { extend: 'print', text: '<i class="fas fa-print"></i> Print' },
                    { extend: 'colvis', text: '<i class="fas fa-columns"></i> Columns' }
                ],
                language: { search: "", searchPlaceholder: "Search records..." },
                pageLength: 25
            });
        });

        // --- Toast Alerts for Copy ---
        const Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false, timer: 2000,
            background: 'var(--md-sys-color-surface)', color: 'var(--md-sys-color-on-surface)'
        });

        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('copy-icon')) {
                const val = e.target.getAttribute('data-value');
                navigator.clipboard.writeText(val).then(() => {
                    Toast.fire({ icon: 'success', title: `Copied: ${val}` });
                });
            }
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