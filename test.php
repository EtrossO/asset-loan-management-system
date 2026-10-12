<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';

$checks = [];
$all_ok = true;

try {
    $pdo->query("SELECT 1");
    $checks[] = ['label' => 'Database Connection', 'status' => 'ok', 'detail' => 'Connected to MySQL'];
} catch (Exception $e) {
    $checks[] = ['label' => 'Database Connection', 'status' => 'fail', 'detail' => $e->getMessage()];
    $all_ok = false;
}

$server_ip = $_SERVER['SERVER_ADDR'] ?? 'unknown';
$checks[] = ['label' => 'Server IP', 'status' => 'ok', 'detail' => $server_ip];

$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$checks[] = ['label' => 'Your Device IP', 'status' => 'ok', 'detail' => $client_ip];

$checks[] = ['label' => 'BASE_URL Config', 'status' => 'ok', 'detail' => $BASE_URL];

if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    $checks[] = ['label' => 'Connection', 'status' => 'ok', 'detail' => 'HTTPS'];
} else {
    $checks[] = ['label' => 'Connection', 'status' => 'ok', 'detail' => 'HTTP (Local Network)'];
}

$server_name = gethostname();
$checks[] = ['label' => 'Server Name', 'status' => 'ok', 'detail' => $server_name];

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM assets");
    $asset_count = (int) $stmt->fetchColumn();
    $checks[] = ['label' => 'Assets in System', 'status' => 'ok', 'detail' => $asset_count . ' asset(s)'];
} catch (Exception $e) {
    $checks[] = ['label' => 'Assets in System', 'status' => 'warn', 'detail' => 'Could not read'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connection Test - ICT Asset Loan System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f1f5f9; font-family: 'Inter', system-ui, sans-serif; }
        .test-container { max-width: 500px; margin: 40px auto; padding: 20px; }
        .status-card { background: white; border-radius: 16px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); margin-bottom: 20px; text-align: center; }
        .status-icon { font-size: 64px; margin-bottom: 16px; }
        .status-icon.ok { color: #16a34a; }
        .status-icon.fail { color: #dc2626; }
        .check-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .check-item:last-child { border-bottom: none; }
        .check-label { color: #64748b; }
        .check-detail { font-weight: 600; color: #1e293b; text-align: right; }
        .instruction-card { background: white; border-radius: 16px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .instruction-card h5 { margin-bottom: 16px; color: #1e293b; }
        .instruction-step { display: flex; gap: 12px; margin-bottom: 16px; }
        .step-num { width: 28px; height: 28px; border-radius: 50%; background: #0369a1; color: white; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; flex-shrink: 0; }
        .step-text { font-size: 14px; color: #475569; line-height: 1.6; padding-top: 2px; }
        .refresh-btn { display: inline-block; margin-top: 12px; padding: 8px 24px; background: #0369a1; color: white; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 500; }
    </style>
</head>
<body>

<div class="test-container">

    <div class="status-card">
        <?php if ($all_ok): ?>
            <div class="status-icon ok"><i class="bi bi-check-circle-fill"></i></div>
            <h3 style="color: #16a34a; margin: 0;">Connected Successfully!</h3>
            <p style="color: #64748b; margin: 8px 0 0; font-size: 14px;">Your device can access the ICT Asset Loan System</p>
        <?php else: ?>
            <div class="status-icon fail"><i class="bi bi-x-circle-fill"></i></div>
            <h3 style="color: #dc2626; margin: 0;">Connection Failed</h3>
            <p style="color: #64748b; margin: 8px 0 0; font-size: 14px;">Please contact your system administrator</p>
        <?php endif; ?>
        <a href="test.php" class="refresh-btn"><i class="bi bi-arrow-clockwise"></i> Re-test</a>
    </div>

    <div class="status-card" style="text-align: left;">
        <h5 style="margin-bottom: 16px; font-size: 16px;"><i class="bi bi-info-circle"></i> System Information</h5>
        <?php foreach ($checks as $check): ?>
        <div class="check-item">
            <span class="check-label"><?= e($check['label']) ?></span>
            <span class="check-detail"><?= e($check['detail']) ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="instruction-card">
        <h5><i class="bi bi-qr-code-scan"></i> How to Scan QR Code</h5>
        <div class="instruction-step">
            <div class="step-num">1</div>
            <div class="step-text">Make sure your phone is connected to the <strong>same WiFi network</strong> as this system</div>
        </div>
        <div class="instruction-step">
            <div class="step-num">2</div>
            <div class="step-text">Open your phone camera and point it at the <strong>QR code</strong> on the asset</div>
        </div>
        <div class="instruction-step">
            <div class="step-num">3</div>
            <div class="step-text">Tap the notification/link that appears to open the <strong>asset status page</strong></div>
        </div>
        <div class="instruction-step">
            <div class="step-num">4</div>
            <div class="step-text">Use the buttons to <strong>loan</strong> or <strong>return</strong> the asset</div>
        </div>

        <div style="margin-top: 16px; padding: 12px; background: #fef3c7; border-radius: 8px; font-size: 13px; color: #92400e;">
            <i class="bi bi-exclamation-triangle"></i> <strong>Tip:</strong> If the page doesn't load, make sure you're on the correct WiFi and try refreshing.
        </div>
    </div>

    <p class="text-center" style="color: #94a3b8; font-size: 12px; margin-top: 24px;">
        ICT Asset Loan Management System &mdash; <?= date('Y') ?>
    </p>

</div>

</body>
</html>
