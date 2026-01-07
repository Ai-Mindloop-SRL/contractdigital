<?php
require_once __DIR__ . '/../config/database.php';

$conn = getDBConnection();

echo "=== CONTRACT #54 DETAILS ===\n\n";

$stmt = $conn->prepare("SELECT id, status, signature_path, signature_data, pdf_path, signed_at FROM contracts WHERE id = 54");
$stmt->execute();
$contract = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($contract) {
    echo "Contract found:\n";
    echo "  - ID: " . $contract['id'] . "\n";
    echo "  - Status: " . $contract['status'] . "\n";
    echo "  - Signed at: " . ($contract['signed_at'] ?? 'NULL') . "\n";
    echo "  - Signature path: " . ($contract['signature_path'] ?? 'NULL') . "\n";
    echo "  - PDF path: " . ($contract['pdf_path'] ?? 'NULL') . "\n";
    echo "  - Signature data length: " . strlen($contract['signature_data'] ?? '') . " chars\n";
    
    if (!empty($contract['signature_data'])) {
        $sig_data = json_decode($contract['signature_data'], true);
        if ($sig_data) {
            echo "\n  Signature data contains:\n";
            foreach (array_keys($sig_data) as $key) {
                echo "    - {$key}\n";
            }
        }
    }
} else {
    echo "Contract #54 not found!\n";
}

// Check if contract_signatures table exists
$result = $conn->query("SHOW TABLES LIKE 'contract_signatures'");
if ($result->num_rows > 0) {
    echo "\n\n=== CONTRACT_SIGNATURES TABLE ===\n";
    $stmt = $conn->prepare("SELECT * FROM contract_signatures WHERE contract_id = 54");
    $stmt->execute();
    $sig = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($sig) {
        echo "Signature found in contract_signatures table\n";
        print_r($sig);
    } else {
        echo "No signature found in contract_signatures for contract #54\n";
    }
} else {
    echo "\n\nTable 'contract_signatures' does NOT exist!\n";
}

$conn->close();
?>
