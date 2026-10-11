<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

$asset_id = (int) ($_GET['id'] ?? 0);

if ($asset_id <= 0) {
    http_response_code(400);
    die('Invalid asset ID.');
}

$asset = get_asset_by_id($pdo, $asset_id);
if (!$asset) {
    http_response_code(404);
    die('Asset not found.');
}

$qr_url = $BASE_URL . '/asset_qr.php?id=' . $asset_id . '&token=' . generate_qr_token($asset_id);

$options = new QROptions([
    'outputType'    => QRCode::OUTPUT_IMAGE_PNG,
    'outputBase64'  => true,
    'scale'         => 8,
    'quietzoneSize' => 2,
]);
$qr_data_uri = (new QRCode($options))->render($qr_url);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Label - <?= e($asset['asset_tag']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        @media screen {
            body {
                background: #f1f5f9;
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            }
            .print-controls {
                position: sticky;
                top: 0;
                z-index: 100;
                background: #fff;
                border-bottom: 1px solid #e2e8f0;
                padding: 1rem;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            }
            .print-area {
                padding: 2rem;
                display: flex;
                flex-wrap: wrap;
                gap: 2rem;
                justify-content: center;
            }
            .label-section {
                background: #fff;
                border-radius: 0.75rem;
                padding: 1.5rem;
                box-shadow: 0 4px 6px rgba(0,0,0,0.07);
            }
            .label-section h5 {
                margin-bottom: 1rem;
                color: #1e293b;
                font-weight: 600;
            }
        }

        @page {
            size: A4;
            margin: 10mm;
        }

        @media print {
            body {
                background: #fff;
                margin: 0;
                padding: 0;
            }
            .print-controls {
                display: none !important;
            }
            .print-area {
                padding: 0;
                display: block;
            }
            .label-section {
                box-shadow: none;
                border: none;
                padding: 0;
                margin-bottom: 10mm;
                page-break-inside: avoid;
            }
            .label-section:last-child {
                margin-bottom: 0;
            }
        }

        /* ============================================
           STICKER LABEL - 50mm x 50mm
           ============================================ */
        .sticker-label {
            width: 50mm;
            height: 50mm;
            border: 1.5px solid #000;
            border-radius: 2mm;
            padding: 2mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            box-sizing: border-box;
            background: #fff;
        }

        .sticker-label .qr-img {
            width: 30mm;
            height: 30mm;
        }

        .sticker-label .asset-tag {
            font-size: 10pt;
            font-weight: 700;
            margin-top: 1mm;
            letter-spacing: 0.5px;
        }

        .sticker-label .asset-name {
            font-size: 7pt;
            color: #555;
            margin-top: 0.5mm;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sticker-label .org-name {
            font-size: 6pt;
            color: #888;
            margin-top: 0.5mm;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        /* ============================================
           BAG TAG - 85mm x 55mm (credit card size)
           ============================================ */
        .bag-tag {
            width: 85mm;
            height: 55mm;
            border: 1.5px solid #000;
            border-radius: 3mm;
            padding: 3mm;
            display: flex;
            box-sizing: border-box;
            background: #fff;
            position: relative;
        }

        .bag-tag .hole-punch {
            position: absolute;
            top: 4mm;
            left: 4mm;
            width: 6mm;
            height: 6mm;
            border: 1.5px solid #000;
            border-radius: 50%;
            background: #fff;
        }

        .bag-tag .tag-content {
            flex: 1;
            display: flex;
            padding-left: 8mm;
        }

        .bag-tag .tag-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding-right: 2mm;
        }

        .bag-tag .tag-qr {
            display: flex;
            align-items: center;
        }

        .bag-tag .tag-qr img {
            width: 30mm;
            height: 30mm;
        }

        .bag-tag .tag-org {
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #666;
            margin-bottom: 1mm;
            font-weight: 600;
        }

        .bag-tag .tag-asset-tag {
            font-size: 16pt;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1mm;
        }

        .bag-tag .tag-name {
            font-size: 8pt;
            color: #333;
            margin-bottom: 1mm;
        }

        .bag-tag .tag-detail {
            font-size: 7pt;
            color: #666;
        }

        .bag-tag .tag-detail strong {
            color: #333;
        }

        /* ============================================
           LARGE STICKER - 70mm x 50mm
           ============================================ */
        .sticker-large {
            width: 70mm;
            height: 50mm;
            border: 1.5px solid #000;
            border-radius: 2mm;
            padding: 2mm;
            display: flex;
            box-sizing: border-box;
            background: #fff;
        }

        .sticker-large .sl-qr img {
            width: 35mm;
            height: 35mm;
        }

        .sticker-large .sl-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding-left: 3mm;
        }

        .sticker-large .sl-asset-tag {
            font-size: 14pt;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1mm;
        }

        .sticker-large .sl-name {
            font-size: 8pt;
            color: #333;
            margin-bottom: 1mm;
        }

        .sticker-large .sl-detail {
            font-size: 7pt;
            color: #666;
        }

        .sticker-large .sl-org {
            font-size: 6pt;
            color: #888;
            text-transform: uppercase;
            margin-top: 2mm;
            letter-spacing: 0.3px;
        }

        /* Multi-copy for stickers */
        .sticker-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 5mm;
            justify-content: flex-start;
        }

        .sticker-grid .sticker-label,
        .sticker-grid .sticker-large {
            flex-shrink: 0;
        }

        @media print {
            .sticker-grid {
                gap: 3mm;
            }
        }
    </style>
