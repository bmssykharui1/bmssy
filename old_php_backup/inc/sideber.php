<style>
    /* =========================================
       Material Design 3 Sidebar Variables
       ========================================= */
    :root {
        --sb-bg: #ffffff;
        --sb-text: #1f1f1f;
        --sb-text-variant: #44474e;
        --sb-hover: rgba(0, 0, 0, 0.04);
        --sb-active-bg: #d3e3fd;
        --sb-active-text: #041e49;
        --sb-border: rgba(0, 0, 0, 0.08);
        --sb-radius: 24px;
        --sb-width: 280px;
        --sb-transition: 0.3s cubic-bezier(0.2, 0, 0, 1);
    }

    /* --- Sidebar Container --- */
    .app-sidebar {
        width: var(--sb-width);
        background: var(--sb-bg);
        height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        z-index: 100;
        display: flex;
        flex-direction: column;
        border-right: 1px solid var(--sb-border);
        transition: transform var(--sb-transition);
        font-family: -apple-system, BlinkMacSystemFont, "Roboto", "Segoe UI", sans-serif;
    }

    /* --- Header & Profile --- */
    .sb-header {
        height: 80px;
        display: flex;
        align-items: center;
        padding: 0 20px;
        border-bottom: 1px solid var(--sb-border);
        flex-shrink: 0;
    }
    .sb-logo {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        margin-right: 12px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    .sb-title {
        font-size: 16px;
        font-weight: 700;
        color: var(--sb-text);
        letter-spacing: 0.5px;
    }

    .sb-profile-mobile {
        display: none;
        align-items: center;
        padding: 16px 20px;
        border-bottom: 1px solid var(--sb-border);
    }
    .sb-profile-mobile img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: 2px solid #e1e3e8;
    }
    .sb-profile-mobile span {
        margin-left: 12px;
        font-weight: 600;
        font-size: 14px;
        color: var(--sb-text);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* --- Navigation List --- */
    .sb-nav {
        flex: 1;
        overflow-y: auto;
        padding: 12px 12px 24px 12px;
        list-style: none;
        margin: 0;
    }

    .sb-nav::-webkit-scrollbar { width: 4px; }
    .sb-nav::-webkit-scrollbar-track { background: transparent; }
    .sb-nav::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.1); border-radius: 4px; }

    /* Nav Items */
    .sb-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 16px;
        margin-bottom: 4px;
        border-radius: 100px;
        color: var(--sb-text-variant);
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.2s, color 0.2s;
        border: none;
        background: transparent;
        width: 100%;
        text-align: left;
    }

    .sb-item:hover { background: var(--sb-hover); }
    .sb-item:active { background: rgba(0,0,0,0.08); }
    
    .sb-item.active {
        background: var(--sb-active-bg);
        color: var(--sb-active-text);
    }

    .sb-item-left {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .sb-icon { width: 22px; height: 22px; fill: currentColor; transition: fill 0.2s; }
    
    .color-dashboard { color: #0b57d0; }
    .color-add { color: #146c2e; }
    .color-pf { color: #6750a4; }
    .color-ds { color: #b36b00; }
    .color-list { color: #b3261e; }
    .color-form4 { color: #0d9488; }

    .sb-badge {
        background: #ba1a1a;
        color: #ffffff;
        padding: 2px 8px;
        border-radius: 100px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    /* --- Accordion --- */
    .sb-submenu-wrapper {
        display: grid;
        grid-template-rows: 0fr;
        transition: grid-template-rows 0.3s ease-out;
    }
    .sb-submenu-wrapper.open {
        grid-template-rows: 1fr;
    }
    .sb-submenu-inner {
        overflow: hidden;
    }
    
    .sb-submenu-list {
        padding: 4px 0 4px 44px;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .sb-subitem {
        display: flex;
        align-items: center;
        padding: 10px 16px;
        border-radius: 100px;
        color: var(--sb-text-variant);
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: background 0.2s, color 0.2s;
    }
    .sb-subitem:hover { background: var(--sb-hover); color: var(--sb-text); }
    .sb-subitem.active { background: var(--sb-active-bg); color: var(--sb-active-text); font-weight: 600; }
    
    .sb-subicon {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        border: 2px solid currentColor;
        margin-right: 12px;
        opacity: 0.6;
    }

    .sb-chevron {
        width: 18px;
        height: 18px;
        fill: currentColor;
        transition: transform 0.3s ease;
        opacity: 0.6;
    }
    .sb-item[aria-expanded="true"] .sb-chevron {
        transform: rotate(90deg);
    }

    /* --- Footer / Logout --- */
    .sb-footer {
        padding: 16px;
        border-top: 1px solid var(--sb-border);
    }
    .sb-logout-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 14px;
        border-radius: 100px;
        background: #ba1a1a;
        color: #ffffff;
        font-size: 14px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: background 0.2s, transform 0.1s, opacity 0.2s;
    }
    .sb-logout-btn:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }
    .sb-logout-btn:active:not(:disabled) {
        transform: scale(0.98);
        background: #8c1d18;
    }
    .btn-text-icon {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .sb-logout-btn svg { width: 20px; height: 20px; fill: currentColor; }

    /* Loading Spinner */
    .spinner {
        display: none;
        width: 20px;
        height: 20px;
        animation: rotate 2s linear infinite;
    }
    .spinner circle {
        stroke: #ffffff;
        stroke-width: 3;
        stroke-dasharray: 1, 200;
        stroke-dashoffset: 0;
        animation: dash 1.5s ease-in-out infinite;
        stroke-linecap: round;
        fill: none;
    }
    @keyframes rotate { 100% { transform: rotate(360deg); } }
    @keyframes dash {
        0% { stroke-dasharray: 1, 200; stroke-dashoffset: 0; }
        50% { stroke-dasharray: 90, 200; stroke-dashoffset: -35px; }
        100% { stroke-dasharray: 90, 200; stroke-dashoffset: -124px; }
    }

    .sb-logout-btn.loading .btn-text-icon { display: none; }
    .sb-logout-btn.loading .spinner { display: block; }

    @media (max-width: 1024px) {
        .app-sidebar { transform: translateX(-100%); }
        .app-sidebar.open { transform: translateX(0); }
        .sb-profile-mobile { display: flex; }
    }
</style>

<aside id="sidebar" class="app-sidebar">
    
    <div class="sb-header">
        
        <svg class="sb-logo" viewBox="0 0 24 24" style="background:#d3e3fd; padding:6px; fill:#0b57d0;" onerror="this.style.display='none'"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>
        <span class="sb-title">BMSSY KHARUI I</span>
    </div>

    <div class="sb-profile-mobile">
        <img src="/dist/img/user2-160x160.jpg" alt="User" onerror="this.style.display='none'">
        <span><?php echo isset($user_name) ? htmlspecialchars($user_name) : 'Admin User'; ?></span>
    </div>

    <div class="sb-nav">
        
        <a href="/dashboard" class="sb-item nav-link">
            <div class="sb-item-left color-dashboard">
                <svg class="sb-icon" viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                <span>Dashboard</span>
            </div>
        </a>

        <a href="/addNew" class="sb-item nav-link">
            <div class="sb-item-left color-add">
                <svg class="sb-icon" viewBox="0 0 24 24"><path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                <span>Add New</span>
            </div>
            <span class="sb-badge">New</span>
        </a>

        <div>
            <button class="sb-item" onclick="toggleNativeSubmenu(this, 'pf-menu')" aria-expanded="false">
                <div class="sb-item-left color-pf">
                    <svg class="sb-icon" viewBox="0 0 24 24"><path d="M3 3v18h18V3H3zm16 16H5V5h14v14zM7 10h2v2H7zm0 4h2v2H7zm4-4h6v2h-6zm0 4h6v2h-6z"/></svg>
                    <span>PF Updation</span>
                </div>
                <svg class="sb-chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
            </button>
            <div id="pf-menu" class="sb-submenu-wrapper">
                <div class="sb-submenu-inner">
                    <div class="sb-submenu-list">
                        <a href="/pfupdate/others" class="sb-subitem nav-link"><div class="sb-subicon"></div> Others</a>
                        <a href="/pfupdate/constractions" class="sb-subitem nav-link"><div class="sb-subicon"></div> Contractions</a>
                        <a href="/pfupdate/settings" class="sb-subitem nav-link"><div class="sb-subicon"></div> Settings</a>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <button class="sb-item" onclick="toggleNativeSubmenu(this, 'ds-menu')" aria-expanded="false">
                <div class="sb-item-left color-ds">
                    <svg class="sb-icon" viewBox="0 0 24 24"><path d="M12 3L4 9v12h16V9l-8-6zm0 2.25l6 4.5v9h-3v-6H9v6H6v-9l6-4.5z"/></svg>
                    <span>Duare Sorkar</span>
                </div>
                <svg class="sb-chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
            </button>
            <div id="ds-menu" class="sb-submenu-wrapper">
                <div class="sb-submenu-inner">
                    <div class="sb-submenu-list">
                        <a href="/ds/entry" class="sb-subitem nav-link"><div class="sb-subicon"></div> Entry</a>
                        <a href="/ds/pf_update" class="sb-subitem nav-link"><div class="sb-subicon"></div> PF Update</a>
                        <a href="/ds/ds_list" class="sb-subitem nav-link"><div class="sb-subicon"></div> DS List</a>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <button class="sb-item" onclick="toggleNativeSubmenu(this, 'list-menu')" aria-expanded="false">
                <div class="sb-item-left color-list">
                    <svg class="sb-icon" viewBox="0 0 24 24"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg>
                    <span>Lists</span>
                </div>
                <svg class="sb-chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
            </button>
            <div id="list-menu" class="sb-submenu-wrapper">
                <div class="sb-submenu-inner">
                    <div class="sb-submenu-list">
                        <a href="/list/alldata" class="sb-subitem nav-link"><div class="sb-subicon"></div> All Data</a>
                        <a href="/list/pfupdate" class="sb-subitem nav-link"><div class="sb-subicon"></div> PF Update</a>
                        <a href="/list/newdata" class="sb-subitem nav-link"><div class="sb-subicon"></div> New Data</a>
                        <a href="/list/inactivedata" class="sb-subitem nav-link"><div class="sb-subicon"></div> Inactive Data</a>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <button class="sb-item" onclick="toggleNativeSubmenu(this, 'form4-menu')" aria-expanded="false">
                <div class="sb-item-left color-form4">
                    <svg class="sb-icon" viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                    <span>Form 4</span>
                </div>
                <svg class="sb-chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
            </button>
            <div id="form4-menu" class="sb-submenu-wrapper">
                <div class="sb-submenu-inner">
                    <div class="sb-submenu-list">
                        <a href="/form4/addnew" class="sb-subitem nav-link"><div class="sb-subicon"></div> Add New</a>
                        <a href="/form4/download" class="sb-subitem nav-link"><div class="sb-subicon"></div> Download PDF</a>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="sb-footer">
        <button id="logout-btn" onclick="executeLogout()" class="sb-logout-btn">
            <div class="btn-text-icon">
                <svg viewBox="0 0 24 24"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
                Logout
            </div>
            <svg class="spinner" viewBox="0 0 50 50">
                <circle cx="25" cy="25" r="20"></circle>
            </svg>
        </button>
    </div>
</aside>

<script>
    // --- 1. Active State Management ---
    document.addEventListener("DOMContentLoaded", () => {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.nav-link');
        
        let foundActive = false;

        navLinks.forEach(link => {
            const linkPath = link.getAttribute('href');
            
            // Check if current URL ends with or exactly matches the link
            if (linkPath && currentPath.includes(linkPath) && linkPath !== '/') {
                link.classList.add('active');
                foundActive = true;

                // Check if this link is inside a dropdown/submenu
                const submenuWrapper = link.closest('.sb-submenu-wrapper');
                if (submenuWrapper) {
                    // Open the dropdown
                    submenuWrapper.classList.add('open');
                    
                    // Highlight and toggle the parent button
                    const parentBtn = submenuWrapper.previousElementSibling;
                    if (parentBtn) {
                        parentBtn.setAttribute('aria-expanded', 'true');
                        parentBtn.classList.add('active'); 
                    }
                }
            }
        });

        // Default to dashboard if root path or no match found
        if(!foundActive && (currentPath === '/' || currentPath === '')) {
            const dashLink = document.querySelector('a[href="/dashboard"]');
            if(dashLink) dashLink.classList.add('active');
        }
    });

    // --- 2. Native Accordion Logic ---
    function toggleNativeSubmenu(btnElement, targetId) {
        const targetMenu = document.getElementById(targetId);
        const isExpanded = btnElement.getAttribute('aria-expanded') === 'true';

        // Toggle state
        if (isExpanded) {
            btnElement.setAttribute('aria-expanded', 'false');
            targetMenu.classList.remove('open');
        } else {
            // Close other open menus for strict accordion style
            document.querySelectorAll('.sb-submenu-wrapper.open').forEach(menu => {
                if(menu.id !== targetId) {
                    menu.classList.remove('open');
                    const parentBtn = menu.previousElementSibling;
                    if(parentBtn) parentBtn.setAttribute('aria-expanded', 'false');
                }
            });

            btnElement.setAttribute('aria-expanded', 'true');
            targetMenu.classList.add('open');
        }
    }

    // --- 3. Secure Logout Logic with Spinner ---
    async function executeLogout() {
        const btn = document.getElementById('logout-btn');
        
        // Add loading state and disable button
        btn.classList.add('loading');
        btn.disabled = true;

        try {
            // Using POST to logout.php for better security (prevents pre-fetching issues)
            const response = await fetch('/logout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            });

            if (response.ok) {
                // Redirect to login page upon success. Update '/login.php' if your route is different.
                window.location.href = '/login.php'; 
            } else {
                console.error("Logout failed with status: ", response.status);
                // Revert button if failed
                btn.classList.remove('loading');
                btn.disabled = false;
            }
        } catch (error) {
            console.error("Error connecting to logout endpoint: ", error);
            // Revert button on network error
            btn.classList.remove('loading');
            btn.disabled = false;
        }
    }
</script>