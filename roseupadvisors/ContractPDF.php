<?php
require_once __DIR__ . '/../includes/tcpdf/tcpdf.php';

class ContractPDF_Mindloop extends TCPDF {
    
    public function __construct() {
        parent::__construct('P', 'mm', 'A4', true, 'UTF-8', false);
        $this->SetCreator('ROSEUP ADVISORS Contract System');
        $this->SetAuthor('ROSEUP ADVISORS S.R.L.');
        $this->SetTitle('Contract de Colaborare BNI');
        $this->SetMargins(15, 15, 15);
        $this->SetAutoPageBreak(TRUE, 15);
        $this->SetFont('dejavusans', '', 10);
    }
    
    /**
     * Generate PDF with signatures
     * @param string $html_content The HTML template content
     * @param string $output_path Full path where to save PDF
     * @param string $signature_data_base64 Client signature from canvas (PNG base64)
     */
    public function generatePDF($html_content, $output_path, $signature_data_base64 = '') {
        $this->AddPage();
        
        // Load prestator signature from file (ROSEUP ADVISORS)
        $prestator_sig_path = __DIR__ . '/signature.png';
        $prestator_sig_html = '';
        if (file_exists($prestator_sig_path)) {
            $prestator_img_data = base64_encode(file_get_contents($prestator_sig_path));
            $prestator_sig_html = '<img src="@' . preg_replace('#^data:image/[^;]+;base64,#', '', $prestator_img_data) . '" style="width:80px;height:40px;display:block;" />';
        }
        
        // Process client signature
        $client_sig_html = '';
        if (!empty($signature_data_base64)) {
            // Remove data:image/png;base64, prefix if present
            $clean_signature = preg_replace('#^data:image/[^;]+;base64,#', '', $signature_data_base64);
            $client_sig_html = '<img src="@' . $clean_signature . '" style="width:80px;height:40px;display:block;" />';
        }
        
        // Replace signature placeholders with images
        $html_content = str_replace(
            '<span id="prestator-signature"></span>',
            $prestator_sig_html,
            $html_content
        );
        
        $html_content = str_replace(
            '<span id="client-signature"></span>',
            $client_sig_html,
            $html_content
        );
        
        // Write HTML to PDF
        $this->writeHTML($html_content, true, false, true, false, '');
        
        // Save PDF
        $this->Output($output_path, 'F');
        
        return true;
    }
}
?>
