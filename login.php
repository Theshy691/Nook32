<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();
require_once __DIR__ . '/config/database.php';

$error = '';
$portal = $_POST['portal'] ?? ($_GET['portal'] ?? 'cashier');
if (!in_array($portal, ['cashier', 'admin'], true)) {
    $portal = 'cashier';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $portal = trim($_POST['portal'] ?? 'cashier');

    if ($username === '' || $password === '') {
        $error = 'Please provide both your terminal username and password.';
    } else {
        try {
            $stmt = $conn->prepare(
                "SELECT user_id, full_name, username, password, role
                 FROM users
                 WHERE username = ?
                 LIMIT 1"
            );

            $stmt->bind_param("s", $username);
            $stmt->execute();

            $stmt->bind_result(
                $userId,
                $fullName,
                $dbUsername,
                $hashedPassword,
                $role
            );

            if ($stmt->fetch() && password_verify($password, $hashedPassword)) {
                $stmt->close();

                if ($portal === 'admin' && strtolower($role) !== 'admin') {
                    $error = 'Access denied: Administrator credentials required for Admin Console.';
                } else {
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $userId;
                    $_SESSION['full_name'] = $fullName;
                    $_SESSION['username'] = $dbUsername;
                    $_SESSION['role'] = $role;
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                    if ($portal === 'admin') {
                        header('Location: dashboard.php');
                    } else {
                        header('Location: index.php');
                    }
                    exit;
                }
            } else {
                $stmt->close();
                $error = 'Invalid username or password. Please verify your credentials.';
            }

        } catch (Throwable $e) {
            error_log('Login error: ' . $e->getMessage());
            $error = 'Database connection error. Please contact technical support.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — Nook32 Smart POS</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-orange: #f96b27;
            --primary-orange-hover: #ea580c;
            --primary-glow: rgba(249, 107, 39, 0.28);
            --orange-soft: #fff4ed;
            
            --text-dark: #18181b;
            --text-muted: #71717a;
            --text-light: #a1a1aa;
            
            --border-color: #e4e4e7;
            --border-focus: #f96b27;
            --input-bg: #fcfcfd;
            
            --right-bg-gradient: linear-gradient(135deg, #fbeee4 0%, #f7d5be 50%, #f0bc9e 100%);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: #ffffff;
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            align-items: stretch;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        /* SPLIT SCREEN LAYOUT */
        .split-layout {
            display: flex;
            width: 100vw;
            min-height: 100vh;
        }

        /* =========================================================
           LEFT COLUMN: LOGIN & AUTH FORM
           ========================================================= */
        .left-col {
            flex: 1.1;
            min-width: 320px;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 36px 56px 28px;
            position: relative;
        }

        /* Top Brand Header */
        .brand-header-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-logo-icon {
            width: 32px;
            height: 32px;
            border-radius: 9px;
            background: #18181b;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brand-logo-icon svg {
            width: 18px;
            height: 18px;
        }

        .brand-name-title {
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -0.4px;
            color: #18181b;
        }

        .brand-name-title span {
            color: var(--primary-orange);
        }

        /* Centered Form Wrapper */
        .auth-form-container {
            width: 100%;
            max-width: 410px;
            margin: auto;
            padding: 24px 0;
        }

        /* Circular Floating Icon in Reference */
        .auth-avatar-halo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: radial-gradient(circle, #ff8c42 0%, #f96b27 100%);
            box-shadow: 0 12px 28px rgba(249, 107, 39, 0.32);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: #ffffff;
            border: 3px solid #ffffff;
        }

        .auth-avatar-halo svg {
            width: 28px;
            height: 28px;
        }

        .auth-heading {
            text-align: center;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #18181b;
            margin-bottom: 6px;
        }

        .auth-subheading {
            text-align: center;
            font-size: 13.5px;
            color: var(--text-muted);
            margin-bottom: 26px;
            line-height: 1.4;
        }

        /* TAB BAR (Underline style from Reference) */
        .tabs-header-strip {
            display: flex;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 26px;
            gap: 28px;
            justify-content: center;
        }

        .portal-tab-btn {
            background: none;
            border: none;
            padding: 10px 4px 12px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            position: relative;
            transition: all 0.15s ease;
        }

        .portal-tab-btn svg {
            width: 17px;
            height: 17px;
            stroke-width: 2.2;
        }

        .portal-tab-btn:hover {
            color: var(--text-dark);
        }

        .portal-tab-btn.active {
            color: var(--text-dark);
            font-weight: 700;
        }

        .portal-tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2.5px;
            background: var(--primary-orange);
            border-radius: 4px 4px 0 0;
        }

        /* FORM INPUTS */
        .form-field-group {
            margin-bottom: 18px;
        }

        .form-field-label {
            display: block;
            font-size: 12.5px;
            font-weight: 700;
            color: #27272a;
            margin-bottom: 7px;
        }

        .input-box-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-lead-icon {
            position: absolute;
            left: 14px;
            color: var(--text-light);
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .input-lead-icon svg {
            width: 17px;
            height: 17px;
        }

        .clean-auth-input {
            width: 100%;
            height: 48px;
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 0 42px 0 42px;
            font-family: inherit;
            font-size: 14px;
            color: var(--text-dark);
            outline: none;
            transition: all 0.2s ease;
        }

        .clean-auth-input:focus {
            background: #ffffff;
            border-color: var(--border-focus);
            box-shadow: 0 0 0 3.5px var(--primary-glow);
        }

        .clean-auth-input::placeholder {
            color: var(--text-light);
            font-size: 13.5px;
        }

        .password-toggle-btn {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            padding: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .password-toggle-btn:hover {
            color: var(--text-dark);
        }

        /* ERROR ALERT BANNER */
        .error-alert-box {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 18px;
            font-size: 13px;
            color: #dc2626;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        /* PRIMARY ORANGE ACTION BUTTON (Matching Reference) */
        .btn-auth-primary {
            width: 100%;
            height: 50px;
            background: linear-gradient(135deg, #f96b27 0%, #ea580c 100%);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-family: inherit;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 8px 24px rgba(249, 107, 39, 0.35);
            transition: all 0.2s ease;
            margin-top: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-auth-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 28px rgba(249, 107, 39, 0.42);
        }

        .btn-auth-primary:active {
            transform: translateY(0);
        }

        /* Quick Account Credential Pills */
        .quick-accounts-card {
            margin-top: 22px;
            padding: 14px 16px;
            background: #fafafa;
            border: 1px solid #f0f0f2;
            border-radius: 12px;
            text-align: center;
        }

        .quick-accounts-title {
            font-size: 11.5px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }

        .quick-pills-row {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .btn-quick-fill {
            padding: 6px 14px;
            border-radius: 9999px;
            background: #ffffff;
            border: 1px solid var(--border-color);
            font-size: 12px;
            font-weight: 700;
            color: #27272a;
            cursor: pointer;
            transition: all 0.15s;
        }

        .btn-quick-fill:hover {
            border-color: var(--primary-orange);
            color: var(--primary-orange);
            background: var(--orange-soft);
        }

        /* Footer Terms */
        .auth-footer-terms {
            text-align: center;
            font-size: 12px;
            color: var(--text-light);
            line-height: 1.5;
        }

        .auth-footer-terms a {
            color: var(--primary-orange);
            text-decoration: none;
            font-weight: 600;
        }

        .auth-footer-terms a:hover {
            text-decoration: underline;
        }

        /* =========================================================
           RIGHT COLUMN: BRAND SHOWCASE & 3D ISOMETRIC POS MOCKUP
           ========================================================= */
        .right-col {
            flex: 1.15;
            background: var(--right-bg-gradient);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 56px 56px 40px;
            box-shadow: inset 1px 0 0 rgba(255, 255, 255, 0.4);
        }

        /* Background ambient circles */
        .right-col::before {
            content: '';
            position: absolute;
            width: 480px;
            height: 480px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.6) 0%, rgba(255, 255, 255, 0) 70%);
            top: -100px;
            right: -100px;
            pointer-events: none;
        }

        .hero-text-block {
            max-width: 480px;
            position: relative;
            z-index: 2;
        }

        .hero-badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(8px);
            font-size: 12px;
            font-weight: 700;
            color: #a34819;
            margin-bottom: 14px;
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        .hero-title-main {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.8px;
            line-height: 1.25;
            color: #2b180d;
            margin-bottom: 12px;
        }

        .hero-desc-main {
            font-size: 14.5px;
            color: #7c5844;
            line-height: 1.6;
        }

        /* 3D ISOMETRIC SCREEN PREVIEW (Matching Image Reference) */
        .isometric-mockup-wrapper {
            position: relative;
            width: 100%;
            height: 380px;
            margin-top: 30px;
            perspective: 1200px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .tilted-pos-device {
            width: 520px;
            height: 330px;
            background: #ffffff;
            border-radius: 20px;
            border: 6px solid #ffffff;
            box-shadow: -24px 34px 70px rgba(160, 70, 20, 0.3), 0 12px 30px rgba(0, 0, 0, 0.08);
            transform: rotateY(-18deg) rotateX(10deg) rotateZ(3deg) skewY(-3deg);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            position: relative;
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .tilted-pos-device:hover {
            transform: rotateY(-12deg) rotateX(6deg) rotateZ(2deg) translateY(-8px);
        }

        /* Inner Mockup UI Elements */
        .mock-topbar {
            height: 36px;
            background: #ffffff;
            border-bottom: 1px solid #f0eef3;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 14px;
        }

        .mock-brand-chip {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 800;
            color: #18181b;
        }

        .mock-pill-orange {
            padding: 3px 8px;
            border-radius: 9999px;
            background: #f96b27;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
        }

        .mock-screen-body {
            flex: 1;
            display: grid;
            grid-template-columns: 80px 1fr 140px;
            background: #faf9fc;
        }

        /* Mockup Mini Sidebar */
        .mock-side {
            background: #ffffff;
            border-right: 1px solid #f0eef3;
            padding: 10px 8px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .mock-side-pill {
            height: 22px;
            border-radius: 6px;
            background: #f4f3f6;
            display: flex;
            align-items: center;
            padding: 0 6px;
            font-size: 9px;
            font-weight: 700;
            color: #71717a;
        }

        .mock-side-pill.active {
            background: #fff4ed;
            color: #f96b27;
        }

        /* Mockup Mini Catalog */
        .mock-catalog {
            padding: 10px 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            overflow: hidden;
        }

        .mock-cat-strip {
            display: flex;
            gap: 4px;
        }

        .mock-cat-item {
            padding: 3px 8px;
            border-radius: 9999px;
            font-size: 9px;
            font-weight: 700;
            background: #ffffff;
            border: 1px solid #edecee;
        }

        .mock-cat-item.active {
            background: #f96b27;
            color: #ffffff;
            border-color: #f96b27;
        }

        .mock-cards-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .mock-drink-card {
            background: #ffffff;
            border: 1px solid #edecee;
            border-radius: 10px;
            padding: 8px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 90px;
        }

        .mock-card-name {
            font-size: 10px;
            font-weight: 700;
            color: #18181b;
        }

        .mock-card-price {
            font-size: 10px;
            font-weight: 800;
            color: #f96b27;
        }

        .mock-card-btn {
            height: 20px;
            border-radius: 9999px;
            background: #fff4ed;
            color: #f96b27;
            border: 1px solid #f96b27;
            font-size: 9px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Mockup Mini Cart */
        .mock-cart {
            background: #ffffff;
            border-left: 1px solid #f0eef3;
            padding: 10px 8px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .mock-cart-title {
            font-size: 10.5px;
            font-weight: 800;
            color: #18181b;
            margin-bottom: 6px;
        }

        .mock-cart-item {
            font-size: 9px;
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px solid #f5f5f8;
            color: #3f3f46;
        }

        .mock-btn-place {
            height: 26px;
            border-radius: 9999px;
            background: #f96b27;
            color: #ffffff;
            font-size: 9.5px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(249, 107, 39, 0.35);
        }

        /* Device Base/Stand Shadow */
        .device-shadow-blur {
            position: absolute;
            bottom: 10px;
            width: 440px;
            height: 30px;
            background: rgba(140, 60, 15, 0.28);
            filter: blur(20px);
            border-radius: 50%;
            transform: rotate(-4deg);
            z-index: 0;
        }

        /* RESPONSIVE BREAKPOINT */
        @media (max-width: 960px) {
            .split-layout {
                flex-direction: column;
            }
            .left-col {
                padding: 32px 24px;
            }
            .right-col {
                display: none;
            }
        }
    </style>
</head>
<body>

    <div class="split-layout">
        
        <!-- =========================================================
             LEFT SIDE: AUTHENTICATION FORM & TERMINAL SELECTION
             ========================================================= -->
        <div class="left-col">
            
            <!-- BRAND LOGO & TITLE -->
            <div class="brand-header-row">
                <div class="brand-logo-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
                        <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
                    </svg>
                </div>
                <div class="brand-name-title">
                    NOOK32<span>.</span>
                </div>
            </div>

            <!-- MAIN FORM WRAPPER -->
            <div class="auth-form-container">
                
                <!-- CIRCULAR FLOATING ICON IN REFERENCE -->
                <div class="auth-avatar-halo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>

                <h1 class="auth-heading">Getting Started with Nook32</h1>
                <p class="auth-subheading">Sign in with your staff terminal credentials to access the register.</p>

                <!-- TERMINAL ROLE TABS (Underline style from image) -->
                <div class="tabs-header-strip">
                    <button
                        type="button"
                        class="portal-tab-btn <?= $portal === 'cashier' ? 'active' : '' ?>"
                        id="tabCashier"
                        onclick="switchPortal('cashier')"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                            <line x1="8" y1="21" x2="16" y2="21"></line>
                            <line x1="12" y1="17" x2="12" y2="21"></line>
                        </svg>
                        <span>Cashier Terminal</span>
                    </button>

                    <button
                        type="button"
                        class="portal-tab-btn <?= $portal === 'admin' ? 'active' : '' ?>"
                        id="tabAdmin"
                        onclick="switchPortal('admin')"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                        <span>Admin Console</span>
                    </button>
                </div>

                <?php if ($error !== ''): ?>
                    <div class="error-alert-box">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <!-- LOGIN FORM -->
                <form method="POST" action="login.php" id="loginForm">
                    <input type="hidden" name="portal" id="portalInput" value="<?= htmlspecialchars($portal) ?>">

                    <div class="form-field-group">
                        <label class="form-field-label" for="username">Username</label>
                        <div class="input-box-wrapper">
                            <span class="input-lead-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </span>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                class="clean-auth-input"
                                placeholder="Enter username (e.g. cashier1)"
                                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                autocomplete="username"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-field-group">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:7px;">
                            <label class="form-field-label" for="password" style="margin-bottom:0;">Password</label>
                            <span id="roleHintBadge" style="font-size:11px; font-weight:700; color:var(--primary-orange);">
                                <?= $portal === 'admin' ? 'Requires Admin Privileges' : 'Front Counter POS' ?>
                            </span>
                        </div>
                        <div class="input-box-wrapper">
                            <span class="input-lead-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                            </span>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="clean-auth-input"
                                placeholder="Enter password"
                                autocomplete="current-password"
                                required
                            >
                            <button type="button" class="password-toggle-btn" onclick="togglePassVisibility()" title="Show/Hide Password">
                                <svg id="eyeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- ORANGE BUTTON (Matching Reference) -->
                    <button type="submit" class="btn-auth-primary" id="btnSubmitAuth">
                        <span>Sign In to Terminal</span>
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </button>

                    <!-- QUICK FILL ACCOUNT PRESETS FOR CONVENIENCE -->
                    <div class="quick-accounts-card">
                        <div class="quick-accounts-title">Quick Demo Logins</div>
                        <div class="quick-pills-row">
                            <button type="button" class="btn-quick-fill" onclick="quickFill('cashier1', 'Cashier123!', 'cashier')">
                                Cashier (cashier1)
                            </button>
                            <button type="button" class="btn-quick-fill" onclick="quickFill('admin', 'Admin123!', 'admin')">
                                Admin (admin)
                            </button>
                        </div>
                    </div>

                </form>

            </div>

            <!-- FOOTER LEGAL -->
            <div class="auth-footer-terms">
                By continuing, you confirm that you have read and agreed to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.
            </div>

        </div>

        <!-- =========================================================
             RIGHT SIDE: HERO SHOWCASE & 3D ISOMETRIC POS MOCKUP
             ========================================================= -->
        <div class="right-col">
            
            <div class="hero-text-block">
                <div class="hero-badge-pill">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                    <span>Artisan Coffee & POS Platform</span>
                </div>

                <h2 class="hero-title-main">
                    Artisan Craft &<br>Smart POS System
                </h2>

                <p class="hero-desc-main">
                    Real-time beverage ordering, automated recipe inventory deductions, QR Ph digital wallet verification, and executive revenue reports.
                </p>
            </div>

            <!-- ISOMETRIC PERSPECTIVE MOCKUP OF THE POS REGISTER -->
            <div class="isometric-mockup-wrapper">
                <div class="device-shadow-blur"></div>

                <div class="tilted-pos-device">
                    
                    <!-- Mini Top Bar -->
                    <div class="mock-topbar">
                        <div class="mock-brand-chip">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f96b27" stroke-width="2.5">
                                <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
                                <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
                            </svg>
                            <span>NOOK32 COFFEE</span>
                        </div>
                        <span class="mock-pill-orange">LIVE REGISTER</span>
                    </div>

                    <!-- Mini Screen Body -->
                    <div class="mock-screen-body">
                        
                        <!-- Mini Sidebar -->
                        <div class="mock-side">
                            <div class="mock-side-pill active">POS Menu</div>
                            <div class="mock-side-pill">Orders</div>
                            <div class="mock-side-pill">Dashboard</div>
                            <div class="mock-side-pill">Inventory</div>
                        </div>

                        <!-- Mini Catalog -->
                        <div class="mock-catalog">
                            <div class="mock-cat-strip">
                                <span class="mock-cat-item active">Coffee</span>
                                <span class="mock-cat-item">Non Coffee</span>
                                <span class="mock-cat-item">Pastry</span>
                            </div>

                            <div class="mock-cards-row">
                                <div class="mock-drink-card">
                                    <div>
                                        <div class="mock-card-name">Cappuccino</div>
                                        <div class="mock-card-price">₱110.00</div>
                                    </div>
                                    <div class="mock-card-btn">Added ✓</div>
                                </div>

                                <div class="mock-drink-card">
                                    <div>
                                        <div class="mock-card-name">Spanish Latte</div>
                                        <div class="mock-card-price">₱130.00</div>
                                    </div>
                                    <div class="mock-card-btn">+ Add</div>
                                </div>
                            </div>
                        </div>

                        <!-- Mini Cart -->
                        <div class="mock-cart">
                            <div>
                                <div class="mock-cart-title">Order #3243</div>
                                <div class="mock-cart-item">
                                    <span>Cappuccino × 2</span>
                                    <strong>₱220</strong>
                                </div>
                                <div class="mock-cart-item">
                                    <span>Spanish Latte × 1</span>
                                    <strong>₱130</strong>
                                </div>
                            </div>

                            <div>
                                <div style="display:flex; justify-content:space-between; font-size:9.5px; font-weight:800; margin-bottom:5px;">
                                    <span>Total Due</span>
                                    <span style="color:#f96b27;">₱350.00</span>
                                </div>
                                <div class="mock-btn-place">
                                    Place an order
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </div>

            <!-- Bottom Note -->
            <div style="font-size:12px; color:#8d6854; z-index:2;">
                Nook32 POS v2.4 · All rights reserved
            </div>

        </div>

    </div>

    <script>
        function switchPortal(portal) {
            document.getElementById('portalInput').value = portal;
            
            const tabCashier = document.getElementById('tabCashier');
            const tabAdmin = document.getElementById('tabAdmin');
            const hint = document.getElementById('roleHintBadge');
            const btn = document.getElementById('btnSubmitAuth');
            
            if (portal === 'cashier') {
                tabCashier.classList.add('active');
                tabAdmin.classList.remove('active');
                hint.textContent = 'Front Counter POS';
                btn.querySelector('span').textContent = 'Sign In to Cashier Terminal';
            } else {
                tabAdmin.classList.add('active');
                tabCashier.classList.remove('active');
                hint.textContent = 'Requires Admin Privileges';
                btn.querySelector('span').textContent = 'Sign In to Admin Console';
            }
        }

        function quickFill(user, pass, portal) {
            switchPortal(portal);
            document.getElementById('username').value = user;
            document.getElementById('password').value = pass;
            document.getElementById('password').focus();
        }

        function togglePassVisibility() {
            const passInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passInput.type === 'password') {
                passInput.type = 'text';
                eyeIcon.innerHTML = `
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                    <line x1="1" y1="1" x2="23" y2="23"></line>
                `;
            } else {
                passInput.type = 'password';
                eyeIcon.innerHTML = `
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                `;
            }
        }
    </script>
</body>
</html>