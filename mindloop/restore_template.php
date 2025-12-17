<?php
require_once __DIR__ . '/../config/database.php';

$conn = getDBConnection();

// Read fixed template from server location
$template_content = file_get_contents(__DIR__ . '/template_fixed_placeholders.html');

if ($template_content === false) {
    die("❌ Cannot read template file!");
}

// Update template in DB
$stmt = $conn->prepare("UPDATE contract_templates SET template_content = ? WHERE id = 8");
$stmt->bind_param("s", $template_content);

if ($stmt->execute()) {
    echo "✅ Template restored successfully!\n";
    echo "Template size: " . strlen($template_content) . " bytes\n";
} else {
    echo "❌ Error: " . $stmt->error . "\n";
}

$stmt->close();
$conn->close();
