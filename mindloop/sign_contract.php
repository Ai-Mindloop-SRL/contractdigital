<?php
// ============================================
// SIGN CONTRACT - CLIENT SIGNING PAGE
// ============================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/email.php';
require_once __DIR__ . '/includes/ContractPDF.php';

// Get signing token from URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
    die('❌ Token invalid sau lipsă');
}

// Get database connection
$conn = getDBConnection();

// Get contract by token
$stmt = $conn->prepare("SELECT * FROM contracts WHERE signing_token = ? OR unique_token = ?");
$stmt->bind_param("ss", $token, $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die('❌ Contract invalid sau expirat');
}

$contract = $result->fetch_assoc();
$stmt->close();

// Check if already signed
if ($contract['status'] == 'signed') {
    echo "<div style='text-align: center; margin-top: 50px;'>";
    echo "<h2 style='color: orange;'>⚠️ Contract deja semnat</h2>";
    echo "<p>Acest contract a fost semnat la data: <strong>" . date('d.m.Y H:i', strtotime($contract['signed_at'])) . "</strong></p>";
    if (!empty($contract['pdf_path'])) {
        echo "<p><a href='" . $contract['pdf_path'] . "' target='_blank'>📄 Descarcă PDF-ul semnat</a></p>";
    }
    echo "</div>";
    exit;
}

