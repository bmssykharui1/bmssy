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
    <title>BMSSY SERVICE | Period Settings</title>

    <style>
        /* =========================================
           Reset & Material Design 3 Variables
           ========================================= */
        :root {
            --md-sys-color-background: #f6f8fa;
            --md-sys-color-surface: #ffffff;
            --md-sys-color-primary: #0b57d0; /* Standard Blue */
            --md-sys-color-on-primary: #ffffff;
            --md-sys-color-primary-container: #d3e3fd;
            --md-sys-color-on-primary-container: #041e49;
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
        .max-w-container { max-width: 600px; margin: 0 auto; width: 100%; padding-top: 24px; }

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
           Cards & Forms
           ========================================= */
        .md-card { background: var(--md-sys-color-surface); border-radius: var(--app-radius-lg); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03); position: relative; overflow: hidden; }
        
        .card-header { padding: 20px 24px; border-bottom: 1px solid var(--md-sys-color-background); display: flex; align-items: center; gap: 12px; font-size: 18px; font-weight: 700; color: var(--md-sys-color-on-surface); }
        .card-header svg { width: 24px; height: 24px; fill: var(--md-sys-color-primary); }
        .card-header small { font-weight: 400; color: var(--md-sys-color-on-surface-variant); font-size: 13px; margin-left: auto; }

        .card-body { padding: 32px 24px; }

        /* Inputs */
        .form-group { margin-bottom: 24px; }
        .form-label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px; color: var(--md-sys-color-on-surface); }
        
        .input-wrapper { position: relative; display: flex; align-items: stretch; min-height: 52px; }
        .input-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); width: 20px; height: 20px; fill: var(--md-sys-color-outline); pointer-events: none; transition: fill 0.2s; }
        
        .md-input { width: 100%; background: var(--md-sys-color-background); border: 2px solid transparent; border-radius: var(--app-radius-md); padding: 16px 16px 16px 48px; font-size: 16px; color: var(--md-sys-color-on-surface); transition: all 0.2s; outline: none; }
        .md-input:focus { background: var(--md-sys-color-surface); border-color: var(--md-sys-color-primary); }
        .md-input:focus ~ .input-icon, .md-input:focus + .input-icon { fill: var(--md-sys-color-primary); }

        /* Buttons with Loaders */
        .btn-primary { position: relative; overflow: hidden; display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; height: 52px; border-radius: 100px; font-size: 16px; font-weight: 600; border: none; cursor: pointer; transition: transform 0.1s, box-shadow 0.2s, background 0.2s; background: var(--md-sys-color-primary); color: var(--md-sys-color-on-primary); box-shadow: 0 4px 12px rgba(11, 87, 208, 0.2); }
        .btn-primary:active { transform: scale(0.98); }
        .btn-primary:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }
        
        .btn-content { display: flex; align-items: center; justify-content: center; transition: opacity 0.2s; width: 100%; }
        
        .btn-spinner { position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); width: 24px; height: 24px; border: 3px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.8s linear infinite; opacity: 0; visibility: hidden; transition: opacity 0.2s; }
        @keyframes spin { 0% { transform: translate(-50%, -50%) rotate(0deg); } 100% { transform: translate(-50%, -50%) rotate(360deg); } }
        
        /* Loading State */
        .btn-primary.loading .btn-content { opacity: 0; visibility: hidden; }
        .btn-primary.loading .btn-spinner { opacity: 1; visibility: visible; }

        /* =========================================
           Native Snackbar / Toast
           ========================================= */
        #app-snackbar { position: fixed; bottom: -100px; left: 50%; transform: translateX(-50%); background: #313033; color: #f4eff4; padding: 14px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 10000; transition: bottom 0.4s cubic-bezier(0.2, 0, 0, 1); display: flex; align-items: center; gap: 12px; width: max-content; max-width: 90%; }
        #app-snackbar.show { bottom: 24px; }
        .snackbar-icon { width: 20px; height: 20px; flex-shrink: 0; }
        .snack-success { fill: #81c995; }
        .snack-warning { fill: #fde293; }
        .snack-error { fill: #ffb4ab; }

        /* Mobile Adjustments */
        @media (max-width: 1024px) {
            .app-main { margin-left: 0; }
            .menu-btn { display: flex; }
        }
        @media (max-width: 600px) {
            .user-info { display: none; }
            .content-scroll { padding: 16px; }
            .app-topbar { padding: 0 16px; }
            .card-body { padding: 24px 16px; }
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
                    <h1 class="page-title">PF Updation <small>| Period Change</small></h1>
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
                        <div class="card-header">
                            <svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg>
                            PF UPDATION 
                            <small>Period Changing</small>
                        </div>

                        <div class="card-body">
                            <form id="quickForm">
                                
                                <div class="form-group">
                                    <label for="periodForm" class="form-label">Period From</label>
                                    <div class="input-wrapper">
                                        <input type="date" name="period_form" id="periodForm" class="md-input" required>
                                        <svg class="input-icon" viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="periodTo" class="form-label">Period To</label>
                                    <div class="input-wrapper">
                                        <input type="date" name="period_to" id="periodTo" class="md-input" required>
                                        <svg class="input-icon" viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg>
                                    </div>
                                </div>

                                <div style="margin-top: 32px;">
                                    <button type="submit" id="submitBtn" class="btn-primary">
                                        <span class="btn-content">Update Period</span>
                                        <div class="btn-spinner"></div>
                                    </button>
                                </div>
                            </form>
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

        // --- Native Snackbar ---
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
            snackbarTimer = setTimeout(() => { snackbar.classList.remove('show'); }, 4000);
        }

        // ==========================================
        // DATA LOGIC (Vanilla JS)
        // ==========================================
        document.addEventListener('DOMContentLoaded', async () => {
            const periodFormInput = document.getElementById('periodForm');
            const periodToInput = document.getElementById('periodTo');
            const quickForm = document.getElementById('quickForm');
            const submitBtn = document.getElementById('submitBtn');

            // 1. Fetch Existing Settings on Load
            try {
                const response = await fetch('fetch_global_settings.php');
                const data = await response.json();
                
                // Maps response.period_from -> input[name="period_form"] (maintaining backend compatibility)
                if (data.period_from) {
                    periodFormInput.value = data.period_from;
                }
                if (data.period_to) {
                    periodToInput.value = data.period_to;
                }
            } catch (error) {
                console.error("Failed to load existing settings", error);
                showSnackbar('Failed to fetch existing data', 'error');
            }

            // 2. Handle Form Submission
            quickForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                const periodFromVal = periodFormInput.value;
                const periodToVal = periodToInput.value;

                if (!periodFromVal || !periodToVal) {
                    showSnackbar('Please fill in both dates', 'warning');
                    return;
                }

                // Trigger Button Loader
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;

                try {
                    // Uses FormData to ensure exactly the same payload names ("period_form", "period_to")
                    const formData = new URLSearchParams(new FormData(quickForm));

                    const response = await fetch('update_global_settings.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: formData
                    });

                    const res = await response.json();

                    if (res.status === "success") {
                        showSnackbar('Dates updated successfully!', 'success');
                    } else {
                        showSnackbar(res.message || 'Failed to update dates', 'error');
                    }
                } catch (error) {
                    console.error("Update request failed", error);
                    showSnackbar('Something went wrong with the request', 'error');
                } finally {
                    // Revert Button State
                    submitBtn.classList.remove('loading');
                    submitBtn.disabled = false;
                }
            });
        });
    </script>
</body>
</html>