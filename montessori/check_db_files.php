<?php
echo "<h2>Checking DB Connection Files:</h2>";

$paths_to_check = [
    __DIR__ . '/../config/db_connect.php',
    __DIR__ . '/../config/database.php',
    __DIR__ . '/../includes/db_connect.php',
    __DIR__ . '/../../config/db_connect.php',
    __DIR__ . '/db_connect.php',
];

foreach ($paths_to_check as $path) {
    $resolved = realpath($path);
    if (file_exists($path)) {
        echo "✅ EXISTS: $path<br>";
        echo "   Real path: $resolved<br><br>";
    } else {
        echo "❌ NOT FOUND: $path<br><br>";
    }
}

echo "<hr><h3>Working Directory:</h3>";
echo __DIR__;
?>