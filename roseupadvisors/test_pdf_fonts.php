<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/tcpdf/tcpdf.php';

echo "<h2>🔍 TCPDF Font Test</h2>";

// Test 1: Check TCPDF installation
echo "<h3>1. TCPDF Installation:</h3>";
if (class_exists('TCPDF')) {
    echo "✅ TCPDF class loaded successfully<br>";
    echo "TCPDF Version: " . TCPDF_STATIC::getTCPDFVersion() . "<br>";
} else {
    echo "❌ TCPDF class NOT found<br>";
    die();
}

// Test 2: Check font directory
echo "<h3>2. Font Directory:</h3>";
$font_dir = K_PATH_FONTS;
echo "Font path: <strong>$font_dir</strong><br>";
if (is_dir($font_dir)) {
    echo "✅ Font directory exists<br>";
    
    // List available fonts
    $fonts = glob($font_dir . '/*.php');
    echo "Available fonts: " . count($fonts) . "<br>";
    
    echo "<details><summary>Font list (click to expand)</summary><ul>";
    foreach ($fonts as $font) {
        $fontname = basename($font, '.php');
        echo "<li>$fontname</li>";
    }
    echo "</ul></details>";
    
    // Check specifically for dejavusans
    if (file_exists($font_dir . '/dejavusans.php')) {
        echo "✅ <strong>dejavusans.php</strong> found!<br>";
    } else {
        echo "❌ <strong>dejavusans.php</strong> NOT found<br>";
    }
} else {
    echo "❌ Font directory does NOT exist<br>";
}

// Test 3: Generate test PDF with Romanian text
echo "<h3>3. Test PDF Generation:</h3>";

try {
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Font Test');
    $pdf->SetTitle('Test Diacritice');
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);
    
    // Test different fonts
    $fonts_to_test = ['helvetica', 'dejavusans', 'times', 'courier'];
    
    foreach ($fonts_to_test as $font) {
        try {
            $pdf->AddPage();
            $pdf->SetFont($font, '', 12);
            
            $html = '<h1>Test Font: ' . $font . '</h1>';
            $html .= '<p>Text cu diacritice românești:</p>';
            $html .= '<p><strong>ă â î ș ț Ă Â Î Ș Ț</strong></p>';
            $html .= '<p>România, București, Brașov, Iași</p>';
            $html .= '<p>Contract de colaborare între părți</p>';
            $html .= '<p>Semnătură electronică validă</p>';
            
            $pdf->writeHTML($html, true, false, true, false, '');
            
            echo "✅ Font '<strong>$font</strong>' - test OK<br>";
        } catch (Exception $e) {
            echo "❌ Font '<strong>$font</strong>' - ERROR: " . $e->getMessage() . "<br>";
        }
    }
    
    // Save test PDF
    $output_dir = __DIR__ . '/../uploads/test/';
    if (!is_dir($output_dir)) {
        mkdir($output_dir, 0755, true);
    }
    
    $pdf_file = $output_dir . 'font_test_' . time() . '.pdf';
    $pdf->Output($pdf_file, 'F');
    
    $pdf_url = str_replace($_SERVER['DOCUMENT_ROOT'], '', $pdf_file);
    
    echo "<br><a href='$pdf_url' target='_blank' style='display:inline-block;padding:10px 20px;background:#28a745;color:white;text-decoration:none;border-radius:5px;'>📄 Download Test PDF</a>";
    
} catch (Exception $e) {
    echo "❌ PDF Generation failed: " . $e->getMessage() . "<br>";
}

// Test 4: Check current ContractPDF implementation
echo "<h3>4. Current ContractPDF Settings:</h3>";
if (file_exists(__DIR__ . '/ContractPDF.php')) {
    $contract_pdf_content = file_get_contents(__DIR__ . '/ContractPDF.php');
    
    // Extract font setting
    if (preg_match('/SetFont\([\'"]([^\'"]*)[\'"]/i', $contract_pdf_content, $matches)) {
        echo "Current font in ContractPDF.php: <strong>" . $matches[1] . "</strong><br>";
    }
    
    // Check encoding
    if (strpos($contract_pdf_content, 'UTF-8') !== false) {
        echo "✅ UTF-8 encoding is set<br>";
    } else {
        echo "⚠️ UTF-8 encoding might not be set<br>";
    }
}

echo "<h3>5. Recommendation:</h3>";
echo "<p>If 'dejavusans' shows diacritics correctly in the test PDF above, your TCPDF installation is fine.</p>";
echo "<p>If diacritics are missing, you need to:</p>";
echo "<ol>";
echo "<li>Ensure TCPDF fonts are properly installed</li>";
echo "<li>Use 'dejavusans' or 'freesans' font (both support Romanian)</li>";
echo "<li>Make sure UTF-8 encoding is enabled</li>";
echo "</ol>";
?>
