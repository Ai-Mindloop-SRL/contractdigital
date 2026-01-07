<?php
require_once __DIR__ . '/../config/database.php';

$conn = getDBConnection();

$stmt = $conn->prepare("SELECT signature_data FROM contracts WHERE id = 54");
$stmt->execute();
$contract = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo "=== SIGNATURE DATA FORMAT ===\n\n";

$sig_data = $contract['signature_data'];

echo "Raw length: " . strlen($sig_data) . " chars\n\n";

echo "First 500 characters:\n";
echo substr($sig_data, 0, 500) . "\n\n";

echo "Last 100 characters:\n";
echo substr($sig_data, -100) . "\n\n";

// Try to decode as JSON
$decoded = json_decode($sig_data, true);
if ($decoded === null) {
    echo "JSON decode FAILED\n";
    echo "JSON error: " . json_last_error_msg() . "\n\n";
    
    // Check if it's base64 image data directly
    if (strpos($sig_data, 'data:image') === 0) {
        echo "It's a DATA URI (base64 image) directly, not JSON!\n";
    }
} else {
    echo "JSON decode SUCCESS\n";
    echo "Keys in decoded data:\n";
    foreach (array_keys($decoded) as $key) {
        echo "  - {$key}\n";
    }
}

$conn->close();
?>
