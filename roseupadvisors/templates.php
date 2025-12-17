<?php
require_once __DIR__ . '/auth_check.php';

// $site_id, $site_slug, $db are now set by auth_check.php

// Handle archive action
if (isset($_GET['archive']) && isset($_GET['id'])) {
    $template_id = intval($_GET['id']);
    
    $stmt = $db->prepare("SELECT id FROM contract_templates WHERE id = ? AND site_id = ?");
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

// Handle restore action
if (isset($_GET['restore']) && isset($_GET['id'])) {
    $template_id = intval($_GET['id']);
    
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

// Handle delete action
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $template_id = intval($_GET['id']);
    
    $stmt = $db->prepare("SELECT id FROM contract_templates WHERE id = ? AND site_id = ?");
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
            header('Location: ' . SUBFOLDER . '/' . $site_slug . '/templates.php?error=has_contracts&count=' . $contract_count);
            exit;
        } else {
            $stmt = $db->prepare("DELETE FROM contract_templates WHERE id = ?");
            $stmt->bind_param("i", $template_id);
            $stmt->execute();
            $stmt->close();
            
            header('Location: ' . SUBFOLDER . '/' . $site_slug . '/templates.php?deleted=1');
            exit;
        }
    }
}

// Get active templates
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
$db->close();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Template-uri Contracte - <?php echo htmlspecialchars($site_name); ?></title>
    <style>
        :root {
            --primary-color: <?php echo $primary_color; ?>;
            --primary-dark: #5568d3;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        .header h1 {
            color: var(--primary-color);
            font-size: 28px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        .btn-info {
            background: #3498db;
            color: white;
            font-size: 14px;
            padding: 8px 16px;
        }
        .btn-success {
            background: #27ae60;
            color: white;
            font-size: 14px;
            padding: 8px 16px;
        }
        .btn-warning {
            background: #f39c12;
            color: white;
            font-size: 14px;
            padding: 8px 16px;
        }
        .btn-danger {
            background: #e74c3c;
            color: white;
            font-size: 14px;
            padding: 8px 16px;
        }
        .btn-secondary {
            background: #95a5a6;
            color: white;
            font-size: 14px;
            padding: 8px 16px;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
        }
        .tab {
            padding: 12px 24px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            font-weight: 600;
            color: #666;
            transition: all 0.3s;
        }
        .tab.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .templates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }
        .template-card {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s;
        }
        .template-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .template-card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #764ba2 100%);
            color: white;
            padding: 20px;
        }
        .template-card-header h3 {
            font-size: 18px;
            margin-bottom: 8px;
        }
        .template-card-header .meta {
            font-size: 12px;
            opacity: 0.9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            background: rgba(255,255,255,0.2);
        }
        .badge.success {
            background: #27ae60;
        }
        .template-card-body {
            padding: 20px;
        }
        .template-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            .container {
                padding: 15px;
                border-radius: 10px;
            }
            .header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            .header h1 {
                font-size: 22px;
            }
            .header > div {
                display: flex;
                gap: 8px;
                width: 100%;
            }
            .header .btn {
                flex: 1;
                text-align: center;
                font-size: 13px;
                padding: 10px 12px;
            }
            .tabs {
                flex-direction: column;
            }
            .tab {
                width: 100%;
                text-align: center;
            }
            .templates-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            .template-card-header h3 {
                font-size: 16px;
            }
            .template-card-header .meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            .template-actions {
                flex-direction: column;
            }
            .template-actions .btn {
                width: 100%;
                text-align: center;
            }
            .btn {
                font-size: 13px;
                padding: 10px 16px;
            }
        }
        
        @media (max-width: 480px) {
            .header h1 {
                font-size: 18px;
            }
            .btn {
                font-size: 12px;
                padding: 8px 12px;
            }
            .template-card-body {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📄 <?php echo htmlspecialchars($site_name); ?></h1>
            <div>
                <a href="<?php echo SUBFOLDER; ?>/<?php echo $site_slug; ?>/view_contract.php" class="btn btn-secondary">📋 Contracte</a>
                <a href="<?php echo SUBFOLDER; ?>/<?php echo $site_slug; ?>/logout.php" class="btn btn-secondary">🚪 Ieșire</a>
            </div>
        </div>

        <?php if (isset($_GET['archived'])): ?>
            <div class="alert alert-success">✅ Template arhivat cu succes!</div>
        <?php endif; ?>

        <?php if (isset($_GET['restored'])): ?>
            <div class="alert alert-success">✅ Template restaurat cu succes!</div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">✅ Template șters cu succes!</div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'has_contracts'): ?>
            <div class="alert alert-danger">
                ❌ Nu poți șterge acest template! Are <?php echo intval($_GET['count']); ?> contracte asociate. 
                <strong>Soluție:</strong> Folosește butonul "📦 Arhivează" în loc de "🗑️ Șterge".
            </div>
        <?php endif; ?>

        <h2 style="margin-bottom: 20px;">Template-uri Contracte</h2>

        <div class="tabs">
            <div class="tab active" onclick="switchTab('active')">
                📄 Template-uri Active <span style="opacity: 0.7;">(<?php echo count($templates); ?>)</span>
            </div>
            <div class="tab" onclick="switchTab('archived')">
                📦 Arhivate <span style="opacity: 0.7;">(<?php echo count($archived_templates); ?>)</span>
            </div>
        </div>

        <!-- Active Templates -->
        <div id="active-tab" class="tab-content active">
            <?php if (empty($templates)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📄</div>
                    <h3>Niciun template activ</h3>
                    <p>Creează primul template pentru a începe.</p>
                    <a href="<?php echo SUBFOLDER; ?>/<?php echo $site_slug; ?>/edit_template.php" class="btn btn-primary" style="margin-top: 20px;">➕ Template Nou</a>
                </div>
            <?php else: ?>
                <div style="text-align: right; margin-bottom: 20px;">
                    <a href="<?php echo SUBFOLDER; ?>/<?php echo $site_slug; ?>/edit_template.php" class="btn btn-primary">➕ Template Nou</a>
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
                                <div class="template-actions">
                                    <a href="<?php echo SUBFOLDER; ?>/<?php echo $site_slug; ?>/preview_template.php?id=<?php echo $template['id']; ?>" 
                                       class="btn btn-info" 
                                       title="Preview">
                                        👁️ Preview
                                    </a>
                                    <a href="<?php echo SUBFOLDER; ?>/<?php echo $site_slug; ?>/edit_template.php?id=<?php echo $template['id']; ?>" class="btn btn-warning">
                                        ✏️ Editează
                                    </a>
                                    <a href="<?php echo SUBFOLDER; ?>/<?php echo $site_slug; ?>/send_contract.php?template_id=<?php echo $template['id']; ?>" class="btn btn-success">
                                        📤 Trimite
                                    </a>
                                    <?php if ($template['contracts_count'] > 0): ?>
                                        <a href="?archive=1&id=<?php echo $template['id']; ?>" 
                                           class="btn btn-secondary"
                                           onclick="return confirm('Arhivezi acest template? (Contractele rămân intacte)')"
                                           title="Arhivează (păstrează contractele)">
                                            📦 Arhivează
                                        </a>
                                    <?php else: ?>
                                        <a href="?delete=1&id=<?php echo $template['id']; ?>" 
                                           class="btn btn-danger" 
                                           onclick="return confirm('Sigur ștergi acest template?')">
                                            🗑️ Șterge
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Archived Templates -->
        <div id="archived-tab" class="tab-content">
            <?php if (empty($archived_templates)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📦</div>
                    <h3>Niciun template arhivat</h3>
                    <p>Template-urile arhivate vor apărea aici.</p>
                </div>
            <?php else: ?>
                <div class="templates-grid">
                    <?php foreach ($archived_templates as $template): ?>
                        <div class="template-card" style="opacity: 0.7;">
                            <div class="template-card-header" style="background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);">
                                <h3><?php echo htmlspecialchars($template['template_name']); ?></h3>
                                <div class="meta">
                                    <span>Arhivat: <?php echo date('d.m.Y', strtotime($template['updated_at'])); ?></span>
                                    <?php if ($template['contracts_count'] > 0): ?>
                                        <span class="badge">
                                            <?php echo $template['contracts_count']; ?> contracte
                                            <?php if ($template['signed_count'] > 0): ?>
                                                (<?php echo $template['signed_count']; ?> semnate)
                                            <?php endif; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="template-card-body">
                                <div class="template-actions">
                                    <a href="<?php echo SUBFOLDER; ?>/<?php echo $site_slug; ?>/preview_template.php?id=<?php echo $template['id']; ?>" 
                                       class="btn btn-info" 
                                       title="Preview">
                                        👁️ Preview
                                    </a>
                                    <a href="?restore=1&id=<?php echo $template['id']; ?>" 
                                       class="btn btn-success"
                                       onclick="return confirm('Restaurezi acest template?')">
                                        ♻️ Restaurează
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            if (tab === 'active') {
                document.querySelector('.tabs .tab:first-child').classList.add('active');
                document.getElementById('active-tab').classList.add('active');
            } else {
                document.querySelector('.tabs .tab:last-child').classList.add('active');
                document.getElementById('archived-tab').classList.add('active');
            }
        }
    </script>
</body>
</html>
