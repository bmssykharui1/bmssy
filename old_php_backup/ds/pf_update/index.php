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
// Using your exact relative path to guarantee it connects properly
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

// --- 5. Page Data Fetching (MySQLi) ---
$startDate = '2025-08-01';
$endDate = date('Y-m-d');
$periodFrom = '';
$periodTo = '';

// Fetch global settings
$settingsQuery = "SELECT period_form, period_to FROM global_settings WHERE id = 1 LIMIT 1";
$settingsResult = $conn->query($settingsQuery);

if ($settingsResult && $settingsResult->num_rows > 0) {
    $settingsRow = $settingsResult->fetch_assoc();
    $periodFrom = htmlspecialchars($settingsRow['period_form'] ?? '');
    $periodTo = htmlspecialchars($settingsRow['period_to'] ?? '');
}

// Fetch DS Records
$sql = "
    SELECT * FROM ds_record 
    WHERE ssin NOT IN (
        SELECT approved_ssin 
        FROM pf_update 
        WHERE date BETWEEN ? AND ?
    )
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#ffffff">
    <title>BMSSY SERVICE | DS PF Update</title>

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

        /* Row Form Elements */
        .md-input-sm { border: 2px solid var(--md-sys-color-background); border-radius: 6px; padding: 6px 12px; outline: none; width: 100%; transition: 0.2s; font-size: 13px; }
        .md-input-sm:focus { border-color: var(--md-sys-color-primary); background: var(--md-sys-color-surface); }
        .action-btn { background: var(--color-success); color: white; border: none; padding: 8px 16px; border-radius: 100px; font-weight: 600; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px; font-size: 13px; }
        .action-btn:hover { opacity: 0.9; transform: scale(0.98); }
        .action-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        
        .copy-icon { color: var(--md-sys-color-primary); cursor: pointer; margin-left: 8px; font-size: 14px; transition: 0.2s; }
        .copy-icon:hover { transform: scale(1.2); }
        .badge { background: var(--md-sys-color-background); padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; color: var(--md-sys-color-on-surface-variant); border: 1px solid var(--md-sys-color-outline); }

        .strike { text-decoration: line-through; opacity: 0.5; }

        /* Native MD3 Modal */
        .md-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; display: none; align-items: center; justify-content: center; backdrop-filter: blur(2px); }
        .md-modal-overlay.show { display: flex; }
        .md-modal { background: var(--md-sys-color-surface); width: 90%; max-width: 400px; border-radius: var(--app-radius-lg); padding: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); animation: popIn 0.3s cubic-bezier(0.2,0,0,1); }
        @keyframes popIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .md-modal-title { font-size: 18px; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; color: var(--md-sys-color-primary); }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
        .md-modal-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; }
        .btn-text { background: none; border: none; font-weight: 600; color: var(--md-sys-color-on-surface-variant); cursor: pointer; padding: 8px 16px; border-radius: 100px; }
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

        <script>window.PAGE_IDENTIFIER = '/ds/pf_update';</script>
        <?php include('../../inc/sideber.php'); ?>

        <main class="app-main">
            
            <header class="app-topbar">
                <div class="topbar-left">
                    <button class="menu-btn" id="menuBtn">
                        <svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
                    </button>
                    <h1 class="page-title">Duare Sorkar PF Updates</h1>
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
                        <i class="fas fa-list-check"></i> Pending SSIN Records
                    </div>

                    <table id="dsTable" class="display nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>SSIN</th>
                                <th>Name</th>
                                <th>DS Details</th>
                                <th>Period From</th>
                                <th>Period To</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): 
                                    $id = htmlspecialchars($row["ssin"], ENT_QUOTES, 'UTF-8');
                                    $name = htmlspecialchars($row["name"], ENT_QUOTES, 'UTF-8');
                                    $dsno = htmlspecialchars($row["dsno"], ENT_QUOTES, 'UTF-8');
                                    $created_at = date("d M y", strtotime($row["created_at"]));
                                ?>
                                <tr id="row-<?php echo $id; ?>">
                                    <td>
                                        <div style="display: flex; align-items: flex-start; gap: 8px;">
                                            <input type="checkbox" class="toggle-edit" data-id="<?php echo $id; ?>" style="margin-top: 4px;">
                                            <div>
                                                <span id="ssin-text-<?php echo $id; ?>" style="font-weight: 600;"><?php echo $id; ?></span>
                                                <i class="fas fa-copy copy-icon" data-value="<?php echo $id; ?>" title="Copy SSIN"></i>
                                                <div id="edit-ssin-box-<?php echo $id; ?>" style="display: none; margin-top: 8px;">
                                                    <input type="text" class="md-input-sm ssin-input" id="edit-ssin-<?php echo $id; ?>" data-rowid="<?php echo $id; ?>" placeholder="New SSIN">
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span id="name-text-<?php echo $id; ?>"><?php echo $name; ?></span>
                                        <div id="edit-name-box-<?php echo $id; ?>" style="display: none; margin-top: 8px;">
                                            <input type="text" class="md-input-sm" id="edit-name-<?php echo $id; ?>" placeholder="Auto-filled Name" readonly>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span style="font-weight: 500;"><?php echo $dsno; ?></span>
                                            <i class="fas fa-copy copy-icon" data-value="<?php echo $dsno; ?>" title="Copy DS Number"></i>
                                        </div>
                                        <div class="badge" style="margin-top: 6px; display: inline-block;"><?php echo $created_at; ?></div>
                                    </td>
                                    <td>
                                        <input type="date" class="md-input-sm" id="periodFrom-<?php echo $id; ?>" value="<?php echo $periodFrom; ?>">
                                    </td>
                                    <td>
                                        <input type="date" class="md-input-sm" id="periodTo-<?php echo $id; ?>" value="<?php echo $periodTo; ?>">
                                    </td>
                                    <td>
                                        <button class="action-btn save-btn" data-id="<?php echo $id; ?>">
                                            <i class="fas fa-save"></i> Save
                                        </button>
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

    <div class="md-modal-overlay" id="reactionModal">
        <div class="md-modal">
            <div class="md-modal-title">
                <i class="fas fa-comment-dots"></i> Reaction Required
            </div>
            <form id="reactionForm">
                <div class="form-group">
                    <label class="form-label">SSIN</label>
                    <input type="text" class="md-input-sm" id="reaction-ssin" readonly style="background: var(--md-sys-color-background);">
                </div>
                <div class="form-group">
                    <label class="form-label">Name</label>
                    <input type="text" class="md-input-sm" id="reaction-name" readonly style="background: var(--md-sys-color-background);">
                </div>
                <div class="form-group">
                    <label class="form-label">Reason for change</label>
                    <textarea class="md-input-sm" id="reaction-reason" rows="3" placeholder="Explain why this SSIN needs modification..." required style="resize: vertical;"></textarea>
                </div>
                <div class="md-modal-actions">
                    <button type="button" class="btn-text" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="action-btn"><i class="fas fa-check"></i> Submit</button>
                </div>
            </form>
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
                
                // Also open the parent dropdown if it exists
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
            $('#dsTable').DataTable({
                responsive: true,
                language: { search: "", searchPlaceholder: "Search records..." }
            });
        });

        // --- Toast Alerts ---
        const Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false, timer: 2000,
            background: 'var(--md-sys-color-surface)', color: 'var(--md-sys-color-on-surface)'
        });

        // --- Copy Logic ---
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('copy-icon')) {
                const val = e.target.getAttribute('data-value');
                navigator.clipboard.writeText(val).then(() => {
                    Toast.fire({ icon: 'success', title: `Copied: ${val}` });
                });
            }
        });

        // --- Modal & Checkbox Logic ---
        let currentCheckboxId = null;

        function closeModal() {
            document.getElementById('reactionModal').classList.remove('show');
            if(currentCheckboxId) {
                const cb = document.querySelector(`.toggle-edit[data-id="${currentCheckboxId}"]`);
                if(cb && !cb.disabled) cb.checked = false; // Reset if cancelled
            }
        }

        document.querySelectorAll('.toggle-edit').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const id = this.dataset.id;
                if (this.checked) {
                    this.checked = false; 
                    currentCheckboxId = id;
                    document.getElementById('reaction-ssin').value = document.getElementById(`ssin-text-${id}`).innerText;
                    document.getElementById('reaction-name').value = document.getElementById(`name-text-${id}`).innerText;
                    document.getElementById('reaction-reason').value = "";
                    document.getElementById('reactionModal').classList.add('show');
                } else {
                    document.getElementById(`edit-ssin-box-${id}`).style.display = "none";
                    document.getElementById(`edit-name-box-${id}`).style.display = "none";
                    document.getElementById(`ssin-text-${id}`).classList.remove('strike');
                    document.getElementById(`name-text-${id}`).classList.remove('strike');
                }
            });
        });

        document.getElementById('reactionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const ssin = document.getElementById("reaction-ssin").value;
            const name = document.getElementById("reaction-name").value;
            const reason = document.getElementById("reaction-reason").value.trim();

            fetch("save_reaction.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams({ ssin, name, reason })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === "success") {
                    closeModal();
                    Toast.fire({ icon: 'success', title: 'Reaction saved' });

                    if (currentCheckboxId) {
                        const cb = document.querySelector(`.toggle-edit[data-id="${currentCheckboxId}"]`);
                        cb.checked = true;
                        cb.disabled = true;

                        document.getElementById(`ssin-text-${currentCheckboxId}`).classList.add('strike');
                        document.getElementById(`name-text-${currentCheckboxId}`).classList.add('strike');
                        
                        document.getElementById(`edit-ssin-box-${currentCheckboxId}`).style.display = "block";
                        document.getElementById(`edit-name-box-${currentCheckboxId}`).style.display = "block";
                    }
                } else {
                    Swal.fire("Error", data.message || "Failed to save", "error");
                }
            })
            .catch(err => Swal.fire("Error", err.message, "error"));
        });

        // --- Auto-fetch Name ---
        document.querySelectorAll('.ssin-input').forEach(input => {
            input.addEventListener('input', function() {
                const ssin = this.value.trim();
                const rowId = this.dataset.rowid;
                const nameInput = document.getElementById(`edit-name-${rowId}`);
                
                if (ssin.length >= 4) {
                    fetch(`fetch_name.php?ssin=${encodeURIComponent(ssin)}`)
                        .then(res => res.text())
                        .then(name => nameInput.value = name || "");
                } else {
                    nameInput.value = "";
                }
            });
        });

        // --- Save Data ---
        document.querySelectorAll('.save-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const originalHtml = this.innerHTML;
                
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

                const checkbox = document.querySelector(`.toggle-edit[data-id="${id}"]`);
                const ssin = checkbox.checked 
                    ? document.getElementById(`edit-ssin-${id}`).value 
                    : document.getElementById(`ssin-text-${id}`).innerText;
                
                const name = checkbox.checked 
                    ? document.getElementById(`edit-name-${id}`).value 
                    : document.getElementById(`name-text-${id}`).innerText;

                const dsnoNode = document.querySelector(`#row-${id} td:nth-child(3) span`);
                const dsno = dsnoNode ? dsnoNode.innerText.trim() : "";
                
                const periodFrom = document.getElementById(`periodFrom-${id}`).value;
                const periodTo = document.getElementById(`periodTo-${id}`).value;

                fetch("save_ds_data.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: newSearchParams({ ssin, name, dsno, periodFrom, periodTo })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === "success") {
                        Toast.fire({ icon: 'success', title: 'Data Saved' });
                        const row = document.getElementById(`row-${id}`);
                        if (row) {
                            $(row).fadeOut(300, function() {
                                $('#dsTable').DataTable().row(row).remove().draw(false);
                            });
                        }
                    } else {
                        Swal.fire("Error", data.message || "Failed to save", "error");
                        this.disabled = false;
                        this.innerHTML = originalHtml;
                    }
                })
                .catch(err => {
                    Swal.fire("Error", err.message, "error");
                    this.disabled = false;
                    this.innerHTML = originalHtml;
                });
            });
        });
        
        // Helper to fix missing URLSearchParams locally on older setups (rare but helpful)
        function newSearchParams(obj) {
            const params = new URLSearchParams();
            for (const key in obj) {
                params.append(key, obj[key]);
            }
            return params;
        }
    </script>
</body>
</html>
<?php
// Close MySQLi connection securely at the very end
if (isset($conn)) {
    $conn->close();
}
?>