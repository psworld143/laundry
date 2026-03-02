<?php
require_once 'config.php';
require_once 'mfa.php';

// Redirect if already logged in
if (auth()) redirect('dashboard.php');

$error = '';

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // Rate limiting check
    $ip = $_SERVER['REMOTE_ADDR'];
    if (!$security->checkRateLimit($ip, 5, 300)) {
        $error = 'Too many login attempts. Please try again later.';
    } elseif ($security->isUserBlocked($ip)) {
        $error = 'Access temporarily blocked due to suspicious activity.';
    } else {
        $username = sanitize($_POST['username'] ?? '', 'string');
        $password = $_POST['password'] ?? '';
        
        // CSRF validation
        if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
            $error = 'Invalid request token';
            $security->logSecurityEvent('CSRF_FAILURE', ['ip' => $ip]);
        } elseif (empty($username) || empty($password)) {
            $error = 'Please enter username and password';
        } elseif (!verifyRecaptcha($_POST['g-recaptcha-response'] ?? '')) {
            $error = 'Please complete the reCAPTCHA verification';
        } else {
            $stmt = $security->secureQuery("SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1", [$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && $security->verifyPassword($password, $user['password_hash'])) {
                // Check if MFA is enabled
                $mfa = new MFA($db, $security);
                if ($mfa->isMFAEnabled($user['user_id'])) {
                    // Store temp user data and redirect to MFA verification
                    $_SESSION['temp_user_id'] = $user['user_id'];
                    $_SESSION['temp_user_data'] = $user;
                    $security->logSecurityEvent('MFA_REQUIRED', ['user_id' => $user['user_id']]);
                    redirect('mfa_verify.php');
                } else {
                    // Complete login without MFA
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['position'] = $user['position'];
                    $_SESSION['session_regenerated'] = false;
                    $security->logSecurityEvent('LOGIN_SUCCESS', ['user_id' => $user['user_id'], 'username' => $username]);
                    redirect('dashboard.php');
                }
            } else {
                $error = 'Invalid credentials';
                $security->logSecurityEvent('LOGIN_FAILURE', ['username' => $username, 'ip' => $ip]);
                
                $attempts = $security->getRateLimitData('rate_limit_' . md5($ip));
                if ($attempts['attempts'] >= 5) {
                    $security->blockUser($ip, 900);
                }
            }
        }
    }
}

