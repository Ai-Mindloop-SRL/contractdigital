<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/email.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/ContractPDF.php';

$conn = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    error_log("🔍 NIVEL 1 DEBUG: POST received, signature_data=" . (empty($_POST['signature_data']) ? 'EMPTY' : 'OK'));
    if (empty($_POST['signature_data'])) die('❌ Semnați contractul');
    
    // Get contract and template
    $token = $_POST['token'] ?? $_GET['token'] ?? '';
    // ✅ FIX: Get cc_email and admin_email from sites table
    $stmt = $conn->prepare("SELECT c.*, s.primary_color, s.cc_email, s.admin_email FROM contracts c LEFT JOIN sites s ON c.site_id = s.id WHERE c.signing_token = ? OR c.unique_token = ?");
    $stmt->bind_param('ss', $token, $token);
    $stmt->execute();
    $contract = $stmt->get_result()->fetch_assoc();
    if (!$contract) die('❌ Contract invalid');
    
    // Check if contract is already signed
    if ($contract['status'] === 'signed') {
        die('❌ Acest contract a fost deja semnat la data de ' . date('d.m.Y H:i', strtotime($contract['signed_at'])) . '. Nu poate fi semnat din nou.');
    }
    
    // Get template and generate sequential contract number
    $stmt = $conn->prepare("SELECT template_content, template_name FROM contract_templates WHERE id = ?");
    $stmt->bind_param('i', $contract['template_id']);
    $stmt->execute();
    $template = $stmt->get_result()->fetch_assoc();
    $contract_html = $template['template_content'];
    
    // GENERATE SEQUENTIAL CONTRACT NUMBER (Varianta B + Optiunea 2)
    // Lock template row and increment counter
    $conn->begin_transaction();
    try {
        // Lock and get current counter
        $stmt = $conn->prepare("SELECT contract_counter FROM contract_templates WHERE id = ? FOR UPDATE");
        $stmt->bind_param('i', $contract['template_id']);
        $stmt->execute();
        $counter_result = $stmt->get_result()->fetch_assoc();
        $current_counter = $counter_result['contract_counter'] ?? 0;
        
        // Increment counter
        $new_counter = $current_counter + 1;
        $stmt = $conn->prepare("UPDATE contract_templates SET contract_counter = ? WHERE id = ?");
        $stmt->bind_param('ii', $new_counter, $contract['template_id']);
        $stmt->execute();
        
        // Generate contract number: ROSEUP-2025-0001
        $template_prefix = 'ROSEUP'; // You can customize per template later
        $numar_contract = $template_prefix . '-' . date('Y') . '-' . str_pad($new_counter, 4, '0', STR_PAD_LEFT);
        
        // Save contract number to contracts table
        $stmt = $conn->prepare("UPDATE contracts SET contract_number = ? WHERE id = ?");
        $stmt->bind_param('si', $numar_contract, $_POST['contract_id']);
        $stmt->execute();
        
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        die('❌ Eroare la generarea numărului de contract: ' . $e->getMessage());
    }
    $data_contract = date('d.m.Y');
    
    // Replace placeholders with actual values
    $contract_html = str_replace('[NUMAR_CONTRACT]', $numar_contract, $contract_html);
    $contract_html = str_replace('[numar_contract]', $numar_contract, $contract_html); // Lowercase
    $contract_html = str_replace('[DATA_CONTRACT]', $data_contract, $contract_html);
    $contract_html = str_replace('[data_contract]', $data_contract, $contract_html); // Lowercase
    
    // Also replace data-field spans for preview consistency
    $contract_html = preg_replace('/<span data-field="field_numar_contract">[^<]*<\/span>/', '<span data-field="field_numar_contract">' . $numar_contract . '</span>', $contract_html);
    $contract_html = preg_replace('/<span data-field="field_data_contract">[^<]*<\/span>/', '<span data-field="field_data_contract">' . $data_contract . '</span>', $contract_html);
    
    // Map form fields to template placeholders (lowercase for RoseUp template)
    $field_map = [
        'client_company_name' => 'nume_firma',
        'client_cui' => 'cui',
        'client_nr_reg_com' => 'reg_com',
        'client_address' => 'adresa',
        'client_legal_rep_name' => 'reprezentant',
        'client_legal_rep_position' => 'functie',
        'client_judet' => 'judet',
        'taxa_membru' => 'taxa_membru',
        'taxa_inscriere' => 'taxa_inscriere'
    ];
    
    // Replace all placeholders with POST values
    foreach ($field_map as $db_field => $placeholder) {
        $value = $_POST[$db_field] ?? '';
        // Replace both lowercase and uppercase versions for compatibility
        $contract_html = str_replace('[' . $placeholder . ']', htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $contract_html);
        $contract_html = str_replace('[' . strtoupper($placeholder) . ']', htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $contract_html);
    }
    
    // Signatures will be inserted by ContractPDF.php using <span id="prestator-signature"> and <span id="client-signature"> placeholders
    // No need to insert them here in HTML
    
    $signature_base64 = str_replace('data:image/png;base64,', '', $_POST['signature_data']);
    $upload_dir = __DIR__ . '/../uploads/signatures/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    $signature_filename = 'signature_' . $_POST['contract_id'] . '_' . time() . '.png';
    file_put_contents($upload_dir . $signature_filename, base64_decode($signature_base64));
    
    $stmt = $conn->prepare("UPDATE contracts SET signature_path = ?, status = 'signed', signed_at = NOW() WHERE id = ?");
    $sig_path = '/uploads/signatures/' . $signature_filename;
    $stmt->bind_param('si', $sig_path, $_POST['contract_id']);
    $stmt->execute();
    
    $pdf_dir = __DIR__ . '/../uploads/contracts/';
    if (!is_dir($pdf_dir)) mkdir($pdf_dir, 0755, true);
    $pdf_filename = 'contract_' . $_POST['contract_id'] . '_' . time() . '.pdf';
    // NIVEL 1 (SES+): Capture metadata BEFORE generating PDF
    error_log("NIVEL 1: Starting signature insert for contract " . $_POST['contract_id']);
    
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
    
    $signer_name = $contract['recipient_name'];
    
    // Generate PDF with NIVEL 1 metadata


    
    $pdf_generator = new ContractPDF_RoseUp();
    // Prepare NIVEL 1 data for PDF (without pdf_hash_after - will calculate after PDF generation)
    $nivel1_data_for_pdf = array(
        'signer_name' => $signer_name,
        'signed_at' => date('Y-m-d H:i:s'),
        'ip_address' => $consent_ip,
        'user_agent' => $user_agent,
        'device_type' => $device_type,
        'screen_resolution' => $screen_resolution,
        'timezone' => $timezone,
        'contract_hash_before' => $contract_hash,
        'consent_read' => $consent_read,
        'consent_sign' => $consent_sign,
        'consent_gdpr' => $consent_gdpr
    );
    
    $pdf_generator->generatePDF($contract_html, $pdf_dir . $pdf_filename, $_POST["signature_data"], $nivel1_data_for_pdf);
    
    // Calculate PDF hash AFTER generation
    $pdf_hash_after = hash_file('sha256', $pdf_dir . $pdf_filename);
    
    // ✅ Save PDF path AND filled contract_html (contract_content in DB) to database
    $stmt = $conn->prepare("UPDATE contracts SET pdf_path = ?, contract_content = ? WHERE id = ?");
    $pdf_path_db = "/uploads/contracts/" . $pdf_filename;
    $stmt->bind_param("ssi", $pdf_path_db, $contract_html, $_POST["contract_id"]);
    $stmt->execute();
    
    
    // Parse user agent
    $ua_parsed = json_encode([
        'raw' => $user_agent,
        'device' => $device_type ?: 'Unknown'
    ]);
    
    // Insert into contract_signatures
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
        $_POST['contract_id'],
        $signer_name,
        $consent_ip,
        $_POST['signature_data'],
        $consent_given,
        $consent_ip,
        $consent_read,
        $consent_sign,
        $consent_gdpr,
        $contract_hash,
        $pdf_hash_after,
        $user_agent,
        $ua_parsed,
        $screen_resolution,
        $timezone,
        $device_type
    );
    
    if (!$stmt_sig->execute()) {
        error_log("NIVEL 1 INSERT FAILED: " . $stmt_sig->error);
    } else {
        error_log("NIVEL 1 SUCCESS: Inserted for contract " . $_POST['contract_id']);
    }
    $stmt_sig->close();

    $stmt = $conn->prepare("SELECT recipient_email FROM contracts WHERE id = ?");
    $stmt->bind_param('i', $_POST['contract_id']);
    $stmt->execute();
    $contract = $stmt->get_result()->fetch_assoc();
    
    if (!empty($contract["recipient_email"])) {
        // Email către client
        sendEmail(
            $contract["recipient_email"], 
            "Contract semnat - " . $numar_contract, 
            "Contractul dumneavoastră a fost semnat. Vă mulțumim! Găsiți PDF-ul atașat.", 
            null,
            null,
            [['path' => $pdf_dir . $pdf_filename, 'filename' => $pdf_filename]]
        );
        
        // ✅ FIX: Email către cc_email/admin_email (from database, not hardcoded)
        $cc_recipient = !empty($contract['cc_email']) ? $contract['cc_email'] : (!empty($contract['admin_email']) ? $contract['admin_email'] : null);
        
        if (!empty($cc_recipient)) {
            sendEmail(
                $cc_recipient, 
            "Contract semnat - " . $numar_contract . " - " . $contract["recipient_email"], 
            "Contract semnat de client: " . $contract["recipient_email"] . ". PDF atașat.", 
            null,
            null,
            [['path' => $pdf_dir . $pdf_filename, 'filename' => $pdf_filename]]
            );
        }
    }
    
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>✅ Contract Semnat</title><style>body{font-family:Arial;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;min-height:100vh}.box{background:#fff;padding:40px;border-radius:15px;box-shadow:0 10px 40px rgba(0,0,0,0.3);text-align:center;max-width:500px}.icon{font-size:80px}h1{color:#2E7D32}.btn{display:inline-block;background:#4CAF50;color:#fff;padding:15px 30px;text-decoration:none;border-radius:8px;font-weight:bold}</style></head><body><div class="box"><div class="icon">✅</div><h1>Contract Semnat!</h1><p>Email trimis.</p><a href="https://contractdigital.ro/ro/uploads/contracts/' . $pdf_filename . '" class="btn" download>📥 Descarcă PDF</a></div></body></html>';
    exit;
}

