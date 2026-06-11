<?php
// --- 1. Error Reporting Configuration ---
// Force PHP to report all errors, but hide them from the user interface
error_reporting(E_ALL);
ini_set('display_errors', 0); // Set to 1 ONLY during local development
ini_set('log_errors', 1);     // Fallback to standard PHP error log if DB fails

// --- 2. Security Enhancements & Session Management ---
session_start();

// Basic HTTP Security Headers
header("X-Frame-Options: SAMEORIGIN"); // Prevents clickjacking
header("X-XSS-Protection: 1; mode=block"); // Enables browser XSS filtering
header("X-Content-Type-Options: nosniff"); // Prevents MIME-type sniffing
header("Strict-Transport-Security: max-age=31536000; includeSubDomains"); // Enforces HTTPS

// Authentication Check
if (!isset($_SESSION["user_id"])) {
    // Secure redirect to login
    header("Location: /");
    exit();
}

// Session Fixation Protection (Regenerate ID every 30 minutes)
if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
} else if (time() - $_SESSION['CREATED'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['CREATED'] = time();
}

$user_name = isset($_SESSION["user_name"]) ? $_SESSION["user_name"] : "Admin User";

// --- 3. Database Connection ---
// Securely include your database connection from the root directory
$db_path = $_SERVER['DOCUMENT_ROOT'] . '/database/index.php';
if (file_exists($db_path)) {
    require_once $db_path;
} else {
    // We die here because without the DB, the dashboard cannot load anyway
    die("Critical Error: Database connection file missing."); 
}

// --- 4. Auto Bug Detection & Database Logging ---
// Ensure the $pdo variable was created by database/index.php
if (isset($pdo)) {
    try {
        // Auto-create bug tracking table if it doesn't exist
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

        // A. Handle Standard Errors (Warnings, Notices)
        function customErrorHandler($errno, $errstr, $errfile, $errline) {
            global $pdo;
            $url = $_SERVER['REQUEST_URI'] ?? 'Unknown';
            $stmt = $pdo->prepare("INSERT INTO system_bug_logs (error_level, error_message, file_name, line_number, request_url) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute(["PHP Error [$errno]", $errstr, $errfile, $errline, $url]);
            return true; // Return true to prevent the default PHP error handler from also running
        }
        set_error_handler("customErrorHandler");

        // B. Handle Uncaught Exceptions
        function customExceptionHandler($exception) {
            global $pdo;
            $url = $_SERVER['REQUEST_URI'] ?? 'Unknown';
            $stmt = $pdo->prepare("INSERT INTO system_bug_logs (error_level, error_message, file_name, line_number, request_url) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute(["PHP Exception", $exception->getMessage(), $exception->getFile(), $exception->getLine(), $url]);
        }
        set_exception_handler("customExceptionHandler");

        // C. Handle Fatal Errors (Syntax errors, Memory limits, Undefined functions)
        function customFatalErrorHandler() {
            global $pdo;
            $error = error_get_last();
            
            // Check if there was an error and if it was a fatal type
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                $url = $_SERVER['REQUEST_URI'] ?? 'Unknown';
                
                // Map the error code to a readable string
                $errorLevel = "PHP Fatal Error [" . $error['type'] . "]";
                
                $stmt = $pdo->prepare("INSERT INTO system_bug_logs (error_level, error_message, file_name, line_number, request_url) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$errorLevel, $error['message'], $error['file'], $error['line'], $url]);
            }
        }
        register_shutdown_function("customFatalErrorHandler");

    } catch (PDOException $e) {
        // If the logger setup fails, log it to the server's default PHP log
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
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="theme-color" content="#ffffff">
    <title>BMSSY SERVICE | Dashboard</title>

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
            --md-sys-color-secondary-container: #c2e7ff;
            --md-sys-color-on-surface: #1f1f1f;
            --md-sys-color-on-surface-variant: #44474e;
            
            /* Status Colors */
            --color-success: #146c2e;
            --color-success-bg: #c4eed0;
            --color-warning: #b36b00;
            --color-warning-bg: #ffe0b2;
            --color-error: #b3261e;
            --color-error-bg: #f9dedc;
            --color-info: #00639b;
            --color-info-bg: #cce5ff;
            
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

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
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

        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* --- Main Content Area --- */
        .app-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100%;
            margin-left: var(--sidebar-width);
            transition: margin 0.3s cubic-bezier(0.2, 0, 0, 1);
        }

        /* --- App Top Bar (Header) --- */
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

        .menu-btn {
            background: none;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--md-sys-color-on-surface-variant);
            transition: background 0.2s;
        }
        .menu-btn:active { background: rgba(0,0,0,0.08); }
        .menu-btn svg { width: 24px; height: 24px; fill: currentColor; }

        .page-title { font-size: 20px; font-weight: 600; }

        .user-profile { display: flex; align-items: center; gap: 12px; }
        .user-info { text-align: right; }
        .user-name { font-size: 14px; font-weight: 600; color: var(--md-sys-color-on-surface); }
        .user-role { font-size: 12px; color: var(--md-sys-color-on-surface-variant); }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--md-sys-color-primary-container);
            color: var(--md-sys-color-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        /* --- Scrollable Content --- */
        .content-scroll {
            flex: 1;
            overflow-y: auto;
            padding: 24px;
        }

        .section-title {
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--md-sys-color-on-surface-variant);
            margin: 32px 0 16px 4px;
        }

        /* =========================================
           Grid & Card System
           ========================================= */
        .grid { display: grid; gap: 16px; }
        .grid-2 { grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
        .grid-3 { grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
        .grid-4 { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); }

        .md-card {
            background: var(--md-sys-color-surface);
            border-radius: var(--app-radius-lg);
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .md-card:active { transform: scale(0.98); }

        .card-row { display: flex; justify-content: space-between; align-items: center; }

        .card-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--md-sys-color-on-surface-variant);
            margin-bottom: 8px;
        }
        .card-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--md-sys-color-on-surface);
        }
        .card-value small { font-size: 18px; font-weight: 500; }

        .icon-circle {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .icon-circle svg { width: 24px; height: 24px; }

        .bg-blue { background: var(--color-info-bg); color: var(--color-info); fill: var(--color-info); }
        .bg-red { background: var(--color-error-bg); color: var(--color-error); fill: var(--color-error); }
        .bg-green { background: var(--color-success-bg); color: var(--color-success); fill: var(--color-success); }
        .bg-yellow { background: var(--color-warning-bg); color: var(--color-warning); fill: var(--color-warning); }

        .deco-circle {
            position: absolute;
            top: -20px;
            right: -20px;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            opacity: 0.4;
            pointer-events: none;
        }

        /* =========================================
           Mobile Responsiveness
           ========================================= */
        @media (max-width: 1024px) {
            .app-main { margin-left: 0; }
            .menu-btn { display: flex; }
        }
        @media (max-width: 600px) {
            .user-info { display: none; }
            .md-card { padding: 20px; }
            .card-value { font-size: 28px; }
        }
    </style>
</head>
<body>

    <div class="app-scaffold">
        
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <script>
            window.PAGE_IDENTIFIER = '/dashboard';
        </script>
        <?php include('../inc/sideber.php'); ?>

        <main class="app-main">
            
            <header class="app-topbar">
                <div class="topbar-left">
                    <button class="menu-btn" id="menuBtn">
                        <svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
                    </button>
                    <h1 class="page-title">Overview</h1>
                </div>

                <div class="user-profile">
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="user-role">Administrator</div>
                    </div>
                    <div class="user-avatar">
                        <?php echo htmlspecialchars(strtoupper(substr($user_name, 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </div>
            </header>

            <div class="content-scroll">
                
                <div class="grid grid-2" style="margin-bottom: 8px;">
                    <div class="md-card">
                        <div class="card-row">
                            <div>
                                <div class="card-label">CPU TRAFFIC</div>
                                <div class="card-value" id="cpuUsage">--<small>%</small></div>
                            </div>
                            <div class="icon-circle bg-blue">
                                <svg viewBox="0 0 24 24"><path d="M19.43 12.98c.04-.32.07-.64.07-.98 0-.34-.03-.66-.07-.98l2.11-1.65c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.3-.61-.22l-2.49 1c-.52-.4-1.08-.73-1.69-.98l-.38-2.65C14.46 2.18 14.25 2 14 2h-4c-.25 0-.46.18-.49.42l-.38 2.65c-.61.25-1.17.59-1.69.98l-2.49-1c-.23-.09-.49 0-.61.22l-2 3.46c-.13.22-.07.49.12.64l2.11 1.65c-.04.32-.07.65-.07.98s.03.66.07.98l-2.11 1.65c-.19.15-.24.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1c.52.4 1.08.73 1.69.98l.38 2.65c.03.24.24.42.49.42h4c.25 0 .46-.18.49-.42l.38-2.65c.61-.25 1.17-.59 1.69-.98l2.49 1c.23.09.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.65zM12 15.5c-1.93 0-3.5-1.57-3.5-3.5s1.57-3.5 3.5-3.5 3.5 1.57 3.5 3.5-1.57 3.5-3.5 3.5z"/></svg>
                            </div>
                        </div>
                    </div>

                    <div class="md-card">
                        <div class="card-row">
                            <div>
                                <div class="card-label">RAM USAGE</div>
                                <div class="card-value" id="ramUsage">--<small>%</small></div>
                            </div>
                            <div class="icon-circle bg-red">
                                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="section-title">SSIN Statistics</div>
                <div class="grid grid-4">
                    <div class="md-card">
                        <div class="card-label" style="color: var(--color-success);">OTHERS</div>
                        <div class="card-value" id="count142">--</div>
                    </div>
                    <div class="md-card">
                        <div class="card-label" style="color: var(--color-warning);">CONTRACTIONS</div>
                        <div class="card-value" id="count242">--</div>
                    </div>
                    <div class="md-card">
                        <div class="card-label" style="color: var(--color-info);">NEW OTHERS</div>
                        <div class="card-value" id="newssincount142">--</div>
                    </div>
                    <div class="md-card">
                        <div class="card-label" style="color: var(--color-error);">NEW CONST.</div>
                        <div class="card-value" id="newssincount242">--</div>
                    </div>
                </div>

                <div class="section-title">Performance Data</div>
                <div class="grid grid-3">
                    <div class="md-card">
                        <div class="deco-circle bg-green"></div>
                        <div class="card-label">PF OTHERS</div>
                        <div class="card-value" id="acceptedssincount142">--</div>
                    </div>
                    <div class="md-card">
                        <div class="deco-circle bg-yellow"></div>
                        <div class="card-label">PF CONTRACTIONS</div>
                        <div class="card-value" id="acceptedssincount242">--</div>
                    </div>
                    <div class="md-card">
                        <div class="deco-circle bg-red"></div>
                        <div class="card-label">PF REJECTED</div>
                        <div class="card-value" style="color: var(--color-error);" id="totalRejected">--</div>
                    </div>
                </div>

                <div class="section-title">Activity Timeline</div>
                <div class="grid grid-3">
                    <div class="md-card" style="align-items: center; text-align: center;">
                        <div class="card-label">NEW ADD (TODAY)</div>
                        <div class="card-value" style="color: var(--color-info);" id="totalToday">--</div>
                    </div>
                    <div class="md-card" style="align-items: center; text-align: center;">
                        <div class="card-label">NEW ADD (YESTERDAY)</div>
                        <div class="card-value" style="color: #6750a4;" id="totalYesterday">--</div>
                    </div>
                    <div class="md-card" style="align-items: center; text-align: center;">
                        <div class="card-label">NEW ADD (MONTH)</div>
                        <div class="card-value" style="color: #b3261e;" id="totalThisMonth">--</div>
                    </div>
                </div>

                <div style="height: 40px;"></div> 
            </div>
        </main>
    </div>

    <script>
        // --- Fallback Sidebar Mobile Drawer Logic ---
        const menuBtn = document.getElementById('menuBtn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        function toggleSidebar() {
            if (sidebar) sidebar.classList.toggle('open');
            if (overlay) overlay.classList.toggle('active');
        }

        if(menuBtn) menuBtn.addEventListener('click', toggleSidebar);
        if(overlay) overlay.addEventListener('click', toggleSidebar);

        // --- Active Link Override Check (Hooking to Sidebar Script) ---
        // Just in case the sidebar script misses it, we explicitly assign active state here based on our PAGE_IDENTIFIER
        document.addEventListener("DOMContentLoaded", () => {
            const currentDashLink = document.querySelector(`a[href="${window.PAGE_IDENTIFIER}"]`);
            if (currentDashLink) {
                currentDashLink.classList.add('active');
            }
        });

        // --- Data Fetching Logic (Secured with Graceful Failures) ---
        // Utility function to fetch JSON safely
        async function fetchSafeJson(url, callback) {
            try {
                const response = await fetch(url);
                if (!response.ok) throw new Error(`HTTP Error: ${response.status}`);
                const data = await response.json();
                callback(data);
            } catch (err) {
                console.warn(`Data fetch warning for ${url}:`, err);
                // The PHP Error Logger backend will handle actual server errors.
            }
        }

        function fetchActiveSSINs() {
            fetchSafeJson("ssinreport.php", data => {
                document.getElementById("totalToday").innerText = data.totalToday || '0';
                document.getElementById("totalYesterday").innerText = data.totalYesterday || '0';
                document.getElementById("totalThisMonth").innerText = data.totalThisMonth || '0';
            });
        }

        function fetchTotalRejected() {
            fetchSafeJson("fetch_total_rejected.php", data => {
                document.getElementById("totalRejected").innerText = data.totalRejected || '0';
            });
        }

        function fetchAcceptedSSIN() {
            fetchSafeJson("fetch_accepted_ssin.php", data => {
                document.getElementById("acceptedssincount142").innerText = data.acceptedssincount142 || '0';
                document.getElementById("acceptedssincount242").innerText = data.acceptedssincount242 || '0';
            });
        }

        function fetchMonthlySSIN() {
            fetchSafeJson("fetch_monthly_ssin.php", data => {
                document.getElementById("newssincount142").innerText = data.newssincount142 || '0';
                document.getElementById("newssincount242").innerText = data.newssincount242 || '0';
            });
        }

        function fetchSSINCounts() {
            fetchSafeJson("count_ssin.php", data => {
                document.getElementById("count142").innerText = data.count_142 || '0';
                document.getElementById("count242").innerText = data.count_242 || '0';
            });
        }

        function fetchSystemUsage() {
            fetchSafeJson("system_usage.php", data => {
                document.getElementById("cpuUsage").innerHTML = (data.cpu_usage || '0') + '<small>%</small>';
                document.getElementById("ramUsage").innerHTML = (data.ram_usage || '0') + '<small>%</small>';
            });
        }

        // Initialize Fetch Calls
        document.addEventListener('DOMContentLoaded', () => {
            fetchActiveSSINs();
            fetchTotalRejected();
            fetchAcceptedSSIN();
            fetchMonthlySSIN();
            fetchSSINCounts();
            fetchSystemUsage();
            
            // Set Intervals to update data silently
            setInterval(fetchSSINCounts, 5000);
            setInterval(fetchSystemUsage, 2000);
        });
    </script>
</body>
</html>