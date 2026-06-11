<?php
// Report all PHP errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Force Kolkata Timezone
date_default_timezone_set('Asia/Kolkata');

session_start();
if (!isset($_SESSION["user_id"])) {
    // header("Location: /"); // Uncomment for production
    // exit();
}

$user_name = isset($_SESSION["user_name"]) ? $_SESSION["user_name"] : "Admin User";

// ==========================================
// AJAX API ENDPOINT FOR HTML TABLE
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch_data') {
    require_once '../../database/index.php'; 
    header('Content-Type: application/json');

    $date_type = isset($_GET['date_type']) && $_GET['date_type'] === 'created_at' ? 'created_at' : 'date_of_collection';
    $from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
    $to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

    try {
        if (!empty($from_date) && !empty($to_date)) {
            // Adjust bounds based on column type
            if ($date_type === 'created_at') {
                $query_from_date = $from_date . ' 00:00:00';
                $query_to_date = $to_date . ' 23:59:59';
            } else {
                $query_from_date = $from_date;
                $query_to_date = $to_date;
            }
            
            $stmt = $conn->prepare("SELECT * FROM form4_entries WHERE $date_type BETWEEN ? AND ? ORDER BY $date_type ASC");
            $stmt->bind_param("ss", $query_from_date, $query_to_date);
        } else {
            // If no dates selected, just show last 50
            $stmt = $conn->prepare("SELECT * FROM form4_entries ORDER BY id DESC LIMIT 50");
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode(['status' => 'success', 'data' => $data]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#ffffff">
    <title>BMSSY SERVICE | Download Form 4</title>

    <style>
        /* Material Design 3 Variables */
        :root {
            --md-sys-color-background: #f6f8fa;
            --md-sys-color-surface: #ffffff;
            --md-sys-color-primary: #0d9488; /* Teal */
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

        .app-scaffold { display: flex; height: 100vh; width: 100vw; overflow: hidden; }
        .sidebar-overlay { position: fixed; inset: 0; background: rgba(0, 0, 0, 0.4); z-index: 90; opacity: 0; visibility: hidden; transition: all 0.3s ease; }
        .sidebar-overlay.active { opacity: 1; visibility: visible; }
        
        .app-main { flex: 1; display: flex; flex-direction: column; height: 100%; margin-left: var(--sidebar-width); transition: margin 0.3s cubic-bezier(0.2, 0, 0, 1); }
        .content-scroll { flex: 1; overflow-y: auto; padding: 24px; }
        .max-w-container { max-width: 1200px; margin: 0 auto; width: 100%; }

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

        .md-card { background: var(--md-sys-color-surface); border-radius: var(--app-radius-lg); padding: 24px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03); margin-bottom: 24px; position: relative; overflow: hidden; }

        /* Filter Form */
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; align-items: end; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-label { font-size: 14px; font-weight: 600; color: var(--md-sys-color-on-surface); }
        
        .md-input { width: 100%; background: var(--md-sys-color-background); border: 2px solid transparent; border-radius: var(--app-radius-md); padding: 14px 16px; font-size: 14px; color: var(--md-sys-color-on-surface); transition: all 0.2s; outline: none; min-height: 48px;}
        .md-input:focus { background: var(--md-sys-color-surface); border-color: var(--md-sys-color-primary); }

        /* Radio Buttons (Material Segmented Button Style) */
        .radio-group { display: flex; background: var(--md-sys-color-background); border-radius: var(--app-radius-md); padding: 4px; gap: 4px; min-height: 48px; }
        .radio-label { flex: 1; text-align: center; border-radius: 8px; padding: 10px; font-size: 13px; font-weight: 600; color: var(--md-sys-color-on-surface-variant); cursor: pointer; transition: 0.2s; }
        .radio-group input[type="radio"] { display: none; }
        .radio-group input[type="radio"]:checked + .radio-label { background: var(--md-sys-color-surface); color: var(--md-sys-color-primary); box-shadow: 0 2px 6px rgba(0,0,0,0.05); }

        /* Action Buttons */
        .btn-action { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 14px 20px; border-radius: 100px; font-size: 14px; font-weight: 600; border: none; cursor: pointer; transition: transform 0.1s, box-shadow 0.2s; color: #fff; width: 100%; min-height: 48px; text-decoration: none;}
        .btn-action:active { transform: scale(0.98); }
        .btn-primary { background: var(--md-sys-color-primary); box-shadow: 0 4px 12px rgba(13, 148, 136, 0.2); }
        .btn-danger { background: #b3261e; box-shadow: 0 4px 12px rgba(179, 38, 30, 0.2); }
        .btn-action svg { width: 18px; height: 18px; fill: currentColor; }

        /* Table */
        .md-table-wrapper { width: 100%; overflow-x: auto; border-radius: var(--app-radius-md); border: 1px solid var(--md-sys-color-background); margin-top: 24px;}
        .md-table { width: 100%; border-collapse: collapse; text-align: left; }
        .md-table th, .md-table td { padding: 12px 16px; border-bottom: 1px solid var(--md-sys-color-background); font-size: 14px;}
        .md-table th { font-size: 12px; font-weight: 600; text-transform: uppercase; color: var(--md-sys-color-on-surface-variant); background: var(--md-sys-color-background); }
        .font-mono { font-family: monospace; font-weight: 600; color: var(--md-sys-color-primary); }

        /* Snackbar */
        #app-snackbar { position: fixed; bottom: -100px; left: 50%; transform: translateX(-50%); background: #313033; color: #f4eff4; padding: 14px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 10000; transition: bottom 0.4s cubic-bezier(0.2, 0, 0, 1); display: flex; align-items: center; gap: 12px; width: max-content; max-width: 90%; }
        #app-snackbar.show { bottom: 24px; }
        .snackbar-icon { width: 20px; height: 20px; fill: #ffb4ab; flex-shrink: 0;}

        @media (max-width: 1024px) { .app-main { margin-left: 0; } .menu-btn { display: flex; } }
        @media (max-width: 768px) {
            .md-table-wrapper { border: none; background: transparent; overflow-x: visible; }
            .md-table thead { display: none; }
            .md-table, .md-table tbody, .md-table tr, .md-table td { display: block; width: 100%; }
            .md-table tr { margin-bottom: 16px; background: var(--md-sys-color-surface); border-radius: var(--app-radius-md); border: 1px solid rgba(0,0,0,0.08); padding: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
            .md-table td { display: flex; flex-direction: column; align-items: flex-start; padding: 10px 8px; border-bottom: 1px dashed rgba(0,0,0,0.05); }
            .md-table td::before { content: attr(data-label); font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--md-sys-color-on-surface-variant); margin-bottom: 6px; }
            .md-table td:last-child { border-bottom: none; }
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
                    <h1 class="page-title">Form 4 <small>| Download PDF</small></h1>
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
                    
                    <div class="md-card">
                        
                        <form id="filterForm" action="generate_pdf.php" method="GET" target="_blank">
                            <div class="filter-grid">
                                
                                <div class="form-group">
                                    <label class="form-label">Filter By</label>
                                    <div class="radio-group">
                                        <input type="radio" name="date_type" id="dt_collection" value="date_of_collection">
                                        <label class="radio-label" for="dt_collection">Date of Coll.</label>
                                        
                                        <input type="radio" name="date_type" id="dt_created" value="created_at" checked>
                                        <label class="radio-label" for="dt_created">Create Date</label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">From Date</label>
                                    <input type="date" name="from_date" id="from_date" class="md-input" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">To Date</label>
                                    <input type="date" name="to_date" id="to_date" class="md-input" required>
                                </div>

                                <div class="form-group">
                                    <button type="button" id="previewBtn" class="btn-action btn-primary">
                                        <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                                        Search Preview
                                    </button>
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn-action btn-danger">
                                        <svg viewBox="0 0 24 24"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
                                        Generate PDF
                                    </button>
                                </div>

                            </div>
                        </form>

                        <div class="md-table-wrapper">
                            <table class="md-table">
                                <thead>
                                    <tr>
                                        <th>SL</th>
                                        <th>Reg. No</th>
                                        <th>Beneficiary Name</th>
                                        <th>Book No</th>
                                        <th>Receipt No</th>
                                        <th>For the Period</th>
                                        <th>Date of Coll.</th>
                                        <th style="text-align: right;">Amount (₹)</th>
                                    </tr>
                                </thead>
                                <tbody id="tableBody">
                                    <tr><td colspan="8" style="text-align:center; padding: 40px; color: var(--md-sys-color-outline);">Select dates and click Search Preview</td></tr>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
                <div style="height: 40px;"></div>
            </div>
        </main>
    </div>

    <div id="app-snackbar">
        <svg class="snackbar-icon" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
        <span id="snackbar-message">Message</span>
    </div>

    <script>
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

        function showSnackbar(message) {
            const snackbar = document.getElementById('app-snackbar');
            document.getElementById('snackbar-message').innerText = message;
            snackbar.classList.add('show');
            setTimeout(() => { snackbar.classList.remove('show'); }, 3000);
        }

        // Format Date to DD/MM/YYYY exactly like the PDF does
        function formatToDDMMYYYY(dateStr) {
            if(!dateStr) return '';
            const d = new Date(dateStr);
            const day = String(d.getDate()).padStart(2, '0');
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const year = d.getFullYear();
            return `${day}/${month}/${year}`;
        }

        // Format the Month Period string from DB (DD-MM-YYYY - DD-MM-YYYY) into (DD/MM/YYYY - DD/MM/YYYY)
        function formatPeriodString(dbString) {
            if(!dbString) return '';
            if (dbString.includes(' - ')) {
                const parts = dbString.split(' - ');
                return parts[0].replace(/-/g, '/') + ' - ' + parts[1].replace(/-/g, '/');
            }
            return dbString.replace(/-/g, '/');
        }

        // AJAX Table Preview Logic
        document.getElementById('previewBtn').addEventListener('click', async () => {
            const dateType = document.querySelector('input[name="date_type"]:checked').value;
            const fromDate = document.getElementById('from_date').value;
            const toDate = document.getElementById('to_date').value;

            if (!fromDate || !toDate) {
                showSnackbar('Please select From and To dates.');
                return;
            }

            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; padding: 20px;">Loading...</td></tr>`;

            try {
                const url = `?action=fetch_data&date_type=${dateType}&from_date=${fromDate}&to_date=${toDate}`;
                const res = await fetch(url);
                const json = await res.json();

                if (json.status === 'success') {
                    tbody.innerHTML = '';
                    if (json.data.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; padding: 20px; color: var(--md-sys-color-error);">No records found in this range.</td></tr>`;
                        return;
                    }

                    json.data.forEach((row, index) => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td data-label="SL">${index + 1}</td>
                            <td data-label="Reg. No" class="font-mono">${row.reg_no}</td>
                            <td data-label="Name">${row.beneficiary_name}</td>
                            <td data-label="Book No">${row.book_no}</td>
                            <td data-label="Receipt No">${row.receipt_no}</td>
                            <td data-label="For Month">${formatPeriodString(row.for_month)}</td>
                            <td data-label="Date of Coll.">${formatToDDMMYYYY(row.date_of_collection)}</td>
                            <td data-label="Amount" style="text-align:right; font-weight:bold;">${parseFloat(row.amount).toFixed(2)}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                } else {
                    showSnackbar(json.message);
                }
            } catch (e) {
                showSnackbar('Failed to fetch preview data.');
            }
        });
    </script>
</body>
</html>