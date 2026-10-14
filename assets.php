<?php
declare(strict_types=1);

ob_start();
session_start();

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/functions.php';

if (isset($_GET['ajax']) && $_GET['ajax'] === 'generate_tag') {
    ob_end_clean();
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not logged in']);
        exit;
    }
    
    $category = $_GET['category'] ?? '';
    $brand = $_GET['brand'] ?? '';
    
    if ($category === '' || !in_array($category, asset_categories(), true)) {
        echo json_encode(['error' => 'Invalid category']);
        exit;
    }
    
    $tag = generate_asset_tag($pdo, $category, $brand);
    echo json_encode(['tag' => $tag]);
    exit;
}

ob_end_flush();

require __DIR__ . '/includes/header.php';
require_login();

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

$action = $_GET['action'] ?? 'list';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid request. Please try again.';
    } else {
        $post_action = $_POST['action'] ?? '';

        if ($post_action === 'add') {
            $data = [
                'asset_tag' => trim($_POST['asset_tag'] ?? ''),
                'name'      => ucwords(strtolower(trim($_POST['name'] ?? ''))),
                'category'  => $_POST['category'] ?? '',
                'brand'     => trim($_POST['brand'] ?? ''),
                'model'     => trim($_POST['model'] ?? ''),
                'serial_no' => trim($_POST['serial_no'] ?? ''),
                'notes'     => trim($_POST['notes'] ?? ''),
            ];
            if ($data['asset_tag'] === '' || $data['name'] === '' || $data['category'] === '') {
                $error = 'Asset tag, name, and category are required.';
            } elseif (!asset_tag_is_unique($pdo, $data['asset_tag'])) {
                $error = 'Asset tag already exists.';
            } elseif (!in_array($data['category'], asset_categories(), true)) {
                $error = 'Invalid category.';
            } else {
                try {
                    create_asset($pdo, $data);
                    set_flash('success', 'Asset "' . e($data['name']) . '" added successfully.');
                    header('Location: assets.php');
                    exit;
                } catch (Exception $ex) {
                    $error = 'Failed to add asset: ' . $ex->getMessage();
                }
            }
        }

        if ($post_action === 'edit') {
            $id = (int) ($_POST['id'] ?? 0);
            $data = [
                'asset_tag' => trim($_POST['asset_tag'] ?? ''),
                'name'      => ucwords(strtolower(trim($_POST['name'] ?? ''))),
                'category'  => $_POST['category'] ?? '',
                'brand'     => trim($_POST['brand'] ?? ''),
                'model'     => trim($_POST['model'] ?? ''),
                'serial_no' => trim($_POST['serial_no'] ?? ''),
                'status'    => $_POST['status'] ?? 'available',
                'notes'     => trim($_POST['notes'] ?? ''),
            ];
            $asset = get_asset_by_id($pdo, $id);
            if (!$asset) {
                $error = 'Asset not found.';
            } elseif ($data['asset_tag'] === '' || $data['name'] === '' || $data['category'] === '') {
                $error = 'Asset tag, name, and category are required.';
            } elseif (!asset_tag_is_unique($pdo, $data['asset_tag'], $id)) {
                $error = 'Asset tag already exists.';
            } elseif (!in_array($data['category'], asset_categories(), true)) {
                $error = 'Invalid category.';
            } else {
                try {
                    update_asset($pdo, $id, $data);
                    set_flash('success', 'Asset updated successfully.');
                    header('Location: assets.php');
                    exit;
                } catch (Exception $ex) {
                    $error = 'Failed to update asset: ' . $ex->getMessage();
                }
            }
        }

        if ($post_action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $asset = get_asset_by_id($pdo, $id);
            if (!$asset) {
                $error = 'Asset not found.';
            } else {
                try {
                    delete_asset($pdo, $id);
                    set_flash('success', 'Asset "' . e($asset['name']) . '" deleted.');
                    header('Location: assets.php');
                    exit;
                } catch (Exception $ex) {
                    $error = 'Failed to delete asset: ' . $ex->getMessage();
                }
            }
        }
    }
}

function generate_qr_data_uri(string $url): string {
    try {
        $options = new QROptions([
            'outputType'    => QRCode::OUTPUT_IMAGE_PNG,
            'outputBase64'  => true,
            'scale'         => 3,
            'quietzoneSize' => 1,
        ]);
        return (new QRCode($options))->render($url);
    } catch (Exception $e) {
        return '';
    }
}

$filter_search = $_GET['search'] ?? '';
$filter_category = $_GET['category'] ?? '';
?>