// HANDLE SIGNATURE SUBMISSION
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['signature_data'])) {
    
    $signature_data = $_POST['signature_data'];
    
    if (empty($signature_data) || $signature_data == 'data:,') {
        die('❌ Vă rugăm să semnați contractul înainte de a trimite');
    }
    
    // Update contract status to signed
    $stmt = $conn->prepare("UPDATE contracts SET status = 'signed', signed_at = NOW(), signature_data = ? WHERE id = ?");
    $stmt->bind_param("si", $signature_data, $contract['id']);
    $stmt->execute();
    $stmt->close();
    
    // Generate PDF with signature
    try {
$pdf = new ContractPDF($contract['id']);
$pdf_obj = $pdf->generate($contract);
$pdf_content = $pdf_obj->Output('', 'S');


        
        // Save PDF to server
        $pdf_filename = 'contract_' . $contract['id'] . '_signed.pdf';
        $pdf_path = __DIR__ . '/uploads/contracts/' . $pdf_filename;
        
        // Create directory if not exists
        if (!is_dir(__DIR__ . '/uploads/contracts/')) {
            mkdir(__DIR__ . '/uploads/contracts/', 0755, true);
        }
        
        file_put_contents($pdf_path, $pdf_content);
        
        // Update PDF path in database
        $pdf_url = '/ro/uploads/contracts/' . $pdf_filename;
        $stmt = $conn->prepare("UPDATE contracts SET pdf_path = ? WHERE id = ?");
        $stmt->bind_param("si", $pdf_url, $contract['id']);
        $stmt->execute();
        $stmt->close();
        
        // NIVEL 1 (SES+): Insert signature data with consent and device info
        $consent_read = isset($_POST['consent_read']) && $_POST['consent_read'] == 'on' ? 1 : 0;
        $consent_sign = isset($_POST['consent_sign']) && $_POST['consent_sign'] == 'on' ? 1 : 0;
        $consent_gdpr = isset($_POST['consent_gdpr']) && $_POST['consent_gdpr'] == 'on' ? 1 : 0;
        $consent_given = ($consent_read && $consent_sign && $consent_gdpr) ? 1 : 0;
        
        $contract_hash = isset($_POST['contract_hash']) ? $_POST['contract_hash'] : null;
        $user_agent = isset($_POST['user_agent']) ? $_POST['user_agent'] : $_SERVER['HTTP_USER_AGENT'];
        $screen_resolution = isset($_POST['screen_resolution']) ? $_POST['screen_resolution'] : null;
        $timezone = isset($_POST['timezone']) ? $_POST['timezone'] : null;
        $device_type = isset($_POST['device_type']) ? $_POST['device_type'] : null;
        
        // Get client IP
        $consent_ip = $_SERVER['REMOTE_ADDR'];
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $consent_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $consent_ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        
        // Parse user agent into JSON (browser, OS, device)
        $ua_parsed = json_encode([
            'raw' => $user_agent,
            'browser' => 'Unknown',
            'os' => 'Unknown',
            'device' => $device_type ?: 'Unknown'
        ]);
        
        // Calculate PDF hash (SHA-256)
        $pdf_hash_after = hash('sha256', $pdf_content);
        
        // Insert into contract_signatures table
        $signer_name = $contract['recipient_name']; // Get signer name from contract
        
        $stmt_sig = $conn->prepare("
            INSERT INTO contract_signatures (
                contract_id, signer_name, signed_at, ip_address, signature_data,
                consent_given, consent_timestamp, consent_ip,
                consent_read, consent_sign, consent_gdpr,
                contract_hash_before, pdf_hash_after,
                user_agent, user_agent_parsed,
                screen_resolution, timezone, device_type
            ) VALUES (
                ?, ?, NOW(), ?, ?,
                ?, NOW(), ?,
                ?, ?, ?,
                ?, ?,
                ?, ?,
                ?, ?, ?
            )
        ");
        
        $stmt_sig->bind_param(
            "isissisiiissssss",
            $contract['id'],              // contract_id
            $signer_name,                 // signer_name
            $consent_ip,                  // ip_address
            $signature_data,              // signature_data
            $consent_given,               // consent_given
            $consent_ip,                  // consent_ip
            $consent_read,                // consent_read
            $consent_sign,                // consent_sign
            $consent_gdpr,                // consent_gdpr
            $contract_hash,               // contract_hash_before
            $pdf_hash_after,              // pdf_hash_after
            $user_agent,                  // user_agent
            $ua_parsed,                   // user_agent_parsed (JSON)
            $screen_resolution,           // screen_resolution
            $timezone,                    // timezone
            $device_type                  // device_type
        );
        
        $stmt_sig->execute();
        $stmt_sig->close();
        
        // Send email with PDF to client AND office@splm.ro (CC)
        $email_subject = "Contract semnat - Confirmare";
        
        $email_body = "<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #27ae60; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>✅ Contract Semnat cu Succes</h2>
        </div>
        <div class='content'>
            <p>Bună ziua,</p>
            <p>Contractul dumneavoastră a fost semnat cu succes la data: <strong>" . date('d.m.Y H:i') . "</strong></p>
            <p>Veți găsi contractul semnat atașat acestui email în format PDF.</p>
            <p>Vă mulțumim!</p>
        </div>
        <div class='footer'>
            <p>Acest email a fost trimis automat de sistemul Contract Digital</p>
        </div>
    </div>
</body>
</html>";
        
       // Prepare CC array
        $cc_array = array('office@splm.ro');
        
        // Attach PDF
        $attachments = array(
            array(
                'content' => base64_encode($pdf_content),
                'filename' => $pdf_filename,
                'type' => 'application/pdf'
            )
        );
        
        // Al 6-lea parametru = attachments (BCC = null)
        $email_sent = sendEmail($contract['recipient_email'], $email_subject, $email_body, $cc_array, null, $attachments);
        
        $conn->close();
        
        // Show success page
        echo "<!DOCTYPE html>
<html lang='ro'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Contract Semnat cu Succes</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 600px; margin: 50px auto; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 40px; text-align: center; }
        .success-icon { font-size: 80px; color: #27ae60; margin-bottom: 20px; }
        h1 { color: #27ae60; margin-bottom: 20px; }
        p { color: #555; line-height: 1.8; margin-bottom: 15px; }
        .download-btn { display: inline-block; margin-top: 20px; padding: 15px 30px; background: #27ae60; color: white; text-decoration: none; border-radius: 5px; }
        .download-btn:hover { background: #229954; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='success-icon'>✅</div>
        <h1>Contract Semnat cu Succes!</h1>
        <p>Contractul dumneavoastră a fost semnat și salvat.</p>
        <p>Veți primi un email cu contractul semnat în format PDF.</p>
        <p><strong>Email trimis către:</strong> " . htmlspecialchars($contract['recipient_email']) . "</p>
        <p><strong>CC:</strong> office@splm.ro</p>
        <a href='" . $pdf_url . "' class='download-btn' target='_blank'>📄 Descarcă PDF Semnat</a>
    </div>
</body>
</html>";
        
        exit;
        
    } catch (Exception $e) {
        die('❌ Eroare la generarea PDF: ' . $e->getMessage());
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semnare Contract</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #2c3e50; color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { padding: 30px; }
        .contract-content { background: #f9f9f9; padding: 30px; border-radius: 8px; margin-bottom: 30px; max-height: 500px; overflow-y: auto; border: 2px solid #ddd; }
        .signature-section { background: #fff3cd; padding: 30px; border-radius: 8px; border: 2px solid #ffc107; }
        .signature-pad { border: 2px solid #333; border-radius: 5px; background: white; cursor: crosshair; display: block; margin: 20px auto; }
        .button-group { text-align: center; margin-top: 20px; }
        .btn { padding: 12px 30px; margin: 0 10px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
        .btn-clear { background: #e74c3c; color: white; }
        .btn-sign { background: #27ae60; color: white; font-size: 18px; padding: 15px 50px; }
        .btn-clear:hover { background: #c0392b; }
        .btn-sign:hover { background: #229954; }
        .info { color: #555; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📄 Semnare Contract</h1>
        </div>
        
        <div class="content">
            <div class="info">
                <p><strong>Destinatar:</strong> <?php echo htmlspecialchars($contract['recipient_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($contract['recipient_email']); ?></p>
            </div>
            
            <h2>Contract</h2>
            <div class="contract-content">
                <?php echo $contract['contract_content']; ?>
            </div>
            
            <div class="signature-section">
                <h3 style="text-align: center; margin-bottom: 20px;">✍️ Vă rugăm să semnați mai jos</h3>
                <canvas id="signaturePad" class="signature-pad" width="600" height="200"></canvas>
                
                <form method="POST" id="signForm">
                    <input type="hidden" name="signature_data" id="signatureData">
                    <div class="button-group">
                        <button type="button" class="btn btn-clear" onclick="clearSignature()">🗑️ Șterge Semnătura</button>
                        <button type="submit" class="btn btn-sign">✅ SEMNEAZĂ ȘI TRIMITE</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        const canvas = document.getElementById('signaturePad');
        const ctx = canvas.getContext('2d');
        let isDrawing = false;
        let hasSignature = false;
        
        // Mouse events
        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('mouseout', stopDrawing);
        
        // Touch events for mobile
        canvas.addEventListener('touchstart', function(e) {
            e.preventDefault();
            const touch = e.touches[0];
            const mouseEvent = new MouseEvent('mousedown', {
                clientX: touch.clientX,
                clientY: touch.clientY
            });
            canvas.dispatchEvent(mouseEvent);
        });
        
        canvas.addEventListener('touchmove', function(e) {
            e.preventDefault();
            const touch = e.touches[0];
            const mouseEvent = new MouseEvent('mousemove', {
                clientX: touch.clientX,
                clientY: touch.clientY
            });
            canvas.dispatchEvent(mouseEvent);
        });
        
        canvas.addEventListener('touchend', function(e) {
            e.preventDefault();
            const mouseEvent = new MouseEvent('mouseup', {});
            canvas.dispatchEvent(mouseEvent);
        });
        
        function startDrawing(e) {
            isDrawing = true;
            hasSignature = true;
            const rect = canvas.getBoundingClientRect();
            ctx.beginPath();
            ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
        }
        
        function draw(e) {
            if (!isDrawing) return;
            const rect = canvas.getBoundingClientRect();
            ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.stroke();
        }
        
        function stopDrawing() {
            isDrawing = false;
        }
        
        function clearSignature() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            hasSignature = false;
        }
        
        document.getElementById('signForm').addEventListener('submit', function(e) {
            if (!hasSignature) {
                e.preventDefault();
                alert('❌ Vă rugăm să semnați contractul înainte de a trimite!');
                return false;
            }
            
            // Save signature as base64 image
            const signatureData = canvas.toDataURL('image/png');
            document.getElementById('signatureData').value = signatureData;
        });
    </script>
</body>
</html>