<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';

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
    die('Contractul trebuie sa fie semnat pentru a descarca PDF');
}

// ✅ FIX: Check if PDF already exists (saved by fill_and_sign.php)
if (empty($contract['pdf_path'])) {
    die('PDF-ul nu a fost gasit. Va rugam sa semnati contractul mai intai.');
}

// ✅ SOLUTION: Redirect to existing PDF file
$pdf_url = 'https://contractdigital.ro/ro' . $contract['pdf_path'];
header('Location: ' . $pdf_url);
exit;
?>
