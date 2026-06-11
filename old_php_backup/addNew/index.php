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

// --- 3. Database Connection ---
$db_path = $_SERVER['DOCUMENT_ROOT'] . '/database/index.php';
if (file_exists($db_path)) {
    require_once $db_path;
} else {
    die("Critical Error: Database connection file missing."); 
}

// --- 4. Auto Bug Detection & Database Logging ---
if (isset($pdo)) {
    try {
        // Auto-create bug tracking table
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
        $pdo->exec($createTableSQL);

        // A. Handle Warnings and Notices
        function customErrorHandler($errno, $errstr, $errfile, $errline) {
            global $pdo;
            $url = $_SERVER['REQUEST_URI'] ?? 'Unknown';
            $stmt = $pdo->prepare("INSERT INTO system_bug_logs (error_level, error_message, file_name, line_number, request_url) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute(["PHP Error [$errno]", $errstr, $errfile, $errline, $url]);
            return true; 
        }
        set_error_handler("customErrorHandler");

        // B. Handle Exceptions
        function customExceptionHandler($exception) {
            global $pdo;
            $url = $_SERVER['REQUEST_URI'] ?? 'Unknown';
            $stmt = $pdo->prepare("INSERT INTO system_bug_logs (error_level, error_message, file_name, line_number, request_url) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute(["PHP Exception", $exception->getMessage(), $exception->getFile(), $exception->getLine(), $url]);
        }
        set_exception_handler("customExceptionHandler");

        // C. Handle Fatal Errors
        function customFatalErrorHandler() {
            global $pdo;
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                $url = $_SERVER['REQUEST_URI'] ?? 'Unknown';
                $errorLevel = "PHP Fatal Error [" . $error['type'] . "]";
                $stmt = $pdo->prepare("INSERT INTO system_bug_logs (error_level, error_message, file_name, line_number, request_url) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$errorLevel, $error['message'], $error['file'], $error['line'], $url]);
            }
        }
        register_shutdown_function("customFatalErrorHandler");

    } catch (PDOException $e) {
        error_log("Bug Logger Setup failed: " . $e->getMessage());
    }
} else {
    error_log("Bug Logger failed: PDO connection not found in database/index.php");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#ffffff">
    <title>BMSSY SERVICE | Add New</title>

    <style>
        /* =========================================
           Reset & Material Design 3 Variables
           ========================================= */
        :root {
            --md-sys-color-background: #f6f8fa;
            --md-sys-color-surface: #ffffff;
            --md-sys-color-primary: #0b57d0;
            --md-sys-color-on-primary: #ffffff;
            --md-sys-color-primary-container: #d3e3fd;
            --md-sys-color-on-primary-container: #041e49;
            --md-sys-color-on-surface: #1f1f1f;
            --md-sys-color-on-surface-variant: #44474e;
            --md-sys-color-outline: #74777f;
            
            /* Status Colors */
            --color-success: #146c2e;
            --color-success-bg: #c4eed0;
            --color-error: #b3261e;
            --color-error-bg: #f9dedc;
            
            --app-radius-lg: 24px;
            --app-radius-md: 16px;
            --sidebar-width: 280px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Roboto", "Segoe UI", Helvetica, Arial, sans-serif;
            background-color: var(--md-sys-color-background);
            color: var(--md-sys-color-on-surface);
            overflow-x: hidden;
        }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(0, 0, 0, 0.2); border-radius: 4px; }

        /* =========================================
           Layout Scaffold
           ========================================= */
        .app-scaffold {
            display: flex;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
        }

        .sidebar-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 90;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        .sidebar-overlay.active { opacity: 1; visibility: visible; }

        .app-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100%;
            margin-left: var(--sidebar-width);
            transition: margin 0.3s cubic-bezier(0.2, 0, 0, 1);
        }

        /* --- App Top Bar --- */
        .app-topbar {
            height: 72px;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--md-sys-color-surface);
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        }

        .topbar-left { display: flex; align-items: center; gap: 16px; }
        .page-title { font-size: 20px; font-weight: 600; white-space: nowrap; }

        .menu-btn {
            background: none; border: none; width: 40px; height: 40px; border-radius: 50%;
            display: none; align-items: center; justify-content: center; cursor: pointer;
            color: var(--md-sys-color-on-surface-variant); transition: background 0.2s;
        }
        .menu-btn:active { background: rgba(0,0,0,0.08); }
        .menu-btn svg { width: 24px; height: 24px; fill: currentColor; }

        .user-profile { display: flex; align-items: center; gap: 12px; }
        .user-info { text-align: right; }
        .user-name { font-size: 14px; font-weight: 600; }
        .user-role { font-size: 12px; color: var(--md-sys-color-on-surface-variant); }
        .user-avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background: var(--md-sys-color-primary-container); color: var(--md-sys-color-primary);
            display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;
        }

        /* --- Content Area --- */
        .content-scroll {
            flex: 1; overflow-y: auto; padding: 24px;
        }

        .max-w-container {
            max-width: 800px;
            margin: 0 auto;
            width: 100%;
        }

        /* =========================================
           Cards & Forms (Material Design 3)
           ========================================= */
        .md-card {
            background: var(--md-sys-color-surface);
            border-radius: var(--app-radius-lg);
            padding: 32px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
        }

        .card-header {
            display: flex; align-items: center; gap: 12px; margin-bottom: 24px;
            font-size: 20px; font-weight: 700; color: var(--md-sys-color-primary);
        }
        .card-header svg { width: 28px; height: 28px; fill: currentColor; flex-shrink: 0; }
        .card-header small { color: var(--md-sys-color-on-surface-variant); font-size: 14px; font-weight: 400; }

        /* Form Inputs */
        .form-group { margin-bottom: 20px; }
        .form-label {
            display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;
            color: var(--md-sys-color-on-surface);
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: stretch;
            min-height: 52px;
        }

        .input-icon {
            position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
            width: 20px; height: 20px; fill: var(--md-sys-color-outline);
            pointer-events: none; transition: fill 0.2s;
        }

        .md-input {
            width: 100%;
            background: var(--md-sys-color-background);
            border: 2px solid transparent;
            border-radius: var(--app-radius-md);
            padding: 16px 16px 16px 48px;
            font-size: 16px; color: var(--md-sys-color-on-surface);
            transition: all 0.2s; outline: none;
        }
        .md-input:focus {
            background: var(--md-sys-color-surface);
            border-color: var(--md-sys-color-primary);
        }
        .md-input:focus ~ .input-icon, .md-input:focus + .input-icon { fill: var(--md-sys-color-primary); }
        .md-input[readonly] { opacity: 0.6; pointer-events: none; }

        .input-with-btn .md-input {
            border-top-right-radius: 0; border-bottom-right-radius: 0;
        }
        .input-action-btn {
            background: var(--md-sys-color-surface-variant, #e1e3e8);
            border: none; padding: 0 20px;
            border-top-right-radius: var(--app-radius-md); border-bottom-right-radius: var(--app-radius-md);
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: background 0.2s; color: var(--md-sys-color-on-surface-variant);
        }
        .input-action-btn:active { background: #c2c5cc; }
        .input-action-btn svg { width: 20px; height: 20px; fill: currentColor; }

        /* Buttons */
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            padding: 16px 24px; border-radius: 100px; font-size: 15px; font-weight: 600;
            border: none; cursor: pointer; transition: transform 0.1s, box-shadow 0.2s;
            width: 100%; height: 52px;
        }
        .btn:active { transform: scale(0.98); }
        .btn svg { width: 20px; height: 20px; fill: currentColor; }
        
        .btn-primary { background: var(--md-sys-color-primary); color: var(--md-sys-color-on-primary); }
        .btn-success { background: var(--color-success); color: #ffffff; }

        .hidden { display: none !important; }
        .animate-fade-in { animation: fadeIn 0.4s cubic-bezier(0.2, 0, 0, 1) forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* =========================================
           Status Profile Card
           ========================================= */
        .status-card {
            border-radius: var(--app-radius-lg);
            padding: 24px;
            color: #ffffff;
            display: flex;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 24px;
        }
        .status-active { background: linear-gradient(135deg, #146c2e, #2e8b49); }
        .status-inactive { background: linear-gradient(135deg, #b3261e, #dc362d); }
        .status-default { background: linear-gradient(135deg, #0b57d0, #4285f4); }

        .status-icon {
            width: 64px; height: 64px; border-radius: 50%;
            background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .status-icon svg { width: 32px; height: 32px; fill: #ffffff; }

        .status-details { flex: 1; min-width: 0; }
        .status-details h4 { font-size: 20px; margin-bottom: 16px; border-bottom: 1px solid rgba(255,255,255,0.3); padding-bottom: 8px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        
        .status-grid { display: flex; flex-direction: column; gap: 10px; font-size: 14px; }
        .status-grid p { display: flex; align-items: flex-start; gap: 8px; word-wrap: break-word; }
        .status-grid svg { width: 16px; height: 16px; fill: rgba(255,255,255,0.7); flex-shrink: 0; margin-top: 2px; }
        .status-val { margin-left: auto; text-align: right; font-weight: 500; word-break: break-word; }

        /* =========================================
           Responsive Table
           ========================================= */
        .md-table-wrapper { overflow-x: auto; border-radius: 12px; border: 1px solid var(--md-sys-color-background); }
        .md-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .md-table th, .md-table td { padding: 16px; text-align: left; border-bottom: 1px solid var(--md-sys-color-background); }
        .md-table th { background: var(--md-sys-color-background); font-weight: 600; color: var(--md-sys-color-on-surface-variant); text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
        .md-table tfoot td { font-weight: 700; background: var(--md-sys-color-background); border: none; }

        /* =========================================
           Native Snackbar / Toast
           ========================================= */
        #app-snackbar {
            position: fixed; bottom: -100px; left: 50%; transform: translateX(-50%);
            background: #313033; color: #f4eff4; padding: 14px 20px; border-radius: 8px;
            font-size: 14px; font-weight: 500; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000; transition: bottom 0.4s cubic-bezier(0.2, 0, 0, 1);
            display: flex; align-items: center; gap: 12px; width: max-content; max-width: 90%;
        }
        #app-snackbar.show { bottom: 24px; }
        .snackbar-icon { width: 20px; height: 20px; flex-shrink: 0; }
        .snack-success { fill: #81c995; }
        .snack-error { fill: #ffb4ab; }

        /* =========================================
           Mobile App View Overrides
           ========================================= */
        @media (max-width: 1024px) {
            .app-main { margin-left: 0; }
            .menu-btn { display: flex; }
        }
        
        @media (max-width: 768px) {
            .md-table-wrapper { border: none; background: transparent; overflow-x: visible; }
            .md-table thead { display: none; }
            .md-table, .md-table tbody, .md-table tr, .md-table td { display: block; width: 100%; }
            .md-table tr { 
                margin-bottom: 16px; background: var(--md-sys-color-background); 
                border-radius: 12px; border: 1px solid rgba(0,0,0,0.05); 
            }
            .md-table td {
                display: flex; justify-content: space-between; align-items: center;
                padding: 12px 16px; border-bottom: 1px solid rgba(0,0,0,0.05); text-align: right !important;
            }
            .md-table td::before {
                content: attr(data-label); font-weight: 600; color: var(--md-sys-color-on-surface-variant); 
                text-transform: uppercase; font-size: 11px; text-align: left; margin-right: 12px;
            }
            .md-table td:last-child { border-bottom: none; }
            
            .md-table tfoot { display: block; width: 100%; }
            .md-table tfoot tr { background: var(--md-sys-color-primary-container); border: none; }
            .md-table tfoot td { display: flex; justify-content: space-between; padding: 16px; font-size: 16px; }
            .md-table tfoot td.hide-on-mobile { display: none; }
        }

        @media (max-width: 600px) {
            .user-info { display: none; }
            .content-scroll { padding: 16px; }
            .md-card { padding: 20px; }
            
            .status-card { padding: 16px; gap: 12px; }
            .status-icon { width: 48px; height: 48px; }
            .status-icon svg { width: 24px; height: 24px; }
            .status-details h4 { font-size: 18px; margin-bottom: 12px; }
            
            .app-topbar { padding: 0 16px; }
        }
    </style>
</head>
<body>

    <div class="app-scaffold">
        
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <script>
            window.PAGE_IDENTIFIER = '/addNew';
        </script>
        <?php include('../inc/sideber.php'); ?>

        <main class="app-main">
            
            <header class="app-topbar">
                <div class="topbar-left">
                    <button class="menu-btn" id="menuBtn">
                        <svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
                    </button>
                    <h1 class="page-title">Add New SSIN</h1>
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
                <div class="max-w-container">
                    
                    <div class="md-card">
                        <div class="card-header">
                            <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 14h-3v3h-2v-3H8v-2h3v-3h2v3h3v2zm-3-7V3.5L18.5 9H13z"/></svg>
                            ADD DATA <small>SSIN</small>
                        </div>

                        <form id="ssinForm">
                            <div class="form-group">
                                <label class="form-label" for="ssinInput">SSIN Number</label>
                                <div class="input-wrapper">
                                    <input type="number" name="ssin" id="ssinInput" placeholder="Enter 12-digit SSIN" maxlength="12" required class="md-input">
                                    <svg class="input-icon" viewBox="0 0 24 24"><path d="M4 6h16v12H4zm2 2v8h12V8H6zm2 2h3v2H8v-2zm0 4h8v2H8v-2zm5-4h3v2h-3v-2z"/></svg>
                                </div>
                            </div>

                            <div id="newData" class="hidden animate-fade-in">
                                
                                <div class="form-group">
                                    <label class="form-label" for="nameInput">Name</label>
                                    <div class="input-wrapper">
                                        <input type="text" name="name" id="nameInput" placeholder="Enter Name" style="text-transform: uppercase;" class="md-input">
                                        <svg class="input-icon" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="dateInput">Date of Attaining 60</label>
                                    <div class="input-wrapper input-with-btn">
                                        <input type="date" name="date" id="dateInput" class="md-input">
                                        <svg class="input-icon" viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg>
                                        <button type="button" id="generateDate" class="input-action-btn">
                                            <svg viewBox="0 0 24 24"><path d="M10.59 9.17L5.41 4 4 5.41l5.17 5.17 1.42-1.41zM14.5 4l2.04 2.04L4 18.59 5.41 20 17.96 7.46 20 9.5V4h-5.5zm.33 9.41l-1.41 1.41 3.13 3.13L14.5 20H20v-5.5l-2.04 2.04-3.13-3.13z"/></svg>
                                        </button>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="phoneInput">Phone Number</label>
                                    <div class="input-wrapper input-with-btn">
                                        <input type="number" name="phone" id="phoneInput" placeholder="Enter Phone Number" class="md-input">
                                        <svg class="input-icon" viewBox="0 0 24 24"><path d="M17 1.01L7 1c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-1.99-2-1.99zM17 19H7V5h10v14z"/></svg>
                                        <button type="button" id="generatePhone" class="input-action-btn">
                                            <svg viewBox="0 0 24 24"><path d="M10.59 9.17L5.41 4 4 5.41l5.17 5.17 1.42-1.41zM14.5 4l2.04 2.04L4 18.59 5.41 20 17.96 7.46 20 9.5V4h-5.5zm.33 9.41l-1.41 1.41 3.13 3.13L14.5 20H20v-5.5l-2.04 2.04-3.13-3.13z"/></svg>
                                        </button>
                                    </div>
                                </div>

                            </div>

                            <div style="margin-top: 32px;">
                                <button type="button" id="checkSSIN" class="btn btn-primary">
                                    <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                                    Check SSIN
                                </button>
                                <button type="submit" id="submitBtn" class="btn btn-success hidden">
                                    <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                                    Submit Data
                                </button>
                            </div>
                        </form>
                    </div>

                    <div id="existingData" class="hidden animate-fade-in">
                        
                        <div id="statusCard" class="status-card status-default">
                            <div class="status-icon">
                                <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/><path d="M16 11l3-3 4 4" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </div>
                            <div class="status-details">
                                <h4>SSIN Found</h4>
                                <div class="status-grid">
                                    <p><svg viewBox="0 0 24 24"><path d="M4 6h16v12H4zm2 2v8h12V8H6z"/></svg> <strong style="color:#fff;">SSIN:</strong> <span class="status-val" id="approvedSSIN"></span></p>
                                    <p><svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg> <strong style="color:#fff;">Name:</strong> <span class="status-val" id="beneficiaryName"></span></p>
                                    <p><svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg> <strong style="color:#fff;">Date of 60:</strong> <span class="status-val" id="dateOf60"></span></p>
                                    <p><svg viewBox="0 0 24 24"><path d="M17 1.01L7 1c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-1.99-2-1.99zM17 19H7V5h10v14z"/></svg> <strong style="color:#fff;">Phone:</strong> <span class="status-val" id="phoneNo"></span></p>
                                    <p><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg> <strong style="color:#fff;">Status:</strong> <span class="status-val" id="status" style="text-transform: uppercase;"></span></p>
                                    <p><svg viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg> <strong style="color:#fff;">Updated:</strong> <span class="status-val" id="lastUpdate"></span></p>
                                </div>
                            </div>
                        </div>

                        <div id="pfCard" class="md-card hidden">
                            <div class="card-header" style="margin-bottom: 16px; font-size: 16px;">
                                <svg viewBox="0 0 24 24"><path d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/></svg>
                                Provident Fund Updates
                            </div>
                            <div class="md-table-wrapper">
                                <table class="md-table" id="pfTable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Period From</th>
                                            <th>Period To</th>
                                            <th style="text-align:center;">Months</th>
                                            <th style="text-align:right;">Amount (₹)</th>
                                            <th>Updated On</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4" class="hide-on-mobile" style="text-align:right; text-transform:uppercase;">Total Amount:</td>
                                            <td id="totalAmount" data-label="TOTAL AMOUNT" style="text-align:right; color: var(--color-success); font-weight: 700;">₹0.00</td>
                                            <td class="hide-on-mobile"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
                <div style="height: 40px;"></div>
            </div>
        </main>
    </div>

    <div id="app-snackbar">
        <svg id="snack-icon-svg" class="snackbar-icon" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
        <span id="snackbar-message">Message</span>
    </div>

    <script>
        // --- Sidebar Mobile Drawer Logic ---
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

        // --- Active Link Override Check (Hooking to Sidebar Script) ---
        document.addEventListener("DOMContentLoaded", () => {
            const currentDashLink = document.querySelector(`a[href="${window.PAGE_IDENTIFIER}"]`);
            if (currentDashLink) {
                currentDashLink.classList.add('active');
            }
        });

        // --- Snackbar Utility ---
        let snackbarTimer;
        function showSnackbar(message, type = 'error') {
            const snackbar = document.getElementById('app-snackbar');
            const msgEl = document.getElementById('snackbar-message');
            const iconEl = document.getElementById('snack-icon-svg');
            
            msgEl.innerText = message;
            
            if(type === 'success') {
                iconEl.classList.add('snack-success');
                iconEl.classList.remove('snack-error');
                iconEl.innerHTML = '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>';
            } else {
                iconEl.classList.add('snack-error');
                iconEl.classList.remove('snack-success');
                iconEl.innerHTML = '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>';
            }

            snackbar.classList.add('show');
            clearTimeout(snackbarTimer);
            snackbarTimer = setTimeout(() => { snackbar.classList.remove('show'); }, 4000);
        }

        // --- Date Formatter ---
        function formatDateTime(dateString) {
            const date = new Date(dateString);
            const options = { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true };
            return date.toLocaleString('en-GB', options).replace(',', '').replace(':', '.');
        }

        // --- App Logic ---
        document.addEventListener('DOMContentLoaded', () => {
            const checkBtn = document.getElementById('checkSSIN');
            const submitBtn = document.getElementById('submitBtn');
            const ssinInput = document.getElementById('ssinInput');
            const ssinForm = document.getElementById('ssinForm');
            const newDataSection = document.getElementById('newData');
            const existingDataSection = document.getElementById('existingData');
            const pfCard = document.getElementById('pfCard');

            function updateStatusCard(status) {
                const card = document.getElementById("statusCard");
                card.classList.remove("status-active", "status-inactive", "status-default");
                
                const stat = status.toLowerCase();
                if (stat === "active") card.classList.add("status-active");
                else if (stat === "inactive" || stat === "rejected") card.classList.add("status-inactive");
                else card.classList.add("status-default");
            }

            checkBtn.addEventListener('click', async () => {
                const ssinVal = ssinInput.value.trim();
                
                if (ssinVal.length !== 12) {
                    showSnackbar('SSIN must be exactly 12 digits', 'error');
                    return;
                }

                try {
                    const formData = new URLSearchParams();
                    formData.append('ssin', ssinVal);

                    const response = await fetch('check_ssin.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: formData
                    });
                    
                    const data = await response.json();

                    if (data.exists) {
                        document.getElementById('approvedSSIN').innerText = data.ssin;
                        document.getElementById('beneficiaryName').innerText = data.name;
                        document.getElementById('dateOf60').innerText = data.date_of_attaining_60;
                        document.getElementById('phoneNo').innerText = data.phone_no;
                        document.getElementById('status').innerText = data.status;
                        document.getElementById('lastUpdate').innerText = formatDateTime(data.last_update);
                        
                        updateStatusCard(data.status);
                        
                        existingDataSection.classList.remove('hidden');
                        newDataSection.classList.add('hidden');
                        submitBtn.classList.add('hidden');

                        const tbody = document.querySelector('#pfTable tbody');
                        tbody.innerHTML = '';
                        
                        if (data.pf_updates && data.pf_updates.length > 0) {
                            pfCard.classList.remove('hidden');
                            let total = 0;

                            data.pf_updates.forEach((update, index) => {
                                const tr = document.createElement('tr');
                                tr.innerHTML = `
                                    <td data-label="#">${index + 1}</td>
                                    <td data-label="Period From">${update.period_from}</td>
                                    <td data-label="Period To">${update.period_to}</td>
                                    <td data-label="Months" style="text-align:center;">${update.months}</td>
                                    <td data-label="Amount" style="text-align:right; font-family: monospace; font-weight:600;">₹${parseFloat(update.amount).toFixed(2)}</td>
                                    <td data-label="Updated On" style="color: var(--md-sys-color-on-surface-variant); font-size:12px;">${formatDateTime(update.last_update)}</td>
                                `;
                                tbody.appendChild(tr);
                                total += parseFloat(update.amount);
                            });
                            document.getElementById('totalAmount').innerText = `₹${total.toFixed(2)}`;
                        } else {
                            pfCard.classList.add('hidden');
                        }

                    } else {
                        ssinInput.readOnly = true;
                        checkBtn.classList.add('hidden');
                        existingDataSection.classList.add('hidden');
                        newDataSection.classList.remove('hidden');
                        submitBtn.classList.remove('hidden');
                        pfCard.classList.add('hidden');
                        
                        document.getElementById('nameInput').required = true;
                        document.getElementById('dateInput').required = true;
                        document.getElementById('phoneInput').required = true;
                    }

                } catch (error) {
                    console.error(error);
                    showSnackbar('System error checking SSIN', 'error');
                }
            });

            document.getElementById('generateDate').addEventListener('click', () => {
                const start = new Date(2043, 0, 1).getTime();
                const end = new Date(2052, 0, 1).getTime();
                const randomDate = new Date(start + Math.random() * (end - start));
                document.getElementById('dateInput').value = randomDate.toISOString().split('T')[0];
            });

            document.getElementById('generatePhone').addEventListener('click', () => {
                const phone = '9' + Math.floor(100000000 + Math.random() * 900000000);
                document.getElementById('phoneInput').value = phone;
            });

            ssinForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                try {
                    const formData = new URLSearchParams(new FormData(ssinForm));

                    const response = await fetch('submit_ssin.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: formData
                    });
                    
                    const result = await response.json();

                    if (result.success) {
                        showSnackbar('Saved successfully!', 'success');
                        
                        ssinForm.reset();
                        newDataSection.classList.add('hidden');
                        submitBtn.classList.add('hidden');
                        checkBtn.classList.remove('hidden');
                        ssinInput.readOnly = false;
                        existingDataSection.classList.add('hidden');
                    } else {
                        showSnackbar(result.error || 'Submission failed', 'error');
                    }

                } catch (error) {
                    console.error(error);
                    showSnackbar('Network error during submission', 'error');
                }
            });
        });
    </script>
</body>
</html>