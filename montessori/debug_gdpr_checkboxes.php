<?php
// Debug GDPR checkbox handling for template 11

require_once __DIR__ . '/../config/database.php';

$conn = getDBConnection();

// Get template 11
$stmt = $conn->prepare("SELECT template_content FROM contract_templates WHERE id = 11");
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$template_content = $result['template_content'];
$stmt->close();

echo "=== DEBUG GDPR CHECKBOXES ===\n\n";

// Test the split with 8.3/8.4/8.5
$sections = preg_split('/(<strong>8\.[345]<\/strong>)/', $template_content, -1, PREG_SPLIT_DELIM_CAPTURE);

echo "1. Number of sections after split: " . count($sections) . "\n\n";

// Show first 200 chars of each section
for ($i = 0; $i < min(7, count($sections)); $i++) {
    echo "Section [{$i}]: " . substr($sections[$i], 0, 200) . "...\n\n";
}

// Check if checkboxes are in the right sections
echo "2. Does section [2] contain checkboxes? ";
echo (strpos($sections[2], 'DA ☐') !== false) ? "YES\n" : "NO\n";

echo "3. Does section [4] contain checkboxes? ";
echo (strpos($sections[4], 'DA ☐') !== false) ? "YES\n" : "NO\n";

// Test replacement
$test_section = $sections[2];
$test_section = preg_replace('/DA ☐/', 'DA ☑', $test_section, 1);

echo "\n4. Test replacement in section [2]:\n";
echo "   Contains 'DA ☑'? " . (strpos($test_section, 'DA ☑') !== false ? "YES" : "NO") . "\n";
echo "   Still has 'DA ☐'? " . (strpos($test_section, 'DA ☐') !== false ? "YES" : "NO") . "\n";

$conn->close();
?>
