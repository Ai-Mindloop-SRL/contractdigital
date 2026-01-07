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

echo "=== DEBUG GDPR CHECKBOXES (DETAILED) ===\n\n";

// Test the split with 8.3/8.4/8.5
$sections = preg_split('/(<strong>8\.[345]<\/strong>)/', $template_content, -1, PREG_SPLIT_DELIM_CAPTURE);

echo "1. Number of sections after split: " . count($sections) . "\n\n";

// Show what each delimiter section contains
echo "2. Delimiter sections:\n";
echo "   Section [1]: " . htmlspecialchars($sections[1] ?? 'NOT FOUND') . "\n";
echo "   Section [3]: " . htmlspecialchars($sections[3] ?? 'NOT FOUND') . "\n";
echo "   Section [5]: " . htmlspecialchars($sections[5] ?? 'NOT FOUND') . "\n\n";

// Check if checkboxes are in the right sections
echo "3. Looking for checkboxes in sections:\n";
for ($i = 0; $i < count($sections); $i++) {
    if (strpos($sections[$i], 'DA ☐') !== false) {
        echo "   Section [{$i}] contains 'DA ☐' - Length: " . strlen($sections[$i]) . " chars\n";
        // Show context around the checkbox
        $pos = strpos($sections[$i], 'DA ☐');
        $context = substr($sections[$i], max(0, $pos - 100), 250);
        echo "   Context: " . htmlspecialchars($context) . "\n\n";
    }
}

// Test replacement on section 2
if (isset($sections[2])) {
    echo "4. Testing replacement on section [2]:\n";
    echo "   Original contains 'DA ☐'? " . (strpos($sections[2], 'DA ☐') !== false ? "YES" : "NO") . "\n";
    
    $test_section = $sections[2];
    $test_section = preg_replace('/DA ☐/', 'DA ☑', $test_section, 1);
    
    echo "   After replacement contains 'DA ☑'? " . (strpos($test_section, 'DA ☑') !== false ? "YES" : "NO") . "\n";
    echo "   After replacement still has 'DA ☐'? " . (strpos($test_section, 'DA ☐') !== false ? "YES (more than 1)" : "NO") . "\n\n";
}

// Test replacement on section 4
if (isset($sections[4])) {
    echo "5. Testing replacement on section [4]:\n";
    echo "   Original contains 'DA ☐'? " . (strpos($sections[4], 'DA ☐') !== false ? "YES" : "NO") . "\n";
    
    $test_section = $sections[4];
    $test_section = preg_replace('/DA ☐/', 'DA ☑', $test_section, 1);
    
    echo "   After replacement contains 'DA ☑'? " . (strpos($test_section, 'DA ☑') !== false ? "YES" : "NO") . "\n";
    echo "   After replacement still has 'DA ☐'? " . (strpos($test_section, 'DA ☐') !== false ? "YES (more than 1)" : "NO") . "\n";
}

$conn->close();
?>
