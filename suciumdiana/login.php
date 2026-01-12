<?php
session_start();
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

// Get site slug from directory name
$site_slug = basename(__DIR__);

// Get site details from database
$db = getDBConnection();
$stmt = $db->prepare("SELECT * FROM sites WHERE site_slug = ? AND is_active = 1");
$stmt->bind_param("s", $site_slug);
$stmt->execute();
$site = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$site) {
    die('Site not found or inactive');
}

// Store site info in session-accessible variables
$site_name = $site['site_name'];
$primary_color = $site['primary_color'];
$logo_path = $site['logo_path'];

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . SUBFOLDER . '/' . $site_slug . '/login.php');
    exit;
}

// Check if already logged in
if (isset($_SESSION['site_user_id']) && $_SESSION['site_slug'] === $site_slug) {
    header('Location: ' . SUBFOLDER . '/' . $site_slug . '/templates.php');
    exit;
}

// Handle login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Nume de utilizator și parolă sunt obligatorii';
    } else {
        // Get user from database
        $stmt = $db->prepare("
            SELECT su.* 
            FROM site_users su
            WHERE su.username = ? AND su.site_id = ?
        ");
        $stmt->bind_param("si", $username, $site['id']);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Login successful
            $_SESSION['site_user_id'] = $user['id'];
            $_SESSION['site_slug'] = $site_slug;
            $_SESSION['site_name'] = $site_name;
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_email'] = $user['email'];
            
            // Redirect to site-specific templates page
            header('Location: ' . SUBFOLDER . '/' . $site_slug . '/templates.php');
            exit;
        } else {
            $error = 'Nume de utilizator sau parolă incorectă';
        }
    }
}

// Helper functions for colors
function getColorBrightness($hexColor) {
    $hex = str_replace('#', '', $hexColor);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
}

function darkenColor($hexColor, $percent) {
    $hex = str_replace('#', '', $hexColor);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $r = max(0, min(255, $r - ($r * $percent / 100)));
    $g = max(0, min(255, $g - ($g * $percent / 100)));
    $b = max(0, min(255, $b - ($b * $percent / 100)));
    
    return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) 
                . str_pad(dechex($g), 2, '0', STR_PAD_LEFT) 
                . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
}

// Calculate button colors dynamically
$brightness = getColorBrightness($primary_color);
if ($brightness > 200) {
    $button_bg_color = darkenColor($primary_color, 40);
} elseif ($brightness > 155) {
    $button_bg_color = darkenColor($primary_color, 25);
} else {
    $button_bg_color = $primary_color;
}
$button_text_color = '#ffffff';
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars($site_name); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, <?php echo $primary_color; ?> 0%, <?php echo darkenColor($primary_color, 20); ?> 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
        }
        
        .login-header {
            background: <?php echo $primary_color; ?>;
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .login-header .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: bold;
        }
        
        .login-header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .login-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .login-body {
            padding: 40px 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: <?php echo $primary_color; ?>;
            box-shadow: 0 0 0 3px <?php echo $primary_color; ?>20;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: <?php echo $button_bg_color; ?>;
            color: <?php echo $button_text_color; ?>;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.3s;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .error {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .login-footer {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-top: 1px solid #e0e0e0;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <?php echo strtoupper(substr($site_name, 0, 1)); ?>
            </div>
            <h1><?php echo htmlspecialchars($site_name); ?></h1>
            <p>Portal Contracte</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="error">❌ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['timeout'])): ?>
                <div class="error">⏱️ Sesiunea a expirat. Vă rugăm să vă autentificați din nou.</div>
            <?php endif; ?>
            
            <?php if (isset($_GET['logout'])): ?>
                <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 12px; border-radius: 5px; margin-bottom: 20px; font-size: 14px;">
                    ✓ Deconectare reușită
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Nume utilizator</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required 
                        autofocus
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Parolă</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                    >
                </div>
                
                <button type="submit" class="btn">🔐 Autentificare</button>
            </form>
        </div>
        
        <div class="login-footer">
            Powered by Contract Digital Platform
        </div>
    </div>
</body>
</html>