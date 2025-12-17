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
    SELECT field_name, field_label, field_type, field_group, is_required, default_value
    FROM template_fields 
    WHERE template_id = ? 
    ORDER BY field_group, field_order, field_label
");
$stmt->bind_param("i", $template_id);
$stmt->execute();
$fields_result = $stmt->get_result();
$fields = [];
while ($field = $fields_result->fetch_assoc()) {
    $fields[] = $field;
}
$stmt->close();

// Generate sample data based on field types
$sample_data = [];
foreach ($fields as $field) {
    $field_name = $field['field_name'];
    
    // Use default value if exists
    if (!empty($field['default_value'])) {
        $sample_data[$field_name] = $field['default_value'];
        continue;
    }
    
    // Generate sample data based on field name patterns
    if (strpos($field_name, 'NUMAR_CONTRACT') !== false) {
        $sample_data[$field_name] = '001/2025';
    } elseif (strpos($field_name, 'DATA') !== false && strpos($field_name, 'NASTERE') === false) {
        $sample_data[$field_name] = date('d.m.Y');
    } elseif (strpos($field_name, 'NUME_PARINTE_1') !== false) {
        $sample_data[$field_name] = 'Popescu';
    } elseif (strpos($field_name, 'PRENUME_PARINTE_1') !== false) {
        $sample_data[$field_name] = 'Ion';
    } elseif (strpos($field_name, 'NUME_PARINTE_2') !== false) {
        $sample_data[$field_name] = 'Popescu';
    } elseif (strpos($field_name, 'PRENUME_PARINTE_2') !== false) {
        $sample_data[$field_name] = 'Maria';
    } elseif (strpos($field_name, 'NUME_COPIL') !== false) {
        $sample_data[$field_name] = 'Popescu';
    } elseif (strpos($field_name, 'PRENUME_COPIL') !== false) {
        $sample_data[$field_name] = 'Andrei';
    } elseif (strpos($field_name, 'DATA_NASTERE') !== false) {
        $sample_data[$field_name] = '15.03.2022';
    } elseif (strpos($field_name, 'VARSTA') !== false) {
        $sample_data[$field_name] = '3 ani';
    } elseif (strpos($field_name, 'CNP') !== false) {
        $sample_data[$field_name] = str_ends_with($field_name, '_1') ? '1234567890123' : '2234567890123';
    } elseif (strpos($field_name, 'CETATEAN') !== false) {
        $sample_data[$field_name] = str_ends_with($field_name, '_1') ? 'Român' : 'Română';
    } elseif (strpos($field_name, 'BI_SERIE') !== false) {
        $sample_data[$field_name] = 'RT';
    } elseif (strpos($field_name, 'BI_NR') !== false) {
        $sample_data[$field_name] = str_ends_with($field_name, '_1') ? '123456' : '234567';
    } elseif (strpos($field_name, 'BI_ELIBERAT') !== false) {
        $sample_data[$field_name] = 'SPCLEP București';
    } elseif (strpos($field_name, 'BI_DATA') !== false) {
        $sample_data[$field_name] = '01.01.2020';
    } elseif (strpos($field_name, 'DOMICILIU') !== false) {
        $sample_data[$field_name] = 'București, Str. Exemplu, Nr. 1, Ap. 10';
    } elseif (strpos($field_name, 'TELEFON') !== false) {
        $sample_data[$field_name] = str_ends_with($field_name, '_1') ? '+40 700 000 001' : '+40 700 000 002';
    } elseif (strpos($field_name, 'EMAIL') !== false) {
        $sample_data[$field_name] = str_ends_with($field_name, '_1') ? 'ion.popescu@example.com' : 'maria.popescu@example.com';
    } elseif (strpos($field_name, 'TIP_PROGRAM') !== false || strpos($field_name, 'PROGRAM') !== false) {
        $sample_data[$field_name] = 'Program scurt (până la 13:30)';
    } elseif (strpos($field_name, 'TAXA_FIXA') !== false) {
        $sample_data[$field_name] = '1.400';
    } elseif (strpos($field_name, 'TAXA_INSCRIERE') !== false) {
        $sample_data[$field_name] = '700';
    } elseif (strpos($field_name, 'TAXA_MASA') !== false) {
        $sample_data[$field_name] = '30';
    } elseif (strpos($field_name, 'DISCOUNT') !== false) {
        $sample_data[$field_name] = '200';
    } elseif (strpos($field_name, 'NUME_INSTITUTIE') !== false) {
        $sample_data[$field_name] = 'S.C LA NOUVELLE GENERATION S.R.L';
    } elseif (strpos($field_name, 'CUI') !== false) {
        $sample_data[$field_name] = '29355885';
    } elseif (strpos($field_name, 'RC') !== false) {
        $sample_data[$field_name] = 'J23/3368/2011';
    } elseif (strpos($field_name, 'SEDIU') !== false) {
        $sample_data[$field_name] = 'Corbeanca, Str. Ciocârliei 58A, Ilfov';
    } elseif (strpos($field_name, 'CONT') !== false) {
        $sample_data[$field_name] = 'RO07BTRLRONCRT0525953601';
    } elseif (strpos($field_name, 'BANCA') !== false) {
        $sample_data[$field_name] = 'Banca Transilvania';
    } elseif (strpos($field_name, 'REPREZENTANT') !== false) {
        $sample_data[$field_name] = 'Alin Pop';
    } elseif (strpos($field_name, 'FUNCTIE') !== false) {
        $sample_data[$field_name] = 'Director';
    } else {
        // Default fallback - use field label
        $sample_data[$field_name] = '[' . $field['field_label'] . ']';
    }
}

