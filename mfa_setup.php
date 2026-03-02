<?php
require_once 'config.php';

// Redirect if not logged in
if (!auth()) {
    redirect('login.php');
}

$mfa = new MFA($db, $security);
$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle MFA setup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_mfa'])) {
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
        $error = 'Invalid request token';
    } else {
        $secret = $_POST['mfa_secret'] ?? '';
        $token = $_POST['verification_code'] ?? '';
        
        if (empty($secret) || empty($token)) {
            $error = 'Missing secret or verification code';
        } elseif (!$mfa->verifyToken($secret, $token)) {
            $error = 'Invalid verification code';
        } else {
            $backupCodes = $mfa->generateBackupCodes();
            if ($mfa->enableMFA($userId, $secret, $backupCodes)) {
                $success = 'MFA enabled successfully! Save your backup codes securely.';
                $backupCodesList = $backupCodes;
            } else {
                $error = 'Failed to enable MFA. Please try again.';
            }
        }
    }
}

// Handle MFA disable
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['disable_mfa'])) {
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
        $error = 'Invalid request token';
    } else {
        $password = $_POST['confirm_password'] ?? '';
        $user = $security->secureQuery("SELECT password_hash FROM users WHERE user_id = ?", [$userId])->fetch();
        
        if (!$user || !$security->verifyPassword($password, $user['password_hash'])) {
            $error = 'Invalid password';
        } elseif ($mfa->disableMFA($userId)) {
            $success = 'MFA disabled successfully';
        } else {
            $error = 'Failed to disable MFA. Please try again.';
        }
    }
}

// Generate new secret for setup
$setupSecret = $mfa->generateSecret();
$provisioningUri = $mfa->getProvisioningUri($setupSecret, $_SESSION['email'], APP_NAME);
$isMFAEnabled = $mfa->isMFAEnabled($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MFA Settings — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
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
            --red: #dc2626;
            --green: #16a34a;
            --radius: 12px;
            --shadow: 0 4px 20px rgba(15,17,23,0.08);
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--surface-2);
            color: var(--ink);
            line-height: 1.6;
        }
        .container { max-width: 600px; margin: 40px auto; padding: 0 20px; }
        .card {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 32px;
            box-shadow: var(--shadow);
        }
        h1 { font-size: 24px; margin-bottom: 8px; }
        .subtitle { color: var(--ink-muted); margin-bottom: 24px; }
        .alert {
            border-radius: 8px; padding: 12px 16px; margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
        }
        .alert.error { background: #fef2f2; border: 1px solid #fecaca; color: var(--red); }
        .alert.success { background: #f0fdf4; border: 1px solid #bbf7d0; color: var(--green); }
        .qr-code {
            width: 200px; height: 200px; margin: 20px auto;
            border: 1px solid var(--border); border-radius: 8px; padding: 10px;
            background: white;
        }
        .secret-box {
            background: var(--surface-2); border: 1px solid var(--border);
            border-radius: 8px; padding: 12px; font-family: monospace;
            word-break: break-all; margin: 16px 0;
        }
        .backup-codes {
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px;
            margin: 16px 0;
        }
        .backup-code {
            background: var(--surface-2); border: 1px solid var(--border);
            border-radius: 6px; padding: 8px 12px; font-family: monospace;
            font-size: 14px; text-align: center;
        }
        .btn {
            background: var(--accent); color: white; border: none;
            border-radius: 8px; padding: 12px 20px; font-weight: 500;
            cursor: pointer; transition: background 0.2s;
        }
        .btn:hover { background: var(--accent-light); }
        .btn-danger { background: var(--red); }
        .btn-danger:hover { background: #b91c1c; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 6px; font-weight: 500; }
        input[type="text"], input[type="password"] {
            width: 100%; padding: 10px 12px; border: 1px solid var(--border);
            border-radius: 6px; font-size: 14px;
        }
        .status-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 12px; border-radius: 20px; font-size: 13px;
            font-weight: 500;
        }
        .status-badge.enabled { background: #dcfce7; color: var(--green); }
        .status-badge.disabled { background: #fef2f2; color: var(--red); }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Multi-Factor Authentication</h1>
            <p class="subtitle">Add an extra layer of security to your account</p>
            
            <?php if ($error): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>
            
            <div style="margin-bottom: 24px;">
                <strong>Status:</strong>
                <span class="status-badge <?= $isMFAEnabled ? 'enabled' : 'disabled' ?>">
                    <i class="fas fa-<?= $isMFAEnabled ? 'check' : 'times' ?>"></i>
                    <?= $isMFAEnabled ? 'Enabled' : 'Disabled' ?>
                </span>
            </div>
            
            <?php if (!$isMFAEnabled): ?>
                <h2 style="margin-bottom: 16px;">Enable MFA</h2>
                <ol style="margin-bottom: 20px; padding-left: 20px;">
                    <li>Install an authenticator app (Google Authenticator, Authy, etc.)</li>
                    <li>Scan the QR code below or enter the secret manually</li>
                    <li>Enter the 6-digit code to verify</li>
                </ol>
                
                <div id="qrcode" class="qr-code"></div>
                <div class="secret-box">
                    <strong>Manual entry key:</strong><br>
                    <span id="secret"><?= htmlspecialchars($setupSecret) ?></span>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                    <input type="hidden" name="mfa_secret" value="<?= htmlspecialchars($setupSecret) ?>">
                    
                    <div class="form-group">
                        <label for="verification_code">Verification Code</label>
                        <input type="text" id="verification_code" name="verification_code" 
                               placeholder="000000" maxlength="6" pattern="[0-9]{6}" required>
                    </div>
                    
                    <button type="submit" name="setup_mfa" class="btn">
                        <i class="fas fa-shield-alt"></i> Enable MFA
                    </button>
                </form>
            <?php else: ?>
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                    <i class="fas fa-check-circle" style="color: var(--green); margin-right: 8px;"></i>
                    Your account is protected with multi-factor authentication.
                </div>
                
                <?php if (isset($backupCodesList)): ?>
                    <h3 style="margin-bottom: 12px;">Backup Codes</h3>
                    <p style="color: var(--ink-muted); margin-bottom: 16px;">
                        Save these backup codes securely. Each code can only be used once.
                    </p>
                    <div class="backup-codes">
                        <?php foreach ($backupCodesList as $code): ?>
                            <div class="backup-code"><?= htmlspecialchars($code) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <h3 style="margin-bottom: 16px;">Disable MFA</h3>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" name="disable_mfa" class="btn btn-danger" 
                            onclick="return confirm('Are you sure you want to disable MFA? This will make your account less secure.')">
                        <i class="fas fa-times-circle"></i> Disable MFA
                    </button>
                </form>
            <?php endif; ?>
            
            <div style="margin-top: 32px; padding-top: 20px; border-top: 1px solid var(--border);">
                <a href="dashboard.php" style="color: var(--accent); text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // Generate QR code
        var qr = qrcode(0, 'M');
        qr.addData('<?= $provisioningUri ?>');
        qr.make();
        document.getElementById('qrcode').innerHTML = qr.createImgTag(4);
        
        // Auto-focus verification code field
        document.getElementById('verification_code')?.focus();
    </script>
</body>
</html>
