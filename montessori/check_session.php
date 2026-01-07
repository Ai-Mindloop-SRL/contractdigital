<?php
session_start();
echo "<h2>Session Debug:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>