// Handle PDF mode
if ($mode === 'pdf') {
    require_once __DIR__ . '/../includes/ContractPDF.php';
    
    // Replace placeholders in template content
    $filled_content = $template['template_content'];
    foreach ($sample_data as $field_name => $field_value) {
        $filled_content = str_replace('[' . $field_name . ']', $field_value, $filled_content);
    }
    
    // Highlight any remaining unfilled placeholders in yellow for PDF
    $filled_content = preg_replace('/\[([A-Z_0-9]+)\]/', '<span style="background-color: yellow; padding: 2px;">[$1]</span>', $filled_content);
    
    try {
        // Create ContractPDF instance with contract ID (use 0 for preview)
        $pdf = new ContractPDF(0);
        
        // Prepare contract data
        // contract_content should have placeholders already replaced
        $contract_data = [
            'contract_content' => $filled_content,
            'signature_data' => null // No signature for preview
        ];
        
        // Generate PDF
        $pdf->generate($contract_data);
        
        // Output PDF
        $filename = 'Preview_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $template['template_name']) . '.pdf';
        $pdf->output($filename);
        
        exit;
    } catch (Exception $e) {
        die('❌ Eroare la generarea PDF: ' . $e->getMessage() . '<br><br>Stack trace:<br>' . nl2br($e->getTraceAsString()));
    }
}

// Web preview mode
$preview_content = $template['template_content'];

// Replace placeholders with highlighted sample data
foreach ($sample_data as $field_name => $field_value) {
    $preview_content = str_replace(
        '[' . $field_name . ']',
        '<span class="filled-field" data-field="' . htmlspecialchars($field_name) . '" title="' . htmlspecialchars($field_name . ': ' . $field_value) . '">' 
        . htmlspecialchars($field_value) . '</span>',
        $preview_content
    );
}

// Highlight any remaining unfilled placeholders
$preview_content = preg_replace_callback(
    '/\[([A-Z_0-9]+)\]/',
    function($matches) {
        return '<span class="unfilled-field" data-field="' . htmlspecialchars($matches[1]) . '" title="Câmp lipsă: ' . htmlspecialchars($matches[1]) . '">' . htmlspecialchars($matches[0]) . '</span>';
    },
    $preview_content
);

// Count fields
// 1. Unique fields (distinct field names from preview content)
preg_match_all('/\[([A-Z_0-9]+)\]/', $template['template_content'], $template_placeholders);
$unique_fields = count(array_unique($template_placeholders[1]));

