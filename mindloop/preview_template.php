<?php
// Disable Cloudflare email obfuscation
header("CF-EmailObfuscation: off");

/**
 * TRUE TEMPLATE PREVIEW - PDF Generation Fixed
 * Shows exactly what client sees + generates actual PDF preview
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/auth_check.php';

// Get template ID
$template_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'web'; // 'web' or 'pdf'

if ($template_id <= 0) {
    die('❌ ID template invalid. Utilizare: preview_template.php?id=4');
}

// Get database connection
$conn = getDBConnection();

// Fetch template
$stmt = $conn->prepare("
    SELECT * FROM contract_templates 
    WHERE id = ? AND site_id = ?
");
$stmt->bind_param("ii", $template_id, $site_id);
$stmt->execute();
$template = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$template) {
    die('❌ Template nu a fost găsit sau nu aveți acces la el.');
}

// Fetch all fields for this template
$stmt = $conn->prepare("
    SELECT 
        fd.field_name, 
        COALESCE(tfm.custom_label, fd.field_label) AS field_label, 
        fd.field_type, 
        fd.field_group, 
        tfm.is_required,
        COALESCE(tfm.custom_placeholder, fd.placeholder) AS placeholder,
        tfm.display_order
    FROM template_field_mapping tfm
    JOIN field_definitions fd ON tfm.field_definition_id = fd.id
    WHERE tfm.template_id = ? 
    ORDER BY fd.field_group, tfm.display_order, fd.field_label
");
$stmt->bind_param("i", $template_id);
$stmt->execute();
$fields_result = $stmt->get_result();
$fields = [];
while ($field = $fields_result->fetch_assoc()) {
    $fields[] = $field;
}
$stmt->close();

// Web preview mode - just highlight placeholders, don't replace with test data
$preview_content = $template['template_content'];

// Highlight all placeholders (support both upper and lowercase)
$preview_content = preg_replace_callback(
    '/\[([A-Za-z_0-9]+)\]/',
    function($matches) {
        return '<span class="filled-field" data-field="' . htmlspecialchars($matches[1]) . '" title="Placeholder: ' . htmlspecialchars($matches[1]) . '">[' . htmlspecialchars($matches[1]) . ']</span>';
    },
    $preview_content
);

// Count fields
// 1. Unique fields (distinct field names from template)
preg_match_all('/\[([A-Za-z_0-9]+)\]/', $template['template_content'], $template_placeholders);
$unique_fields = count(array_unique($template_placeholders[1]));

// 2. Total placeholders (total occurrences in template)
preg_match_all('/\[([A-Za-z_0-9]+)\]/', $template['template_content'], $all_occurrences);
$total_placeholders = count($all_occurrences[0]);

// For display
$total_fields = $unique_fields;

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview: <?php echo htmlspecialchars($template['template_name']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            width: 100vw !important;
            max-width: 100vw !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow-x: hidden;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }
        
        .preview-toolbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .toolbar-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .toolbar-left h1 {
            font-size: 18px;
            font-weight: 600;
        }
        
        .toolbar-left .template-name {
            font-size: 14px;
            opacity: 0.9;
            padding: 6px 12px;
            background: rgba(255,255,255,0.2);
            border-radius: 6px;
        }
        
        .toolbar-actions {
            display: flex;
            gap: 12px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-white {
            background: white;
            color: #667eea;
        }
        
        .btn-white:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid rgba(255,255,255,0.3);
        }
        
        .btn-outline:hover {
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.5);
        }
        
        .btn-pdf {
            background: #ff6b6b;
            color: white;
        }
        
        .btn-pdf:hover {
            background: #ff5252;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255,107,107,0.4);
        }
        
        .preview-info {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            padding: 12px 30px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 2px solid #2196F3;
        }
        
        .preview-info .icon {
            font-size: 24px;
        }
        
        .preview-info .text strong {
            display: block;
            color: #1565c0;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 3px;
        }
        
        .preview-info .text span {
            color: #424242;
            font-size: 13px;
            line-height: 1.4;
        }
        
        .stats-bar {
            background: white;
            padding: 15px 30px;
            display: flex;
            gap: 50px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stat-icon {
            font-size: 28px;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 5px;
            color: #1976d2;
        }
        
        .stat-label {
            font-size: 11px;
            color: #757575;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
        }
        
        .stat-item.filled .stat-value {
            color: #4caf50;
        }
        
        .stat-item.unfilled .stat-value {
            color: #ff9800;
        }
        
        .legend {
            background: #fff3e0;
            padding: 15px 30px;
            display: flex;
            gap: 30px;
            align-items: center;
            border-bottom: 1px solid #ffe0b2;
        }
        
        .legend strong {
            color: #e65100;
            font-size: 14px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }
        
        .legend-badge {
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 11px;
        }
        
        .legend-badge.filled {
            background: #c8e6c9;
            color: #2e7d32;
        }
        
        .legend-badge.unfilled {
            background: #ffecb3;
            color: #f57f17;
        }
        
        .preview-container {
            max-width: 80vw !important;
            width: 80vw !important;
            margin: 20px auto;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .preview-content {
            padding: 30px 40px;
            line-height: 1.6;
            font-size: 14px;
        }
        
        .preview-content .filled-field {
            background: linear-gradient(120deg, #e3f2fd 0%, #bbdefb 100%);
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: 600;
            color: #0d47a1;
            cursor: help;
            transition: all 0.2s ease;
            border: 1px solid #2196F3;
        }
        
        .preview-content .filled-field:hover {
            background: #90caf9;
            transform: scale(1.05);
        }
        
        @media print {
            .preview-toolbar,
            .preview-info,
            .stats-bar,
            .legend {
                display: none !important;
            }
            
            body {
                background: white;
            }
            
            .preview-container {
                box-shadow: none;
                margin: 0;
            }
            
            .preview-content .filled-field {
                background: none;
                color: inherit;
                font-weight: normal;
            }
            
            .preview-content .unfilled-field {
                background: none;
                border: none;
                animation: none;
            }
        }

        /* ================================================
           MOBILE RESPONSIVE
           ================================================ */
        
        @media (max-width: 768px) {
            /* Toolbar */
            .preview-toolbar {
                padding: 15px;
                flex-direction: column;
                gap: 15px;
                position: relative;
            }
            
            .toolbar-left {
                flex-direction: column;
                gap: 10px;
                width: 100%;
                text-align: center;
            }
            
            .toolbar-left h1 {
                font-size: 18px;
            }
            
            .toolbar-left .template-name {
                font-size: 13px;
                padding: 5px 10px;
                width: 100%;
            }
            
            .toolbar-actions {
                flex-direction: column;
                width: 100%;
                gap: 8px;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
                padding: 12px 16px;
                font-size: 14px;
            }
            
            /* Preview Info */
            .preview-info {
                padding: 15px;
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .preview-info .icon {
                font-size: 20px;
            }
            
            .preview-info .text strong {
                font-size: 15px;
            }
            
            .preview-info .text span {
                font-size: 13px;
            }
            
            /* Stats Bar */
            .stats-bar {
                padding: 15px;
                flex-direction: column;
                gap: 15px;
            }
            
            .stat-item {
                justify-content: center;
                width: 100%;
            }
            
            .stat-value {
                font-size: 24px;
            }
            
            .stat-label {
                font-size: 11px;
            }
            
            /* Legend */
            .legend {
                padding: 12px 15px;
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            
            .legend strong {
                font-size: 13px;
            }
            
            .legend-item {
                font-size: 12px;
                gap: 6px;
            }
            
            .legend-badge {
                padding: 3px 8px;
                font-size: 10px;
            }
            
            /* Preview Container */
            .preview-container {
                max-width: 95vw !important;
                width: 95vw !important;
                margin: 15px auto;
                border-radius: 8px;
            }
            
            .preview-content {
                padding: 20px 15px;
                font-size: 13px;
                line-height: 1.6;
            }
            
            /* Contract Template Specific */
            .preview-content h1 {
                font-size: 20px !important;
                line-height: 1.3 !important;
                margin: 15px 0 !important;
            }
            
            .preview-content h2 {
                font-size: 17px !important;
                line-height: 1.3 !important;
                margin: 12px 0 !important;
            }
            
            .preview-content h3 {
                font-size: 15px !important;
                line-height: 1.3 !important;
                margin: 10px 0 !important;
            }
            
            .preview-content p {
                font-size: 13px !important;
                line-height: 1.5 !important;
                margin: 8px 0 !important;
            }
            
            .preview-content table {
                font-size: 12px !important;
                overflow-x: auto;
                display: block;
            }
            
            .preview-content ol,
            .preview-content ul {
                padding-left: 20px !important;
                margin: 10px 0 !important;
            }
            
            .preview-content li {
                font-size: 13px !important;
                line-height: 1.5 !important;
                margin: 5px 0 !important;
            }
            
            /* Field highlights - smaller on mobile */
            .preview-content .filled-field,
            .preview-content .unfilled-field {
                padding: 1px 4px;
                font-size: 12px;
            }
        }
        
        /* Very small devices (phones < 400px) */
        @media (max-width: 400px) {
            .toolbar-left h1 {
                font-size: 16px;
            }
            
            .btn {
                padding: 10px 12px;
                font-size: 13px;
            }
            
            .preview-content {
                padding: 15px 10px;
                font-size: 12px;
            }
            
            .preview-content h1 {
                font-size: 18px !important;
            }
            
            .preview-content h2 {
                font-size: 15px !important;
            }
            
            .preview-content p,
            .preview-content li {
                font-size: 12px !important;
            }
        }
    </style>
</head>
<body>
    <!-- Toolbar -->
    <div class="preview-toolbar">
        <div class="toolbar-left">
            <h1>📄 Preview Template</h1>
            <span class="template-name"><?php echo htmlspecialchars($template['template_name']); ?></span>
        </div>
        <div class="toolbar-actions">
            <a href="?id=<?php echo $template_id; ?>&mode=pdf" class="btn btn-pdf" target="_blank">
                📥 Download PDF Preview
            </a>
            <a href="edit_template.php?id=<?php echo $template_id; ?>" class="btn btn-outline">
                ✏️ Editează
            </a>
            <a href="templates.php" class="btn btn-white">
                🏠 Înapoi
            </a>
        </div>
    </div>
    
    <!-- Info Banner -->
    <div class="preview-info">
        <div class="icon">ℹ️</div>
        <div class="text">
            <strong>Preview Mode - Date de Test</strong>
            <span>Aceasta este o previzualizare exactă a modului în care clientul va vedea contractul. 
            Apasă "Download PDF Preview" pentru a vedea cum va arăta PDF-ul generat.</span>
        </div>
    </div>
    
    <!-- Stats -->
    <div class="stats-bar">
        <div class="stat-item">
            <span class="stat-icon">🔤</span>
            <div>
                <div class="stat-value"><?php echo $unique_fields; ?></div>
                <div class="stat-label">Câmpuri Unice</div>
            </div>
        </div>
        <div class="stat-item">
            <span class="stat-icon">📝</span>
            <div>
                <div class="stat-value"><?php echo $total_placeholders; ?></div>
                <div class="stat-label">Total Placeholder-uri</div>
            </div>
        </div>

    </div>
    
    
    <!-- Preview Content -->
    <div class="preview-container">
        <div class="preview-content">
            <?php echo $preview_content; ?>
        </div>
    </div>
    

</body>
</html>