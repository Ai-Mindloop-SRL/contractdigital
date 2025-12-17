<?php
require_once __DIR__ . '/../config/database.php';

$conn = getDBConnection();

// Read fixed template
$template_content = file_get_contents('/home/user/template_fixed_placeholders.html');

// Update template in DB
$stmt = $conn->prepare("UPDATE contract_templates SET template_content = ? WHERE id = 8");
$stmt->bind_param("s", $template_content);

if ($stmt->execute()) {
    echo "✅ Template updated successfully!\n";
    echo "Placeholders changed from {{...}} to [...]:\n";
    echo "  {{company_name}} → [NUME_FIRMA]\n";
    echo "  {{cui}} → [CUI]\n";
    echo "  {{reg_com}} → [REG_COM]\n";
    echo "  {{address}} → [ADRESA]\n";
    echo "  {{email}} → [EMAIL]\n";
    echo "  {{phone}} → [TELEFON]\n";
    echo "  {{bank_account}} → [CONT_BANCAR]\n";
    echo "  {{legal_representative}} → [REPREZENTANT]\n";
    echo "  {{position}} → [FUNCTIE]\n";
} else {
    echo "❌ Error: " . $stmt->error . "\n";
}

$stmt->close();
$conn->close();
