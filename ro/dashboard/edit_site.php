<?php
/**
 * Edit Existing Site
 * 
 * Purpose: Edit site settings, branding, and configuration
 * Access: Admin only
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../../includes/db_helpers.php';

// Get site ID from URL
$site_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($site_id <= 0) {
    redirectWithError('dashboard/home.php', 'ID site invalid.');
}

// Fetch site data
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT * FROM sites WHERE id = ?");
$stmt->bind_param('i', $site_id);
$stmt->execute();
$result = $stmt->get_result();
$site = $result->fetch_assoc();
$stmt->close();

if (!$site) {
    redirectWithError('dashboard/home.php', 'Site-ul nu a fost găsit.');
}

$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de securitate invalid.';
    } else {
        // Get form data
        $site_name = sanitizeInput($_POST['site_name'] ?? '');
        $admin_email = sanitizeEmail($_POST['admin_email'] ?? '');
        $primary_color = sanitizeInput($_POST['primary_color'] ?? '#3498db');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Validation
        if (empty($site_name)) {
            $errors[] = 'Numele site-ului este obligatoriu.';
        }
        
        if (!$admin_email) {
            $errors[] = 'Email-ul este invalid.';
        }
        
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $primary_color)) {
            $errors[] = 'Culoarea trebuie să fie în format hex.';
        }
        
        // Handle logo upload (if new logo uploaded)
        $logo_path = $site['logo_path']; // Keep existing by default
        
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $validation = validateImageUpload($_FILES['logo']);
            
            if (!$validation['valid']) {
                $errors[] = $validation['error'];
            } else {
                // Delete old logo if exists
                if ($site['logo_path'] && file_exists(ROOT_PATH . $site['logo_path'])) {
                    unlink(ROOT_PATH . $site['logo_path']);
                }
                
                $filename = generateUniqueFilename($_FILES['logo']['name'], 'logo_');
                $upload_path = LOGO_PATH . '/' . $filename;
                
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                    $logo_path = SUBFOLDER . '/uploads/logos/' . $filename;
                } else {
                    $errors[] = 'Eroare la încărcarea logo-ului nou.';
                }
            }
        }
        
        // Update site if no errors
        if (empty($errors)) {
            $affected = executeUpdate(
                $conn,
                "UPDATE sites SET site_name = ?, admin_email = ?, logo_path = ?, primary_color = ?, is_active = ? WHERE id = ?",
                [$site_name, $admin_email, $logo_path, $primary_color, $is_active, $site_id],
                'sssiii'
            );
            
            if ($affected !== false) {
                // Refresh site data
                $site = fetchOne($conn, "SELECT * FROM sites WHERE id = ?", [$site_id], 'i');
                $success = true;
            } else {
                $errors[] = 'Eroare la actualizarea site-ului.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editare Site - <?php echo htmlspecialchars($site['site_name']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f5f7fa;
            color: #333;
        }
        
        .header {
            background: white;
            padding: 20px 40px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header h1 { color: #667eea; font-size: 24px; }
        
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .breadcrumb {
            margin-bottom: 20px;
            color: #666;
            font-size: 14px;
        }
        
        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .card h2 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 14px;
        }
        
        .hint {
            font-weight: 400;
            color: #999;
            font-size: 13px;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="color"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 15px;
        }
        
        input[type="text"]:focus,
        input[type="email"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        input[type="color"] {
            height: 50px;
            cursor: pointer;
        }
        
        input[type="file"] {
            border: 1px dashed #ddd;
            padding: 20px;
            border-radius: 5px;
            width: 100%;
        }
        
        .current-logo {
            margin-top: 10px;
            padding: 15px;
            background: #f9fafb;
            border-radius: 5px;
            display: inline-block;
        }
        
        .current-logo img {
            max-width: 150px;
            max-height: 100px;
            display: block;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .btn-secondary {
            background: #e0e7ff;
            color: #667eea;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .info-box {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 5px;
        }
        
        .info-box strong {
            display: block;
            margin-bottom: 5px;
        }
        
        .slug-display {
            font-family: monospace;
            color: #667eea;
            background: #f9fafb;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
        }
        .btn-danger {
            background: #fee;
            color: #c33;
        }
        
        .btn-danger:hover {
            background: #fdd;
        }

    </style>
</head>
<body>
    <div class="header">
        <h1>Contract Digital - Editare Site</h1>
    </div>
    
    <div class="container">
        <div class="breadcrumb">
            <a href="<?php echo url('dashboard/home.php'); ?>">Dashboard</a> / Editare Site
        </div>
        
        <div class="card">
            <h2>Editare: <?php echo htmlspecialchars($site['site_name']); ?></h2>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    Site actualizat cu succes!
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <strong>Erori:</strong>
                    <ul style="margin: 10px 0 0 20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="info-box">
                <strong>Slug URL (nu poate fi modificat):</strong>
                <div class="slug-display"><?php echo fullUrl($site['site_slug'] . '/'); ?></div>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <?php echo csrfTokenField(); ?>
                
                <div class="form-group">
                    <label for="site_name">Numele Site-ului</label>
                    <input 
                        type="text" 
                        id="site_name" 
                        name="site_name" 
                        required
                        value="<?php echo htmlspecialchars($site['site_name']); ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="admin_email">Email Administrator</label>
                    <input 
                        type="email" 
                        id="admin_email" 
                        name="admin_email" 
                        required
                        value="<?php echo htmlspecialchars($site['admin_email']); ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="logo">
                        Logo <span class="hint">(încarcă un logo nou pentru a înlocui pe cel actual)</span>
                    </label>
                    
                    <?php if ($site['logo_path'] && file_exists(filesystemPath($site['logo_path']))): ?>
                        <div class="current-logo">
                            <strong style="display: block; margin-bottom: 10px; font-size: 13px;">Logo actual:</strong>
                            <img src="<?php echo htmlspecialchars($site['logo_path']); ?>" alt="Logo">
                        </div>
                    <?php endif; ?>
                    
                    <input 
                        type="file" 
                        id="logo" 
                        name="logo" 
                        accept="image/jpeg,image/png,image/gif"
                    >
                </div>
                
                <div class="form-group">
                    <label for="primary_color">Culoare Primară</label>
                    <input 
                        type="color" 
                        id="primary_color" 
                        name="primary_color" 
                        value="<?php echo htmlspecialchars($site['primary_color']); ?>"
                    >
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input 
                            type="checkbox" 
                            id="is_active" 
                            name="is_active" 
                            value="1"
                            <?php echo $site['is_active'] ? 'checked' : ''; ?>
                        >
                        <label for="is_active" style="margin: 0;">
                            Site Activ <span class="hint">(dezactivarea va face site-ul inaccesibil)</span>
                        </label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Salvare Modificări</button>
                    <a href="<?php echo url('dashboard/home.php'); ?>" class="btn btn-secondary">Înapoi la Dashboard</a>
                    <a href="<?php echo url('dashboard/delete_site.php?id=' . $site['id']); ?>" 
                       class="btn btn-danger" 
                       style="margin-left: auto;">🗑️ Ștergere Site</a>
                </div>

            </form>
        </div>
    </div>
</body>
</html>