</head>
<body>

<!-- Print Controls (screen only) -->
<div class="print-controls">
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h5 class="mb-0"><i class="bi bi-printer"></i> Print Labels for <?= e($asset['asset_tag']) ?></h5>
                <small class="text-muted"><?= e($asset['name']) ?></small>
            </div>
            <div class="d-flex gap-2">
                <a href="assets.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print All
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Printable Area -->
<div class="print-area">

    <!-- Section 1: Small Sticker (50x50mm) - x3 copies -->
    <div class="label-section">
        <h5><i class="bi bi-upc-scan"></i> Small Sticker (50 x 50mm) &mdash; 3 copies</h5>
        <div class="sticker-grid">
            <?php for ($i = 0; $i < 3; $i++): ?>
            <div class="sticker-label">
                <img src="<?= $qr_data_uri ?>" alt="QR" class="qr-img">
                <div class="asset-tag"><?= e($asset['asset_tag']) ?></div>
                <div class="asset-name"><?= e($asset['name']) ?></div>
                <div class="org-name">ICT Asset Management</div>
            </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Section 2: Large Sticker (70x50mm) - x2 copies -->
    <div class="label-section">
        <h5><i class="bi bi-upc"></i> Large Sticker (70 x 50mm) &mdash; 2 copies</h5>
        <div class="sticker-grid">
            <?php for ($i = 0; $i < 2; $i++): ?>
            <div class="sticker-large">
                <div class="sl-qr">
                    <img src="<?= $qr_data_uri ?>" alt="QR">
                </div>
                <div class="sl-info">
                    <div class="sl-asset-tag"><?= e($asset['asset_tag']) ?></div>
                    <div class="sl-name"><?= e($asset['name']) ?></div>
                    <?php if ($asset['brand'] || $asset['model']): ?>
                    <div class="sl-detail"><?= e(trim(($asset['brand'] ?? '') . ' ' . ($asset['model'] ?? ''))) ?></div>
                    <?php endif; ?>
                    <?php if ($asset['serial_no']): ?>
                    <div class="sl-detail">S/N: <?= e($asset['serial_no']) ?></div>
                    <?php endif; ?>
                    <div class="sl-org">ICT Asset Management</div>
                </div>
            </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Section 3: Bag Tag (85x55mm) - x2 copies -->
    <div class="label-section">
        <h5><i class="bi bi-luggage"></i> Bag Tag (85 x 55mm) &mdash; 2 copies</h5>
        <div class="sticker-grid">
            <?php for ($i = 0; $i < 2; $i++): ?>
            <div class="bag-tag">
                <div class="hole-punch"></div>
                <div class="tag-content">
                    <div class="tag-info">
                        <div class="tag-org">ICT Asset Management</div>
                        <div class="tag-asset-tag"><?= e($asset['asset_tag']) ?></div>
                        <div class="tag-name"><?= e($asset['name']) ?></div>
                        <?php if ($asset['brand'] || $asset['model']): ?>
                        <div class="tag-detail"><strong><?= e(trim(($asset['brand'] ?? '') . ' ' . ($asset['model'] ?? ''))) ?></strong></div>
                        <?php endif; ?>
                        <?php if ($asset['serial_no']): ?>
                        <div class="tag-detail">S/N: <?= e($asset['serial_no']) ?></div>
                        <?php endif; ?>
                        <div class="tag-detail" style="margin-top:1mm;font-style:italic;">
                            If found, please return to ICT Dept.
                        </div>
                    </div>
                    <div class="tag-qr">
                        <img src="<?= $qr_data_uri ?>" alt="QR">
                    </div>
                </div>
            </div>
            <?php endfor; ?>
        </div>
    </div>

</div>

<script>
// Auto-trigger print on load if ?print=1
if (new URLSearchParams(window.location.search).get('print') === '1') {
    window.addEventListener('load', function() { setTimeout(function() { window.print(); }, 300); });
}
</script>
</body>
</html>
