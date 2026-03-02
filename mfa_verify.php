<?php
require_once 'config.php';

// Redirect if already logged in
if (auth()) {
    redirect('dashboard.php');
}

$mfa = new MFA($db, $security);
$error = '';

// Handle MFA verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['temp_user_id'] ?? null;
    $user = $_SESSION['temp_user_data'] ?? null;
    
    if (!$userId || !$user) {
        redirect('login.php');
    }
    
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
        $error = 'Invalid request token';
    } else {
        $token = $_POST['mfa_code'] ?? '';
        $backupCode = $_POST['backup_code'] ?? '';
        
        if (!empty($token)) {
            // Verify TOTP token
            $sessionId = $mfa->verifyAndCreateSession($userId, $token);
            if ($sessionId) {
                // Complete login
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['position'] = $user['position'];
                $_SESSION['mfa_verified'] = true;
                $_SESSION['mfa_session_id'] = $sessionId;
                $_SESSION['session_regenerated'] = false;
                
                // Clear temp data
                unset($_SESSION['temp_user_id'], $_SESSION['temp_user_data']);
                
                $security->logSecurityEvent('MFA_LOGIN_SUCCESS', ['user_id' => $userId]);
                redirect('dashboard.php');
            } else {
                $error = 'Invalid authentication code';
                $security->logSecurityEvent('MFA_LOGIN_FAILURE', ['user_id' => $userId]);
            }
        } elseif (!empty($backupCode)) {
            // Verify backup code
            if ($mfa->verifyBackupCode($userId, $backupCode)) {
                // Complete login with backup code
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['position'] = $user['position'];
                $_SESSION['mfa_verified'] = true;
                $_SESSION['used_backup_code'] = true;
                $_SESSION['session_regenerated'] = false;
                
                // Clear temp data
                unset($_SESSION['temp_user_id'], $_SESSION['temp_user_data']);
                
                $security->logSecurityEvent('MFA_BACKUP_LOGIN_SUCCESS', ['user_id' => $userId]);
                redirect('dashboard.php');
            } else {
                $error = 'Invalid backup code';
                $security->logSecurityEvent('MFA_BACKUP_LOGIN_FAILURE', ['user_id' => $userId]);
            }
        } else {
            $error = 'Please enter an authentication code or backup code';
        }
    }
} else {
    // Check if we have temp user data from login
    $userId = $_SESSION['temp_user_id'] ?? null;
    $user = $_SESSION['temp_user_data'] ?? null;
    
    if (!$userId || !$user) {
        redirect('login.php');
    }
    
    // Check if user actually has MFA enabled
    if (!$mfa->isMFAEnabled($userId)) {
        // Complete login without MFA
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['position'] = $user['position'];
        $_SESSION['session_regenerated'] = false;
        
        // Clear temp data
        unset($_SESSION['temp_user_id'], $_SESSION['temp_user_data']);
        
        redirect('dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MFA Verification — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            --radius: 16px;
            --shadow: 0 8px 40px rgba(15,17,23,0.08);
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
        }
        .card {
            background: var(--surface); border-radius: var(--radius);
            padding: 40px; width: 100%; max-width: 420px;
            box-shadow: var(--shadow);
        }
        .logo {
            text-align: center; margin-bottom: 32px;
        }
        .logo i {
            font-size: 48px; color: var(--accent); margin-bottom: 12px;
        }
        h1 { font-size: 24px; text-align: center; margin-bottom: 8px; }
        .subtitle { color: var(--ink-muted); text-align: center; margin-bottom: 32px; }
        .alert {
            border-radius: 8px; padding: 12px 16px; margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
        }
        .alert.error { background: #fef2f2; border: 1px solid #fecaca; color: var(--red); }
        .tabs {
            display: grid; grid-template-columns: 1fr 1fr; gap: 8px;
            background: var(--surface-2); padding: 4px; border-radius: 8px; margin-bottom: 24px;
        }
        .tab-btn {
            background: transparent; border: none; padding: 10px;
            border-radius: 6px; cursor: pointer; font-weight: 500; color: var(--ink-muted);
            transition: all 0.2s;
        }
        .tab-btn.active {
            background: var(--surface); color: var(--accent); box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; }
        input[type="text"] {
            width: 100%; padding: 12px 16px; border: 1.5px solid var(--border);
            border-radius: 8px; font-size: 16px; text-align: center;
            letter-spacing: 2px; font-weight: 600;
        }
        input[type="text"]:focus {
            outline: none; border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .btn {
            width: 100%; background: var(--accent); color: white;
            border: none; border-radius: 8px; padding: 14px;
            font-size: 16px; font-weight: 600; cursor: pointer;
            transition: all 0.2s;
        }
        .btn:hover { background: var(--accent-light); transform: translateY(-1px); }
        .user-info {
            background: var(--surface-2); border-radius: 8px; padding: 16px;
            margin-bottom: 24px; text-align: center;
        }
        .user-info .name { font-weight: 600; color: var(--ink); }
        .user-info .email { color: var(--ink-muted); font-size: 14px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">
            <i class="fas fa-shield-alt"></i>
        </div>
        
        <h1>Multi-Factor Authentication</h1>
        <p class="subtitle">Enter your authentication code to continue</p>
        
        <?php if ($error): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>
        
        <div class="user-info">
            <div class="name"><?= htmlspecialchars($_SESSION['temp_user_data']['name']) ?></div>
            <div class="email"><?= htmlspecialchars($_SESSION['temp_user_data']['email']) ?></div>
        </div>
        
        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('totp')">
                <i class="fas fa-mobile-alt"></i> App Code
            </button>
            <button class="tab-btn" onclick="showTab('backup')">
                <i class="fas fa-key"></i> Backup Code
            </button>
        </div>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
            
            <div id="totp-tab" class="tab-content active">
                <div class="form-group">
                    <label for="mfa_code">Authentication Code</label>
                    <input type="text" id="mfa_code" name="mfa_code" 
                           placeholder="000000" maxlength="6" pattern="[0-9]{6}" required>
                </div>
                <button type="submit" class="btn">
                    <i class="fas fa-check"></i> Verify
                </button>
            </div>
            
            <div id="backup-tab" class="tab-content">
                <div class="form-group">
                    <label for="backup_code">Backup Code</label>
                    <input type="text" id="backup_code" name="backup_code" 
                           placeholder="XXXXXXXX" maxlength="8" pattern="[A-Z0-9]{8}" required>
                </div>
                <button type="submit" class="btn">
                    <i class="fas fa-key"></i> Use Backup Code
                </button>
            </div>
        </form>
        
        <div style="margin-top: 24px; text-align: center;">
            <a href="logout.php" style="color: var(--ink-muted); text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        </div>
    </div>
    
    <script>
        function showTab(tab) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tab + '-tab').classList.add('active');
            event.target.classList.add('active');
            
            // Focus appropriate input
            if (tab === 'totp') {
                document.getElementById('mfa_code').focus();
            } else {
                document.getElementById('backup_code').focus();
            }
        }
        
        // Auto-focus on page load
        document.getElementById('mfa_code').focus();
        
        // Auto-submit when 6 digits entered
        document.getElementById('mfa_code').addEventListener('input', function(e) {
            if (e.target.value.length === 6) {
                e.target.form.submit();
            }
        });
    </script>
</body>
</html>
