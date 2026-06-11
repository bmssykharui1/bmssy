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
    <title>BMSSY SERVICE | DS Entry</title>

    <style>
        /* =========================================
           Reset & Material Design 3 Variables
           ========================================= */
        :root {
            --md-sys-color-background: #f6f8fa;
            --md-sys-color-surface: #ffffff;
            --md-sys-color-primary: #6750a4; /* Purple for Duare Sorkar */
            --md-sys-color-on-primary: #ffffff;
            --md-sys-color-primary-container: #eaddff;
            --md-sys-color-on-primary-container: #21005d;
            --md-sys-color-on-surface: #1f1f1f;
            --md-sys-color-on-surface-variant: #44474e;
            --md-sys-color-outline: #74777f;
            
            --color-success: #146c2e;
            --color-error: #b3261e;
            --color-info: #0b57d0;
            
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
        .page-title { font-size: 20px; font-weight: 600; white-space: nowrap; display: flex; align-items: center; gap: 12px; }
        .badge { background: var(--md-sys-color-primary-container); color: var(--md-sys-color-on-primary-container); padding: 4px 10px; border-radius: 100px; font-size: 13px; font-weight: 700; }
        
        .menu-btn { background: none; border: none; width: 40px; height: 40px; border-radius: 50%; display: none; align-items: center; justify-content: center; cursor: pointer; color: var(--md-sys-color-on-surface-variant); transition: background 0.2s; }
        .menu-btn:active { background: rgba(0,0,0,0.08); }
        .menu-btn svg { width: 24px; height: 24px; fill: currentColor; }
        
        .user-profile { display: flex; align-items: center; gap: 12px; }
        .user-info { text-align: right; }
        .user-name { font-size: 14px; font-weight: 600; }
        .user-role { font-size: 12px; color: var(--md-sys-color-on-surface-variant); }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: #f3e8ff; color: #4f46e5; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0; }

        /* =========================================
           Cards & Dash Components
           ========================================= */
        .md-card { background: var(--md-sys-color-surface); border-radius: var(--app-radius-lg); padding: 24px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03); margin-bottom: 24px; position: relative; overflow: hidden; }
        
        /* Top Status/Filter Card */
        .dash-header-grid { display: grid; grid-template-columns: 1fr auto; gap: 20px; align-items: center; }
        
        .last-entry-box { display: flex; flex-direction: column; gap: 8px; }
        .last-entry-label { font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--md-sys-color-outline); }
        .last-entry-data { display: flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 500; flex-wrap: wrap; }
        .data-chip { background: var(--md-sys-color-background); padding: 6px 12px; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; }
        
        .edit-icon-btn { background: rgba(103, 80, 164, 0.1); color: var(--md-sys-color-primary); border: none; width: 32px; height: 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; }
        .edit-icon-btn:active { transform: scale(0.9); background: rgba(103, 80, 164, 0.2); }
        .edit-icon-btn svg { width: 16px; height: 16px; fill: currentColor; }

        /* Inputs & Filters */
        .md-select, .md-input { background: var(--md-sys-color-background); border: 2px solid transparent; border-radius: var(--app-radius-md); padding: 12px 16px; font-size: 14px; outline: none; transition: all 0.2s; color: var(--md-sys-color-on-surface); width: 100%; }
        .md-select:focus, .md-input:focus { border-color: var(--md-sys-color-primary); background: var(--md-sys-color-surface); }
        
        .table-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 16px; flex-wrap: wrap; }
        .search-box { position: relative; width: 100%; max-width: 320px; }
        .search-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); width: 20px; height: 20px; fill: var(--md-sys-color-outline); pointer-events: none; }
        .search-input { width: 100%; background: var(--md-sys-color-background); border: 2px solid transparent; border-radius: 100px; padding: 12px 16px 12px 48px; font-size: 14px; outline: none; transition: all 0.2s; }
        .search-input:focus { background: var(--md-sys-color-surface); border-color: var(--md-sys-color-primary); }

        /* =========================================
           Input Groups (DS NO & Paste)
           ========================================= */
        .input-group { display: flex; align-items: stretch; width: 100%; max-width: 250px; }
        .input-group .md-input { border-top-right-radius: 0; border-bottom-right-radius: 0; }
        .input-group-btn { background: var(--md-sys-color-primary-container); color: var(--md-sys-color-on-primary-container); border: none; padding: 0 16px; border-top-right-radius: var(--app-radius-md); border-bottom-right-radius: var(--app-radius-md); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
        .input-group-btn:active { background: #d0bcff; }
        .input-group-btn svg { width: 18px; height: 18px; fill: currentColor; }

        /* Copy Buttons */
        .copy-btn { background: none; border: none; color: var(--color-info); margin-left: 8px; cursor: pointer; opacity: 0.7; transition: 0.2s; padding: 4px; border-radius: 4px; }
        .copy-btn:hover, .copy-btn:active { opacity: 1; background: rgba(11, 87, 208, 0.1); }
        .copy-btn svg { width: 16px; height: 16px; fill: currentColor; }
        
        .row-highlight { background-color: rgba(11, 87, 208, 0.1) !important; transition: background-color 0.3s; }

        /* =========================================
           Buttons with Built-in Loaders
           ========================================= */
        .btn-action { position: relative; overflow: hidden; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 16px; border-radius: var(--app-radius-md); font-size: 14px; font-weight: 600; border: none; cursor: pointer; transition: transform 0.1s, background 0.2s; color: #fff; min-height: 44px; }
        .btn-action:active { transform: scale(0.96); }
        .btn-action:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        
        .btn-success { background: var(--color-success); }
        .btn-primary { background: var(--md-sys-color-primary); }
        
        .btn-content { display: flex; align-items: center; justify-content: center; gap: 6px; transition: opacity 0.2s; width: 100%; }
        .btn-content svg { width: 18px; height: 18px; fill: currentColor; }

        .btn-spinner { position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); width: 20px; height: 20px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.8s linear infinite; opacity: 0; visibility: hidden; transition: opacity 0.2s; }
        @keyframes spin { 0% { transform: translate(-50%, -50%) rotate(0deg); } 100% { transform: translate(-50%, -50%) rotate(360deg); } }
        
        .btn-action.loading .btn-content { opacity: 0; visibility: hidden; }
        .btn-action.loading .btn-spinner { opacity: 1; visibility: visible; }

        /* =========================================
           Responsive Table -> Cards (Mobile)
           ========================================= */
        .md-table-wrapper { width: 100%; overflow-x: auto; border-radius: var(--app-radius-md); border: 1px solid var(--md-sys-color-background); }
        .md-table { width: 100%; border-collapse: collapse; text-align: left; }
        .md-table th, .md-table td { padding: 16px; border-bottom: 1px solid var(--md-sys-color-background); vertical-align: middle; }
        .md-table th { font-size: 12px; font-weight: 600; text-transform: uppercase; color: var(--md-sys-color-on-surface-variant); background: var(--md-sys-color-background); }
        
        .font-mono { font-family: monospace; font-size: 15px; font-weight: 600; color: var(--md-sys-color-on-surface); }
        .td-flex { display: flex; align-items: center; }

        @media (max-width: 768px) {
            .dash-header-grid { grid-template-columns: 1fr; gap: 16px; }
            
            .md-table-wrapper { border: none; background: transparent; overflow-x: visible; }
            .md-table thead { display: none; }
            .md-table, .md-table tbody, .md-table tr, .md-table td { display: block; width: 100%; }
            .md-table tr { margin-bottom: 16px; background: var(--md-sys-color-surface); border-radius: var(--app-radius-md); border: 1px solid rgba(0,0,0,0.08); padding: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
            .md-table td { display: flex; flex-direction: column; align-items: flex-start; padding: 10px 8px; border-bottom: 1px dashed rgba(0,0,0,0.05); }
            .md-table td::before { content: attr(data-label); font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--md-sys-color-on-surface-variant); margin-bottom: 6px; }
            .md-table td:last-child { border-bottom: none; }
            
            .td-flex { width: 100%; justify-content: space-between; }
            .input-group { max-width: 100%; }
            .btn-action { width: 100%; padding: 14px; }
            
            .table-toolbar { flex-direction: column; align-items: stretch; }
            .search-box { max-width: 100%; }
        }

        /* =========================================
           Native Modal Dialog
           ========================================= */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; display: flex; align-items: center; justify-content: center; padding: 24px; opacity: 0; visibility: hidden; transition: all 0.3s ease; }
        .modal-overlay.show { opacity: 1; visibility: visible; }
        .modal-content { background: var(--md-sys-color-surface); width: 100%; max-width: 400px; border-radius: var(--app-radius-lg); padding: 24px; transform: translateY(20px) scale(0.95); transition: all 0.3s cubic-bezier(0.2, 0, 0, 1); box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        .modal-overlay.show .modal-content { transform: translateY(0) scale(1); }
        
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-title { font-size: 18px; font-weight: 700; color: var(--md-sys-color-on-surface); }
        .btn-close { background: none; border: none; cursor: pointer; color: var(--md-sys-color-outline); padding: 4px; }
        .btn-close svg { width: 24px; height: 24px; fill: currentColor; }
        
        .modal-body .form-group { margin-bottom: 16px; }
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
        .snack-warning { fill: #fde293; }
        .snack-error { fill: #ffb4ab; }

        @media (max-width: 1024px) {
            .app-main { margin-left: 0; }
            .menu-btn { display: flex; }
        }
        @media (max-width: 600px) {
            .user-info { display: none; }
            .content-scroll { padding: 16px; }
            .app-topbar { padding: 0 16px; }
            .md-card { padding: 16px; }
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
                    <h1 class="page-title">
                        DS ENTRY 
                        <span id="ds-total" class="badge">...</span>
                    </h1>
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
                        <div class="dash-header-grid">
                            
                            <div class="last-entry-box">
                                <div class="last-entry-label">Last Saved Entry</div>
                                <div class="last-entry-data" id="last-entry-container">
                                    <span style="opacity:0.6; font-size: 13px;">Loading...</span>
                                </div>
                            </div>

                            <div>
                                <select id="ssinFilter" class="md-select">
                                    <option value="">Filter: All Categories</option>
                                    <option value="^142">Others (142)</option>
                                    <option value="^242">Constructions (242)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="md-card">
                        
                        <div class="table-toolbar">
                            <h3 style="font-size: 18px; font-weight: 700;">Pending Entries</h3>
                            <div class="search-box">
                                <svg class="search-icon" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                                <input type="text" id="searchInput" class="search-input" placeholder="Search by name, SSIN, phone...">
                            </div>
                        </div>
                        
                        <div class="md-table-wrapper">
                            <table class="md-table">
                                <thead>
                                    <tr>
                                        <th>SSIN</th>
                                        <th>Name</th>
                                        <th>Phone</th>
                                        <th>DS NO</th>
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

                    </div>
                    
                </div>
                <div style="height: 40px;"></div>
            </div>
        </main>
    </div>

    <div id="editModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Edit Last Entry</div>
                <button type="button" class="btn-close" onclick="closeEditModal()">
                    <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"/></svg>
                </button>
            </div>
            
            <form id="edit-entry-form">
                <input type="hidden" id="edit-ssin-old">
                
                <div class="form-group">
                    <label class="form-label">SSIN</label>
                    <input type="text" class="md-input" id="edit-ssin" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Name</label>
                    <input type="text" class="md-input" id="edit-name" required>
                </div>
                <div class="form-group">
                    <label class="form-label">DS NO</label>
                    <input type="text" class="md-input" id="edit-dsno" required>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-text" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" id="submitEditBtn" class="btn-action btn-primary" style="padding: 10px 24px;">
                        <span class="btn-content">Update Entry</span>
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
            iconEl.classList.remove('snack-success', 'snack-error', 'snack-warning');
            
            if(type === 'success') {
                iconEl.classList.add('snack-success');
                iconEl.innerHTML = '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>';
            } else if(type === 'warning') {
                iconEl.classList.add('snack-warning');
                iconEl.innerHTML = '<path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>';
            } else {
                iconEl.classList.add('snack-error');
                iconEl.innerHTML = '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>';
            }
            snackbar.classList.add('show');
            clearTimeout(snackbarTimer);
            snackbarTimer = setTimeout(() => { snackbar.classList.remove('show'); }, 3000);
        }

        // SVG string for the copy icon
        const copySvg = `<svg viewBox="0 0 24 24"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>`;

        // ==========================================
        // DATA LOGIC (Vanilla JS)
        // ==========================================
        let fullDataCache = [];
        let existingSSINs = [];
        
        // 1. Fetch Total Count
        async function updateTotalCount() {
            try {
                const res = await fetch("get_total_count.php");
                const data = await res.json();
                document.getElementById("ds-total").innerText = data.total !== undefined ? data.total : "Err";
            } catch (e) {
                document.getElementById("ds-total").innerText = "Err";
            }
        }

        // 2. Fetch Last Entry
        async function loadLastEntry() {
            try {
                const res = await fetch("get_last_entry.php");
                const data = await res.json();
                const container = document.getElementById("last-entry-container");
                
                if (data.error) {
                    container.innerHTML = `<span style="color:var(--color-error); font-size:13px;">${data.error}</span>`;
                } else {
                    container.innerHTML = `
                        <div class="data-chip"><span class="font-mono">${data.ssin}</span></div>
                        <div class="data-chip"><span>${data.name}</span></div>
                        <div class="data-chip"><strong>DS:</strong> <span>${data.dsno}</span></div>
                        <button class="edit-icon-btn" onclick="openEditModal('${data.ssin}')" title="Edit">
                            <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                        </button>
                    `;
                }
            } catch (err) {
                document.getElementById("last-entry-container").innerHTML = `<span style="color:var(--color-error); font-size:13px;">Error fetching data</span>`;
            }
        }

        // 3. Render Table
        function renderTable() {
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '';
            
            // Filter Logic
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const filterPattern = document.getElementById('ssinFilter').value;
            let regex = filterPattern ? new RegExp(filterPattern) : null;

            // Apply filters: Not existing AND matches regex AND matches search
            const filteredData = fullDataCache.filter(row => {
                if (existingSSINs.includes(row.approved_ssin)) return false;
                if (regex && !regex.test(row.approved_ssin)) return false;
                
                if (searchTerm) {
                    return row.beneficiary_name.toLowerCase().includes(searchTerm) || 
                           row.approved_ssin.includes(searchTerm) || 
                           (row.phone && row.phone.includes(searchTerm));
                }
                return true;
            });

            if(filteredData.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding: 40px; color: var(--md-sys-color-outline);">No matching records found.</td></tr>`;
                return;
            }

            // Render up to 50 for performance (Client-side pagination can be added if needed)
            const displayData = filteredData.slice(0, 50);

            displayData.forEach(row => {
                const tr = document.createElement('tr');
                tr.dataset.ssin = row.approved_ssin;
                
                tr.innerHTML = `
                    <td data-label="SSIN">
                        <div class="td-flex">
                            <span class="font-mono">${row.approved_ssin}</span>
                            <button class="copy-btn" onclick="handleCopy('${row.approved_ssin}', 'SSIN', this)" title="Copy SSIN">${copySvg}</button>
                        </div>
                    </td>
                    <td data-label="Name">
                        <div class="td-flex">
                            <span>${row.beneficiary_name}</span>
                            <button class="copy-btn" onclick="handleCopy('${row.beneficiary_name}', 'Name', this)" title="Copy Name">${copySvg}</button>
                        </div>
                    </td>
                    <td data-label="Phone">
                        <div class="td-flex">
                            <span>${row.phone || '-'}</span>
                            ${row.phone ? `<button class="copy-btn" onclick="handleCopy('${row.phone}', 'Phone', this)" title="Copy Phone">${copySvg}</button>` : ''}
                        </div>
                    </td>
                    <td data-label="DS NO">
                        <div class="input-group">
                            <input type="text" class="md-input dsno-input" placeholder="Enter DS NO">
                            <button class="input-group-btn" onclick="handlePaste(this)" title="Paste">
                                <svg viewBox="0 0 24 24"><path d="M19 2h-4.18C14.4.84 13.3 0 12 0c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm7 18H5V4h2v3h10V4h2v16z"/></svg>
                            </button>
                        </div>
                    </td>
                    <td data-label="Action">
                        <button class="btn-action btn-success" style="width: 100%;" onclick="handleSave(this, '${row.approved_ssin}', '${row.beneficiary_name}')">
                            <span class="btn-content"><svg viewBox="0 0 24 24"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg> Save</span>
                            <div class="btn-spinner"></div>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        // 4. Fetch Table Data (Async)
        async function fetchAllData() {
            try {
                // Fetch existing (saved) SSINs
                const resExisting = await fetch('fetch_existing_ssins.php');
                const jsonExisting = await resExisting.json();
                if(jsonExisting.status === 'success') existingSSINs = jsonExisting.data;

                // Fetch new data
                const resData = await fetch('fetch_data.php');
                const jsonData = await resData.json();
                fullDataCache = jsonData.data || [];
                
                renderTable();
            } catch (error) {
                document.getElementById('tableBody').innerHTML = `<tr><td colspan="5" style="text-align:center; color: var(--color-error);">Failed to load data.</td></tr>`;
            }
        }

        // Event Listeners for Filters
        document.getElementById('searchInput').addEventListener('input', renderTable);
        document.getElementById('ssinFilter').addEventListener('change', renderTable);

        // ==========================================
        // ACTION HANDLERS
        // ==========================================
        
        // Native Clipboard Copy
        window.handleCopy = async function(text, label, btnElement) {
            try {
                await navigator.clipboard.writeText(text);
                
                // Highlight Row
                const row = btnElement.closest('tr');
                document.querySelectorAll('.row-highlight').forEach(r => r.classList.remove('row-highlight'));
                row.classList.add('row-highlight');
                
                showSnackbar(`${label} Copied!`, 'success');
            } catch (err) {
                showSnackbar('Failed to copy', 'error');
            }
        };

        // Native Clipboard Paste
        window.handlePaste = async function(btnElement) {
            const input = btnElement.previousElementSibling;
            try {
                const text = await navigator.clipboard.readText();
                const cleanText = text.replace(/\D/g, ''); // Extract only digits
                input.value = cleanText;
            } catch (err) {
                showSnackbar('Clipboard access denied.', 'error');
            }
        };

        // Save Entry
        window.handleSave = async function(btn, ssin, name) {
            const row = btn.closest('tr');
            const dsnoInput = row.querySelector('.dsno-input');
            const dsno = dsnoInput.value.trim();

            if (!dsno) {
                showSnackbar('Please enter a DS NO', 'warning');
                dsnoInput.focus();
                return;
            }

            btn.classList.add('loading');
            btn.disabled = true;

            try {
                const formData = new URLSearchParams();
                formData.append('ssin', ssin);
                formData.append('name', name);
                formData.append('dsno', dsno);

                const res = await fetch('save_dsno.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData
                });

                if (res.status === 409) {
                    showSnackbar('Already Saved', 'warning');
                } else if (res.ok) {
                    showSnackbar('DS NO Saved!', 'success');
                    
                    // Add to existing array so it's filtered out
                    existingSSINs.push(ssin);
                    
                    // Remove row from DOM with animation
                    row.style.opacity = '0';
                    setTimeout(() => {
                        row.remove();
                        // If table is empty after removing, re-render to show empty state
                        if(document.querySelectorAll('#tableBody tr').length === 0) renderTable();
                    }, 300);

                    updateTotalCount();
                    loadLastEntry();
                } else {
                    showSnackbar('Save Failed', 'error');
                }
            } catch (err) {
                showSnackbar('Network Error', 'error');
            } finally {
                btn.classList.remove('loading');
                btn.disabled = false;
            }
        };

        // ==========================================
        // EDIT MODAL LOGIC
        // ==========================================
        window.openEditModal = async function(ssin) {
            try {
                const formData = new URLSearchParams();
                formData.append('ssin', ssin);

                const res = await fetch("get_entry_by_ssin.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: formData
                });
                
                const data = await res.json();

                if (data.error) {
                    showSnackbar(data.error, "error");
                } else {
                    document.getElementById("edit-ssin-old").value = data.ssin;
                    document.getElementById("edit-ssin").value = data.ssin;
                    document.getElementById("edit-name").value = data.name;
                    document.getElementById("edit-dsno").value = data.dsno;
                    
                    document.getElementById('editModal').classList.add('show');
                }
            } catch (err) {
                showSnackbar("Failed to load data", "error");
            }
        };

        window.closeEditModal = function() {
            document.getElementById('editModal').classList.remove('show');
        };

        document.getElementById("edit-entry-form").addEventListener("submit", async function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('submitEditBtn');
            btn.classList.add('loading');
            btn.disabled = true;

            const oldSSIN = document.getElementById("edit-ssin-old").value;
            const ssin = document.getElementById("edit-ssin").value;
            const name = document.getElementById("edit-name").value;
            const dsno = document.getElementById("edit-dsno").value;

            try {
                const formData = new URLSearchParams();
                formData.append('old_ssin', oldSSIN);
                formData.append('ssin', ssin);
                formData.append('name', name);
                formData.append('dsno', dsno);

                const res = await fetch("update_entry.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: formData
                });
                
                const data = await res.json();

                if (data.status === "Success") {
                    showSnackbar("Record updated successfully.", "success");
                    closeEditModal();
                    loadLastEntry();
                } else {
                    showSnackbar(data.error || "Update failed.", "error");
                }
            } catch (err) {
                showSnackbar("Request failed", "error");
            } finally {
                btn.classList.remove('loading');
                btn.disabled = false;
            }
        });

        // Init
        document.addEventListener('DOMContentLoaded', () => {
            updateTotalCount();
            loadLastEntry();
            fetchAllData();
        });

    </script>
</body>
</html>