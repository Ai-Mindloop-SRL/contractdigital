<?php
require_once __DIR__ . '/../includes/tcpdf/tcpdf.php';

class ContractPDF_Mindloop extends TCPDF {
    
    public function __construct() {
        parent::__construct('P', 'mm', 'A4', true, 'UTF-8', false);
        $this->SetCreator('AI Mindloop Contract System');
        $this->SetAuthor('AI Mindloop SRL');
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
        
        // Write HTML to PDF
        $this->writeHTML($html_content, true, false, true, false, '');
        
        // NIVEL 1 (SES+): Add electronic signature metadata section
        if (!empty($nivel1_data)) {
            $this->AddPage();
            $metadata_html = $this->generateNivel1MetadataHTML($nivel1_data);
            $this->writeHTML($metadata_html, true, false, true, false, '');
        }
        
        // Save PDF
        $this->Output($output_path, 'F');
        
        return true;
    }
    
    /**
     * Generate HTML for NIVEL 1 metadata section
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
        
        // Truncate hashes for display (first 32 chars + ...)
        $contract_hash = isset($data['contract_hash_before']) ? substr($data['contract_hash_before'], 0, 32) . '...' : 'N/A';
        $pdf_hash = isset($data['pdf_hash_after']) ? substr($data['pdf_hash_after'], 0, 32) . '...' : 'N/A';
        
        $html = '
        <div style="font-family: DejaVu Sans, sans-serif; font-size: 10pt; line-height: 1.6;">
            <div style="text-align: center; margin-bottom: 20px; padding: 15px; background: #f0f0f0; border-radius: 5px;">
                <h2 style="margin: 0; color: #2c3e50; font-size: 14pt;">DETALII SEMNATURA ELECTRONICA</h2>
                <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 9pt;">Metadate conform NIVEL 1 (SES+) - eIDAS/eSign Compliant</p>
            </div>
            
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
                <tr>
                    <td style="width: 40%; padding: 8px; border: 1px solid #ddd; background: #f8f9fa; font-weight: bold;">Data si ora semnarii:</td>
                    <td style="width: 60%; padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($signed_at_formatted) . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; background: #f8f9fa; font-weight: bold;">Semnatar:</td>
                    <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($data['signer_name'] ?? 'N/A') . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; background: #f8f9fa; font-weight: bold;">Adresa IP:</td>
                    <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($data['ip_address'] ?? 'N/A') . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; background: #f8f9fa; font-weight: bold;">Dispozitiv:</td>
                    <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($device_info) . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; background: #f8f9fa; font-weight: bold;">Browser:</td>
                    <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($browser) . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; background: #f8f9fa; font-weight: bold;">Fus orar:</td>
                    <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($data['timezone'] ?? 'N/A') . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; background: #f8f9fa; font-weight: bold;">Rezolutie ecran:</td>
                    <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($data['screen_resolution'] ?? 'N/A') . '</td>
                </tr>
            </table>
            
            <div style="margin-bottom: 15px; padding: 12px; background: #e8f5e9; border-left: 4px solid #4caf50; border-radius: 3px;">
                <h3 style="margin: 0 0 8px 0; color: #2e7d32; font-size: 11pt;">Consimtamant Explicit (GDPR)</h3>
                <p style="margin: 3px 0;">[ X ] Am citit si inteles in totalitate termenii si conditiile contractului</p>
                <p style="margin: 3px 0;">[ X ] Sunt de acord sa semnez prin mijloace electronice (valoare juridica egala cu semnatura olografa)</p>
                <p style="margin: 3px 0;">[ X ] Sunt de acord cu procesarea datelor personale conform GDPR (Regulamentul UE 2016/679)</p>
            </div>
            
            <div style="margin-bottom: 15px; padding: 12px; background: #fff3e0; border-left: 4px solid #ff9800; border-radius: 3px;">
                <h3 style="margin: 0 0 8px 0; color: #e65100; font-size: 11pt;">Integritate Document (SHA-256)</h3>
                <p style="margin: 3px 0; font-size: 9pt;"><strong>Hash contract HTML (inainte de semnare):</strong><br/><span style="font-family: monospace; font-size: 8pt;">' . htmlspecialchars($contract_hash) . '</span></p>
                <p style="margin: 8px 0 0 0; font-size: 8pt; color: #666;"><em>Nota: Hash-ul PDF-ului final este calculat dupa generare si salvat in baza de date. Orice modificare a documentului va genera un hash diferit, garantand integritatea continutului.</em></p>
            </div>
            
            <div style="margin-top: 20px; padding: 10px; background: #f5f5f5; border-radius: 3px; text-align: center; font-size: 8pt; color: #666;">
                <p style="margin: 0;"><strong>Semnatura Electronica Conforma:</strong> Regulament eIDAS (UE) Nr. 910/2014 | Legea 455/2001 (RO)</p>
                <p style="margin: 5px 0 0 0;">Document generat de AI Mindloop Contract System | Hash-uri verificabile in baza de date</p>
            </div>
        </div>
        ';
        
        return $html;
    }
}
?>