// Handle Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $ip = $_SERVER['REMOTE_ADDR'];
    if (!$security->checkRateLimit($ip . '_reg', 3, 300)) {
        $error = 'Too many registration attempts. Please try again later.';
    } else {
        $username = sanitize($_POST['reg_username'] ?? '', 'string');
        $name = sanitize($_POST['reg_name'] ?? '', 'string');
        $email = sanitize($_POST['reg_email'] ?? '', 'email');
        $password = $_POST['reg_password'] ?? '';
        $phone = sanitize($_POST['reg_phone'] ?? '', 'string');
        
        if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
            $error = 'Invalid request token';
            $security->logSecurityEvent('CSRF_FAILURE', ['ip' => $ip]);
        } else {
            $validationRules = [
                'reg_username' => ['required', 'min:3', 'max:20'],
                'reg_name' => ['required', 'min:2', 'max:50'],
                'reg_email' => ['required', 'email'],
                'reg_password' => ['required', 'min:8']
            ];
            
            $inputData = [
                'reg_username' => $username,
                'reg_name' => $name,
                'reg_email' => $email,
                'reg_password' => $password
            ];
            
            $validationErrors = $security->validateInput($inputData, $validationRules);
            $passwordErrors = $security->validatePasswordStrength($password);
            $validationErrors = array_merge($validationErrors, $passwordErrors);
            
            if (!empty($validationErrors)) {
                $error = implode('<br>', array_values($validationErrors));
            } elseif (!verifyRecaptcha($_POST['g-recaptcha-response'] ?? '')) {
                $error = 'Please complete the reCAPTCHA verification';
            } else {
                $hash = $security->hashPassword($password);
                
                try {
                    $security->secureQuery("INSERT INTO users (username, name, email, password_hash, phone_number, position) VALUES (?, ?, ?, ?, ?, 'user')", [$username, $name, $email, $hash, $phone]);
                    $security->logSecurityEvent('REGISTRATION_SUCCESS', ['username' => $username, 'email' => $email, 'ip' => $ip]);
                    $error = '<span class="success-msg">Registration successful! Please login.</span>';
                } catch (Exception $e) {
                    $error = 'Username or email already exists';
                    $security->logSecurityEvent('REGISTRATION_FAILURE', ['username' => $username, 'email' => $email, 'error' => $e->getMessage(), 'ip' => $ip]);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://www.google.com/recaptcha/api.js?onload=recaptchaOnload&render=explicit" async defer></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --ink: #0f1117;
            --ink-soft: #3d4258;
            --ink-muted: #8b91a8;
            --surface: #ffffff;
            --surface-2: #f5f6fa;
            --border: #e4e6ef;
            --accent: #2563eb;
            --accent-light: #3b82f6;
            --accent-glow: rgba(37,99,235,0.15);
            --teal: #0d9488;
            --teal-light: #14b8a6;
            --red: #dc2626;
            --green: #16a34a;
            --radius: 16px;
            --radius-sm: 10px;
            --shadow: 0 8px 40px rgba(15,17,23,0.08), 0 2px 8px rgba(15,17,23,0.04);
            --shadow-lg: 0 24px 80px rgba(15,17,23,0.14), 0 4px 16px rgba(15,17,23,0.06);
        }

        html, body {
            height: 100%;
            font-family: 'DM Sans', sans-serif;
            background: #0d1117;
            overflow-x: hidden;
        }

        /* ── Background ── */
        .bg-scene {
            position: fixed;
            inset: 0;
            z-index: 0;
            overflow: hidden;
        }

        .bg-scene svg {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        /* Floating orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.45;
            animation: drift 18s ease-in-out infinite alternate;
        }
        .orb-1 { width: 600px; height: 600px; background: #1d4ed8; top: -160px; left: -120px; animation-duration: 20s; }
        .orb-2 { width: 500px; height: 500px; background: #0d9488; bottom: -100px; right: -80px; animation-duration: 25s; animation-delay: -8s; }
        .orb-3 { width: 340px; height: 340px; background: #7c3aed; top: 40%; left: 55%; animation-duration: 16s; animation-delay: -4s; }

        @keyframes drift {
            from { transform: translate(0, 0) scale(1); }
            to   { transform: translate(40px, 30px) scale(1.06); }
        }

        /* ── Layout ── */
        .page {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        /* Left panel — branding */
        .left-panel {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px 56px;
            position: relative;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            backdrop-filter: blur(12px);
            border-radius: 100px;
            padding: 8px 18px;
            width: fit-content;
            margin-bottom: 40px;
        }
        .brand-badge .dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #22d3ee;
            box-shadow: 0 0 8px #22d3ee;
            animation: pulse-dot 2s ease-in-out infinite;
        }
        @keyframes pulse-dot {
            0%,100% { opacity: 1; transform: scale(1); }
            50%      { opacity: 0.6; transform: scale(1.3); }
        }
        .brand-badge span {
            font-family: 'Syne', sans-serif;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.06em;
            color: rgba(255,255,255,0.7);
            text-transform: uppercase;
        }

        .left-heading {
            font-family: 'Syne', sans-serif;
            font-size: clamp(36px, 4vw, 56px);
            font-weight: 800;
            line-height: 1.1;
            color: #fff;
            margin-bottom: 20px;
        }
        .left-heading .accent-word {
            background: linear-gradient(135deg, #38bdf8, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .left-desc {
            font-size: 16px;
            line-height: 1.7;
            color: rgba(255,255,255,0.5);
            max-width: 380px;
            margin-bottom: 48px;
        }

        .feature-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .feature-list li {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            color: rgba(255,255,255,0.65);
        }
        .feature-list li .icon-wrap {
            width: 34px; height: 34px;
            border-radius: 8px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.1);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            font-size: 13px;
            color: #38bdf8;
        }

        /* Decorative curve divider */
        .curve-divider {
            position: absolute;
            right: -2px;
            top: 0;
            height: 100%;
            width: 80px;
            z-index: 2;
        }

        /* ── Right panel — form ── */
        .right-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 32px;
            background: rgba(255,255,255,0.03);
            backdrop-filter: blur(4px);
        }

        .card {
            background: #ffffff;
            border-radius: 24px;
            padding: 44px 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: var(--shadow-lg);
            animation: card-in 0.6s cubic-bezier(.22,.68,0,1.2) both;
        }
        @keyframes card-in {
            from { opacity: 0; transform: translateY(28px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0)    scale(1); }
        }

        /* Logo area */
        .card-logo {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 32px;
        }
        .logo-icon {
            width: 48px; height: 48px;
            background: linear-gradient(135deg, var(--accent), #7c3aed);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            color: #fff;
            flex-shrink: 0;
            box-shadow: 0 4px 16px rgba(37,99,235,0.35);
        }
        .logo-text h1 {
            font-family: 'Syne', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.2;
        }
        .logo-text p {
            font-size: 12px;
            color: var(--ink-muted);
            letter-spacing: 0.02em;
        }

        /* Tab switcher */
        .tab-bar {
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: var(--surface-2);
            border-radius: var(--radius-sm);
            padding: 4px;
            margin-bottom: 28px;
            gap: 4px;
        }
        .tab-btn {
            background: transparent;
            border: none;
            border-radius: 8px;
            padding: 10px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 500;
            color: var(--ink-muted);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .tab-btn.active {
            background: #fff;
            color: var(--accent);
            box-shadow: 0 2px 8px rgba(15,17,23,0.08);
        }

        /* Alert */
        .alert {
            border-radius: var(--radius-sm);
            padding: 12px 14px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            animation: slide-down 0.3s ease;
        }
        @keyframes slide-down {
            from { opacity:0; transform:translateY(-6px); }
            to   { opacity:1; transform:translateY(0); }
        }
        .alert.error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: var(--red);
        }
        .alert.success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: var(--green);
        }
        .alert i { margin-top: 1px; flex-shrink: 0; }

        /* Form elements */
        .form-group { margin-bottom: 18px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 18px; }

        label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--ink-soft);
            margin-bottom: 7px;
        }

        .input-wrap {
            position: relative;
        }
        .input-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 14px;
            color: var(--ink-muted);
            pointer-events: none;
            transition: color 0.2s;
        }
        .input-wrap input {
            width: 100%;
            padding: 11px 14px 11px 40px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            color: var(--ink);
            background: var(--surface);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .input-wrap input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }
        .input-wrap input:focus + i,
        .input-wrap input:focus ~ i { color: var(--accent); }
        /* Fix icon color on focus (icon comes before in DOM) */
        .input-wrap input:focus { }
        .input-wrap:focus-within i { color: var(--accent); }

        input::placeholder { color: #b5baca; }

        /* Buttons */
        .btn-primary {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: var(--radius-sm);
            font-family: 'Syne', sans-serif;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            letter-spacing: 0.01em;
        }
        .btn-primary.blue {
            background: linear-gradient(135deg, var(--accent), #4f46e5);
            color: #fff;
            box-shadow: 0 4px 18px rgba(37,99,235,0.35);
        }
        .btn-primary.blue:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(37,99,235,0.4);
        }
        .btn-primary.blue:active { transform: translateY(0); }

        .btn-primary.teal {
            background: linear-gradient(135deg, var(--teal), var(--accent));
            color: #fff;
            box-shadow: 0 4px 18px rgba(13,148,136,0.35);
        }
        .btn-primary.teal:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(13,148,136,0.4);
        }

        /* Footer link */
        .form-footer {
            text-align: center;
            margin-top: 22px;
            font-size: 13px;
            color: var(--ink-muted);
        }
        .form-footer a {
            color: var(--accent);
            font-weight: 500;
            text-decoration: none;
            margin-left: 4px;
        }
        .form-footer a:hover { text-decoration: underline; }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 20px 0;
            color: var(--border);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--ink-muted);
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .services-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 13px;
            color: var(--ink-muted);
            text-decoration: none;
            padding: 8px;
            border-radius: 8px;
            transition: background 0.2s, color 0.2s;
        }
        .services-link:hover {
            background: var(--surface-2);
            color: var(--accent);
        }

        .success-msg { color: var(--green); }

        /* reCAPTCHA styling */
        .g-recaptcha {
            margin: 16px 0;
            transform: scale(0.95);
            transform-origin: left center;
        }
        @media (max-width: 480px) {
            .g-recaptcha {
                transform: scale(0.85);
                transform-origin: left center;
            }
        }

        /* Responsive */
        @media (max-width: 820px) {
            .page { grid-template-columns: 1fr; }
            .left-panel { display: none; }
            .right-panel { padding: 24px 16px; background: transparent; min-height: 100vh; }
            .card { padding: 32px 24px; }
        }
    </style>
