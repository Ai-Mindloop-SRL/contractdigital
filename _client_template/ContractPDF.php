<?php
require_once __DIR__ . '/../includes/tcpdf/tcpdf.php';

class ContractPDF_Client extends TCPDF {
    
    public function __construct() {
        parent::__construct('P', 'mm', 'A4', true, 'UTF-8', false);
        $this->SetCreator('ContractDigital Client System');
        $this->SetAuthor('ContractDigital');
        $this->SetTitle('Contract de Prestări Servicii');
        $this->SetMargins(15, 15, 15);
        $this->SetAutoPageBreak(TRUE, 15);
        $this->SetFont('dejavusans', '', 10);
    }
    
    /**
     * Generate PDF with signatures and NIVEL 1 metadata
     * @param string $html_content The HTML template content
     * @param string $output_path Full path where to save PDF
     * @param string $signature_data_base64 Client signature from canvas (PNG base64)
     * @param array $nivel1_data NIVEL 1 metadata (signer_name, signed_at, ip_address, etc.)
     */
    public function generatePDF($html_content, $output_path, $signature_data_base64 = '', $nivel1_data = array()) {
        $this->AddPage();
        
        // Load prestator signature from file
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
        
        // NIVEL 1 (SES+): Add compact metadata BELOW signature (not new page)
        if (!empty($nivel1_data)) {
            $metadata_html = $this->generateNivel1MetadataHTML($nivel1_data);
            $html_content .= $metadata_html;
        }
        
        // Write HTML to PDF
        $this->writeHTML($html_content, true, false, true, false, '');
        
        // Save PDF
        $this->Output($output_path, 'F');
        
        return true;
    }
    
    /**
     * Generate compact HTML for NIVEL 1 metadata (4 lines below signature)
     * @param array $data NIVEL 1 signature metadata
     * @return string HTML content
     */
    private function generateNivel1MetadataHTML($data) {
        // Format timestamp
        $signed_at_formatted = date('d.m.Y, H:i:s', strtotime($data['signed_at']));
        
        // Parse user agent for readable format
        $ua = $data['user_agent'] ?? 'Unknown';
        $browser = 'Unknown';
        $os = 'Unknown';
        
        // Simple browser detection
        if (preg_match('/Edg\/(\d+)/', $ua)) $browser = 'Microsoft Edge';
        elseif (preg_match('/Chrome\/(\d+)/', $ua)) $browser = 'Google Chrome';
        elseif (preg_match('/Firefox\/(\d+)/', $ua)) $browser = 'Mozilla Firefox';
        elseif (preg_match('/Safari\/(\d+)/', $ua) && !preg_match('/Chrome/', $ua)) $browser = 'Safari';
        
        // Simple OS detection
        if (preg_match('/Windows NT (\d+\.\d+)/', $ua, $matches)) {
            $os = 'Windows ' . ($matches[1] == '10.0' ? '10/11' : $matches[1]);
        } elseif (preg_match('/Mac OS X/', $ua)) {
            $os = 'macOS';
        } elseif (preg_match('/Linux/', $ua)) {
            $os = 'Linux';
        } elseif (preg_match('/Android/', $ua)) {
            $os = 'Android';
        } elseif (preg_match('/iOS|iPhone|iPad/', $ua)) {
            $os = 'iOS';
        }
        
        $device_type = ucfirst($data['device_type'] ?? 'Unknown');
        $device_info = "$device_type ($os)";
        
        // Compact metadata - just 4 lines below signature
        $html = '
        <div style="margin-top: 20px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd; font-family: DejaVu Sans, sans-serif; font-size: 9pt;">
            <p style="margin: 3px 0;"><strong>Data si ora semnarii:</strong> ' . htmlspecialchars($signed_at_formatted) . '</p>
            <p style="margin: 3px 0;"><strong>Adresa IP:</strong> ' . htmlspecialchars($data['ip_address'] ?? 'N/A') . '</p>
            <p style="margin: 3px 0;"><strong>Dispozitiv:</strong> ' . htmlspecialchars($device_info) . '</p>
            <p style="margin: 3px 0;"><strong>Browser:</strong> ' . htmlspecialchars($browser) . '</p>
        </div>
        ';
        
        return $html;
    }
}
?>
