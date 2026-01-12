<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$db = getDBConnection();

// Get all contracts for this site
$stmt = $db->prepare("
    SELECT 
        c.*,
        ct.template_name,
        DATE_FORMAT(c.sent_at, '%d.%m.%Y %H:%i') as sent_date,
        DATE_FORMAT(c.signed_at, '%d.%m.%Y %H:%i') as signed_date
    FROM contracts c
    JOIN contract_templates ct ON c.template_id = ct.id
    WHERE c.site_id = (SELECT id FROM sites WHERE site_slug = ?)
    ORDER BY c.created_at DESC
");
$stmt->bind_param("s", $site_slug);
$stmt->execute();
$contracts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Count by status - with proper handling of empty/null values
$stats = [
    'total' => count($contracts),
    'draft' => 0,
    'sent' => 0,
    'signed' => 0
];

foreach ($contracts as $contract) {
    $status = $contract['status'] ?? 'draft'; // Default to 'draft' if null/empty
    if (isset($stats[$status])) {
        $stats[$status]++;
    }
}

$success = isset($_GET['success']) && $_GET['success'] == 1;
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contracte - <?php echo htmlspecialchars($site_name); ?></title>
    <link rel="stylesheet" href="<?php echo asset('/css/style.css'); ?>">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, <?php echo $primary_color; ?> 0%, <?php echo adjustBrightness($primary_color, -20); ?> 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .header-actions a {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .header-actions a:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card .number {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-card .label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-card.total .number { color: #3498db; }
        .stat-card.draft .number { color: #95a5a6; }
        .stat-card.sent .number { color: #f39c12; }
        .stat-card.signed .number { color: #27ae60; }
        
        .contracts-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-header h2 {
            font-size: 20px;
            color: #333;
        }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
        }
        
        .filter-tabs button {
            background: none;
            border: 1px solid #ddd;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .filter-tabs button.active {
            background: <?php echo $primary_color; ?>;
            color: white;
            border-color: <?php echo $primary_color; ?>;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f8f9fa;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        th {
            font-weight: 600;
            color: #666;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        tbody tr {
            transition: background 0.2s;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
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
        
        .action-btn {
            padding: 8px 16px;
            background: <?php echo $primary_color; ?>;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
        }
        
        .action-btn:hover {
            opacity: 0.8;
            transform: translateY(-2px);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .empty-state h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #666;
        }
        
        .empty-state p {
            color: #999;
            margin-bottom: 20px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            table {
                font-size: 12px;
            }
            
            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>📋 Contracte</h1>
                <p><?php echo htmlspecialchars($site_name); ?></p>
            </div>
            <div class="header-actions">
                <a href="<?php echo SUBFOLDER . '/' . $site_slug . '/templates.php'; ?>">📄 Template-uri</a>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                ✅ Contract trimis cu succes! Destinatarul va primi un email cu link-ul de semnare.
            </div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="number"><?php echo $stats['total']; ?></div>
                <div class="label">Total</div>
            </div>
            <div class="stat-card draft">
                <div class="number"><?php echo $stats['draft']; ?></div>
                <div class="label">Draft</div>
            </div>
            <div class="stat-card sent">
                <div class="number"><?php echo $stats['sent']; ?></div>
                <div class="label">Trimise</div>
            </div>
            <div class="stat-card signed">
                <div class="number"><?php echo $stats['signed']; ?></div>
                <div class="label">Semnate</div>
            </div>
        </div>
        
        <div class="contracts-table">
            <div class="table-header">
                <h2>Lista Contracte</h2>
                <div class="filter-tabs">
                    <button class="active" onclick="filterContracts('all')">Toate</button>
                    <button onclick="filterContracts('sent')">Trimise</button>
                    <button onclick="filterContracts('signed')">Semnate</button>
                </div>
            </div>
            
            <?php if (empty($contracts)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <h3>Niciun contract încă</h3>
                    <p>Începeți prin a crea un template și trimiteți primul contract</p>
                    <a href="<?php echo SUBFOLDER . '/' . $site_slug . '/templates.php'; ?>" class="action-btn">
                        Creează Template
                    </a>
                </div>
            <?php else: ?>
                <table id="contractsTable">
                    <thead>
                        <tr>
                            <th>Destinatar</th>
                            <th>Template</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Data Trimitere</th>
                            <th>Data Semnare</th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contracts as $contract): ?>
                            <?php 
                            $status = $contract['status'] ?? 'draft';
                            $status_labels = [
                                'draft' => '📝 Draft',
                                'sent' => '📤 Trimis',
                                'signed' => '✅ Semnat'
                            ];
                            ?>
                            <tr data-status="<?php echo $status; ?>">
                                <td><strong><?php echo htmlspecialchars($contract['recipient_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($contract['template_name']); ?></td>
                                <td><?php echo htmlspecialchars($contract['recipient_email']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $status; ?>">
                                        <?php echo $status_labels[$status] ?? '❓ Unknown'; ?>
                                    </span>
                                </td>
                                <td><?php echo $contract['sent_date'] ?: '-'; ?></td>
                                <td><?php echo $contract['signed_date'] ?: '-'; ?></td>
                                <td>
                                    <a href="<?php echo SUBFOLDER . '/' . $site_slug . '/contract_detail.php?id=' . $contract['id']; ?>" class="action-btn">
                                        Vezi Detalii
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function filterContracts(status) {
            const rows = document.querySelectorAll('#contractsTable tbody tr');
            const buttons = document.querySelectorAll('.filter-tabs button');
            
            // Update active button
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Filter rows
            rows.forEach(row => {
                if (status === 'all' || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>