</head>
<body>

<!-- Background scene -->
<div class="bg-scene">
    <!-- SVG curved waves -->
    <svg viewBox="0 0 1440 900" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="wave1" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#1e3a8a" stop-opacity="0.6"/>
                <stop offset="100%" stop-color="#312e81" stop-opacity="0.2"/>
            </linearGradient>
            <linearGradient id="wave2" x1="100%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" stop-color="#0f766e" stop-opacity="0.4"/>
                <stop offset="100%" stop-color="#1e40af" stop-opacity="0.1"/>
            </linearGradient>
            <linearGradient id="wave3" x1="0%" y1="100%" x2="100%" y2="0%">
                <stop offset="0%" stop-color="#6d28d9" stop-opacity="0.25"/>
                <stop offset="100%" stop-color="#0891b2" stop-opacity="0.1"/>
            </linearGradient>
        </defs>
        <!-- Wave 1 - large sweeping curve -->
        <path d="M-100,600 C200,400 400,800 700,500 C900,300 1100,700 1540,400 L1540,900 L-100,900 Z"
              fill="url(#wave1)" opacity="0.7"/>
        <!-- Wave 2 - mid curve -->
        <path d="M-100,720 C300,550 500,850 800,620 C1000,460 1250,780 1540,560 L1540,900 L-100,900 Z"
              fill="url(#wave2)" opacity="0.5"/>
        <!-- Wave 3 - small accent -->
        <path d="M0,820 C250,700 450,880 720,780 C920,700 1100,860 1440,750 L1440,900 L0,900 Z"
              fill="url(#wave3)" opacity="0.6"/>
        <!-- Top curves -->
        <path d="M0,0 C300,120 600,-60 900,80 C1150,180 1300,-30 1440,60 L1440,0 Z"
              fill="#1e3a8a" opacity="0.25"/>
        <!-- Decorative arcs -->
        <circle cx="120" cy="120" r="200" fill="none" stroke="rgba(255,255,255,0.04)" stroke-width="1"/>
        <circle cx="120" cy="120" r="300" fill="none" stroke="rgba(255,255,255,0.025)" stroke-width="1"/>
        <circle cx="1320" cy="780" r="180" fill="none" stroke="rgba(255,255,255,0.04)" stroke-width="1"/>
        <circle cx="1320" cy="780" r="280" fill="none" stroke="rgba(255,255,255,0.025)" stroke-width="1"/>
        <!-- Grid dots subtle -->
        <pattern id="dots" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
            <circle cx="1" cy="1" r="1" fill="rgba(255,255,255,0.06)"/>
        </pattern>
        <rect width="100%" height="100%" fill="url(#dots)"/>
    </svg>

    <!-- Orbs -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
