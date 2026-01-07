<?php
// ============================================
// SEND CONTRACT - SIMPLIFIED (EMAIL ONLY)
// Admin sends only email, client fills everything
// ============================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/email.php';

// Check if user is logged in
if (!isset($_SESSION['site_user_id']) || !isset($_SESSION['site_slug'])) {
    header('Location: /ro/login.php');
    exit;
}

$template_id = isset($_GET['template_id']) ? intval($_GET['template_id']) : 0;

if ($template_id == 0) {
    die('❌ Template ID invalid');
}

// Get database connection
$conn = getDBConnection();

// Get site_id from session
$site_slug = $_SESSION['site_slug'];
$stmt = $conn->prepare("SELECT id, site_name FROM sites WHERE site_slug = ?");
$stmt->bind_param("s", $site_slug);
$stmt->execute();
$site_result = $stmt->get_result();
$site = $site_result->fetch_assoc();
$site_id = $site['id'];
$site_name = $site['site_name'];
$stmt->close();

// Get template data
$stmt = $conn->prepare("SELECT * FROM contract_templates WHERE id = ? AND site_id = ?");
$stmt->bind_param("ii", $template_id, $site_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die('❌ Template not found');
}

$template = $result->fetch_assoc();
$stmt->close();

// HANDLE FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $recipient_email = $_POST['recipient_email'] ?? '';
    $recipient_name = $_POST['recipient_name'] ?? '';
    
    if (empty($recipient_email)) {
        die('❌ Email destinatar este obligatoriu');
    }
    
    // Generate unique token
    $unique_base = $recipient_email . microtime(true) . rand(1000, 9999);
    $signing_token = md5($unique_base);
    $unique_token = $signing_token;
    
    // Insert contract (status = 'draft' - waiting for client to fill)
    $stmt = $conn->prepare("INSERT INTO contracts (site_id, template_id, contract_content, signing_token, unique_token, recipient_name, recipient_email, status, sent_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'draft', NOW())");
    $stmt->bind_param("iisssss", $site_id, $template_id, $template['template_content'], $signing_token, $unique_token, $recipient_name, $recipient_email);
    
    if (!$stmt->execute()) {
        die('❌ Eroare la salvarea contractului: ' . $stmt->error);
    }
    
    $contract_id = $stmt->insert_id;
    $stmt->close();
    $conn->close();
    
    // Send email with fill+sign link
    $fill_link = BASE_URL . "/montessori/fill_and_sign.php?token=" . $signing_token;
    
    $email_subject = "Contract de completat și semnat - " . $template['template_name'];
    
    $email_body = "<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2c3e50; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .button { display: inline-block; padding: 15px 40px; background: #27ae60; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; font-size: 18px; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>📄 Contract de Completat</h2>
        </div>
        <div class='content'>
            <p>Bună ziua" . (!empty($recipient_name) ? " <strong>" . htmlspecialchars($recipient_name) . "</strong>" : "") . ",</p>
            <p>Aveți de completat și semnat următorul contract: <strong>" . htmlspecialchars($template['template_name']) . "</strong></p>
            <p>Veți putea:</p>
            <ul>
                <li>✏️ Completa datele necesare</li>
                <li>📄 Citi contractul</li>
                <li>✍️ Semna contractul</li>
            </ul>
            <p>Totul pe o singură pagină, simplu și rapid.</p>
            <p style='text-align: center;'>
                <a href='" . $fill_link . "' class='button'>COMPLETEAZĂ ȘI SEMNEAZĂ</a>
            </p>
            <p style='font-size: 12px; color: #666;'>Sau copiați acest link în browser:<br>" . $fill_link . "</p>
        </div>
        <div class='footer'>
            <p>Acest email a fost trimis de: <strong>" . htmlspecialchars($site_name) . "</strong></p>
            <p>Sistemul Contract Digital</p>
        </div>
    </div>
</body>
</html>";
    
    $email_sent = sendEmail($recipient_email, $email_subject, $email_body);
    
    if ($email_sent) {
        echo "<div style='text-align: center; margin-top: 50px;'>";
        echo "<h2 style='color: green;'>✅ Link trimis cu succes!</h2>";
        echo "<p>Email trimis către: <strong>" . htmlspecialchars($recipient_email) . "</strong></p>";
        echo "<p>Clientul va primi un link pentru a completa și semna contractul.</p>";
        echo "<p><a href='templates.php'>← Înapoi la template-uri</a></p>";
        echo "</div>";
    } else {
        echo "<div style='text-align: center; margin-top: 50px;'>";
        echo "<h2 style='color: red;'>❌ Eroare la trimiterea email-ului</h2>";
        echo "<p><a href='templates.php'>← Înapoi la template-uri</a></p>";
        echo "</div>";
    }
    
    exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trimite Contract - <?php echo htmlspecialchars($template['template_name']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 40px; }
        h1 { color: #2c3e50; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #3498db; }
        .info-box { background: #e8f4f8; padding: 20px; border-radius: 8px; margin-bottom: 30px; border-left: 4px solid #3498db; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #555; font-weight: 500; }
        input[type="text"], input[type="email"] { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 15px; }
        input:focus { outline: none; border-color: #3498db; }
        .required { color: red; }
        .button-group { text-align: center; margin-top: 30px; }
        button { background: #27ae60; color: white; padding: 15px 50px; border: none; border-radius: 5px; font-size: 18px; cursor: pointer; }
        button:hover { background: #229954; }
        .back-link { display: block; text-align: center; margin-top: 20px; color: #3498db; text-decoration: none; }
        .feature-list { list-style: none; padding: 0; }
        .feature-list li { padding: 8px 0; color: #555; }
        .feature-list li:before { content: "✓ "; color: #27ae60; font-weight: bold; margin-right: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Trimite Contract</h1>
        
        <div class="info-box">
            <h3 style="margin-bottom: 10px; color: #2c3e50;">📄 <?php echo htmlspecialchars($template['template_name']); ?></h3>
            <p style="color: #666; margin-bottom: 15px;">Clientul va primi un link unde poate:</p>
            <ul class="feature-list">
                <li>Completa toate datele necesare</li>
                <li>Citi contractul complet</li>
                <li>Semna electronic</li>
            </ul>
            <p style="color: #666; margin-top: 15px;"><strong>Totul pe o singură pagină!</strong></p>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label>Email Destinatar <span class="required">*</span></label>
                <input type="email" name="recipient_email" required placeholder="exemplu@email.com">
            </div>
            
            <div class="form-group">
                <label>Nume Destinatar (opțional)</label>
                <input type="text" name="recipient_name" placeholder="Ex: Ion Popescu">
                <small style="color: #666;">Pentru personalizarea email-ului</small>
            </div>
            
            <div class="button-group">
                <button type="submit">📤 TRIMITE LINK</button>
            </div>
        </form>
        
        <a href="templates.php" class="back-link">← Înapoi la template-uri</a>
    </div>
</body>
</html>