<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/template_versioning.php';

$db = getDBConnection();

// Get template ID from URL (if editing)
$template_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$is_edit = $template_id > 0;

// Initialize variables
$template_name = '';
$template_content = '';
$error = '';
$success = false;

// If editing, load existing template
if ($is_edit) {
    $stmt = $db->prepare("
        SELECT * FROM contract_templates 
        WHERE id = ? AND site_id = ?
    ");
    $stmt->bind_param("ii", $template_id, $site_id);
    $stmt->execute();
    $template = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$template) {
        header('Location: ' . SUBFOLDER . '/' . $site_slug . '/templates.php');
        exit;
    }
    
    $template_name = $template['template_name'];
    $template_content = $template['template_content'];
}

// Handle version restore
if ($is_edit && isset($_GET['restore'])) {
    $restore_id = intval($_GET['restore']);
    $versioning = new TemplateVersioning($db);
    
    if ($versioning->restoreVersion($restore_id, $template_id)) {
        $success = "✅ Versiunea a fost restaurată cu succes!";
        // Reload template content
        $sql = "SELECT template_name, template_content FROM contract_templates WHERE id = ? AND site_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ii", $template_id, $site_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $template = $result->fetch_assoc();
        $stmt->close();
        
        if ($template) {
            $template_name = $template['template_name'];
            $template_content = $template['template_content'];
        }
    } else {
        $error = "❌ Eroare la restaurarea versiunii!";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $template_name = trim($_POST['template_name'] ?? '');
    $template_content = $_POST['template_content'] ?? '';
    
    // Validation
    if (empty($template_name)) {
        $error = 'Numele template-ului este obligatoriu';
    } elseif (empty($template_content)) {
        $error = 'Conținutul template-ului este obligatoriu';
    } else {
        if ($is_edit) {
            // === AUTO-BACKUP ÎNAINTE DE SALVARE ===
            $versioning = new TemplateVersioning($db);
            
            // Obține versiunea veche pentru backup
            $old_sql = "SELECT template_name, template_content FROM contract_templates WHERE id = ? AND site_id = ?";
            $old_stmt = $db->prepare($old_sql);
            $old_stmt->bind_param("ii", $template_id, $site_id);
            $old_stmt->execute();
            $old_result = $old_stmt->get_result();
            $old_template = $old_result->fetch_assoc();
            $old_stmt->close();
            
            if ($old_template) {
                // Creează backup automat
                $versioning->createBackup(
                    $template_id, 
                    $site_id, 
                    $old_template['template_name'], 
                    $old_template['template_content'],
                    'editor',
                    'Auto-backup înainte de salvare'
                );
            }
            // === SFÂRȘIT AUTO-BACKUP ===
            
            // Update existing template
            $stmt = $db->prepare("
                UPDATE contract_templates 
                SET template_name = ?, template_content = ?, updated_at = NOW()
                WHERE id = ? AND site_id = ?
            ");
            $stmt->bind_param("ssii", $template_name, $template_content, $template_id, $site_id);
        } else {
            // Create new template
            $stmt = $db->prepare("
                INSERT INTO contract_templates (site_id, template_name, template_content, created_at, updated_at)
                VALUES (?, ?, ?, NOW(), NOW())
            ");
            $stmt->bind_param("iss", $site_id, $template_name, $template_content);
        }
        
        if ($stmt->execute()) {
            // ✅ AUTO-MAPPING PENTRU TEMPLATE NOU
            if (!$is_edit) {
                $new_template_id = $db->insert_id;
                
                // Extrage placeholder-uri unice din template (excluzând NUMAR_CONTRACT și DATA_CONTRACT)
                preg_match_all('/\[([A-Z_0-9]+)\]/', $template_content, $placeholder_matches);
                $unique_placeholders = array_unique($placeholder_matches[1]);
                
                // Exclude special placeholders care nu sunt în baza de date
                $excluded_placeholders = ['NUMAR_CONTRACT', 'DATA_CONTRACT'];
                $placeholders_to_map = array_diff($unique_placeholders, $excluded_placeholders);
                
                if (!empty($placeholders_to_map)) {
                    // Obține toate field_definitions disponibile
                    $fields_query = "SELECT id, field_name FROM field_definitions";
                    $fields_result = $db->query($fields_query);
                    $available_fields = [];
                    while ($field = $fields_result->fetch_assoc()) {
                        // Mapează lowercase pentru matching (ex: client_company_name)
                        $available_fields[strtolower($field['field_name'])] = $field['id'];
                    }
                    
                    // Auto-map placeholders
                    $display_order = 1;
                    $mapped_count = 0;
                    
                    foreach ($placeholders_to_map as $placeholder) {
                        // Convert placeholder la lowercase pentru matching
                        // Ex: NUME_COMPANIE -> nume_companie
                        $field_name_lowercase = strtolower($placeholder);
                        
                        // Check dacă există field cu acest nume
                        if (isset($available_fields[$field_name_lowercase])) {
                            $field_id = $available_fields[$field_name_lowercase];
                            
                            // Inserează mapping
                            $map_stmt = $db->prepare("
                                INSERT INTO template_field_mapping (template_id, field_definition_id, is_required, display_order)
                                VALUES (?, ?, 1, ?)
                                ON DUPLICATE KEY UPDATE display_order = ?
                            ");
                            $map_stmt->bind_param("iiii", $new_template_id, $field_id, $display_order, $display_order);
                            
                            if ($map_stmt->execute()) {
                                $mapped_count++;
                                $display_order++;
                            }
                            $map_stmt->close();
                        }
                    }
                    
                    // Log success (opțional)
                    if ($mapped_count > 0) {
                        // Success - auto-mapped $mapped_count fields
                    }
                }
            }
            // ✅ SFÂRȘIT AUTO-MAPPING
            
            $stmt->close();
            header('Location: ' . SUBFOLDER . '/' . $site_slug . '/templates.php?success=1');
            exit;
        } else {
            $error = 'Eroare la salvarea template-ului: ' . $stmt->error;
            $stmt->close();
        }
    }
}

// Extract all placeholders from template content
preg_match_all('/\[([A-Z_0-9]+)\]/', $template_content, $matches);
$found_placeholders = array_unique($matches[1]);
sort($found_placeholders);


?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Editare' : 'Creare'; ?> Template - <?php echo htmlspecialchars($site_name); ?></title>
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
            margin-bottom: 30px;
        }
        
        .page-header h2 {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .breadcrumb {
            color: #666;
            font-size: 14px;
        }
        
        .breadcrumb a {
            color: <?php echo $primary_color; ?>;
            text-decoration: none;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 20px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group label .required {
            color: #e74c3c;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: <?php echo $primary_color; ?>;
            box-shadow: 0 0 0 3px <?php echo $primary_color; ?>20;
        }
        
        .form-group textarea {
            min-height: 400px;
            font-family: 'Courier New', monospace;
            line-height: 1.6;
        }
        
        .placeholder-help {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #2196F3;
            margin-bottom: 15px;
            font-size: 13px;
        }
        
        .placeholder-help strong {
            display: block;
            margin-bottom: 5px;
            color: #1976D2;
        }
        
        .placeholder-help code {
            background: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: <?php echo $primary_color; ?>;
            color: white;
        }
        
        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            opacity: 0.9;
        }
        
        .btn-submit {
            padding: 16px 40px;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 30px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        
        .btn-submit:active {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(102, 126, 234, 0.4);
        }
        
        .editor-toolbar {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px 8px 0 0;
            border: 2px solid #e0e0e0;
            border-bottom: none;
            display: flex;
            gap: 10px;
        }
        
        .toolbar-btn {
            padding: 8px 12px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s;
        }
        
        .toolbar-btn:hover {
            background: #e9ecef;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                text-align: center;
            }
        }

        /* ================================================
           MOBILE RESPONSIVE
           ================================================ */
        
        @media (max-width: 768px) {
            .header {
                padding: 15px;
            }
            
            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .header h1 {
                font-size: 20px;
            }
            
            .header-actions {
                display: flex;
                flex-direction: column;
                gap: 10px;
                width: 100%;
            }
            
            .header-actions a {
                display: block;
                width: 100%;
                text-align: center;
            }
            
            .container {
                margin: 15px auto;
                padding: 0 10px;
            }
            
            .form-group {
                margin-bottom: 15px;
            }
            
            .form-group label {
                font-size: 14px;
                margin-bottom: 8px;
            }
            
            .form-control {
                font-size: 14px;
                padding: 10px;
            }
            
            .editor-toolbar {
                flex-wrap: wrap;
                gap: 8px;
                padding: 10px;
            }
            
            .toolbar-btn {
                flex: 1 1 calc(50% - 8px);
                min-width: calc(50% - 8px);
                font-size: 12px;
                padding: 8px 10px;
            }
            
            .placeholder-help {
                padding: 12px;
                font-size: 12px;
                line-height: 1.8;
            }
            
            .placeholder-help code {
                font-size: 11px;
                padding: 2px 4px;
            }
            
            .btn {
                padding: 10px 16px;
                font-size: 14px;
            }
            
            .btn-group {
                flex-direction: column;
                gap: 10px;
            }
            
            .btn-group .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 400px) {
            .header h1 {
                font-size: 18px;
            }
            
            .toolbar-btn {
                flex: 1 1 100%;
                min-width: 100%;
            }
            
            .placeholder-help {
                font-size: 11px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>📄 <?php echo htmlspecialchars($site_name); ?></h1>
            <div class="header-actions">
                <a href="<?php echo SUBFOLDER . '/' . $site_slug . '/templates.php'; ?>">← Template-uri</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="page-header">
            <h2><?php echo $is_edit ? '✏️ Editare Template' : '➕ Template Nou'; ?></h2>
            <div class="breadcrumb">
                <a href="<?php echo SUBFOLDER . '/' . $site_slug . '/templates.php'; ?>">Template-uri</a> / 
                <?php echo $is_edit ? 'Editare' : 'Creare'; ?>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <form method="POST" action="">
                <div class="form-group">
                    <label>
                        Nume Template <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="template_name" 
                        required 
                        placeholder="Ex: Contract de Colaborare"
                        value="<?php echo htmlspecialchars($template_name); ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label>
                        Conținut Template (HTML) <span class="required">*</span>
                    </label>
                    
                    <div class="placeholder-help">
                        <strong>📌 Placeholder-uri în template (<?php echo count($found_placeholders); ?>):</strong><br>
                        <?php if (empty($found_placeholders)): ?>
                            <em>Nu s-au găsit placeholder-uri în template.</em>
                        <?php else: ?>
                            <?php foreach ($found_placeholders as $placeholder): ?>
                                <code>[<?php echo $placeholder; ?>]</code>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="editor-toolbar">
                        <button type="button" class="toolbar-btn" onclick="insertHTML('<h1></h1>')" title="Heading 1">
                            <strong>H1</strong>
                        </button>
                        <button type="button" class="toolbar-btn" onclick="insertHTML('<h2></h2>')" title="Heading 2">
                            <strong>H2</strong>
                        </button>
                        <button type="button" class="toolbar-btn" onclick="insertHTML('<h3></h3>')" title="Heading 3">
                            <strong>H3</strong>
                        </button>
                        <button type="button" class="toolbar-btn" onclick="insertHTML('<p></p>')" title="Paragraph">
                            <strong>P</strong>
                        </button>
                        <button type="button" class="toolbar-btn" onclick="insertHTML('<strong></strong>')" title="Bold">
                            <strong>B</strong>
                        </button>
                        <button type="button" class="toolbar-btn" onclick="insertHTML('<em></em>')" title="Italic">
                            <em>I</em>
                        </button>
                        <button type="button" class="toolbar-btn" onclick="insertHTML('<ul>\n  <li></li>\n</ul>')" title="Unordered List">
                            • List
                        </button>
                        <button type="button" class="toolbar-btn" onclick="insertHTML('<ol>\n  <li></li>\n</ol>')" title="Ordered List">
                            1. List
                        </button>
                        <button type="button" class="toolbar-btn" onclick="insertHTML('<br>')" title="Line Break">
                            ↵ BR
                        </button>
                        <button type="button" class="toolbar-btn" onclick="insertHTML('<hr>')" title="Horizontal Rule">
                            ─ HR
                        </button>
                    </div>
                    
                    <textarea 
                        id="templateContent"
                        name="template_content" 
                        rows="30" 
                        required
                        placeholder="Scrieți sau inserați HTML-ul template-ului aici..."><?php echo htmlspecialchars($template_content); ?></textarea>
                </div>
                
                <button type="submit" class="btn-submit">💾 Salvează Template</button>
            </form>
            
            <!-- Version History Section - Moved below Save button -->
            <?php if ($is_edit): ?>
                <?php
                $versioning = new TemplateVersioning($db);
                $versions = $versioning->getVersions($template_id, 10);
                ?>
                
                <?php if (!empty($versions)): ?>
                <div class="versions-section" style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 12px; border: 2px solid #e0e0e0;">
                    <h2 style="margin-top: 0; display: flex; align-items: center; gap: 10px;">
                        📚 Istoric Versiuni (<?php echo count($versions); ?>/10)
                    </h2>
                    <p style="color: #666; margin-bottom: 15px;">
                        Ultimele modificări salvate automat. Click pe versiune pentru a restaura.
                    </p>
                    
                    <div class="versions-list" style="display: grid; gap: 10px;">
                        <?php foreach ($versions as $version): ?>
                            <div class="version-item" style="padding: 12px 15px; background: white; border-radius: 8px; border: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center;">
                                <div style="flex: 1;">
                                    <strong style="color: <?php echo $primary_color; ?>;">
                                        Versiunea #<?php echo $version['version_number']; ?>
                                    </strong>
                                    <span style="color: #666; margin-left: 10px;">
                                        📅 <?php echo date('d.m.Y H:i', strtotime($version['created_at'])); ?>
                                    </span>
                                    <span style="color: #999; margin-left: 10px;">
                                        <?php echo round($version['content_size'] / 1024, 1); ?> KB
                                    </span>
                                </div>
                                <div style="display: flex; gap: 10px;">
                                    <a href="?id=<?php echo $template_id; ?>&restore=<?php echo $version['id']; ?>" 
                                       class="version-restore-btn"
                                       onclick="return confirm('Restaurezi versiunea #<?php echo $version['version_number']; ?>?\n\nAtenție: Template-ul curent va fi înlocuit!');"
                                       style="padding: 6px 12px; background: #28a745; color: white; text-decoration: none; border-radius: 6px; font-size: 13px; transition: all 0.3s;">
                                        ↺ Restaurează
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    
    <script>
        function insertPlaceholder(placeholder) {
            const textarea = document.getElementById('templateContent');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            
            textarea.value = text.substring(0, start) + placeholder + text.substring(end);
            textarea.selectionStart = textarea.selectionEnd = start + placeholder.length;
            textarea.focus();
        }
        
        function insertHTML(html) {
            const textarea = document.getElementById('templateContent');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            const selected = text.substring(start, end);
            
            // If text is selected, wrap it
            if (selected) {
                const openTag = html.split('>')[0] + '>';
                const closeTag = '</' + html.split('<')[1].split('>')[0] + '>';
                const wrapped = openTag + selected + closeTag;
                textarea.value = text.substring(0, start) + wrapped + text.substring(end);
                textarea.selectionStart = start;
                textarea.selectionEnd = start + wrapped.length;
            } else {
                textarea.value = text.substring(0, start) + html + text.substring(end);
                const cursorPos = start + html.indexOf('</');
                textarea.selectionStart = textarea.selectionEnd = cursorPos;
            }
            
            textarea.focus();
        }
    </script>
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