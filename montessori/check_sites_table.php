<?php
require_once __DIR__ . '/../config/database.php';
$conn = getDBConnection();

echo "<h2>Sites Table Structure:</h2>";
$result = $conn->query("DESCRIBE sites");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><h2>Sites Data:</h2>";
$result = $conn->query("SELECT * FROM sites LIMIT 5");
echo "<pre>";
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";
?>