<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$db = getDBConnection();

// Get contract ID
$contract_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($contract_id <= 0) {
    header('Location: ' . SUBFOLDER . '/' . $site_slug . '/view_contract.php');
    exit;
}

// Get contract details with template and site info
$stmt = $db->prepare("
    SELECT 
        c.*,
        ct.template_name,
        s.site_name,
        s.primary_color,
        DATE_FORMAT(c.sent_at, '%d.%m.%Y la %H:%i') as sent_date,
        DATE_FORMAT(c.signed_at, '%d.%m.%Y la %H:%i') as signed_date,
        DATE_FORMAT(c.created_at, '%d.%m.%Y la %H:%i') as created_date
    FROM contracts c
    JOIN contract_templates ct ON c.template_id = ct.id
    JOIN sites s ON c.site_id = s.id
    WHERE c.id = ? AND s.site_slug = ?
");
$stmt->bind_param("is", $contract_id, $site_slug);
$stmt->execute();
$contract = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$contract) {
    header('Location: ' . SUBFOLDER . '/' . $site_slug . '/view_contract.php');
    exit;
}

// Get signature data if signed
$signature_data = null;
if ($contract['status'] === 'signed') {
    $stmt = $db->prepare("SELECT * FROM contract_signatures WHERE contract_id = ?");
    $stmt->bind_param("i", $contract_id);
    $stmt->execute();
    $signature_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$signing_url = fullUrl('/sign/' . $contract['signing_token']);
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalii Contract - <?php echo htmlspecialchars($contract['recipient_name']); ?></title>
    <!-- <link rel="stylesheet" href="<?php echo asset('/css/style.css'); ?>"> -->
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .header {
            background: linear-gradient(135deg, <?php echo $contract['primary_color'] ?? '#3498db'; ?> 0%, <?php echo adjustBrightness($contract['primary_color'], -20); ?> 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            margin: 0 0 10px;
            font-size: 28px;
        }
        
        .header .breadcrumb {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .header .breadcrumb a {
            color: white;
            text-decoration: none;
        }
        
        .status-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .status-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .status-badge {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-draft {
            background: #ecf0f1;
            color: #7f8c8d;
        }
        
        .status-sent {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-signed {
            background: #d4edda;
            color: #155724;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid <?php echo $contract['primary_color'] ?? '#3498db'; ?>;
        }
        
        .info-item label {
            display: block;
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }
        
        .info-item .value {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }
        
        .contract-content {
            background: white;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            line-height: 1.8;
        }
        
        .contract-content h2 {
            margin-top: 0;
            color: <?php echo $contract['primary_color'] ?? '#3498db'; ?>;
        }
        
        .signature-section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .signature-section h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid <?php echo $contract['primary_color'] ?? '#3498db'; ?>;
            padding-bottom: 10px;
        }
        
        .signature-image {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            background: #f9f9f9;
            max-width: 400px;
        }
        
        .signature-image img {
            width: 100%;
            height: auto;
            display: block;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: <?php echo $contract['primary_color'] ?? '#3498db'; ?>;
            color: white;
        }
        
        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            opacity: 0.9;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .link-box {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #2196F3;
            margin: 20px 0;
        }
        
        .link-box label {
            display: block;
            font-weight: 600;
            margin-bottom: 10px;
            color: #1976D2;
        }
        
        .link-box input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: monospace;
        }
        
        .timeline {
            position: relative;
            padding-left: 40px;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 30px;
        }
        
        .timeline-item:before {
            content: '';
            position: absolute;
            left: -33px;
            top: 5px;
            width: 12px;
            height: 12px;
            background: <?php echo $contract['primary_color'] ?? '#3498db'; ?>;
            border-radius: 50%;
        }
        
        .timeline-item:after {
            content: '';
            position: absolute;
            left: -28px;
            top: 17px;
            width: 2px;
            height: calc(100% - 12px);
            background: #ddd;
        }
        
        .timeline-item:last-child:after {
            display: none;
        }
        
        .timeline-item .time {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .timeline-item .event {
            font-weight: 600;
            color: #333;
        }
        
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📄 Detalii Contract</h1>
            <div class="breadcrumb">
    <a href="<?php echo SUBFOLDER . '/' . $site_slug . '/view_contract.php'; ?>">Contracte</a> / Detalii
</div>
        </div>
        
        <div class="status-card">
            <div class="status-header">
                <h2 style="margin: 0;">Informații General</h2>
                <?php 
// Safe status handling
$status = $contract['status'] ?? 'draft';
$status_labels = [
    'draft' => '📝 Draft',
    'sent' => '📤 Trimis',
    'signed' => '✅ Semnat'
];
?>

<span class="status-badge status-<?php echo $status; ?>">
    <?php echo $status_labels[$status] ?? '❓ Unknown'; ?>
</span>
                
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <label>Destinatar</label>
                    <div class="value"><?php echo htmlspecialchars($contract['recipient_name']); ?></div>
                </div>
                
                <div class="info-item">
                    <label>Email</label>
                    <div class="value"><?php echo htmlspecialchars($contract['recipient_email']); ?></div>
                </div>
                
                <?php if (!empty($contract['recipient_phone'])): ?>
                <div class="info-item">
                    <label>Telefon</label>
                    <div class="value"><?php echo htmlspecialchars($contract['recipient_phone']); ?></div>
                </div>
                <?php endif; ?>
                
                <div class="info-item">
                    <label>Template</label>
                    <div class="value"><?php echo htmlspecialchars($contract['template_name']); ?></div>
                </div>
            </div>
            
            <?php if ($contract['status'] === 'sent'): ?>
            <div class="link-box">
                <label>🔗 Link de Semnare (trimis prin email)</label>
                <input type="text" readonly value="<?php echo htmlspecialchars($signing_url); ?>" onclick="this.select()">
            </div>
            <?php endif; ?>
            
            <div style="margin-top: 30px;">
                <h3 style="margin-bottom: 15px;">📅 Cronologie</h3>
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="time"><?php echo $contract['created_date']; ?></div>
                        <div class="event">Contract creat</div>
                    </div>
                    
                    <?php if ($contract['sent_at']): ?>
                    <div class="timeline-item">
                        <div class="time"><?php echo $contract['sent_date']; ?></div>
                        <div class="event">Contract trimis către <?php echo htmlspecialchars($contract['recipient_email']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($contract['signed_at']): ?>
                    <div class="timeline-item">
                        <div class="time"><?php echo $contract['signed_date']; ?></div>
                        <div class="event">Contract semnat de <?php echo htmlspecialchars($contract['recipient_name']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="contract-content">
            <h2>Conținut Contract</h2>
            <?php echo $contract['contract_content']; ?>
        </div>
        
        <?php if ($signature_data): ?>
        <div class="signature-section">
            <h3>✍️ Semnătură Electronică</h3>
            <div class="signature-image">
                <img src="<?php echo htmlspecialchars($signature_data['signature_data']); ?>" alt="Semnătură">
            </div>
            <p style="margin-top: 15px; color: #666;">
                <strong>Semnat de:</strong> <?php echo htmlspecialchars($signature_data['signer_name']); ?><br>
                <strong>Data:</strong> <?php echo $contract['signed_date']; ?><br>
                <strong>IP:</strong> <?php echo htmlspecialchars($signature_data['ip_address']); ?>
            </p>
        </div>
        <?php endif; ?>
        
        <div class="action-buttons">
            <a href="<?php echo SUBFOLDER . '/' . $site_slug . '/view_contract.php'; ?>" class="btn btn-secondary">
    ← Înapoi la listă
</a>
            
            <?php if ($status === 'signed'): ?>
            <a href="<?php echo SUBFOLDER . '/' . $site_slug . '/download_pdf.php?id=' . $contract['id']; ?>" class="btn btn-success">
                📥 Descarcă PDF
            </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>