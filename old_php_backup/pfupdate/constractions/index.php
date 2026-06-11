<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    // header("Location: /"); // Uncomment for production
    // exit();
}

$user_name = isset($_SESSION["user_name"]) ? $_SESSION["user_name"] : "Admin User";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#ffffff">
    <title>BMSSY SERVICE | PF Updation</title>

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
        .max-w-container { max-width: 1200px; margin: 0 auto; width: 100%; }

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
           Cards & Components
           ========================================= */
        .md-card { background: var(--md-sys-color-surface); border-radius: var(--app-radius-lg); padding: 24px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03); margin-bottom: 24px; position: relative; overflow: hidden; }
        
        /* Ticker Card */
        .ticker-card { border-left: 4px solid var(--md-sys-color-primary); padding: 16px 24px; }
        .ticker-label { font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--md-sys-color-primary); margin-bottom: 6px; letter-spacing: 0.5px; }
        .ticker-content { display: flex; align-items: center; gap: 12px; font-size: 15px; font-weight: 500; }
        .pulse-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--color-success); animation: pulse 1.5s infinite; flex-shrink: 0; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(20, 108, 46, 0.4); } 70% { box-shadow: 0 0 0 8px rgba(20, 108, 46, 0); } 100% { box-shadow: 0 0 0 0 rgba(20, 108, 46, 0); } }

        /* Search Bar */
        .table-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 16px; flex-wrap: wrap; }
        .search-box { position: relative; width: 100%; max-width: 320px; }
        .search-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); width: 20px; height: 20px; fill: var(--md-sys-color-outline); pointer-events: none; }
        .search-input { width: 100%; background: var(--md-sys-color-background); border: 2px solid transparent; border-radius: 100px; padding: 12px 16px 12px 48px; font-size: 14px; outline: none; transition: all 0.2s; }
        .search-input:focus { background: var(--md-sys-color-surface); border-color: var(--md-sys-color-primary); }

        /* Inputs */
        .md-input { width: 100%; background: var(--md-sys-color-background); border: 2px solid transparent; border-radius: var(--app-radius-md); padding: 12px; font-size: 14px; outline: none; transition: all 0.2s; color: var(--md-sys-color-on-surface); }
        .md-input:focus { border-color: var(--md-sys-color-primary); background: var(--md-sys-color-surface); }

        /* =========================================
           Buttons with Built-in Loaders
           ========================================= */
        .btn-action {
            position: relative; overflow: hidden; display: inline-flex; align-items: center; justify-content: center;
            padding: 10px 16px; border-radius: var(--app-radius-md); font-size: 14px; font-weight: 600;
            border: none; cursor: pointer; transition: transform 0.1s, background 0.2s; color: #fff; min-width: 60px; min-height: 40px;
        }
        .btn-action:active { transform: scale(0.95); }
        .btn-action:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        
        .btn-success { background: var(--color-success); }
        .btn-error { background: var(--color-error); }
        
        .btn-content { display: flex; align-items: center; justify-content: center; transition: opacity 0.2s; }
        .btn-content svg { width: 18px; height: 18px; fill: currentColor; }

        /* Spinner inside Button */
        .btn-spinner {
            position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%);
            width: 20px; height: 20px; border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff; border-radius: 50%;
            animation: spin 0.8s linear infinite;
            opacity: 0; visibility: hidden; transition: opacity 0.2s;
        }
        @keyframes spin { 0% { transform: translate(-50%, -50%) rotate(0deg); } 100% { transform: translate(-50%, -50%) rotate(360deg); } }
        
        /* Loading State Class */
        .btn-action.loading .btn-content { opacity: 0; visibility: hidden; }
        .btn-action.loading .btn-spinner { opacity: 1; visibility: visible; }

        /* =========================================
           Responsive Table
           ========================================= */
        .md-table-wrapper { width: 100%; overflow-x: auto; border-radius: var(--app-radius-md); border: 1px solid var(--md-sys-color-background); }
        .md-table { width: 100%; border-collapse: collapse; text-align: left; }
        .md-table th, .md-table td { padding: 16px; border-bottom: 1px solid var(--md-sys-color-background); vertical-align: middle; }
        .md-table th { font-size: 12px; font-weight: 600; text-transform: uppercase; color: var(--md-sys-color-on-surface-variant); background: var(--md-sys-color-background); }
        
        .font-mono { font-family: monospace; font-size: 15px; font-weight: 500; color: var(--md-sys-color-primary); }
        .action-cell { display: flex; gap: 8px; align-items: center; }

        /* =========================================
           New Modern Pagination UI Style Bar
           ========================================= */
        .pagination-bar { display: flex; justify-content: space-between; align-items: center; margin-top: 20px; gap: 16px; flex-wrap: wrap; padding: 4px; }
        .pagination-info { font-size: 14px; color: var(--md-sys-color-on-surface-variant); font-weight: 500; }
        .pagination-controls { display: flex; gap: 8px; align-items: center; }
        .btn-page { background: var(--md-sys-color-background); color: var(--md-sys-color-on-surface); border: none; padding: 10px 18px; border-radius: 100px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; }
        .btn-page:hover:not(:disabled) { background: var(--md-sys-color-primary-container); color: var(--md-sys-color-primary); }
        .btn-page:disabled { opacity: 0.4; cursor: not-allowed; }

        /* Mobile View: Convert rows to cards */
        @media (max-width: 768px) {
            .md-table-wrapper { border: none; background: transparent; overflow-x: visible; }
            .md-table thead { display: none; }
            .md-table, .md-table tbody, .md-table tr, .md-table td { display: block; width: 100%; }
            .md-table tr { margin-bottom: 16px; background: var(--md-sys-color-surface); border-radius: var(--app-radius-md); border: 1px solid rgba(0,0,0,0.08); padding: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
            .md-table td { display: flex; flex-direction: column; padding: 10px 8px; border-bottom: 1px dashed rgba(0,0,0,0.05); }
            .md-table td::before { content: attr(data-label); font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--md-sys-color-on-surface-variant); margin-bottom: 6px; }
            .md-table td:last-child { border-bottom: none; }
            
            .action-cell { flex-direction: row; width: 100%; justify-content: space-between; margin-top: 8px; }
            .btn-action { flex: 1; padding: 14px; }
            
            .table-toolbar { flex-direction: column; align-items: flex-start; }
            .search-box { max-width: 100%; }
            .pagination-bar { flex-direction: column; text-align: center; }
        }

        /* =========================================
           Native Modal Dialog
           ========================================= */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; display: flex; align-items: center; justify-content: center; padding: 24px; opacity: 0; visibility: hidden; transition: all 0.3s ease; }
        .modal-overlay.show { opacity: 1; visibility: visible; }
        .modal-content { background: var(--md-sys-color-surface); width: 100%; max-width: 400px; border-radius: var(--app-radius-lg); padding: 24px; transform: translateY(20px) scale(0.95); transition: all 0.3s cubic-bezier(0.2, 0, 0, 1); box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        .modal-overlay.show .modal-content { transform: translateY(0) scale(1); }
        
        .modal-header { display: flex; gap: 16px; margin-bottom: 20px; }
        .modal-icon { width: 48px; height: 48px; border-radius: 50%; background: rgba(179,38,30,0.1); color: var(--color-error); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .modal-icon svg { width: 24px; height: 24px; fill: currentColor; }
        .modal-title { font-size: 18px; font-weight: 700; color: var(--md-sys-color-on-surface); margin-bottom: 4px; }
        .modal-desc { font-size: 14px; color: var(--md-sys-color-on-surface-variant); }
        
        textarea.md-input { resize: vertical; min-height: 100px; }
        
        .modal-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; }
        .btn-text { background: transparent; color: var(--md-sys-color-on-surface-variant); border: none; font-weight: 600; padding: 10px 16px; cursor: pointer; border-radius: 100px; }
        .btn-text:active { background: rgba(0,0,0,0.05); }

        /* =========================================
           Native Snackbar / Toast
           ========================================= */
        #app-snackbar { position: fixed; bottom: -100px; left: 50%; transform: translateX(-50%); background: #313033; color: #f4eff4; padding: 14px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 10000; transition: bottom 0.4s cubic-bezier(0.2, 0, 0, 1); display: flex; align-items: center; gap: 12px; width: max-content; max-width: 90%; }
        #app-snackbar.show { bottom: 24px; }
        .snackbar-icon { width: 20px; height: 20px; flex-shrink: 0; }
        .snack-success { fill: #81c995; }
        .snack-error { fill: #ffb4ab; }

        @media (max-width: 1024px) {
            .app-main { margin-left: 0; }
            .menu-btn { display: flex; }
        }
        @media (max-width: 600px) {
            .user-info { display: none; }
            .content-scroll { padding: 16px; }
            .app-topbar { padding: 0 16px; }
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
                    <button class="menu-btn" id="menuBtn">
                        <svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
                    </button>
                    <h1 class="page-title">PF Updation <small>| Others</small></h1>
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
                        <div class="ticker-label">Latest Activity</div>
                        <div class="ticker-content" id="latestData">
                            <div class="pulse-dot"></div>
                            <span style="opacity: 0.7;">Loading latest data...</span>
                        </div>
                    </div>

                    <div class="md-card">
                        <div class="table-toolbar">
                            <h3 style="font-size: 18px; font-weight: 700;">Pending Updates</h3>
                            <div class="search-box">
                                <svg class="search-icon" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                                <input type="text" id="searchInput" class="search-input" placeholder="Search by name or SSIN...">
                            </div>
                        </div>
                        
                        <div class="md-table-wrapper">
                            <table class="md-table" id="dataTable">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>SSIN</th>
                                        <th>Period From</th>
                                        <th>Period To</th>
                                        <th style="text-align: right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="tableBody">
                                    <tr>
                                        <td colspan="5" style="text-align:center; padding: 40px; color: var(--md-sys-color-outline);">
                                            Loading records...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="pagination-bar">
                            <div class="pagination-info" id="paginationInfo">Showing 0 to 0 of 0 entries</div>
                            <div class="pagination-controls">
                                <button class="btn-page" id="btnPrev" disabled>Previous</button>
                                <button class="btn-page" id="btnNext" disabled>Next</button>
                            </div>
                        </div>

                    </div>
                </div>
                <div style="height: 40px;"></div>
            </div>
        </main>
    </div>

    <div id="rejectModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon">
                    <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                </div>
                <div>
                    <div class="modal-title">Reject Beneficiary</div>
                    <div class="modal-desc">Please provide a reason for rejecting this update.</div>
                </div>
            </div>
            
            <textarea id="rejectReason" class="md-input" placeholder="Type reason here..."></textarea>
            
            <div class="modal-actions">
                <button type="button" class="btn-text" onclick="closeRejectModal()">Cancel</button>
                <button type="button" id="submitReject" class="btn-action btn-error" style="border-radius: 100px; padding: 10px 24px;">
                    <span class="btn-content">Reject</span>
                    <div class="btn-spinner"></div>
                </button>
            </div>
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
            snackbarTimer = setTimeout(() => { snackbar.classList.remove('show'); }, 4000);
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.remove('show');
            document.getElementById('rejectReason').value = '';
        }

        // ==========================================
        // OPTIMIZED PAGINATION DATA ENGINE LOGIC
        // ==========================================
        let globalPeriodFrom = "";
        let globalPeriodTo = "";
        let tableDataCache = [];
        let filteredData = []; // Target filter arrays cache
        
        // Pagination Engine Setup Parameters
        let currentPage = 1;
        const rowsPerPage = 15; // Ekta page-e koto ta row dekhabe (Smooth, lag-free execution er jonno 15-20 optimal)
        
        let currentRejectSSIN = "";
        let currentRejectName = "";

        // 1. Fetch Settings
        async function fetchSettings() {
            try {
                const res = await fetch('fetch_global_settings.php');
                const json = await res.json();
                if (json.period_from && json.period_to) {
                    globalPeriodFrom = json.period_from;
                    globalPeriodTo = json.period_to;
                }
            } catch (err) { console.log('Settings fetch error'); }
        }

        // 2. Fetch Latest Ticker Data
        async function fetchLatestData() {
            try {
                const res = await fetch('fetch_last_data.php');
                const json = await res.json();
                if (json.data && Object.keys(json.data).length > 0) {
                    const d = json.data;
                    const periodStr = (d.period_form !== "Not Available" && d.period_to !== "Not Available") ? ` <span style="opacity:0.6; font-size:12px;">(${d.period_form} - ${d.period_to})</span>` : "";
                    const reasonStr = d.reason ? ` <span style="color:var(--color-error); font-size:12px;">| Reason: ${d.reason}</span>` : "";
                    const statusColor = d.status === 'Rejected' ? 'var(--color-error)' : 'var(--color-success)';
                    
                    document.getElementById('latestData').innerHTML = `
                        <div class="pulse-dot"></div>
                        <span><strong>${d.beneficiary_name}</strong> | <span style="font-family:monospace; color:var(--md-sys-color-primary);">${d.approved_ssin}</span> | <span style="color:${statusColor}; font-weight:700; text-transform:uppercase; font-size:12px;">${d.status}</span>${periodStr}${reasonStr}</span>
                    `;
                }
            } catch(e) {}
        }

        // 3. Render Table (Slices array into pagination rows chunk)
        function renderTable() {
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '';
            
            const totalRecords = filteredData.length;
            
            if(totalRecords === 0) {
                tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding: 40px; color: var(--md-sys-color-outline);">No matching records found.</td></tr>`;
                updatePaginationControls(0, 0, 0);
                return;
            }

            // Calculation of bounds index values
            const startIndex = (currentPage - 1) * rowsPerPage;
            const endIndex = Math.min(startIndex + rowsPerPage, totalRecords);
            
            // Only extract active chunk bounds
            const pageData = filteredData.slice(startIndex, endIndex);

            // DocumentFragment use kora hoyeche processing workload performance optimize korte
            const fragment = document.createDocumentFragment();

            pageData.forEach(row => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td data-label="Name"><strong>${row.beneficiary_name}</strong></td>
                    <td data-label="SSIN" class="font-mono">${row.approved_ssin}</td>
                    <td data-label="Period From">
                        <input type="date" class="md-input dt-from" value="${globalPeriodFrom}">
                    </td>
                    <td data-label="Period To">
                        <input type="date" class="md-input dt-to" value="${globalPeriodTo}">
                    </td>
                    <td data-label="Action">
                        <div class="action-cell" style="justify-content: flex-end;">
                            <button class="btn-action btn-success" onclick="handleAccept(this, '${row.approved_ssin}', '${row.beneficiary_name}')">
                                <span class="btn-content"><svg viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg></span>
                                <div class="btn-spinner"></div>
                            </button>
                            <button class="btn-action btn-error" onclick="handleRejectClick('${row.approved_ssin}', '${row.beneficiary_name}')">
                                <span class="btn-content"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"/></svg></span>
                            </button>
                        </div>
                    </td>
                `;
                fragment.appendChild(tr);
            });

            tbody.appendChild(fragment);
            updatePaginationControls(startIndex + 1, endIndex, totalRecords);
        }

        // 4. Update Pagination View Bar Control
        function updatePaginationControls(start, end, total) {
            document.getElementById('paginationInfo').innerText = `Showing ${start} to ${end} of ${total} entries`;
            
            const maxPage = Math.ceil(total / rowsPerPage);
            document.getElementById('btnPrev').disabled = (currentPage === 1 || total === 0);
            document.getElementById('btnNext').disabled = (currentPage === maxPage || total === 0);
        }

        // Pagination Buttons Event Listeners Setup
        document.getElementById('btnPrev').addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                renderTable();
            }
        });

        document.getElementById('btnNext').addEventListener('click', () => {
            const maxPage = Math.ceil(filteredData.length / rowsPerPage);
            if (currentPage < maxPage) {
                currentPage++;
                renderTable();
            }
        });

        // 5. Fetch Table Data
        async function loadTableData() {
            try {
                const res = await fetch('fetch_data.php');
                const json = await res.json();
                tableDataCache = json.data || [];
                filteredData = [...tableDataCache]; // Synchronize default state clone
                currentPage = 1; // Reset to page 1
                renderTable();
            } catch (error) {
                document.getElementById('tableBody').innerHTML = `<tr><td colspan="5" style="text-align:center; color: var(--color-error);">Failed to load data.</td></tr>`;
            }
        }

        // 6. Native Debounce Handler Function to stop continuous execution lags
        function debounce(func, delay) {
            let timeoutId;
            return function (...args) {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => {
                    func.apply(this, args);
                }, delay);
            };
        }

        // Live Search Handler bound to 300ms delay window mechanics
        const processSearch = debounce((term) => {
            filteredData = tableDataCache.filter(row => 
                row.beneficiary_name.toLowerCase().includes(term) || 
                row.approved_ssin.includes(term)
            );
            currentPage = 1; // Filter out tracking resets page to top
            renderTable();
        }, 300);

        document.getElementById('searchInput').addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase().trim();
            processSearch(term);
        });

        // 7. Accept Action with Button Loader
        window.handleAccept = async function(btn, ssin, name) {
            const tr = btn.closest('tr');
            const periodFrom = tr.querySelector('.dt-from').value;
            const periodTo = tr.querySelector('.dt-to').value;

            if (!periodFrom || !periodTo) {
                showSnackbar('Please select both dates.', 'error');
                return;
            }

            const allBtnsInRow = tr.querySelectorAll('.btn-action');
            allBtnsInRow.forEach(b => b.disabled = true);
            btn.classList.add('loading');

            try {
                const formData = new URLSearchParams();
                formData.append('beneficiary_name', name);
                formData.append('approved_ssin', ssin);
                formData.append('period_from', periodFrom);
                formData.append('period_to', periodTo);

                const res = await fetch('save_pf_update.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData
                });
                
                const json = await res.json();

                if (json.status === "success") {
                    showSnackbar('Saved successfully!', 'success');
                    
                    // Optimization: Pure array filtering removes matching target item without full ajax reload reload latency loop
                    tableDataCache = tableDataCache.filter(item => item.approved_ssin !== ssin);
                    filteredData = filteredData.filter(item => item.approved_ssin !== ssin);
                    
                    // Safe verification handling edge conditions boundaries
                    const maxPage = Math.ceil(filteredData.length / rowsPerPage);
                    if (currentPage > maxPage && currentPage > 1) {
                        currentPage = maxPage;
                    }
                    renderTable();
                } else {
                    showSnackbar(json.message || 'Failed to save', 'error');
                    allBtnsInRow.forEach(b => b.disabled = false);
                    btn.classList.remove('loading');
                }
            } catch (err) {
                showSnackbar('Network Error', 'error');
                allBtnsInRow.forEach(b => b.disabled = false);
                btn.classList.remove('loading');
            }
        };

        // 8. Reject Logic
        window.handleRejectClick = function(ssin, name) {
            currentRejectSSIN = ssin;
            currentRejectName = name;
            document.getElementById('rejectModal').classList.add('show');
        };

        document.getElementById('submitReject').addEventListener('click', async function() {
            const reason = document.getElementById('rejectReason').value.trim();
            const btn = this;

            if (!reason) {
                showSnackbar('Enter a rejection reason', 'error');
                return;
            }

            btn.classList.add('loading');
            btn.disabled = true;

            try {
                const formData = new URLSearchParams();
                formData.append('beneficiary_name', currentRejectName);
                formData.append('approved_ssin', currentRejectSSIN);
                formData.append('status', 'Rejected');
                formData.append('reason', reason);

                const res = await fetch('save_rejection.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData
                });
                const json = await res.json();

                if (json.status === "success") {
                    showSnackbar('Beneficiary rejected', 'success');
                    closeRejectModal();
                    
                    // Optimization parsing logic
                    tableDataCache = tableDataCache.filter(item => item.approved_ssin !== currentRejectSSIN);
                    filteredData = filteredData.filter(item => item.approved_ssin !== currentRejectSSIN);
                    
                    const maxPage = Math.ceil(filteredData.length / rowsPerPage);
                    if (currentPage > maxPage && currentPage > 1) {
                        currentPage = maxPage;
                    }
                    renderTable();
                } else {
                    showSnackbar(json.message || 'Failed', 'error');
                }
            } catch (err) {
                showSnackbar('Network Error', 'error');
            } finally {
                btn.classList.remove('loading');
                btn.disabled = false;
            }
        });

        // Initialize Everything
        document.addEventListener('DOMContentLoaded', async () => {
            await fetchSettings();
            fetchLatestData();
            loadTableData();
            setInterval(fetchLatestData, 5000);
        });
    </script>
</body>
</html>