$token = $_GET['token'] ?? '';
$stmt = $conn->prepare("SELECT c.*, s.primary_color FROM contracts c LEFT JOIN sites s ON c.site_id = s.id WHERE c.signing_token = ? OR c.unique_token = ?");
$stmt->bind_param('ss', $token, $token);
$stmt->execute();
$contract = $stmt->get_result()->fetch_assoc();
if (!$contract) die("❌ Contract invalid");

// Check if contract is already signed - show nice message
if ($contract['status'] === 'signed') {
    $signed_date = date('d.m.Y H:i', strtotime($contract['signed_at']));
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>✅ Contract Deja Semnat</title><style>body{font-family:Arial;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;min-height:100vh}.box{background:#fff;padding:40px;border-radius:15px;box-shadow:0 10px 40px rgba(0,0,0,0.3);text-align:center;max-width:500px}.icon{font-size:80px}h1{color:#FFA500}p{color:#555;font-size:16px}</style></head><body><div class="box"><div class="icon">⚠️</div><h1>Contract Deja Semnat</h1><p>Acest contract a fost semnat la data de <strong>' . $signed_date . '</strong>.</p><p>Nu poate fi semnat din nou.</p></div></body></html>';
    exit;
}

$primary_color = $contract['primary_color'] ?? '#667eea';

// Decode form_data (admin-set values like taxa_membru, taxa_inscriere)
$admin_data = [];
if (!empty($contract['form_data'])) {
    $admin_data = json_decode($contract['form_data'], true) ?? [];
}

// Get template from database
$template_id = $contract['template_id'];
$stmt = $conn->prepare("SELECT template_content FROM contract_templates WHERE id = ?");
$stmt->bind_param('i', $template_id);
$stmt->execute();
$template = $stmt->get_result()->fetch_assoc();
$template_html = $template['template_content'];

$stmt = $conn->prepare("SELECT fd.field_name, fd.field_label, fd.field_type, tfm.is_required, fd.placeholder, tfm.display_order FROM template_field_mapping tfm INNER JOIN field_definitions fd ON tfm.field_definition_id = fd.id WHERE tfm.template_id = ? ORDER BY tfm.display_order");
$stmt->bind_param('i', $contract['template_id']);
$stmt->execute();
$field_definitions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Completare Contract</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:Arial,sans-serif;background:linear-gradient(135deg,<?=$primary_color?>,#764ba2);padding:20px}
.container{max-width:900px;margin:0 auto}
.card{background:#fff;border-radius:15px;padding:30px;box-shadow:0 10px 40px rgba(0,0,0,0.15);margin-bottom:20px}
h1{color:<?=$primary_color?>;margin-bottom:10px;font-size:28px}
.subtitle{color:#666;margin-bottom:25px;font-size:14px}
.form-group{margin-bottom:15px}
label{display:block;margin-bottom:5px;font-weight:600;color:#333;font-size:14px}
input[type="text"],input[type="email"],input[type="tel"],input[type="number"],textarea{width:100%;padding:12px;border:2px solid #e0e0e0;border-radius:8px;font-size:14px}
input:focus,textarea:focus{outline:none;border-color:<?=$primary_color?>}
input[readonly],textarea[readonly]{background-color:#f5f5f5;color:#666;cursor:not-allowed;border-color:#ccc}
textarea{min-height:80px;resize:vertical}
#signature-pad{border:3px solid <?=$primary_color?>;border-radius:10px;background:#fff;cursor:crosshair;width:100%;height:200px}
.btn{padding:12px 24px;border:none;border-radius:8px;font-size:16px;font-weight:bold;cursor:pointer}
.btn-clear{background:#f44336;color:#fff;margin-right:10px}
.btn-reset{background:#ff9800;color:#fff;margin-right:10px}
.btn-primary{background:<?=$primary_color?>;color:#fff}
.btn-primary:disabled{background:#ccc;cursor:not-allowed}
.signature-controls{margin-top:10px}
.auto-save-notice{background:#e8f5e9;color:#2e7d32;padding:10px;border-radius:8px;margin-bottom:15px;font-size:13px;text-align:center}
</style>
</head>
<body>
<div class="container">

<div class="card">
<h1>📝 Completare Date</h1>
<p class="subtitle">Pasul 1: Completați datele</p>
<div class="auto-save-notice">💾 Datele se salvează automat</div>
<div style="margin: 15px 0;">
    <button type="button" id="cuiLookupBtn" class="btn" style="background: linear-gradient(135deg, #FF6B6B, #FF8E53); color: white; font-size: 14px; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; box-shadow: 0 4px 6px rgba(255, 107, 107, 0.3); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 12px rgba(255, 107, 107, 0.4)'" onmouseout="this.style.transform=''; this.style.boxShadow='0 4px 6px rgba(255, 107, 107, 0.3)'">
        🔍 Completează din CUI
    </button>
    <span id="cuiStatus" style="margin-left: 15px; font-size: 14px;"></span>
</div>
<form id="mainForm" method="POST" action="">
<input type="hidden" name="token" value="<?=$token?>">
<input type="hidden" name="contract_id" value="<?=$contract['id']?>">
<input type="hidden" name="signature_data" id="signature_data_input">
<?php 
// No field mapping needed - use field_name directly to match template data-field attributes
foreach($field_definitions as $field): 
$data_field = $field['field_name']; // Direct mapping: field_name = data-field in template
// Check if admin preset this field (e.g., taxa_membru, taxa_inscriere)
$preset_value = $admin_data[$field['field_name']] ?? '';
// Check if this field is admin-only (can't be modified by client)
$is_admin_only = in_array($field['field_name'], ['taxa_membru', 'taxa_inscriere']);
$readonly_attr = ($is_admin_only && !empty($preset_value)) ? 'readonly' : '';
?>
<div class="form-group">
<label><?=$field['field_label']?><?=!$field['is_required']?' (opțional)':' *'?></label>
<?php if($is_admin_only && !empty($preset_value)): ?>
<small style="color: #666; display: block; margin-bottom: 5px;">⚠️ Valoare setată de administrator (nu poate fi modificată)</small>
<?php endif; ?>
<?php if($field['field_type']=='textarea'): ?>
<textarea name="<?=$field['field_name']?>" <?=!$field['is_required']?'':'required'?> class="contract-field" data-field="<?=$data_field?>" <?=$readonly_attr?>><?=htmlspecialchars($preset_value)?></textarea>
<?php else: ?>
<input type="<?=$field['field_type']?>" name="<?=$field['field_name']?>" placeholder="<?=$field['placeholder']??''?>" value="<?=htmlspecialchars($preset_value)?>" <?=!$field['is_required']?'':'required'?> class="contract-field" data-field="<?=$data_field?>" <?=$readonly_attr?>>
<?php endif; ?>
</div>
<?php endforeach; ?>
<button type="button" class="btn btn-reset" onclick="if(confirm('Ștergeți toate datele?')){localStorage.removeItem('contract_form_data_<?=$contract['id']?>');location.reload()}">🗑️ Șterge Date</button>
</form>
</div>

<div class="card">
<h1>👁️ Preview</h1>
<p class="subtitle">Pasul 2: Verificați</p>
<div id="contract-preview"><?=$template_html?></div>
</div>

<div class="card">
<h1>✍️ Semnătură</h1>
<p class="subtitle">Pasul 3: Semnați</p>
<canvas id="signature-pad" width="600" height="200"></canvas>

<!-- NIVEL 1 (SES+): Explicit Consent Checkboxes -->
<div class="consent-section" style="margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid <?=$primary_color?>;">
<h3 style="margin: 0 0 15px 0; font-size: 16px; color: #333;">📋 Consimțământ Semnare Electronică</h3>
<div style="margin-bottom: 12px;">
<label style="display: flex; align-items: start; cursor: pointer; font-size: 14px;">
<input type="checkbox" id="consent_read" name="consent_read" required style="margin-right: 10px; margin-top: 3px; width: 18px; height: 18px; cursor: pointer;">
<span>Am citit și înțeles în totalitate termenii și condițiile acestui contract.</span>
</label>
</div>
<div style="margin-bottom: 12px;">
<label style="display: flex; align-items: start; cursor: pointer; font-size: 14px;">
<input type="checkbox" id="consent_sign" name="consent_sign" required style="margin-right: 10px; margin-top: 3px; width: 18px; height: 18px; cursor: pointer;">
<span>Sunt de acord să semnez acest contract prin mijloace electronice și accept că semnătura mea electronică are aceeași valoare juridică ca și o semnătură olografă.</span>
</label>
</div>
<div style="margin-bottom: 0;">
<label style="display: flex; align-items: start; cursor: pointer; font-size: 14px;">
<input type="checkbox" id="consent_gdpr" name="consent_gdpr" required style="margin-right: 10px; margin-top: 3px; width: 18px; height: 18px; cursor: pointer;">
<span>Sunt de acord cu procesarea datelor mele personale în conformitate cu <a href="https://www.dataprotection.ro/?page=Regulamentul_general_privind_protectia_datelor" target="_blank" style="color: <?=$primary_color?>; text-decoration: underline;">GDPR (Regulamentul UE 2016/679)</a> în scopul executării acestui contract.</span>
</label>
</div>
</div>

<!-- Hidden fields for NIVEL 1 data -->
<input type="hidden" id="contract_hash" name="contract_hash" value="">
<input type="hidden" id="user_agent" name="user_agent" value="">
<input type="hidden" id="screen_resolution" name="screen_resolution" value="">
<input type="hidden" id="timezone" name="timezone" value="">
<input type="hidden" id="device_type" name="device_type" value="">

<div class="signature-controls">
<button type="button" class="btn btn-clear" id="clear-signature">🗑️ Șterge Semnătura</button>
<button type="button" class="btn btn-primary" id="submit-contract" disabled>✅ ACCEPT ȘI SEMNEZ</button>
</div>
</div>

</div>

<script>
const STORAGE_KEY='contract_form_data_<?=$contract['id']?>';
const canvas=document.getElementById('signature-pad');
const ctx=canvas.getContext('2d');
let isDrawing=false,hasSignature=false;
ctx.strokeStyle='#000';ctx.lineWidth=2;ctx.lineCap='round';

function startDrawing(e){
    isDrawing=true;
    e.preventDefault();
    const rect=canvas.getBoundingClientRect();
    const x=((e.clientX||e.touches[0].clientX)-rect.left)*(canvas.width/rect.width);
    const y=((e.clientY||e.touches[0].clientY)-rect.top)*(canvas.height/rect.height);
    ctx.beginPath();
    ctx.moveTo(x,y);
}
function draw(e){
    if(!isDrawing)return;
    e.preventDefault();
    const rect=canvas.getBoundingClientRect();
    const x=((e.clientX||e.touches[0].clientX)-rect.left)*(canvas.width/rect.width);
    const y=((e.clientY||e.touches[0].clientY)-rect.top)*(canvas.height/rect.height);
    ctx.lineTo(x,y);
    ctx.stroke();
    hasSignature=true;
    document.getElementById('submit-contract').disabled=false;
}
function stopDrawing(e){
    if(isDrawing) e.preventDefault();
    isDrawing=false;
    ctx.beginPath();
}

canvas.addEventListener('mousedown',startDrawing);
canvas.addEventListener('mousemove',draw);
canvas.addEventListener('mouseup',stopDrawing);
canvas.addEventListener('mouseleave',stopDrawing); // Stop drawing when cursor leaves canvas
canvas.addEventListener('mouseout',stopDrawing);   // Additional safety for older browsers
canvas.addEventListener('touchstart',startDrawing,{passive:false});
canvas.addEventListener('touchmove',draw,{passive:false});
canvas.addEventListener('touchend',stopDrawing,{passive:false});

// NIVEL 1: Add consent checkbox listeners
document.getElementById('consent_read').addEventListener('change', checkFormValidity);
document.getElementById('consent_sign').addEventListener('change', checkFormValidity);
document.getElementById('consent_gdpr').addEventListener('change', checkFormValidity);

document.getElementById('clear-signature').addEventListener('click',()=>{ctx.clearRect(0,0,canvas.width,canvas.height);hasSignature=false;document.getElementById('submit-contract').disabled=true});

document.getElementById('submit-contract').addEventListener('click',()=>{
// Validare: verifică dacă toate câmpurile obligatorii sunt completate
const requiredFields = document.querySelectorAll('.contract-field[required]');
const emptyFields = [];
requiredFields.forEach(field => {
    if (!field.value.trim()) {
        emptyFields.push(field.closest('.form-group').querySelector('label').textContent.replace(' *', ''));
    }
});

if (emptyFields.length > 0) {
    alert('❌ Completați toate câmpurile obligatorii:\n\n' + emptyFields.join('\n'));
    // Scroll to first empty field
    const firstEmpty = document.querySelector('.contract-field[required]:invalid, .contract-field[required][value=""]');
    if (firstEmpty) firstEmpty.scrollIntoView({ behavior: 'smooth', block: 'center' });
    return;
}

if(!hasSignature){alert('❌ Semnați contractul!');return}

document.getElementById('signature_data_input').value=canvas.toDataURL('image/png');
localStorage.removeItem(STORAGE_KEY);
document.getElementById('mainForm').submit()});

const formFields=document.querySelectorAll('.contract-field');
const previewDiv=document.getElementById('contract-preview');

// Contract number will be generated at signing time - show placeholder in preview
const numarContractPlaceholder='[VA FI GENERAT LA SEMNARE]';
const dataContract=new Date().toLocaleDateString('ro-RO');

function updatePreview(){
    // Show placeholder for contract number (will be generated when signed)
    const numSpans=previewDiv.querySelectorAll('span[data-field="field_numar_contract"]');
    numSpans.forEach(s=>{
        s.textContent=numarContractPlaceholder;
        s.style.fontStyle='italic';
        s.style.color='#999';
    });
    
    // Show current date for preview
    const dateSpans=previewDiv.querySelectorAll('span[data-field="field_data_contract"]');
    dateSpans.forEach(s=>s.textContent=dataContract);
    
    formFields.forEach(f=>{
        const field=f.getAttribute('data-field');
        if(field){
            const spans=previewDiv.querySelectorAll('span[data-field="'+field+'"]');
            spans.forEach(span=>{span.textContent=f.value||'[...]'});
        }
    });
}

function saveFormData(){
    const data={};
    formFields.forEach(f=>{data[f.name]=f.value});
    localStorage.setItem(STORAGE_KEY,JSON.stringify(data));
}

function loadFormData(){
    const saved=localStorage.getItem(STORAGE_KEY);
    if(saved){
        const data=JSON.parse(saved);
        Object.keys(data).forEach(key=>{
            const field=document.querySelector('[name="'+key+'"]');
            if(field)field.value=data[key];
        });
        updatePreview();
    }
}

formFields.forEach(f=>{
    f.addEventListener('input',()=>{updatePreview();saveFormData()});
    f.addEventListener('change',()=>{updatePreview();saveFormData()});
});

loadFormData();
updatePreview();

// CUI Lookup functionality
document.getElementById('cuiLookupBtn').addEventListener('click', async function() {
    const cuiInput = document.querySelector('[name="client_cui"]');
    if (!cuiInput) {
        alert('Câmp CUI nu găsit!');
        return;
    }
    
    const cui = cuiInput.value.trim();
    if (!cui) {
        alert('Introduceți CUI-ul mai întâi!');
        cuiInput.focus();
        return;
    }
    
    const btn = this;
    const status = document.getElementById('cuiStatus');
    
    btn.disabled = true;
    btn.textContent = '⏳ Se încarcă...';
    status.textContent = '';
    
    try {
        const response = await fetch('cui_lookup.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({cui: cui})
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.error || 'Eroare la căutare');
        }
        
        // Fill fields
        const fieldMap = {
            'client_company_name': data.denumire,
            'client_cui': data.cui,
            'client_nr_reg_com': data.numar_reg_com,
            'client_address': data.adresa,
            'client_phone': data.telefon
        };
        
        for (const [fieldName, value] of Object.entries(fieldMap)) {
            const input = document.querySelector(`[name="${fieldName}"]`);
            if (input && value) {
                input.value = value;
                input.dispatchEvent(new Event('change'));
            }
        }
        
        status.innerHTML = '<span style="color: #4CAF50;">✔️ Date completate cu succes!</span>';
        updatePreview();
        saveFormData();
        
    } catch (error) {
        status.innerHTML = '<span style="color: #f44336;">❌ ' + error.message + '</span>';
    } finally {
        btn.disabled = false;
        btn.textContent = '🔍 Completează din CUI';
    }
});
</script>
</body>
</html>
