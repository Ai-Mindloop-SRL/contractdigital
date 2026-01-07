<?php
// Check templates for site_id = 2
require_once __DIR__ . '/../config/database.php';

$conn = getDBConnection();

echo "=== TEMPLATES FOR SITE_ID = 2 ===\n\n";

// Get all templates for site_id = 2
$stmt = $conn->prepare("SELECT id, template_name, site_id FROM contract_templates WHERE site_id = 2 ORDER BY id");
$stmt->execute();
$templates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo "Templates:\n";
foreach ($templates as $t) {
    echo "  ID: {$t['id']} - {$t['template_name']} (site_id: {$t['site_id']})\n";
    
    // Count fields for each
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM template_fields WHERE template_id = ?");
    $stmt->bind_param("i", $t['id']);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    echo "    Fields: " . $count['total'] . "\n\n";
}

// Also check template 5
echo "\n=== TEMPLATE 5 INFO ===\n";
$stmt = $conn->prepare("SELECT id, template_name, site_id FROM contract_templates WHERE id = 5");
$stmt->execute();
$t5 = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($t5) {
    echo "Template 5: {$t5['template_name']} (site_id: {$t5['site_id']})\n";
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM template_fields WHERE template_id = 5");
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    echo "Fields: " . $count['total'] . "\n";
} else {
    echo "Template 5 not found\n";
}

$conn->close();
?>
