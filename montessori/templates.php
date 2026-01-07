<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$db = getDBConnection();

// Handle template archiving
if (isset($_GET['archive']) && isset($_GET['id'])) {
    $template_id = intval($_GET['id']);
    
    // Verify template belongs to this site
    $stmt = $db->prepare("SELECT id, template_name FROM contract_templates WHERE id = ? AND site_id = ?");
    $stmt->bind_param("ii", $template_id, $site_id);
    $stmt->execute();
    $template_exists = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($template_exists) {
        $stmt = $db->prepare("UPDATE contract_templates SET is_active = 0, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $template_id);
        $stmt->execute();
        $stmt->close();
        
        header('Location: ' . SUBFOLDER . '/' . $site_slug . '/templates.php?archived=1');
        exit;
    }
}

// Handle template restoration
if (isset($_GET['restore']) && isset($_GET['id'])) {
    $template_id = intval($_GET['id']);
    
    // Verify template belongs to this site
    $stmt = $db->prepare("SELECT id FROM contract_templates WHERE id = ? AND site_id = ?");
    $stmt->bind_param("ii", $template_id, $site_id);
    $stmt->execute();
    $template_exists = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($template_exists) {
        $stmt = $db->prepare("UPDATE contract_templates SET is_active = 1, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $template_id);
        $stmt->execute();
        $stmt->close();
        
        header('Location: ' . SUBFOLDER . '/' . $site_slug . '/templates.php?restored=1');
        exit;
    }
}

// Handle template deletion (only if no contracts)
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $template_id = intval($_GET['id']);
    
    // Verify template belongs to this site
    $stmt = $db->prepare("SELECT id, template_name FROM contract_templates WHERE id = ? AND site_id = ?");
    $stmt->bind_param("ii", $template_id, $site_id);
    $stmt->execute();
    $template_exists = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($template_exists) {
        // Check if template has contracts
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM contracts WHERE template_id = ?");
        $stmt->bind_param("i", $template_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $contract_count = $result['total'];
        $stmt->close();
        
        if ($contract_count > 0) {
            // Has contracts - cannot delete, redirect with error
            header('Location: ' . SUBFOLDER . '/' . $site_slug . '/templates.php?error=has_contracts&count=' . $contract_count);
            exit;
        } else {
            // No contracts - safe to delete
            $stmt = $db->prepare("DELETE FROM contract_templates WHERE id = ?");
            $stmt->bind_param("i", $template_id);
            $stmt->execute();
            $stmt->close();
            
            header('Location: ' . SUBFOLDER . '/' . $site_slug . '/templates.php?deleted=1');
            exit;
        }
    }
}

// Get active templates for this site
$stmt = $db->prepare("
    SELECT t.*, 
           COUNT(c.id) as contracts_count,
           SUM(CASE WHEN c.status = 'signed' THEN 1 ELSE 0 END) as signed_count
    FROM contract_templates t
    LEFT JOIN contracts c ON t.id = c.template_id
    WHERE t.site_id = ? AND t.is_active = 1
    GROUP BY t.id
    ORDER BY t.created_at DESC
");
$stmt->bind_param("i", $site_id);
$stmt->execute();
$result = $stmt->get_result();
$templates = [];
while ($row = $result->fetch_assoc()) {
    $templates[] = $row;
}
$stmt->close();

// Get archived templates
$stmt = $db->prepare("
    SELECT t.*, 
           COUNT(c.id) as contracts_count,
           SUM(CASE WHEN c.status = 'signed' THEN 1 ELSE 0 END) as signed_count
    FROM contract_templates t
    LEFT JOIN contracts c ON t.id = c.template_id
    WHERE t.site_id = ? AND t.is_active = 0
    GROUP BY t.id
    ORDER BY t.updated_at DESC
");
$stmt->bind_param("i", $site_id);
$stmt->execute();
$result = $stmt->get_result();
$archived_templates = [];
while ($row = $result->fetch_assoc()) {
    $archived_templates[] = $row;
}
$stmt->close();

$success = isset($_GET['success']) && $_GET['success'] == 1;
$deleted = isset($_GET['deleted']) && $_GET['deleted'] == 1;
$archived = isset($_GET['archived']) && $_GET['archived'] == 1;
$restored = isset($_GET['restored']) && $_GET['restored'] == 1;
$error = isset($_GET['error']) ? $_GET['error'] : null;
$error_count = isset($_GET['count']) ? intval($_GET['count']) : 0;
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Template-uri - <?php echo htmlspecialchars($site_name); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            color: #333;
        }
        
        .header {
            background: linear-gradient(135deg, <?php echo $primary_color; ?> 0%, <?php echo darkenColor($primary_color, 20); ?> 100%);
            color: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 24px;
        }
        
        .header-actions {
            display: flex;
            gap: 15px;
        }
        
        .header-actions a {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .header-actions a:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .page-header h2 {
            font-size: 28px;
            color: #333;
        }
        
        .btn {
            padding: 12px 24px;
            background: <?php echo $primary_color; ?>;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            display: inline-block;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: #27ae60;
        }
        
        .btn-danger {
            background: #e74c3c;
        }
        
        .btn-warning {
            background: #f39c12;
        }
        
        .btn-info {
            background: #3498db;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 13px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .section-header {
            margin: 40px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #e1e4e8;
        }
        
        .section-header h3 {
            font-size: 20px;
            color: #333;
        }
        
        .section-header .count {
            color: #666;
            font-size: 14px;
            font-weight: normal;
            margin-left: 10px;
        }
        
        .templates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .template-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .template-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .template-card.archived {
            opacity: 0.7;
        }
        
        .template-card-header {
            background: linear-gradient(135deg, <?php echo $primary_color; ?>20 0%, <?php echo $primary_color; ?>10 100%);
            padding: 20px;
            border-bottom: 3px solid <?php echo $primary_color; ?>;
        }
        
        .template-card.archived .template-card-header {
            background: linear-gradient(135deg, #95a5a620 0%, #95a5a610 100%);
            border-bottom: 3px solid #95a5a6;
        }
        
        .template-card-header h3 {
            font-size: 18px;
            color: #333;
            margin-bottom: 5px;
        }
        
        .template-card-header .meta {
            font-size: 12px;
            color: #666;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 8px;
        }
        
        .template-card-header .badge {
            background: #3498db;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .template-card-header .badge.success {
            background: #27ae60;
        }
        
        .template-card-body {
            padding: 20px;
        }
        
        .template-preview {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 15px;
            max-height: 80px;
            overflow: hidden;
            position: relative;
        }
        
        .template-preview:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            background: linear-gradient(transparent, white);
        }
        
        .template-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .template-actions .btn {
            flex: 1;
            text-align: center;
            font-size: 13px;
            padding: 10px;
            min-width: 80px;
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #666;
        }
        
        .empty-state p {
            color: #999;
            margin-bottom: 30px;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .page-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .templates-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>📄 <?php echo htmlspecialchars($site_name); ?></h1>
            <div class="header-actions">
                <a href="<?php echo SUBFOLDER . '/' . $site_slug . '/view_contract.php'; ?>">📋 Contracte</a>
                <a href="<?php echo SUBFOLDER . '/' . $site_slug . '/logout.php'; ?>">🚪 Ieșire</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <?php if ($success): ?>
            <div class="alert alert-success">
                ✅ Template salvat cu succes!
            </div>
        <?php endif; ?>
        
        <?php if ($deleted): ?>
            <div class="alert alert-success">
                ✅ Template șters cu succes!
            </div>
        <?php endif; ?>
        
        <?php if ($archived): ?>
            <div class="alert alert-success">
                📦 Template arhivat cu succes! Contractele existente rămân intacte.
            </div>
        <?php endif; ?>
        
        <?php if ($restored): ?>
            <div class="alert alert-success">
                ♻️ Template restaurat cu succes! Este din nou disponibil pentru contracte noi.
            </div>
        <?php endif; ?>
        
        <?php if ($error == 'has_contracts'): ?>
            <div class="alert alert-danger">
                ❌ Nu poți șterge acest template! Are <?php echo $error_count; ?> contracte asociate.<br>
                <strong>Soluție:</strong> Folosește butonul "📦 Arhivează" în loc de "🗑️ Șterge".<br>
                Arhivarea va ascunde template-ul din liste, dar va păstra contractele existente intacte.
            </div>
        <?php endif; ?>
        
        <div class="page-header">
            <h2>Template-uri Contracte</h2>
            <a href="<?php echo SUBFOLDER . '/' . $site_slug . '/edit_template.php'; ?>" class="btn">
                ➕ Template Nou
            </a>
        </div>
        
        <?php if (empty($templates) && empty($archived_templates)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📝</div>
                <h3>Niciun template încă</h3>
                <p>Începeți prin a crea primul template de contract</p>
                <a href="<?php echo SUBFOLDER . '/' . $site_slug . '/edit_template.php'; ?>" class="btn">
                    Creează Primul Template
                </a>
            </div>
        <?php else: ?>
            <!-- Active Templates -->
            <?php if (!empty($templates)): ?>
                <div class="section-header">
                    <h3>📄 Template-uri Active <span class="count">(<?php echo count($templates); ?>)</span></h3>
                </div>
                <div class="templates-grid">
                    <?php foreach ($templates as $template): ?>
                        <div class="template-card">
                            <div class="template-card-header">
                                <h3><?php echo htmlspecialchars($template['template_name']); ?></h3>
                                <div class="meta">
                                    <span>Creat: <?php echo date('d.m.Y', strtotime($template['created_at'])); ?></span>
                                    <?php if ($template['contracts_count'] > 0): ?>
                                        <span class="badge <?php echo $template['signed_count'] > 0 ? 'success' : ''; ?>">
                                            <?php echo $template['contracts_count']; ?> contracte
                                            <?php if ($template['signed_count'] > 0): ?>
                                                (<?php echo $template['signed_count']; ?> semnate)
                                            <?php endif; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="template-card-body">
                                <div class="template-preview">
                                    <?php echo strip_tags($template['template_content']); ?>
                                </div>
                                <div class="template-actions">
                                    <a href="<?php echo SUBFOLDER . '/' . $site_slug . '/edit_template.php?id=' . $template['id']; ?>" 
                                       class="btn btn-sm" title="Editează">
                                        ✏️ Editează
                                    </a>
                                    <a href="preview_template.php?id=<?php echo $template['id']; ?>" 
                                       class="btn btn-info btn-sm" 
                                       target="_blank"
                                       title="Preview">
                                        👁️ Preview
                                    </a>
                                    <a href="<?php echo SUBFOLDER . '/' . $site_slug . '/send_contract.php?template_id=' . $template['id']; ?>" 
                                       class="btn btn-success btn-sm" title="Trimite contract">
                                        📤 Trimite
                                    </a>
                                    <?php if ($template['contracts_count'] > 0): ?>
                                        <a href="<?php echo SUBFOLDER . '/' . $site_slug . '/templates.php?archive=1&id=' . $template['id']; ?>" 
                                           class="btn btn-warning btn-sm" 
                                           onclick="return confirm('Arhivezi template-ul? Are <?php echo $template['contracts_count']; ?> contracte care vor rămâne intacte.');"
                                           title="Arhivează (păstrează contractele)">
                                            📦 Arhivează
                                        </a>
                                    <?php else: ?>
                                        <a href="<?php echo SUBFOLDER . '/' . $site_slug . '/templates.php?delete=1&id=' . $template['id']; ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Ștergi definitiv template-ul? Nu are contracte asociate.');"
                                           title="Șterge definitiv">
                                            🗑️ Șterge
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Archived Templates -->
            <?php if (!empty($archived_templates)): ?>
                <div class="section-header">
                    <h3>📦 Template-uri Arhivate <span class="count">(<?php echo count($archived_templates); ?>)</span></h3>
                </div>
                <div class="templates-grid">
                    <?php foreach ($archived_templates as $template): ?>
                        <div class="template-card archived">
                            <div class="template-card-header">
                                <h3><?php echo htmlspecialchars($template['template_name']); ?></h3>
                                <div class="meta">
                                    <span>Arhivat: <?php echo date('d.m.Y', strtotime($template['updated_at'])); ?></span>
                                    <?php if ($template['contracts_count'] > 0): ?>
                                        <span class="badge">
                                            <?php echo $template['contracts_count']; ?> contracte
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="template-card-body">
                                <div class="template-preview">
                                    <?php echo strip_tags($template['template_content']); ?>
                                </div>
                                <div class="template-actions">
                                    <a href="preview_template.php?id=<?php echo $template['id']; ?>" 
                                       class="btn btn-info btn-sm" 
                                       target="_blank"
                                       title="Preview">
                                        👁️ Preview
                                    </a>
                                    <a href="<?php echo SUBFOLDER . '/' . $site_slug . '/templates.php?restore=1&id=' . $template['id']; ?>" 
                                       class="btn btn-success btn-sm" 
                                       onclick="return confirm('Restaurezi template-ul? Va fi din nou disponibil pentru contracte noi.');"
                                       title="Restaurează">
                                        ♻️ Restaurează
                                    </a>
                                    <?php if ($template['contracts_count'] == 0): ?>
                                        <a href="<?php echo SUBFOLDER . '/' . $site_slug . '/templates.php?delete=1&id=' . $template['id']; ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Ștergi definitiv template-ul arhivat?');"
                                           title="Șterge definitiv">
                                            🗑️ Șterge
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
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
?>
