<?php
// Script pentru adăugare câmp reg_com în DB
// Rulează: https://contractdigital.ro/ro/mindloop/add_reg_com_to_db.php
// APOI ȘTERGE!

require_once __DIR__ . '/../config/database.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body>";
echo "<h2>🔧 Adăugare câmp reg_com</h2>";

try {
    // Adaugă câmpul în field_definitions
    $sql1 = "INSERT INTO field_definitions (
        site_id, field_name, field_label, field_type, 
        validation_rules, is_required, display_order, created_at
    ) VALUES (
        2, 'reg_com', 'Nr. Reg. Com.', 'text', 
        '{\"min_length\": 5}', 1, 3, NOW()
    )";
    
    if ($conn->query($sql1)) {
        $field_id = $conn->insert_id;
        echo "<p>✅ Câmp adăugat în field_definitions (ID: $field_id)</p>";
        
        // Mapează la Template 8
        $sql2 = "INSERT INTO template_field_mapping (
            template_id, field_definition_id, display_order, is_required, created_at
        ) VALUES (8, $field_id, 3, 1, NOW())";
        
        if ($conn->query($sql2)) {
            echo "<p>✅ Câmp mapat la Template 8</p>";
        } else {
            throw new Exception("Eroare mapping: " . $conn->error);
        }
    } else {
        throw new Exception("Eroare insert: " . $conn->error);
    }
    
    // Verificare
    $result = $conn->query("
        SELECT fd.id, fd.field_name, fd.field_label, 
               tfm.template_id, tfm.display_order
        FROM field_definitions fd
        LEFT JOIN template_field_mapping tfm ON fd.id = tfm.field_definition_id
        WHERE fd.site_id = 2 AND fd.field_name = 'reg_com'
    ");
    
    if ($row = $result->fetch_assoc()) {
        echo "<h3>📋 Verificare:</h3><ul>";
        echo "<li>ID: {$row['id']}</li>";
        echo "<li>Nume: {$row['field_name']}</li>";
        echo "<li>Label: {$row['field_label']}</li>";
        echo "<li>Template ID: {$row['template_id']}</li>";
        echo "<li>Ordine: {$row['display_order']}</li>";
        echo "</ul>";
    }
    
    echo "<hr><h3>✅ GATA!</h3>";
    echo "<p><strong style='color:red;'>🔐 ȘTERGE ACEST FIȘIER ACUM!</strong></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ EROARE: " . $e->getMessage() . "</p>";
}

$conn->close();
echo "</body></html>";
?>