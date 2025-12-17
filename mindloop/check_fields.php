<?php
require_once '../config/database.php';

// Check template_fields table structure
$result = $conn->query("DESCRIBE template_fields");
echo "=== TEMPLATE_FIELDS STRUCTURE ===\n";
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . " - " . ($row['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . "\n";
}

// Check existing fields for template 8
echo "\n=== EXAMPLE FROM TEMPLATE 8 ===\n";
$stmt = $conn->prepare("SELECT * FROM template_fields WHERE template_id = 8 LIMIT 3");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