<?php render_flash(); ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= e($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
<div class="page-header">
    <h2><i class="bi bi-box"></i> Assets</h2>
    <a href="assets.php?action=add" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Add Asset
    </a>
</div>

<!-- Filter Bar -->
<form method="get" action="assets.php" class="filter-bar">
    <div class="filter-group">
        <label>Search</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control" name="search" placeholder="Tag, name, brand, serial..."
                   value="<?= e($filter_search) ?>">
        </div>
    </div>
    <div class="filter-group" style="min-width:150px;flex:0.5;">
        <label>Category</label>
        <select class="form-select" name="category">
            <option value="">All Categories</option>
            <?php foreach (asset_categories() as $cat): ?>
                <option value="<?= e($cat) ?>" <?= $filter_category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-actions">
        <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
        <?php if ($filter_search || $filter_category): ?>
            <a href="assets.php" class="btn btn-outline-secondary">Clear</a>
        <?php endif; ?>
    </div>
</form>

<!-- Assets Table -->
<div class="table-card">
    <div class="table-responsive">
        <table class="table-modern table-card-view">
            <thead>
                <tr>
                    <th>QR</th>
                    <th>Asset Tag</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Brand / Model</th>
                    <th>Serial No</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $assets = get_all_assets($pdo, $filter_search, $filter_category);
                if (empty($assets)): ?>
                    <tr><td colspan="8">
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>No assets found.</p>
                        </div>
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($assets as $asset):
                        $qr_url = $BASE_URL . '/asset_qr.php?id=' . $asset['id'] . '&token=' . generate_qr_token((int) $asset['id']);
                        $qr_data_uri = generate_qr_data_uri($qr_url);
                    ?>
                    <tr>
                        <td data-label="QR">
                            <?php if ($qr_data_uri): ?>
                            <img src="<?= $qr_data_uri ?>" alt="QR" class="qr-thumbnail"
                                 data-bs-toggle="modal" data-bs-target="#qrModal<?= (int) $asset['id'] ?>">
                            <div class="modal fade" id="qrModal<?= (int) $asset['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">QR - <?= e($asset['asset_tag']) ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body text-center">
                                            <img src="<?= $qr_data_uri ?>" alt="QR Code" class="qr-modal-img mb-3">
                                            <p class="text-muted" style="font-size:var(--fs-sm);">Scan to view status or return asset</p>
                                            <p class="mb-1"><strong><?= e($asset['asset_tag']) ?></strong> &mdash; <?= e($asset['name']) ?></p>
                                            <p><span class="badge-status badge-<?= $asset['status'] ?>"><?= e(ucfirst(str_replace('_',' ',$asset['status']))) ?></span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td data-label="Asset Tag"><strong><?= e($asset['asset_tag']) ?></strong></td>
                        <td data-label="Name"><?= e($asset['name']) ?></td>
                        <td data-label="Category"><?= e($asset['category']) ?></td>
                        <td data-label="Brand/Model"><?= e(trim($asset['brand'] . ' ' . $asset['model'])) ?></td>
                        <td data-label="Serial No"><?= e($asset['serial_no'] ?? '-') ?></td>
                        <td data-label="Status"><span class="badge-status badge-<?= $asset['status'] ?>"><?= e(ucfirst(str_replace('_',' ',$asset['status']))) ?></span></td>
                        <td data-label="Actions" class="td-actions">
                            <div class="d-flex gap-1">
                                <a href="assets.php?action=edit&id=<?= (int) $asset['id'] ?>" class="btn btn-sm btn-outline-primary btn-icon" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="print_label.php?id=<?= (int) $asset['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary btn-icon" title="Print Label">
                                    <i class="bi bi-printer"></i>
                                </a>
                                <form method="post" action="assets.php" class="d-inline"
                                      onsubmit="return confirmDelete('Delete asset <?= e($asset['asset_tag']) ?>?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $asset['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger btn-icon" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($assets)): ?>
    <div class="card-footer">Showing <?= count($assets) ?> asset(s)</div>
    <?php endif; ?>
</div>

<?php elseif ($action === 'add'): ?>
<div class="page-header">
    <h2><i class="bi bi-plus-lg"></i> Add Asset</h2>
</div>
<div class="card">
    <div class="card-body">
        <form method="post" action="assets.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
            <?php require __DIR__ . '/includes/asset_form.php'; ?>
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Asset</button>
                <a href="assets.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php elseif ($action === 'edit'): ?>
<?php
$asset = get_asset_by_id($pdo, (int) ($_GET['id'] ?? 0));
if (!$asset) {
    echo '<div class="alert alert-danger">Asset not found.</div>';
} else {
?>
<div class="page-header">
    <h2><i class="bi bi-pencil"></i> Edit: <?= e($asset['asset_tag']) ?></h2>
</div>
<div class="card">
    <div class="card-body">
        <form method="post" action="assets.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= (int) $asset['id'] ?>">
            <?php require __DIR__ . '/includes/asset_form.php'; ?>
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Update Asset</button>
                <a href="assets.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php } ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
