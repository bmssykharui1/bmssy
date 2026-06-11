<?php
// Report all PHP errors to catch bugs during development.
// (Remember to set display_errors to 0 in a live production environment)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Force PHP to use Kolkata Timezone
date_default_timezone_set('Asia/Kolkata');

session_start();
if (!isset($_SESSION["user_id"])) {
    // header("Location: /"); // Uncomment for production
    // exit();
}

$user_name = isset($_SESSION["user_name"]) ? $_SESSION["user_name"] : "Admin User";

// ==========================================
// DATABASE CONNECTION & AUTO-SETUP
// ==========================================
// Require your centralized database connection file
require_once '../../database/index.php'; 

// Check if $conn exists (from your database/index.php)
if (!isset($conn) || $conn->connect_error) {
    die("Database Connection Failed: Ensure database/index.php initializes \$conn properly.");
}

// Auto-create the table if it doesn't exist using MySQLi
// Updated for_month to VARCHAR(100) to safely hold the "DD-MM-YYYY - DD-MM-YYYY" string
$tableQuery = "
    CREATE TABLE IF NOT EXISTS form4_entries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reg_no VARCHAR(50) NOT NULL,
        beneficiary_name VARCHAR(150) NOT NULL,
        book_no VARCHAR(50) NOT NULL,
        receipt_no VARCHAR(50) NOT NULL,
        for_month VARCHAR(100) NOT NULL,
        date_of_collection DATE NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if (!$conn->query($tableQuery)) {
    die("Table Creation Failed: " . $conn->error);
}

// ==========================================
// AJAX API ENDPOINTS (Handled in the same file)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // 1. SAVE NEW ENTRY
    if ($_POST['action'] === 'save_entry') {
        
        // Format the date range into a single string: "01-01-2025 - 01-01-2026"
        $from_date = date('d-m-Y', strtotime($_POST['for_month_from']));
        $to_date = date('d-m-Y', strtotime($_POST['for_month_to']));
        $for_month_string = $from_date . ' - ' . $to_date;
        
        // Generate exact Kolkata timestamp from PHP
        $current_kolkata_time = date('Y-m-d H:i:s');

        // Added created_at to the INSERT query to force the Kolkata time
        $stmt = $conn->prepare("INSERT INTO form4_entries (reg_no, beneficiary_name, book_no, receipt_no, for_month, date_of_collection, amount, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt) {
            // "ssssssds" means 6 strings, 1 double (decimal), 1 string
            $stmt->bind_param("ssssssds", 
                $_POST['reg_no'], 
                $_POST['beneficiary_name'], 
                $_POST['book_no'], 
                $_POST['receipt_no'], 
                $for_month_string, 
                $_POST['date_of_collection'], 
                $_POST['amount'],
                $current_kolkata_time
            );
            
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Entry saved successfully!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Prepare statement failed: ' . $conn->error]);
        }
        exit;
    }

    // 2. FETCH DATA FOR EDITING (By Reg No & Book No)
    if ($_POST['action'] === 'fetch_edit_data') {
        $stmt = $conn->prepare("SELECT * FROM form4_entries WHERE reg_no = ? AND book_no = ? ORDER BY id DESC LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("ss", $_POST['reg_no'], $_POST['book_no']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($data = $result->fetch_assoc()) {
                echo json_encode(['status' => 'success', 'data' => $data]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No record found with that Reg No and Book No.']);
            }
            $stmt->close();
        }
        exit;
    }

    // 3. UPDATE ENTRY
    if ($_POST['action'] === 'update_entry') {
        $stmt = $conn->prepare("UPDATE form4_entries SET beneficiary_name = ?, amount = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("sdi", $_POST['beneficiary_name'], $_POST['amount'], $_POST['edit_id']);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update database.']);
            }
            $stmt->close();
        }
        exit;
    }
}