// 2. Total placeholders (unique placeholders in template)
// Count unique data-field attributes in preview content
preg_match_all('/data-field="([^"]+)"/', $preview_content, $all_fields_matches);
$total_placeholders = count(array_unique($all_fields_matches[1]));

// 3. Count unfilled placeholders (unique)
preg_match_all('/<span class="unfilled-field" data-field="([^"]+)"/', $preview_content, $unfilled_matches);
$unfilled_count = count(array_unique($unfilled_matches[1]));

// For display
$filled_count = $total_placeholders - $unfilled_count;
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
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
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
            font-size: 20px;
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
            padding: 10px 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-bottom: 3px solid #2196F3;
        }
        
        .preview-info .icon {
            font-size: 32px;
        }
        
        .preview-info .text strong {
            display: block;
            color: #1565c0;
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .preview-info .text span {
            color: #424242;
            font-size: 14px;
        }
        
        .stats-bar {
            background: white;
            padding: 10px 30px;
            display: flex;
            gap: 40px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stat-icon {
            font-size: 24px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 12px;
            color: #757575;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            margin: 10px auto;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .preview-content {
            padding: 20px 15px;
            line-height: 1.8;
            font-size: 14px;
        }
        
        .preview-content .filled-field {
            background: linear-gradient(120deg, #e8f5e9 0%, #c8e6c9 100%);
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: 600;
            color: #1b5e20;
            cursor: help;
            transition: all 0.2s ease;
        }
        
        .preview-content .filled-field:hover {
            background: #a5d6a7;
            transform: scale(1.05);
        }
        
        .preview-content .unfilled-field {
            background: linear-gradient(120deg, #fff9c4 0%, #fff59d 100%);
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: 600;
            color: #f57f17;
            cursor: help;
            border: 1px dashed #fbc02d;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .preview-content .unfilled-field:hover {
            background: #ffeb3b;
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
                font-size: 28px;
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
                font-size: 20px;
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
                margin: 15px 10px;
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
<?php  // DEBUG: Show all found placeholders echo "<div style='position:fixed;top:60px;right:10px;background:blue;color:white;padding:15px;z-index:9999;max-width:300px;font-size:12px;border-radius:8px;max-height:80vh;overflow-y:auto;'>"; echo "<strong>🔍 PLACEHOLDERS GĂSITE:</strong><br><br>"; echo "<strong>Unique în template:</strong> " . $unique_fields . "<br>"; echo implode(', ', array_unique($template_placeholders[1])) . "<br><br>"; echo "<strong>Total în preview:</strong> " . $total_placeholders . "<br>"; echo implode(', ', array_unique($all_fields_matches[1])) . "<br><br>"; echo "<strong>Unfilled:</strong> " . $unfilled_count . "<br>"; if (!empty($unfilled_matches[1])) {     echo implode(', ', array_unique($unfilled_matches[1])); } echo "</div>";  ?>
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
        <div class="stat-item filled">
            <span class="stat-icon">✅</span>
            <div>
                <div class="stat-value"><?php echo $filled_count; ?></div>
                <div class="stat-label">Câmpuri cu Date Test</div>
            </div>
        </div>
    </div>
    
    <!-- Legend -->
    <div class="legend">
        <strong>Legendă:</strong>
        <div class="legend-item">
            <span class="legend-badge filled">Date de test</span>
            <span>Câmpuri înlocuite cu valori de test</span>
        </div>
        <div class="legend-item">
            <span class="legend-badge unfilled">[CAMP_LIPSA]</span>
            <span>Placeholders care trebuie adăugate în template</span>
        </div>
    </div>
    
    <!-- Preview Content -->
    <div class="preview-container">
        <div class="preview-content">
            <?php echo $preview_content; ?>
        </div>
    </div>
    
    <script>
        // Count unfilled fields
        document.addEventListener('DOMContentLoaded', function() {
            const unfilledCount = document.querySelectorAll('.unfilled-field').length;
            document.getElementById('unfilled-count').textContent = unfilledCount;
        });
    </script>
</body>
</html>