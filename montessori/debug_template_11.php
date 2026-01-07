<?php
// Debug script pentru template 11
require_once __DIR__ . '/../config/database.php';

$conn = getDBConnection();

echo "=== DEBUG TEMPLATE 11 ===\n\n";

// 1. Check if template exists
$stmt = $conn->prepare("SELECT id, template_name, site_id FROM contract_templates WHERE id = 11");
$stmt->execute();
$template = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo "1. Template Info:\n";
print_r($template);
echo "\n";

// 2. Count fields in template_fields
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM template_fields WHERE template_id = 11");
$stmt->execute();
$count = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo "2. Total fields in template_fields: " . $count['total'] . "\n\n";

// 3. Show first 5 fields
$stmt = $conn->prepare("
    SELECT field_name, field_label, field_type, field_group, is_required 
    FROM template_fields 
    WHERE template_id = 11 
    ORDER BY field_order 
    LIMIT 5
");
$stmt->execute();
$fields = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo "3. First 5 fields:\n";
print_r($fields);
echo "\n";

// 4. Get all unique field names
$stmt = $conn->prepare("
    SELECT DISTINCT field_name 
    FROM template_fields 
    WHERE template_id = 11 
    ORDER BY field_name
");
$stmt->execute();
$unique_fields = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo "4. Total unique field names: " . count($unique_fields) . "\n";
echo "Field names:\n";
foreach ($unique_fields as $f) {
    echo "  - " . $f['field_name'] . "\n";
}

$conn->close();
?>
