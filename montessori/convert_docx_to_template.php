<?php
/**
 * Word Document to Contract Template Converter
 * Upload DOCX, converts to HTML, detects fields, creates template
 */

// Start session first
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

// Check authentication - load from auth_check.php
require_once __DIR__ . '/auth_check.php';

// Should have these variables from auth_check.php:
// $user_id, $site_id, $site_name, $site_slug, $primary_color

$message = '';
$error = '';

// Handle file upload and conversion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['docx_file'])) {
    $upload_file = $_FILES['docx_file'];
    
    if ($upload_file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Eroare la încărcarea fișierului. Cod eroare: ' . $upload_file['error'];
    } elseif (!in_array($upload_file['type'], [
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/msword'
    ])) {
        $error = 'Vă rugăm să încărcați un fișier Word (.docx sau .doc)';
    } else {
        // Create temporary directory
        $temp_dir = sys_get_temp_dir() . '/docx_convert_' . uniqid();
        mkdir($temp_dir);
        
        $zip = new ZipArchive();
        if ($zip->open($upload_file['tmp_name']) === TRUE) {
            $zip->extractTo($temp_dir);
            $zip->close();
            
            // Read document.xml
            $xml_file = $temp_dir . '/word/document.xml';
            if (file_exists($xml_file)) {
                $xml = simplexml_load_file($xml_file);
                
                // Extract text content
                $html_content = convertXMLToHTML($xml);
                
                // Detect field placeholders
                $detected_fields = detectFields($html_content);
                
                // Get template details from form
                $template_name = $_POST['template_name'] ?? 'Contract Educational ' . date('Y-m-d');
                $template_description = $_POST['template_description'] ?? '';
                
                // Insert template
                $conn = getDBConnection();
                $stmt = $conn->prepare("
                    INSERT INTO contract_templates 
                    (site_id, template_name, template_content, description, is_active, created_at) 
                    VALUES (?, ?, ?, ?, 1, NOW())
                ");
                $stmt->bind_param('isss', $site_id, $template_name, $html_content, $template_description);
                
                if ($stmt->execute()) {
                    $template_id = $conn->insert_id;
                    
                    // Insert detected fields
                    $field_insert_count = 0;
                    foreach ($detected_fields as $order => $field) {
                        $field_stmt = $conn->prepare("
                            INSERT INTO template_fields 
                            (template_id, field_name, field_label, field_type, field_order, is_required, field_group, placeholder)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $field_stmt->bind_param(
                            'isssiiss',
                            $template_id,
                            $field['name'],
                            $field['label'],
                            $field['type'],
                            $order,
                            $field['required'],
                            $field['group'],
                            $field['placeholder']
                        );
                        
                        if ($field_stmt->execute()) {
                            $field_insert_count++;
                        }
                        $field_stmt->close();
                    }
                    
                    $message = "✅ Template creat cu succes!<br>" .
                              "<strong>Template ID: {$template_id}</strong><br>" .
                              "Nume: {$template_name}<br>" .
                              "Câmpuri detectate automat: {$field_insert_count}<br><br>" .
                              "📝 <strong>NEXT STEP:</strong> Trebuie să adaugi manual toate câmpurile necesare.<br>" .
                              "Editează fișierul <code>insert_template_fields.sql</code> și înlocuiește <code>XX</code> cu <strong>{$template_id}</strong>,<br>" .
                              "apoi rulează SQL-ul în phpMyAdmin.<br><br>" .
                              "<a href='" . SUBFOLDER . "/" . $site_slug . "/templates.php' class='btn btn-primary' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>📋 Vezi Templates</a>";
                } else {
                    $error = 'Eroare la salvarea template-ului: ' . $conn->error;
                }
                
                $stmt->close();
                $conn->close();
            } else {
                $error = 'Nu s-a putut găsi document.xml în fișierul Word';
            }
            
            // Cleanup
            deleteDirectory($temp_dir);
        } else {
            $error = 'Nu s-a putut deschide fișierul Word (format invalid)';
        }
    }
}

/**
 * Convert Word XML to clean HTML
 */
function convertXMLToHTML($xml) {
    $html = '<div class="contract-content">';
    
    // Register namespaces
    $xml->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
    
    // Get all paragraphs
    $paragraphs = $xml->xpath('//w:p');
    
    foreach ($paragraphs as $p) {
        $p->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        
        $text = '';
        $texts = $p->xpath('.//w:t');
        foreach ($texts as $t) {
            $text .= (string)$t;
        }
        
        $text = trim($text);
        if (empty($text)) {
            continue;
        }
        
        // Check if it's a heading
        $pStyle = $p->xpath('.//w:pStyle/@w:val');
        $is_heading = false;
        if (!empty($pStyle)) {
            $style = (string)$pStyle[0];
            $is_heading = (strpos($style, 'Heading') !== false || strpos($style, 'Title') !== false);
        }
        
        if ($is_heading) {
            $html .= '<h3>' . htmlspecialchars($text) . '</h3>';
        } else {
            $html .= '<p>' . htmlspecialchars($text) . '</p>';
        }
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Detect field placeholders in content
 */
function detectFields($html_content) {
    $fields = [];
    
    // Common patterns to detect
    $patterns = [
        // Dots pattern: Număr…………./Data.........
        '/Număr[.…]+\/Data[.…]+/' => ['NUMAR_CONTRACT', 'Număr Contract', 'text', 'Contract'],
        
        // Named fields with dots/underscores
        '/cetăţean[\s]+[.…_]+/' => ['CETATEAN_1', 'Cetățean Părinte 1', 'text', 'Părinte 1'],
        '/cu domiciliul[\s\n]+în[\s]+[.…]+/' => ['DOMICILIU_1', 'Domiciliu Părinte 1', 'textarea', 'Părinte 1'],
        '/tel[\s]*[.…_]+/' => ['TELEFON_1', 'Telefon', 'phone', 'Părinte 1'],
        '/e-mail[.…]+/' => ['EMAIL_1', 'Email', 'email', 'Părinte 1'],
        '/identificat cu [.…]+, seria [.…]+, nr[.\s]*[.…]+/' => ['TIP_ACT_1', 'Act Identitate', 'text', 'Părinte 1'],
        '/CNP[\s]*[.…_]+/' => ['CNP_1', 'CNP', 'cnp', 'Părinte 1'],
        
        // Child fields
        '/minorului[\s]*[.…]+/' => ['NUME_COPIL', 'Nume Copil', 'text', 'Copil'],
        '/născut[\s\n]+la data de [.…]+/' => ['DATA_NASTERE_COPIL', 'Data Naștere Copil', 'date', 'Copil'],
        '/în localitatea[\s]*[.…]+/' => ['LOC_NASTERE_COPIL', 'Localitatea Nașterii', 'text', 'Copil'],
        
        // Fees
        '/taxa fixă[^0-9]*(\d+)\s*lei/' => ['TAXA_FIXA', 'Taxa Fixă (lei)', 'number', 'Taxe'],
        '/(\d+)[,.]00\s*lei\/zi/' => ['TAXA_VARIABILA', 'Taxa Variabilă pe Zi (lei)', 'number', 'Taxe'],
    ];
    
    $order = 0;
    foreach ($patterns as $pattern => $field_info) {
        if (preg_match($pattern, $html_content)) {
            $fields[$order++] = [
                'name' => $field_info[0],
                'label' => $field_info[1],
                'type' => $field_info[2],
                'group' => $field_info[3],
                'required' => 1,
                'placeholder' => ''
            ];
        }
    }
    
    return $fields;
}

/**
 * Delete directory recursively
 */
function deleteDirectory($dir) {
    if (!file_exists($dir)) return;
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? deleteDirectory($path) : unlink($path);
    }
    rmdir($dir);
}

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Convertire Contract Word → Template</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input[type="text"]:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 2px dashed #667eea;
            border-radius: 8px;
            cursor: pointer;
            background: #f8f9ff;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            width: 100%;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            line-height: 1.6;
        }
        .message strong {
            font-size: 16px;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .info-box h3 {
            color: #0066cc;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .info-box ul {
            margin-left: 20px;
            color: #333;
        }
        .info-box li {
            margin-bottom: 5px;
        }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            color: #d63384;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📄 Convertire Contract Word → Template</h1>
        <p class="subtitle">Pentru: <?php echo htmlspecialchars($site_name); ?></p>
        
        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error">❌ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="info-box">
            <h3>ℹ️ Cum funcționează:</h3>
            <ul>
                <li>Încarcă fișierul Word (.docx) cu contractul</li>
                <li>Sistemul va extrage textul și îl va converti în HTML</li>
                <li>Câteva câmpuri vor fi detectate automat</li>
                <li>Template-ul va fi salvat în baza de date</li>
                <li><strong>Important:</strong> După creare, vei primi Template ID-ul pe care îl vei folosi în SQL</li>
            </ul>
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="template_name">Nume Template:</label>
                <input type="text" id="template_name" name="template_name" 
                       value="Contract Educational Baby LNG 2025-2026" required>
            </div>
            
            <div class="form-group">
                <label for="template_description">Descriere (opțional):</label>
                <textarea id="template_description" name="template_description" rows="3" 
                          placeholder="Ex: Contract educațional pentru anul școlar 2025-2026">Contract educațional pentru anul școlar 2025-2026 - Baby LNG</textarea>
            </div>
            
            <div class="form-group">
                <label for="docx_file">Fișier Word (.docx):</label>
                <input type="file" id="docx_file" name="docx_file" accept=".docx,.doc" required>
            </div>
            
            <button type="submit" class="btn">🚀 Convertește și Creează Template</button>
        </form>
    </div>
</body>
</html>