// FETCH LATEST DATA (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    if ($_GET['action'] === 'get_latest') {
        $result = $conn->query("SELECT * FROM form4_entries ORDER BY id DESC LIMIT 1");
        if ($result) {
            $latest = $result->fetch_assoc();
            if ($latest) echo json_encode(['status' => 'success', 'data' => $latest]);
            else echo json_encode(['status' => 'empty', 'message' => 'No entries found.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#ffffff">
    <title>BMSSY SERVICE | Form 4 - Add New</title>

    <style>
        /* =========================================
           Reset & Material Design 3 Variables
           ========================================= */
        :root {
            --md-sys-color-background: #f6f8fa;
            --md-sys-color-surface: #ffffff;
            --md-sys-color-primary: #0d9488; /* Teal for Form 4 */
            --md-sys-color-on-primary: #ffffff;
            --md-sys-color-primary-container: #ccfbf1;
            --md-sys-color-on-primary-container: #115e59;
            --md-sys-color-on-surface: #1f1f1f;
            --md-sys-color-on-surface-variant: #44474e;
            --md-sys-color-outline: #74777f;
            
            --color-success: #146c2e;
            --color-error: #b3261e;
            
            --app-radius-lg: 24px;
            --app-radius-md: 12px;
            --sidebar-width: 280px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Roboto", sans-serif; background-color: var(--md-sys-color-background); color: var(--md-sys-color-on-surface); overflow-x: hidden; }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(0, 0, 0, 0.2); border-radius: 4px; }

        /* =========================================
           Layout Scaffold
           ========================================= */
        .app-scaffold { display: flex; height: 100vh; width: 100vw; overflow: hidden; }
        .sidebar-overlay { position: fixed; inset: 0; background: rgba(0, 0, 0, 0.4); z-index: 90; opacity: 0; visibility: hidden; transition: all 0.3s ease; }
        .sidebar-overlay.active { opacity: 1; visibility: visible; }
        
        .app-main { flex: 1; display: flex; flex-direction: column; height: 100%; margin-left: var(--sidebar-width); transition: margin 0.3s cubic-bezier(0.2, 0, 0, 1); }
        .content-scroll { flex: 1; overflow-y: auto; padding: 24px; }
        .max-w-container { max-width: 900px; margin: 0 auto; width: 100%; }

        /* --- App Top Bar --- */
        .app-topbar { height: 72px; padding: 0 24px; display: flex; align-items: center; justify-content: space-between; background: var(--md-sys-color-surface); position: sticky; top: 0; z-index: 50; box-shadow: 0 1px 2px rgba(0,0,0,0.02); }
        .topbar-left { display: flex; align-items: center; gap: 16px; }
        .page-title { font-size: 20px; font-weight: 600; white-space: nowrap; }
        .page-title small { font-weight: 400; color: var(--md-sys-color-on-surface-variant); font-size: 14px; margin-left: 6px; }
        
        .menu-btn { background: none; border: none; width: 40px; height: 40px; border-radius: 50%; display: none; align-items: center; justify-content: center; cursor: pointer; color: var(--md-sys-color-on-surface-variant); transition: background 0.2s; }
        .menu-btn:active { background: rgba(0,0,0,0.08); }
        .menu-btn svg { width: 24px; height: 24px; fill: currentColor; }
        
        .user-profile { display: flex; align-items: center; gap: 12px; }
        .user-info { text-align: right; }
        .user-name { font-size: 14px; font-weight: 600; }
        .user-role { font-size: 12px; color: var(--md-sys-color-on-surface-variant); }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--md-sys-color-primary-container); color: var(--md-sys-color-primary); display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0; }

        /* =========================================
           Cards & Forms (Material Design 3)
           ========================================= */
        .md-card { background: var(--md-sys-color-surface); border-radius: var(--app-radius-lg); padding: 32px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03); margin-bottom: 24px; position: relative; overflow: hidden; }
        
        /* Ticker Card (Teal for Form 4) */
        .ticker-card { border-left: 4px solid var(--md-sys-color-primary); padding: 16px 24px; display: flex; flex-direction: column; gap: 8px;}
        .ticker-header { display: flex; justify-content: space-between; align-items: center; }
        .ticker-label { font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--md-sys-color-primary); letter-spacing: 0.5px; }
        .ticker-content { display: flex; align-items: center; gap: 12px; font-size: 15px; font-weight: 500; flex-wrap: wrap;}
        .pulse-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--md-sys-color-primary); animation: pulse 1.5s infinite; flex-shrink: 0; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(13, 148, 136, 0.4); } 70% { box-shadow: 0 0 0 8px rgba(13, 148, 136, 0); } 100% { box-shadow: 0 0 0 0 rgba(13, 148, 136, 0); } }

        /* Icon Button */
        .icon-btn { background: var(--md-sys-color-primary-container); color: var(--md-sys-color-primary); border: none; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; }
        .icon-btn:active { transform: scale(0.9); }
        .icon-btn svg { width: 18px; height: 18px; fill: currentColor; }

        /* Form Grid */
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-label { font-size: 14px; font-weight: 600; color: var(--md-sys-color-on-surface); }
        
        .input-wrapper { position: relative; display: flex; align-items: stretch; min-height: 52px; }
        .input-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); width: 20px; height: 20px; fill: var(--md-sys-color-outline); pointer-events: none; transition: fill 0.2s; }
        
        .md-input { width: 100%; background: var(--md-sys-color-background); border: 2px solid transparent; border-radius: var(--app-radius-md); padding: 16px 16px 16px 48px; font-size: 16px; color: var(--md-sys-color-on-surface); transition: all 0.2s; outline: none; }
        .md-input:focus { background: var(--md-sys-color-surface); border-color: var(--md-sys-color-primary); }
        .md-input:focus ~ .input-icon, .md-input:focus + .input-icon { fill: var(--md-sys-color-primary); }
        .md-input[readonly] { opacity: 0.6; pointer-events: none; }
        
        .md-input.rupee-input { padding-left: 36px; font-weight: 600; font-family: monospace; font-size: 18px;}
        .rupee-symbol { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); font-size: 18px; font-weight: bold; color: var(--md-sys-color-outline); pointer-events: none;}
        .md-input:focus ~ .rupee-symbol { color: var(--md-sys-color-primary); }

        /* Dual Date Wrapper */
        .dual-date-wrapper { display: flex; gap: 12px; align-items: center; }
        .dual-date-wrapper .input-wrapper { flex: 1; }
        .dual-separator { font-weight: 700; color: var(--md-sys-color-outline); font-size: 14px;}

        /* Buttons with Loaders */
        .btn-action { position: relative; overflow: hidden; display: inline-flex; align-items: center; justify-content: center; padding: 16px 24px; border-radius: 100px; font-size: 16px; font-weight: 600; border: none; cursor: pointer; transition: transform 0.1s, box-shadow 0.2s; color: #fff; width: 100%; margin-top: 16px; }
        .btn-action:active { transform: scale(0.98); }
        .btn-action:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }
        .btn-primary { background: var(--md-sys-color-primary); box-shadow: 0 4px 12px rgba(13, 148, 136, 0.2); }
        
        .btn-content { display: flex; align-items: center; justify-content: center; gap: 8px; transition: opacity 0.2s; }
        .btn-content svg { width: 20px; height: 20px; fill: currentColor; }
        .btn-spinner { position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); width: 24px; height: 24px; border: 3px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.8s linear infinite; opacity: 0; visibility: hidden; transition: opacity 0.2s; }
        .btn-action.loading .btn-content { opacity: 0; visibility: hidden; }
        .btn-action.loading .btn-spinner { opacity: 1; visibility: visible; }

        /* =========================================
           Native Modal Dialog
           ========================================= */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; display: flex; align-items: center; justify-content: center; padding: 24px; opacity: 0; visibility: hidden; transition: all 0.3s ease; }
        .modal-overlay.show { opacity: 1; visibility: visible; }
        .modal-content { background: var(--md-sys-color-surface); width: 100%; max-width: 500px; border-radius: var(--app-radius-lg); padding: 24px; transform: translateY(20px) scale(0.95); transition: all 0.3s cubic-bezier(0.2, 0, 0, 1); box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        .modal-overlay.show .modal-content { transform: translateY(0) scale(1); }
        
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid var(--md-sys-color-background); padding-bottom: 16px; }
        .modal-title { font-size: 18px; font-weight: 700; color: var(--md-sys-color-on-surface); }
        .btn-close { background: none; border: none; cursor: pointer; color: var(--md-sys-color-outline); padding: 4px; }
        .btn-close svg { width: 24px; height: 24px; fill: currentColor; }
        
        .modal-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; border-top: 1px solid var(--md-sys-color-background); padding-top: 16px; }
        .btn-text { background: transparent; color: var(--md-sys-color-on-surface-variant); border: none; font-weight: 600; padding: 10px 16px; cursor: pointer; border-radius: 100px; transition: 0.2s; }
        .btn-text:active { background: rgba(0,0,0,0.05); }

        /* Search Section in Modal */
        .search-area { display: flex; gap: 8px; margin-bottom: 24px; }
        .search-area .md-input { padding: 12px 16px; min-height: auto; }
        .search-area .btn-action { margin-top: 0; width: auto; border-radius: var(--app-radius-md); padding: 0 20px;}

        /* =========================================
           Native Snackbar / Toast
           ========================================= */
        #app-snackbar { position: fixed; bottom: -100px; left: 50%; transform: translateX(-50%); background: #313033; color: #f4eff4; padding: 14px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 10000; transition: bottom 0.4s cubic-bezier(0.2, 0, 0, 1); display: flex; align-items: center; gap: 12px; width: max-content; max-width: 90%; }
        #app-snackbar.show { bottom: 24px; }
        .snackbar-icon { width: 20px; height: 20px; flex-shrink: 0; }
        .snack-success { fill: #81c995; }
        .snack-error { fill: #ffb4ab; }

        @media (max-width: 1024px) { .app-main { margin-left: 0; } .menu-btn { display: flex; } }
        @media (max-width: 600px) { 
            .user-info { display: none; } 
            .content-scroll { padding: 16px; } 
            .app-topbar { padding: 0 16px; } 
            .md-card { padding: 20px; } 
            .form-grid { grid-template-columns: 1fr; gap: 16px;} 
            .search-area { flex-direction: column; } 
            .dual-date-wrapper { flex-direction: column; align-items: stretch; gap: 8px;}
            .dual-separator { display: none; }
        }
    </style>
</head>
<body>

    <div class="app-scaffold">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php include('../../inc/sideber.php'); ?>

        <main class="app-main">
            <header class="app-topbar">
                <div class="topbar-left">
                    <button class="menu-btn" id="menuBtn"><svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg></button>
                    <h1 class="page-title">Form 4 <small>| Add New Entry</small></h1>
                </div>
                <div class="user-profile">
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
                        <div class="user-role">Administrator</div>
                    </div>
                    <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                </div>
            </header>

            <div class="content-scroll">
                <div class="max-w-container">
                    
                    <div class="md-card ticker-card">
                        <div class="ticker-header">
                            <div class="ticker-label">Latest Saved Entry</div>
                            <button class="icon-btn" onclick="openEditModal()" title="Edit an Entry">
                                <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                            </button>
                        </div>
                        <div class="ticker-content" id="latestData">
                            <div class="pulse-dot"></div>
                            <span style="opacity: 0.7;">Fetching latest data...</span>
                        </div>
                    </div>

                    <div class="md-card">
                        <form id="form4-entry">
                            <div class="form-grid">
                                
                                <div class="form-group">
                                    <label class="form-label">Registration No.</label>
                                    <div class="input-wrapper">
                                        <input type="text" name="reg_no" id="reg_no" class="md-input font-mono" placeholder="Enter Reg No" required autofocus>
                                        <svg class="input-icon" viewBox="0 0 24 24"><path d="M4 6h16v12H4zm2 2v8h12V8H6zm2 2h3v2H8v-2zm0 4h8v2H8v-2zm5-4h3v2h-3v-2z"/></svg>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Name of Beneficiary</label>
                                    <div class="input-wrapper">
                                        <input type="text" name="beneficiary_name" id="beneficiary_name" class="md-input" placeholder="Full Name" style="text-transform: uppercase;" required>
                                        <svg class="input-icon" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Book No.</label>
                                    <div class="input-wrapper">
                                        <input type="number" name="book_no" id="book_no" class="md-input" placeholder="e.g. 1024" >
                                        <svg class="input-icon" viewBox="0 0 24 24"><path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H8V4h12v12zM10 9h8v2h-8zm0 3h4v2h-4zm0-6h8v2h-8z"/></svg>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Receipt No.</label>
                                    <div class="input-wrapper">
                                        <input type="number" name="receipt_no" id="receipt_no" class="md-input" placeholder="e.g. 549" >
                                        <svg class="input-icon" viewBox="0 0 24 24"><path d="M18 17H6v-2h12v2zm0-4H6v-2h12v2zm0-4H6V7h12v2zM3 22l1.5-1.5L6 22l1.5-1.5L9 22l1.5-1.5L12 22l1.5-1.5L15 22l1.5-1.5L18 22l1.5-1.5L21 22V2l-1.5 1.5L18 2l-1.5 1.5L15 2l-1.5 1.5L12 2l-1.5 1.5L9 2 7.5 3.5 6 2 4.5 3.5 3 2v20z"/></svg>
                                    </div>
                                </div>

                                <div class="form-group full-width">
                                    <label class="form-label">For the Period (Date to Date)</label>
                                    <div class="dual-date-wrapper">
                                        <div class="input-wrapper">
                                            <input type="date" name="for_month_from" id="for_month_from" class="md-input" required>
                                            <svg class="input-icon" viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg>
                                        </div>
                                        <div class="dual-separator">TO</div>
                                        <div class="input-wrapper">
                                            <input type="date" name="for_month_to" id="for_month_to" class="md-input" required>
                                            <svg class="input-icon" viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Date of Collection</label>
                                    <div class="input-wrapper">
                                        <input type="date" name="date_of_collection" id="date_of_collection" class="md-input" required>
                                        <svg class="input-icon" viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Amount</label>
                                    <div class="input-wrapper">
                                        <span class="rupee-symbol">₹</span>
                                        <input type="number" step="0.01" name="amount" id="amount" class="md-input rupee-input" placeholder="0.00" required>
                                    </div>
                                </div>

                                <div class="form-group full-width">
                                    <button type="submit" id="submitBtn" class="btn-action btn-primary">
                                        <span class="btn-content">
                                            <svg viewBox="0 0 24 24"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
                                            Save Entry
                                        </span>
                                        <div class="btn-spinner"></div>
                                    </button>
                                </div>

                            </div>
                        </form>
                    </div>
                </div>
                <div style="height: 40px;"></div>
            </div>
        </main>
    </div>

    <div id="editModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Search & Edit Entry</div>
                <button type="button" class="btn-close" onclick="closeEditModal()"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"/></svg></button>
            </div>
            
            <div class="search-area">
                <input type="text" id="search_reg_no" class="md-input font-mono" placeholder="Reg No">
                <input type="number" id="search_book_no" class="md-input" placeholder="Book No">
                <button type="button" id="searchBtn" class="btn-action btn-primary" onclick="searchEntry()">
                    <span class="btn-content"><svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg></span>
                    <div class="btn-spinner"></div>
                </button>
            </div>

            <form id="editForm" class="hidden">
                <input type="hidden" name="edit_id" id="edit_id">
                
                <div class="form-group">
                    <label class="form-label">Beneficiary Name</label>
                    <input type="text" name="beneficiary_name" id="edit_name" class="md-input" style="text-transform: uppercase;" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Amount</label>
                    <div class="input-wrapper">
                        <span class="rupee-symbol">₹</span>
                        <input type="number" step="0.01" name="amount" id="edit_amount" class="md-input rupee-input" required>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-text" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" id="submitEditBtn" class="btn-action btn-primary" style="border-radius: 100px; padding: 10px 24px; width: auto; margin-top: 0;">
                        <span class="btn-content">Update</span>
                        <div class="btn-spinner"></div>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="app-snackbar">
        <svg id="snack-icon-svg" class="snackbar-icon" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
        <span id="snackbar-message">Message</span>
    </div>

    <script>
        // ==========================================
        // UI & UTILITIES
        // ==========================================
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

        let snackbarTimer;
        function showSnackbar(message, type = 'error') {
            const snackbar = document.getElementById('app-snackbar');
            const msgEl = document.getElementById('snackbar-message');
            const iconEl = document.getElementById('snack-icon-svg');
            
            msgEl.innerText = message;
            if(type === 'success') {
                iconEl.classList.add('snack-success'); iconEl.classList.remove('snack-error');
                iconEl.innerHTML = '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>';
            } else {
                iconEl.classList.add('snack-error'); iconEl.classList.remove('snack-success');
                iconEl.innerHTML = '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>';
            }
            snackbar.classList.add('show');
            clearTimeout(snackbarTimer);
            snackbarTimer = setTimeout(() => { snackbar.classList.remove('show'); }, 3000);
        }

        function formatDate(dateStr) {
            if(!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        }

        // ==========================================
        // DATA LOGIC
        // ==========================================
        const entryForm = document.getElementById('form4-entry');
        const submitBtn = document.getElementById('submitBtn');
        const latestDataContainer = document.getElementById('latestData');

        async function fetchLatestEntry() {
            try {
                const res = await fetch('?action=get_latest');
                const json = await res.json();

                if (json.status === 'success') {
                    const d = json.data;
                    latestDataContainer.dataset.reg = d.reg_no;
                    latestDataContainer.dataset.book = d.book_no;
                    
                    latestDataContainer.innerHTML = `
                        <div class="pulse-dot"></div>
                        <span style="font-family:monospace; color:var(--md-sys-color-primary); background:var(--md-sys-color-primary-container); padding:2px 8px; border-radius:4px;">${d.reg_no}</span>
                        <strong>${d.beneficiary_name}</strong> 
                        <span style="opacity:0.5">|</span> 
                        <span>Book: <strong>${d.book_no}</strong></span>
                        <span style="opacity:0.5">|</span> 
                        <span>Rec: <strong>${d.receipt_no}</strong></span>
                        <span style="opacity:0.5">|</span> 
                        <span style="color:var(--color-success); font-weight:700;">₹${parseFloat(d.amount).toFixed(2)}</span>
                        <span style="font-size:12px; opacity:0.6; width:100%; display:block; margin-top:4px;">
                            For Period: <strong>${d.for_month}</strong> • Collected: ${formatDate(d.date_of_collection)}
                        </span>
                    `;
                } else {
                    latestDataContainer.innerHTML = `<span style="opacity:0.6;">No entries saved yet. Fill the form below to start.</span>`;
                }
            } catch (err) {
                latestDataContainer.innerHTML = `<span style="color:var(--color-error);">Failed to load latest entry.</span>`;
            }
        }

        entryForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;

            try {
                const formData = new URLSearchParams(new FormData(entryForm));
                formData.append('action', 'save_entry');

                const res = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData
                });

                const json = await res.json();

                if (json.status === 'success') {
                    showSnackbar('Entry saved successfully!', 'success');
                    
                    entryForm.reset();
                    document.getElementById('date_of_collection').valueAsDate = new Date();
                    document.getElementById('reg_no').focus();
                    
                    await fetchLatestEntry();
                } else {
                    showSnackbar(json.message || 'Failed to save entry', 'error');
                }
            } catch (err) {
                showSnackbar('Network Error. Please try again.', 'error');
            } finally {
                submitBtn.classList.remove('loading');
                submitBtn.disabled = false;
            }
        });

        // ==========================================
        // EDIT MODAL LOGIC
        // ==========================================
        function openEditModal() {
            const reg = latestDataContainer.dataset.reg || '';
            const book = latestDataContainer.dataset.book || '';
            
            document.getElementById('search_reg_no').value = reg;
            document.getElementById('search_book_no').value = book;
            document.getElementById('editForm').classList.add('hidden'); 
            document.getElementById('editModal').classList.add('show');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('show');
            document.getElementById('editForm').reset();
            document.getElementById('editForm').classList.add('hidden');
        }

        async function searchEntry() {
            const regNo = document.getElementById('search_reg_no').value.trim();
            const bookNo = document.getElementById('search_book_no').value.trim();
            const searchBtn = document.getElementById('searchBtn');

            if (!regNo || !bookNo) {
                showSnackbar('Please enter both Reg No and Book No.', 'error');
                return;
            }

            searchBtn.classList.add('loading');
            searchBtn.disabled = true;

            try {
                const formData = new URLSearchParams({ action: 'fetch_edit_data', reg_no: regNo, book_no: bookNo });
                const res = await fetch(window.location.href, { method: 'POST', body: formData });
                const json = await res.json();
                
                if (json.status === 'success') {
                    document.getElementById('edit_id').value = json.data.id;
                    document.getElementById('edit_name').value = json.data.beneficiary_name;
                    document.getElementById('edit_amount').value = parseFloat(json.data.amount).toFixed(2);
                    document.getElementById('editForm').classList.remove('hidden'); 
                } else {
                    document.getElementById('editForm').classList.add('hidden');
                    showSnackbar(json.message || "Entry not found.", "error");
                }
            } catch (err) {
                showSnackbar('Network Error.', 'error');
            } finally {
                searchBtn.classList.remove('loading');
                searchBtn.disabled = false;
            }
        }

        document.getElementById('editForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('submitEditBtn');
            btn.classList.add('loading');
            btn.disabled = true;
            
            try {
                const formData = new URLSearchParams(new FormData(document.getElementById('editForm')));
                formData.append('action', 'update_entry');
                
                const res = await fetch(window.location.href, { method: 'POST', body: formData });
                const json = await res.json();
                
                if (json.status === 'success') {
                    showSnackbar("Updated successfully!", "success");
                    closeEditModal();
                    await fetchLatestEntry(); 
                } else {
                    showSnackbar(json.message || "Update failed.", "error");
                }
            } catch (err) {
                showSnackbar("Network error.", "error");
            } finally {
                btn.classList.remove('loading');
                btn.disabled = false;
            }
        });

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('date_of_collection').valueAsDate = new Date();
            fetchLatestEntry();
        });

    </script>
</body>
</html>