</div>

<div class="page">

    <!-- ── Left Branding Panel ── -->
    <div class="left-panel">
        <div class="brand-badge">
            <span class="dot"></span>
            <span>Laundry Management</span>
        </div>

        <h2 class="left-heading">
            Clean clothes,<br>
            <span class="accent-word">effortless</span><br>
            management.
        </h2>

        <p class="left-desc">
            A modern platform built for laundry businesses to streamline orders, track customers, and grow revenue — all in one place.
        </p>

        <ul class="feature-list">
            <li>
                <span class="icon-wrap"><i class="fas fa-bolt"></i></span>
                Real-time order tracking & status updates
            </li>
            <li>
                <span class="icon-wrap"><i class="fas fa-users"></i></span>
                Customer management & loyalty tools
            </li>
            <li>
                <span class="icon-wrap"><i class="fas fa-chart-line"></i></span>
                Revenue analytics & reports
            </li>
            <li>
                <span class="icon-wrap"><i class="fas fa-shield-alt"></i></span>
                Secure, role-based access control
            </li>
        </ul>

        <!-- Curve divider -->
        <svg class="curve-divider" viewBox="0 0 80 900" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M80,0 C20,150 60,350 10,450 C-20,520 40,700 80,900 L80,0 Z"
                  fill="rgba(255,255,255,0.025)"/>
            <path d="M80,0 C50,200 70,400 30,500 C5,570 60,750 80,900 L80,0 Z"
                  fill="rgba(255,255,255,0.015)"/>
        </svg>
    </div>

    <!-- ── Right Form Panel ── -->
    <div class="right-panel">
        <div class="card">
            <!-- Logo -->
            <div class="card-logo">
                <div class="logo-icon">
                    <i class="fas fa-tshirt"></i>
                </div>
                <div class="logo-text">
                    <h1><?= APP_NAME ?></h1>
                    <p>Laundry Management System</p>
                </div>
            </div>

            <!-- Tab bar -->
            <div class="tab-bar" role="tablist">
                <button class="tab-btn active" id="tab-login" onclick="showLogin()" role="tab" aria-selected="true">
                    <i class="fas fa-sign-in-alt" style="margin-right:6px;font-size:12px;"></i>Sign In
                </button>
                <button class="tab-btn" id="tab-register" onclick="showRegister()" role="tab" aria-selected="false">
                    <i class="fas fa-user-plus" style="margin-right:6px;font-size:12px;"></i>Sign Up
                </button>
            </div>

            <!-- Alert -->
            <?php if ($error): ?>
            <?php $isSuccess = strpos($error, 'success') !== false || strpos($error, 'successful') !== false; ?>
            <div class="alert <?= $isSuccess ? 'success' : 'error' ?>">
                <i class="fas <?= $isSuccess ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                <span><?= $error ?></span>
            </div>
            <?php endif; ?>

            <!-- ── Login Form ── -->
            <div id="loginForm">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">

                    <div class="form-group">
                        <label for="username">Username or Email</label>
                        <div class="input-wrap">
                            <i class="fas fa-user"></i>
                            <input type="text" id="username" name="username" placeholder="Enter your username or email" required autocomplete="username">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrap">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                        </div>
                    </div>

                    <div class="g-recaptcha" id="recaptcha-login" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>"></div>

                    <button type="submit" name="login" class="btn-primary blue" style="margin-top:8px;">
                        <i class="fas fa-arrow-right-to-bracket"></i>
                        Sign In to Dashboard
                    </button>
                </form>

                <div class="divider">or</div>

                <a href="services.php" class="services-link">
                    <i class="fas fa-concierge-bell"></i>
                    Browse our services without logging in
                </a>
            </div>

            <!-- ── Register Form ── -->
            <div id="registerForm" style="display:none;">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">

                    <div class="form-grid">
                        <div>
                            <label for="reg_username">Username</label>
                            <div class="input-wrap">
                                <i class="fas fa-at"></i>
                                <input type="text" id="reg_username" name="reg_username" placeholder="username" required>
                            </div>
                        </div>
                        <div>
                            <label for="reg_name">Full Name</label>
                            <div class="input-wrap">
                                <i class="fas fa-id-card"></i>
                                <input type="text" id="reg_name" name="reg_name" placeholder="Your name" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reg_email">Email Address</label>
                        <div class="input-wrap">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="reg_email" name="reg_email" placeholder="you@email.com" required autocomplete="email">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reg_phone">Phone Number <span style="color:var(--ink-muted);font-weight:400;">(optional)</span></label>
                        <div class="input-wrap">
                            <i class="fas fa-phone"></i>
                            <input type="tel" id="reg_phone" name="reg_phone" placeholder="+63 912 345 6789">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reg_password">Password</label>
                        <div class="input-wrap">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="reg_password" name="reg_password" placeholder="Min. 8 characters" required autocomplete="new-password">
                        </div>
                    </div>

                    <div class="g-recaptcha" id="recaptcha-register" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>"></div>

                    <button type="submit" name="register" class="btn-primary teal">
                        <i class="fas fa-user-plus"></i>
                        Create My Account
                    </button>
                </form>
            </div>

        </div><!-- /.card -->
    </div><!-- /.right-panel -->

