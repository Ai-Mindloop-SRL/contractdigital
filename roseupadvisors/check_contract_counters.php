<?php
require_once '../config/config.php';

echo "<h2>📊 Contract Counters Status</h2>";

// Check if columns exist
echo "<h3>1. Database Schema Check:</h3>";
$result = $conn->query("DESCRIBE contract_templates");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
while ($row = $result->fetch_assoc()) {
    if ($row['Field'] == 'contract_counter') {
        echo "<tr style='background: #d4edda;'>";
    } else {
        echo "<tr>";
    }
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "</tr>";
}
echo "</table>";

$result = $conn->query("DESCRIBE contracts");
echo "<br><table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
while ($row = $result->fetch_assoc()) {
    if ($row['Field'] == 'contract_number') {
        echo "<tr style='background: #d4edda;'>";
    } else {
        echo "<tr>";
    }
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Show template counters
echo "<h3>2. Template Counters:</h3>";
$result = $conn->query("SELECT id, template_name, contract_counter, created_at FROM contract_templates ORDER BY id");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Template Name</th><th>Counter</th><th>Created</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['template_name'] . "</td>";
    echo "<td><strong style='color: #007bff;'>" . ($row['contract_counter'] ?? 'NULL') . "</strong></td>";
    echo "<td>" . $row['created_at'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Show contracts with numbers
echo "<h3>3. Contracts Status:</h3>";
$result = $conn->query("
    SELECT 
        c.id,
        c.contract_number,
        c.status,
        c.template_id,
        ct.template_name,
        c.created_at,
        c.signed_at
    FROM contracts c
    LEFT JOIN contract_templates ct ON c.template_id = ct.id
    ORDER BY c.id DESC
    LIMIT 20
");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Contract Number</th><th>Template</th><th>Status</th><th>Created</th><th>Signed</th></tr>";
while ($row = $result->fetch_assoc()) {
    $bg = $row['status'] == 'signed' ? 'background: #d4edda;' : '';
    echo "<tr style='$bg'>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td><strong>" . ($row['contract_number'] ?? '[NOT GENERATED YET]') . "</strong></td>";
    echo "<td>" . $row['template_name'] . "</td>";
    echo "<td>" . $row['status'] . "</td>";
    echo "<td>" . $row['created_at'] . "</td>";
    echo "<td>" . ($row['signed_at'] ?? '-') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Statistics
echo "<h3>4. Statistics:</h3>";
$result = $conn->query("
    SELECT 
        COUNT(*) as total_contracts,
        COUNT(contract_number) as numbered_contracts,
        COUNT(*) - COUNT(contract_number) as pending_contracts,
        COUNT(CASE WHEN status = 'signed' THEN 1 END) as signed_contracts
    FROM contracts
");
$stats = $result->fetch_assoc();
echo "<table border='1' cellpadding='10'>";
echo "<tr><td><strong>Total Contracts:</strong></td><td>" . $stats['total_contracts'] . "</td></tr>";
echo "<tr><td><strong>Contracts with Numbers:</strong></td><td style='color: #28a745;'>" . $stats['numbered_contracts'] . "</td></tr>";
echo "<tr><td><strong>Pending (no number yet):</strong></td><td style='color: #ffc107;'>" . $stats['pending_contracts'] . "</td></tr>";
echo "<tr><td><strong>Signed Contracts:</strong></td><td style='color: #007bff;'>" . $stats['signed_contracts'] . "</td></tr>";
echo "</table>";
?>
