<?php
require_once __DIR__ . '/../config/database.php';
$conn = getDBConnection();

echo "<h2>Contracts Table Structure:</h2>";
$result = $conn->query("SHOW CREATE TABLE contracts");
$row = $result->fetch_assoc();
echo "<pre>" . htmlspecialchars($row['Create Table']) . "</pre>";

echo "<hr><h2>Check signing_token column:</h2>";
$result = $conn->query("DESCRIBE contracts");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
    echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr><h2>Existing contracts with empty tokens:</h2>";
$result = $conn->query("SELECT id, signing_token, LENGTH(signing_token) as len, created_at FROM contracts WHERE signing_token = '' OR signing_token IS NULL ORDER BY id DESC LIMIT 10");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Token</th><th>Length</th><th>Created</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>" . htmlspecialchars($row['signing_token'] ?? 'NULL') . "</td>";
    echo "<td>{$row['len']}</td>";
    echo "<td>{$row['created_at']}</td>";
    echo "</tr>";
}
echo "</table>";
?>