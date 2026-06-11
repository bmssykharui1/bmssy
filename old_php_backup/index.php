<?php
// ==========================================
// SECURITY: Force HTTPS Connection
// ==========================================
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === "off") {
    $redirect_url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: $redirect_url");
    exit();
}

// ==========================================
// SECURITY: Add HTTP Security Headers
// ==========================================
header("X-Frame-Options: DENY"); // Prevents Clickjacking
header("X-XSS-Protection: 1; mode=block"); // Prevents Cross-Site Scripting
header("X-Content-Type-Options: nosniff"); // Prevents MIME-sniffing
header("Strict-Transport-Security: max-age=31536000; includeSubDomains"); // Enforces HTTPS strictly

session_start();
if (isset($_SESSION["user_id"])) {
    header("Location: /dashboard"); // Redirect logged-in users to dashboard
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="theme-color" content="#ffffff">
    <title>DEEPGYA SERVICE | Login</title>

    <style>
        /* =========================================
           Reset & Base Setup (Android 16 Vibe)
           ========================================= */
        :root {
            --md-sys-color-background: #f6f8fa;
            --md-sys-color-surface: #ffffff;
            --md-sys-color-primary: #0b57d0;
            --md-sys-color-on-primary: #ffffff;
            --md-sys-color-surface-variant: #e1e3e8;
            --md-sys-color-on-surface: #1f1f1f;
            --md-sys-color-on-surface-variant: #44474e;
            --md-sys-color-error: #b3261e;
            --md-sys-color-on-error: #ffffff;
            --app-radius-lg: 28px;
            --app-radius-sm: 16px;
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
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow: hidden; 
        }

        /* =========================================
           Page Loader (App Splash Screen)
           ========================================= */
        #splash-screen {
            position: fixed;
            inset: 0;
            background-color: var(--md-sys-color-surface);
            z-index: 9999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: opacity 0.6s cubic-bezier(0.2, 0, 0, 1), visibility 0.6s;
        }

        .splash-icon {
            width: 80px;
            height: 80px;
            background: var(--md-sys-color-primary);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 1.5s infinite alternate;
            margin-bottom: 24px;
        }

        .splash-icon svg {
            width: 40px;
            height: 40px;
            fill: var(--md-sys-color-on-primary);
        }

        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(11, 87, 208, 0.4); }
            100% { transform: scale(1.05); box-shadow: 0 0 0 15px rgba(11, 87, 208, 0); }
        }

        .hidden-splash {
            opacity: 0;
            visibility: hidden;
        }

        /* =========================================
           Main App Container & Card
           ========================================= */
        .app-container {
            width: 100%;
            max-width: 420px;
            padding: 24px;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.6s cubic-bezier(0.2, 0, 0, 1);
        }

        .app-container.loaded {
            opacity: 1;
            transform: translateY(0);
        }

        .card {
            background: var(--md-sys-color-surface);
            border-radius: var(--app-radius-lg);
            padding: 40px 32px;
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.04), 0 4px 12px rgba(0, 0, 0, 0.02);
            text-align: center;
        }

        .header-icon {
            width: 64px;
            height: 64px;
            background-color: #e8def8; 
            color: var(--md-sys-color-primary);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .header-icon svg {
            width: 32px;
            height: 32px;
            fill: currentColor;
        }

        h1 {
            font-size: 26px;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin-bottom: 8px;
            color: var(--md-sys-color-on-surface);
        }

        p.subtitle {
            font-size: 14px;
            color: var(--md-sys-color-on-surface-variant);
            margin-bottom: 32px;
        }

        /* =========================================
           Form Inputs (Material 3 Style)
           ========================================= */
        .input-group {
            position: relative;
            margin-bottom: 20px;
            text-align: left;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            fill: var(--md-sys-color-on-surface-variant);
            transition: fill 0.3s ease;
            pointer-events: none;
        }

        .app-input {
            width: 100%;
            background: var(--md-sys-color-background);
            border: 2px solid transparent;
            border-radius: var(--app-radius-sm);
            padding: 18px 16px 18px 48px;
            font-size: 16px;
            color: var(--md-sys-color-on-surface);
            transition: all 0.3s cubic-bezier(0.2, 0, 0, 1);
            outline: none;
        }

        .app-input::placeholder { color: #8e9196; }

        .app-input:focus {
            background: var(--md-sys-color-surface);
            border-color: var(--md-sys-color-primary);
            box-shadow: 0 0 0 4px rgba(11, 87, 208, 0.1);
        }

        .app-input:focus + .input-icon { fill: var(--md-sys-color-primary); }

        /* =========================================
           Checkbox & Links
           ========================================= */
        .row-between {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            font-size: 14px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--md-sys-color-on-surface-variant);
            cursor: pointer;
            font-weight: 500;
        }

        .app-checkbox {
            width: 18px;
            height: 18px;
            accent-color: var(--md-sys-color-primary);
            cursor: pointer;
        }

        .forgot-link { color: var(--md-sys-color-primary); text-decoration: none; font-weight: 600; }
        .forgot-link:active { opacity: 0.7; }

        /* =========================================
           Submit Button with Loader
           ========================================= */
        .btn-primary {
            width: 100%;
            background: var(--md-sys-color-primary);
            color: var(--md-sys-color-on-primary);
            border: none;
            border-radius: 100px;
            padding: 18px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: transform 0.2s, background 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 12px rgba(11, 87, 208, 0.2);
        }

        .btn-primary:active { transform: scale(0.96); background: #0842a0; }

        .btn-text { transition: opacity 0.2s; display: flex; align-items: center; gap: 8px; }
        .btn-text svg { width: 18px; height: 18px; fill: currentColor; }

        .btn-spinner {
            position: absolute;
            width: 24px;
            height: 24px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top: 3px solid #ffffff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s;
        }

        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        .btn-primary.loading .btn-text { opacity: 0; visibility: hidden; }
        .btn-primary.loading .btn-spinner { opacity: 1; visibility: visible; }

        /* =========================================
           App Snackbar (Error Notification)
           ========================================= */
        #app-snackbar {
            position: fixed; bottom: -100px; left: 50%; transform: translateX(-50%);
            background: #313033; color: #f4eff4; padding: 14px 24px; border-radius: 8px;
            font-size: 14px; font-weight: 500; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000; transition: bottom 0.4s cubic-bezier(0.2, 0, 0, 1);
            display: flex; align-items: center; gap: 12px; width: max-content; max-width: 90%;
        }
        #app-snackbar.show { bottom: 24px; }
        .snackbar-icon { width: 20px; height: 20px; fill: #ffb4ab; }
    </style>
</head>
<body>

    <div id="splash-screen">
        <div class="splash-icon">
            <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>
        </div>
        <h2 style="color: var(--md-sys-color-primary); font-weight: 600;">DEEPGYA</h2>
    </div>

    <div class="app-container" id="main-content">
        <div class="card">
            
            <div class="header-icon">
                <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            </div>
            
            <h1>Welcome Back</h1>
            <p class="subtitle">Sign in to continue to DEEPGYA Service</p>

            <form action="login.php" method="post" id="loginForm">
                
                <div class="input-group">
                    <input type="email" name="email" class="app-input" placeholder="Email Address" value="<?php echo isset($_COOKIE['remembered_email']) ? htmlspecialchars($_COOKIE['remembered_email']) : ''; ?>" required >
                    <svg class="input-icon" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                </div>

                <div class="input-group">
                    <input type="password" name="password" class="app-input" placeholder="Password" required >
                    <svg class="input-icon" viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                </div>

                <div class="row-between">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" class="app-checkbox" <?php echo isset($_COOKIE['remembered_email']) ? 'checked' : ''; ?>>
                        Remember me
                    </label>
                    <a href="forgot-password.html" class="forgot-link">Forgot?</a>
                </div>

                <button type="submit" class="btn-primary" id="submitBtn">
                    <span class="btn-text">
                        Sign In 
                        <svg viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>
                    </span>
                    <div class="btn-spinner"></div>
                </button>

            </form>
        </div>
    </div>

    <div id="app-snackbar">
        <svg class="snackbar-icon" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
        <span id="snackbar-message">Error message goes here</span>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const splashScreen = document.getElementById('splash-screen');
            const mainContent = document.getElementById('main-content');
            const loginForm = document.getElementById('loginForm');
            const submitBtn = document.getElementById('submitBtn');

            setTimeout(() => {
                splashScreen.classList.add('hidden-splash');
                document.body.style.overflow = 'auto';
                setTimeout(() => {
                    mainContent.classList.add('loaded');
                }, 100);
            }, 800); 

            loginForm.addEventListener('submit', (e) => {
                submitBtn.classList.add('loading');
                submitBtn.setAttribute('disabled', 'true');
            });
        });

        function showSnackbar(message) {
            const snackbar = document.getElementById('app-snackbar');
            document.getElementById('snackbar-message').innerText = message;
            
            snackbar.classList.add('show');
            
            setTimeout(() => {
                snackbar.classList.remove('show');
                window.history.replaceState(null, null, window.location.pathname);
            }, 4000);
        }
    </script>

    <?php
    if (isset($_GET["error"])) {
        $errorType = htmlspecialchars($_GET["error"]);
        $text = $errorType === 'invalid_user' ? 'No account found with this email.' : 'The password you entered is incorrect.';
        
        echo "<script>
            window.addEventListener('load', () => {
                setTimeout(() => {
                    showSnackbar('" . addslashes($text) . "');
                }, 1200); 
            });
        </script>";
    }
    ?>

</body>
</html>