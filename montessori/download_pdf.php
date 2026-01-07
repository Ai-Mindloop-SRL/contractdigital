<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/ContractPDF.php';

$db = getDBConnection();

// Get contract ID
$contract_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($contract_id <= 0) {
    die('ID contract invalid');
}

// Get contract details
$stmt = $db->prepare("
    SELECT 
        c.*,
        ct.template_name,
        s.site_name,
        s.primary_color,
        s.admin_email
    FROM contracts c
    JOIN contract_templates ct ON c.template_id = ct.id
    JOIN sites s ON c.site_id = s.id
    WHERE c.id = ? AND s.site_slug = ?
");
$stmt->bind_param("is", $contract_id, $site_slug);
$stmt->execute();
$contract = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$contract) {
    die('Contract negasit');
}

// Check if signed
if ($contract['status'] !== 'signed') {
    die('Contractul trebuie sa fie semnat pentru a genera PDF');
}

// Get signature
// Get signature data from contract (stored as base64 data URI)
if (empty($contract["signature_data"])) {
    die("Semnatura nu a fost gasita");
}

// signature_data contains the base64 image directly (data:image/png;base64,...)
$signature = [
    'signature_image' => $contract['signature_data'],
    'signed_at' => $contract['signed_at'],
    'ip_address' => $contract['ip_address'] ?? ''
];

// Prepare site data
$site = [
    'site_name' => $contract['site_name'],
    'primary_color' => $contract['primary_color']
];

try {
    // Generate PDF
    $pdf = new ContractPDF($contract, $site, $signature);
    $pdf->generate();
    
    // Generate filename
    $filename = 'Contract_' . $contract['id'] . '_' . date('Y-m-d') . '.pdf';
    $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
    
    // Output to browser
    $pdf->output($filename);
    
} catch (Exception $e) {
    error_log("PDF Generation Error: " . $e->getMessage());
    die('Eroare la generarea PDF: ' . $e->getMessage());
}
?>