</div><!-- /.page -->

<script>
    function recaptchaOnload() {
        console.log('reCAPTCHA script loaded successfully');
        console.log('Site key:', '<?= RECAPTCHA_SITE_KEY ?>');
        
        // Explicitly render reCAPTCHA widgets
        try {
            if (typeof grecaptcha !== 'undefined') {
                // Render login reCAPTCHA
                var loginWidget = grecaptcha.render('recaptcha-login', {
                    'sitekey': '<?= RECAPTCHA_SITE_KEY ?>',
                    'theme': 'light'
                });
                console.log('Login reCAPTCHA rendered with widget ID:', loginWidget);
                
                // Render register reCAPTCHA
                var registerWidget = grecaptcha.render('recaptcha-register', {
                    'sitekey': '<?= RECAPTCHA_SITE_KEY ?>',
                    'theme': 'light'
                });
                console.log('Register reCAPTCHA rendered with widget ID:', registerWidget);
            } else {
                console.error('reCAPTCHA is not available');
            }
        } catch (error) {
            console.error('Error rendering reCAPTCHA:', error);
        }
    }
    
    window.onload = function() {
        if (typeof grecaptcha !== 'undefined') {
            console.log('reCAPTCHA is available');
        } else {
            console.error('reCAPTCHA is not available');
        }
    };
    
    function showRegister() {
        document.getElementById('loginForm').style.display = 'none';
        document.getElementById('registerForm').style.display = 'block';
        document.getElementById('tab-login').classList.remove('active');
        document.getElementById('tab-register').classList.add('active');
    }
    function showLogin() {
        document.getElementById('registerForm').style.display = 'none';
        document.getElementById('loginForm').style.display = 'block';
        document.getElementById('tab-register').classList.remove('active');
        document.getElementById('tab-login').classList.add('active');
    }
</script>
</body>
</html>