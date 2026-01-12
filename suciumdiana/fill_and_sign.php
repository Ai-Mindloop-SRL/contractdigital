<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/email.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/ContractPDF.php';

$conn = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
    
    // Get template and site info for contract number generation
    $stmt = $conn->prepare("SELECT ct.template_content, ct.template_name, s.site_name FROM contract_templates ct LEFT JOIN sites s ON ct.site_id = s.id WHERE ct.id = ?");
    $stmt->bind_param('i', $contract['template_id']);
    $stmt->execute();
    $template = $stmt->get_result()->fetch_assoc();
    $contract_html = $template['template_content'];
    $site_name_for_prefix = $template['site_name'] ?? 'CONTRACT';
    
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
        
        // Generate contract number with site-specific prefix
        // Convert site name to uppercase prefix (e.g., "ROSEUP ADVISORS" -> "ROSEUP")
        $prefix_parts = explode(' ', strtoupper($site_name_for_prefix));
        $template_prefix = $prefix_parts[0]; // Take first word as prefix
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
    $contract_html = str_replace('[NUMAR_CONTRACT]', $numar_contract, $contract_html);
    $contract_html = str_replace('[DATA_CONTRACT]', $data_contract, $contract_html);
    
    $field_map = [
        'client_company_name' => 'NUME_FIRMA',
        'client_cui' => 'CUI',
        'client_nr_reg_com' => 'REG_COM',
        'client_address' => 'ADRESA',
        'client_email' => 'EMAIL',
        'client_phone' => 'TELEFON',
        'client_bank_account' => 'CONT_BANCAR',
        'client_legal_rep_name' => 'REPREZENTANT',
        'client_legal_rep_position' => 'FUNCTIE'
    ];
    
    foreach ($field_map as $db_field => $placeholder) {
        $value = $_POST[$db_field] ?? '';
        $contract_html = str_replace('[' . $placeholder . ']', $value, $contract_html);
    }
    
    // Insert signature in client's signature area (smaller size, inline with signature block)
    $signature_html = '<img src="' . $_POST['signature_data'] . '" style="max-width:150px;height:auto;margin:10px 0;">';
    $signature_with_timestamp = '<br>' . $signature_html . '<br><small style="color:#4CAF50;">✅ Semnat electronic: ' . date('d.m.Y H:i:s') . '</small>';
    
    // Get client name to find where to insert signature
    $client_name = htmlspecialchars($_POST['client_legal_rep_name'] ?? '', ENT_QUOTES, 'UTF-8');
    
    // Try to insert after client's name in signature-block or signature-section
    if (!empty($client_name)) {
        $contract_html = preg_replace(
            '/(signature-(?:block|section)[^>]*>.*?<p>' . preg_quote($client_name, '/') . '<\/p>)/s',
            '$1' . $signature_with_timestamp,
            $contract_html,
            1
        );
    }
    
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
    
    $pdf_generator = new ContractPDF_Mindloop(); // Generic class name - works for all sites
    $pdf_generator->generatePDF($contract_html, $pdf_dir . $pdf_filename, $_POST["signature_data"]);
    
    // ✅ Save PDF path AND filled contract_html (contract_content in DB) to database
    $stmt = $conn->prepare("UPDATE contracts SET pdf_path = ?, contract_content = ? WHERE id = ?");
    $pdf_path_db = "/uploads/contracts/" . $pdf_filename;
    $stmt->bind_param("ssi", $pdf_path_db, $contract_html, $_POST["contract_id"]);
    $stmt->execute();
    
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
input[type="text"],input[type="email"],input[type="tel"],textarea{width:100%;padding:12px;border:2px solid #e0e0e0;border-radius:8px;font-size:14px}
input:focus,textarea:focus{outline:none;border-color:<?=$primary_color?>}
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
$field_map = [
    'client_company_name' => 'field_nume_firma',
    'client_cui' => 'field_cui',
    'client_nr_reg_com' => 'field_reg_com',
    'client_address' => 'field_adresa',
    'client_email' => 'field_email',
    'client_phone' => 'field_telefon',
    'client_bank_account' => 'field_cont_bancar',
    'client_legal_rep_name' => 'field_reprezentant',
    'client_legal_rep_position' => 'field_functie'
];
foreach($field_definitions as $field): 
$data_field = $field_map[$field['field_name']] ?? 'field_' . $field['field_name'];
?>
<div class="form-group">
<label><?=$field['field_label']?><?=!$field['is_required']?' (opțional)':' *'?></label>
<?php if($field['field_type']=='textarea'): ?>
<textarea name="<?=$field['field_name']?>" <?=!$field['is_required']?'':'required'?> class="contract-field" data-field="<?=$data_field?>"><?=$field['field_name']??''?></textarea>
<?php else: ?>
<input type="<?=$field['field_type']?>" name="<?=$field['field_name']?>" placeholder="<?=$field['placeholder']??''?>" value="" <?=!$field['is_required']?'':'required'?> class="contract-field" data-field="<?=$data_field?>">
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
document.getElementById('clear-signature').addEventListener('click',()=>{ctx.clearRect(0,0,canvas.width,canvas.height);hasSignature=false;document.getElementById('submit-contract').disabled=true});

document.getElementById('submit-contract').addEventListener('click',()=>{
if(!hasSignature){alert('❌ Semnați!');return}
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

// CUI Lookup functionality
document.getElementById('cuiLookupBtn').addEventListener('click', async function() {
    const cuiInput = document.querySelector('[name="cui"]');
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
        const response = await fetch('/cui_lookup.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({cui: cui})
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.error || 'Eroare la căutare');
        }
        
        // Fill fields with correct field names
        const fieldMap = {
            'nume_firma': data.denumire,
            'cui': data.cui,
            'reg_com': data.numar_reg_com,
            'adresa': data.adresa,
            'telefon': data.telefon
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

loadFormData();
updatePreview();
</script>
</body>
</html>
