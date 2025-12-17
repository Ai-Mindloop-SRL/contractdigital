<?php
// Update template in database
require_once __DIR__ . '/../includes/database.php';

$conn = getDBConnection();

$template_html = file_get_contents(__DIR__ . '/template_clean_compact.html');

$stmt = $conn->prepare("UPDATE contract_templates SET template_content = ? WHERE id = 8 AND site_id = 4");
$stmt->bind_param('s', $template_html);

if ($stmt->execute()) {
    echo "✅ Template updated successfully in database (ID=8, Site=4)\n";
    echo "Length: " . strlen($template_html) . " bytes\n";
} else {
    echo "❌ Error: " . $stmt->error . "\n";
}

$conn->close();
