<?php
/**
 * Minimal Template Creator - Works with existing table structure
 */

// Start session first
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

// Check authentication
require_once __DIR__ . '/auth_check.php';

$message = '';
$error = '';
$template_id = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_template'])) {
    $template_name = trim($_POST['template_name'] ?? '');
    $template_content_raw = $_POST['template_content'] ?? '';
    
    if (empty($template_name)) {
        $error = '❌ Vă rugăm să introduceți un nume pentru template.';
    } elseif (empty($template_content_raw)) {
        $error = '❌ Vă rugăm să introduceți conținutul contractului.';
    } else {
        // Convert text to HTML with proper formatting
        $html_content = convertTextToHTML($template_content_raw);
        
        // Insert template with minimal fields
        $conn = getDBConnection();
        
        // Try to insert with only the most basic fields
        $stmt = $conn->prepare("
            INSERT INTO contract_templates 
            (site_id, template_name, template_content, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->bind_param('iss', $site_id, $template_name, $html_content);
        
        if ($stmt->execute()) {
            $template_id = $conn->insert_id;
            
            $message = "✅ <strong>Template creat cu succes!</strong><br><br>" .
                      "<div style='background: #fff3cd; padding: 20px; border-radius: 10px; border: 3px solid #ffc107; text-align: center;'>" .
                      "<div style='font-size: 14px; color: #856404; margin-bottom: 10px;'>🎯 TEMPLATE ID</div>" .
                      "<div style='font-size: 48px; font-weight: bold; color: #dc3545; font-family: monospace;'>{$template_id}</div>" .
                      "</div><br>" .
                      "<div style='background: #e7f3ff; padding: 20px; border-radius: 8px; border-left: 4px solid #0066cc;'>" .
                      "<strong style='color: #0066cc; font-size: 16px;'>📝 NEXT STEPS:</strong><br><br>" .
                      "<ol style='margin-left: 20px; line-height: 2;'>" .
                      "<li>Deschide fișierul <code style='background: #f5f5f5; padding: 3px 8px; border-radius: 3px; color: #d63384; font-weight: 600;'>insert_template_fields.sql</code></li>" .
                      "<li>Găsește linia: <code style='background: #f5f5f5; padding: 3px 8px; border-radius: 3px; color: #d63384; font-weight: 600;'>SET @template_id = XX;</code></li>" .
                      "<li>Înlocuiește <code style='background: #f5f5f5; padding: 3px 8px; border-radius: 3px; color: #d63384; font-weight: 600;'>XX</code> cu <strong style='color: #dc3545; font-size: 18px;'>{$template_id}</strong></li>" .
                      "<li>Rulează SQL-ul complet în phpMyAdmin (selectează database-ul <code style='background: #f5f5f5; padding: 3px 8px; border-radius: 3px; color: #d63384; font-weight: 600;'>r68649site_contractdigital_db</code>)</li>" .
                      "<li>Apoi editează template-ul pentru a adăuga placeholders precum <code style='background: #f5f5f5; padding: 3px 8px; border-radius: 3px; color: #d63384; font-weight: 600;'>[NUME_PARINTE_1]</code></li>" .
                      "</ol>" .
                      "</div><br>" .
                      "<div style='text-align: center;'>" .
                      "<a href='" . SUBFOLDER . "/" . $site_slug . "/edit_template.php?id={$template_id}' style='background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; display: inline-block; margin-right: 10px; font-weight: 600; font-size: 16px;'>✏️ Editează Template Acum</a>" .
                      "<a href='" . SUBFOLDER . "/" . $site_slug . "/templates.php' style='background: #6c757d; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; display: inline-block; font-weight: 600; font-size: 16px;'>📋 Vezi Toate Templates</a>" .
                      "</div>";
        } else {
            $error = '❌ Eroare la salvarea template-ului: ' . $conn->error;
        }
        
        $stmt->close();
        $conn->close();
    }
}

/**
 * Convert plain text to HTML with proper formatting
 */
function convertTextToHTML($text) {
    $html = '<div class="contract-content" style="font-family: Arial, sans-serif; line-height: 1.6; font-size: 11pt;">';
    
    // Split by double newlines to get paragraphs
    $paragraphs = preg_split('/\n\s*\n/', $text);
    
    foreach ($paragraphs as $para) {
        $para = trim($para);
        if (empty($para)) continue;
        
        // Check if it's a heading
        $is_heading = false;
        
        // Pattern 1: All uppercase (like "CONTRACT EDUCAȚIONAL")
        if (mb_strtoupper($para) === $para && mb_strlen($para) < 100 && mb_strlen($para) > 5) {
            $is_heading = true;
        }
        
        // Pattern 2: Starts with numbers (like "1. OBIECTUL")
        if (preg_match('/^\d+[\.\)]\s+[A-ZĂÂÎȘȚ]/', $para)) {
            $is_heading = true;
        }
        
        // Pattern 3: Starts with bullet points
        if (preg_match('/^[\-\•\◦\·]\s+/', $para)) {
            $html .= '<p style="margin-left: 20px; margin-bottom: 8px;">• ' . htmlspecialchars(trim(preg_replace('/^[\-\•\◦\·]\s+/', '', $para))) . '</p>';
            continue;
        }
        
        if ($is_heading) {
            $html .= '<h3 style="color: #2c3e50; margin-top: 15px; margin-bottom: 10px; font-size: 13pt; font-weight: bold;">' . htmlspecialchars($para) . '</h3>';
        } else {
            // Regular paragraph
            $html .= '<p style="margin-bottom: 10px; text-align: justify;">' . nl2br(htmlspecialchars($para)) . '</p>';
        }
    }
    
    $html .= '</div>';
    
    return $html;
}

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creare Template Manual</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
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
            font-size: 14px;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
            font-family: inherit;
        }
        input[type="text"]:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        textarea {
            font-family: 'Courier New', monospace;
            min-height: 400px;
            resize: vertical;
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
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            line-height: 1.8;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        .info-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .info-box h3 {
            color: #856404;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .info-box ol {
            margin-left: 20px;
            color: #333;
        }
        .info-box li {
            margin-bottom: 8px;
            line-height: 1.5;
        }
        .tip {
            background: #e7f3ff;
            border-left: 4px solid #0066cc;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .tip strong {
            color: #0066cc;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📝 Creare Template Manual</h1>
        <p class="subtitle">Pentru: <?php echo htmlspecialchars($site_name); ?></p>
        
        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!$template_id): ?>
            
            <div class="info-box">
                <h3>⚠️ Cum să copiezi textul din Word:</h3>
                <ol>
                    <li>Deschide fișierul <strong>Contract educational baby LNG 2025-2026.docx</strong></li>
                    <li>Selectează TOT textul (Ctrl+A sau Cmd+A)</li>
                    <li>Copiază-l (Ctrl+C sau Cmd+C)</li>
                    <li>Lipește-l în câmpul de mai jos (Ctrl+V sau Cmd+V)</li>
                    <li>Click pe "Creează Template"</li>
                </ol>
            </div>
            
            <div class="tip">
                <strong>💡 TIP:</strong> După creare, vei primi un Template ID mare pe ecran. 
                Copiază-l și folosește-l în fișierul SQL pentru a adăuga câmpurile.
            </div>
            
            <form method="POST">
                <input type="hidden" name="create_template" value="1">
                
                <div class="form-group">
                    <label for="template_name">Nume Template: *</label>
                    <input type="text" id="template_name" name="template_name" 
                           value="Contract Educational Baby LNG 2025-2026" required>
                </div>
                
                <div class="form-group">
                    <label for="template_content">Conținut Contract (lipește tot textul din Word): *</label>
                    <textarea id="template_content" name="template_content" required 
                              placeholder="Lipește aici tot textul din documentul Word..."></textarea>
                </div>
                
                <button type="submit" class="btn">🚀 Creează Template</button>
            </form>
            
        <?php endif; ?>
    </div>
</body>
</html>