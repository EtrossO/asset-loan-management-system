<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

$asset_id = (int) ($_GET['id'] ?? 0);
$token = $_GET['token'] ?? '';

if ($asset_id <= 0 || $token === '') {
    http_response_code(400);
    die('Invalid request.');
}

$asset = get_asset_by_token($pdo, $asset_id, $token);
if (!$asset) {
    http_response_code(403);
    die('Invalid or expired token.');
}

$active_loan = get_active_loan_for_asset($pdo, $asset_id);
$loan_history = get_asset_loan_history($pdo, $asset_id);
$borrowers = get_all_active_borrowers($pdo);

$qr_url = $BASE_URL . '/asset_qr.php?id=' . $asset_id . '&token=' . $token;
$options = new QROptions([
    'outputType'    => QRCode::OUTPUT_IMAGE_PNG,
    'outputBase64'  => true,
    'scale'         => 5,
    'quietzoneSize' => 2,
]);
$qr_data_uri = (new QRCode($options))->render($qr_url);

$flash = '';
$flash_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['action'] ?? '';
    
    if ($post_action === 'return_asset' && $active_loan) {
        $return_date = trim($_POST['return_date'] ?? date('Y-m-d'));
        $condition_in = trim($_POST['condition_in'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $new_status = $_POST['new_status'] ?? 'available';
        if (!is_valid_date($return_date)) {
            $flash = 'Invalid return date.';
            $flash_type = 'danger';
        } elseif ($condition_in === '') {
            $flash = 'Condition on return is required.';
            $flash_type = 'danger';
        } else {
            try {
                return_loan($pdo, (int) $active_loan['id'], $return_date, $condition_in, $notes, $new_status);
                $flash = 'Asset returned successfully!';
                $flash_type = 'success';
                $active_loan = null;
                $asset = get_asset_by_token($pdo, $asset_id, $token);
                $loan_history = get_asset_loan_history($pdo, $asset_id);
            } catch (Exception $ex) {
                $flash = 'Failed to return asset: ' . $ex->getMessage();
                $flash_type = 'danger';
            }
        }
    }
    
    if ($post_action === 'create_borrower_and_loan' && !$active_loan && $asset['status'] === 'available') {
        $b_name = trim($_POST['b_name'] ?? '');
        $b_id_number = trim($_POST['b_id_number'] ?? '');
        $b_department = trim($_POST['b_department'] ?? '');
        $b_phone = trim($_POST['b_phone'] ?? '');
        $b_email = trim($_POST['b_email'] ?? '');
        $loan_date = trim($_POST['loan_date'] ?? date('Y-m-d'));
        $due_date = trim($_POST['due_date'] ?? date('Y-m-d', strtotime('+7 days')));
        $condition_out = trim($_POST['condition_out'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        if ($b_name === '') {
            $flash = 'Borrower name is required.';
            $flash_type = 'danger';
        } elseif (!is_valid_date($loan_date)) {
            $flash = 'Invalid loan date.';
            $flash_type = 'danger';
        } elseif (!is_valid_date($due_date)) {
            $flash = 'Invalid due date.';
            $flash_type = 'danger';
        } elseif ($condition_out === '') {
            $flash = 'Condition out is required.';
            $flash_type = 'danger';
        } else {
            try {
                $borrower_data = [
                    'name' => $b_name,
                    'id_number' => $b_id_number,
                    'department' => $b_department,
                    'phone' => $b_phone,
                    'email' => $b_email,
                ];
                $new_borrower_id = create_borrower($pdo, $borrower_data);
                $issued_by = $_SESSION['user_id'] ?? 1;
                create_loan($pdo, $asset_id, $new_borrower_id, $loan_date, $due_date, $condition_out, $notes, $issued_by);
                $flash = 'New borrower added and loan issued successfully!';
                $flash_type = 'success';
                $active_loan = get_active_loan_for_asset($pdo, $asset_id);
                $loan_history = get_asset_loan_history($pdo, $asset_id);
                $borrowers = get_all_active_borrowers($pdo);
            } catch (Exception $ex) {
                $flash = 'Failed to create borrower/loan: ' . $ex->getMessage();
                $flash_type = 'danger';
            }
        }
    }
    
    if ($post_action === 'issue_loan' && !$active_loan && $asset['status'] === 'available') {
        $borrower_id = (int) ($_POST['borrower_id'] ?? 0);
        $loan_date = trim($_POST['loan_date'] ?? date('Y-m-d'));
        $due_date = trim($_POST['due_date'] ?? date('Y-m-d', strtotime('+7 days')));
        $condition_out = trim($_POST['condition_out'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        $borrower = get_borrower_by_id($pdo, $borrower_id);
        if (!$borrower) {
            $flash = 'Invalid borrower selected.';
            $flash_type = 'danger';
        } elseif (!is_valid_date($loan_date)) {
            $flash = 'Invalid loan date.';
            $flash_type = 'danger';
        } elseif (!is_valid_date($due_date)) {
            $flash = 'Invalid due date.';
            $flash_type = 'danger';
        } elseif ($condition_out === '') {
            $flash = 'Condition out is required.';
            $flash_type = 'danger';
        } else {
            try {
                $issued_by = $_SESSION['user_id'] ?? 1;
                create_loan($pdo, $asset_id, $borrower_id, $loan_date, $due_date, $condition_out, $notes, $issued_by);
                $flash = 'Loan issued successfully!';
                $flash_type = 'success';
                $active_loan = get_active_loan_for_asset($pdo, $asset_id);
                $loan_history = get_asset_loan_history($pdo, $asset_id);
            } catch (Exception $ex) {
                $flash = 'Failed to issue loan: ' . $ex->getMessage();
                $flash_type = 'danger';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset: <?= e($asset['asset_tag']) ?> - <?= e($asset['name']) ?></title>
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg?v=<?= time() ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body style="background:var(--clr-bg);">

<div style="max-width:600px;margin:0 auto;padding:var(--space-lg);">

    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash_type) ?> alert-dismissible fade show mb-3">
            <?= e($flash) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Asset Info Card -->
    <div class="card mb-3" style="overflow:hidden;">
        <div class="public-qr-header">
            <h4><i class="bi bi-box-seam-fill"></i> Asset Status</h4>
            <p>Scan to return or check status</p>
        </div>
        <div class="card-body text-center">
            <img src="<?= $qr_data_uri ?>" alt="QR Code" class="qr-modal-img mb-3">
            <h4 style="margin:0;font-weight:700;"><?= e($asset['asset_tag']) ?></h4>
            <p style="color:var(--clr-text-secondary);margin:0.25rem 0 0.75rem;"><?= e($asset['name']) ?></p>
            <span class="badge-status badge-<?= $asset['status'] ?>" style="font-size:var(--fs-sm);padding:0.4em 0.8em;">
                <?= e(ucfirst(str_replace('_',' ',$asset['status']))) ?>
            </span>

            <hr style="border-color:var(--clr-border-light);margin:var(--space-md) 0;">

            <table style="width:100%;font-size:var(--fs-sm);">
                <tr>
                    <td style="padding:0.375rem 0;color:var(--clr-text-secondary);width:40%;">Category</td>
                    <td style="padding:0.375rem 0;font-weight:500;"><?= e($asset['category']) ?></td>
                </tr>
                <?php if ($asset['brand'] || $asset['model']): ?>
                <tr>
                    <td style="padding:0.375rem 0;color:var(--clr-text-secondary);">Brand / Model</td>
                    <td style="padding:0.375rem 0;font-weight:500;"><?= e(trim(($asset['brand'] ?? '') . ' ' . ($asset['model'] ?? ''))) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($asset['serial_no']): ?>
                <tr>
                    <td style="padding:0.375rem 0;color:var(--clr-text-secondary);">Serial No</td>
                    <td style="padding:0.375rem 0;font-weight:500;"><?= e($asset['serial_no']) ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- Loan Info or Status -->
    <?php if ($active_loan): ?>
    <?php $is_overdue = strtotime($active_loan['due_date']) < strtotime('today'); ?>
    <div class="card mb-3">
        <div class="card-header" style="background:var(--clr-warning-light);border-bottom:1px solid var(--clr-border);">
            <strong style="color:var(--clr-warning);"><i class="bi bi-clipboard-check"></i> Currently On Loan</strong>
        </div>
        <div class="card-body">
            <div style="font-size:var(--fs-sm);margin-bottom:0.75rem;padding-bottom:0.75rem;border-bottom:1px solid var(--clr-border-light);">
                <strong style="font-size:var(--fs-base);color:var(--clr-text);"><i class="bi bi-person-fill"></i> Borrower Info</strong>
            </div>
            <table style="width:100%;font-size:var(--fs-sm);">
                <tr>
                    <td style="padding:0.375rem 0;color:var(--clr-text-secondary);width:40%;">Name</td>
                    <td style="padding:0.375rem 0;font-weight:600;"><?= e($active_loan['borrower_name']) ?></td>
                </tr>
                <?php if ($active_loan['borrower_id_number']): ?>
                <tr>
                    <td style="padding:0.375rem 0;color:var(--clr-text-secondary);">ID Number</td>
                    <td style="padding:0.375rem 0;font-weight:500;"><?= e($active_loan['borrower_id_number']) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($active_loan['borrower_dept']): ?>
                <tr>
                    <td style="padding:0.375rem 0;color:var(--clr-text-secondary);">Department</td>
                    <td style="padding:0.375rem 0;font-weight:500;"><?= e($active_loan['borrower_dept']) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($active_loan['borrower_phone']): ?>
                <tr>
                    <td style="padding:0.375rem 0;color:var(--clr-text-secondary);">Phone</td>
                    <td style="padding:0.375rem 0;font-weight:500;"><a href="tel:<?= e($active_loan['borrower_phone']) ?>" style="color:var(--clr-primary);text-decoration:none;"><i class="bi bi-telephone"></i> <?= e($active_loan['borrower_phone']) ?></a></td>
                </tr>
                <?php endif; ?>
                <?php if ($active_loan['borrower_email']): ?>
                <tr>
                    <td style="padding:0.375rem 0;color:var(--clr-text-secondary);">Email</td>
                    <td style="padding:0.375rem 0;font-weight:500;"><a href="mailto:<?= e($active_loan['borrower_email']) ?>" style="color:var(--clr-primary);text-decoration:none;word-break:break-all;"><i class="bi bi-envelope"></i> <?= e($active_loan['borrower_email']) ?></a></td>
                </tr>
                <?php endif; ?>
            </table>

            <div style="font-size:var(--fs-sm);margin-top:1rem;margin-bottom:0.75rem;padding-bottom:0.75rem;border-bottom:1px solid var(--clr-border-light);">
                <strong style="font-size:var(--fs-base);color:var(--clr-text);"><i class="bi bi-calendar-event"></i> Loan Details</strong>
            </div>
            <table style="width:100%;font-size:var(--fs-sm);">
                <?php if ($active_loan['issued_by_name']): ?>
                <tr>
                    <td style="padding:0.375rem 0;color:var(--clr-text-secondary);width:40%;">Issued By</td>
                    <td style="padding:0.375rem 0;font-weight:500;"><?= e($active_loan['issued_by_name']) ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td style="padding:0.375rem 0;color:var(--clr-text-secondary);">Loan Date</td>
                    <td style="padding:0.375rem 0;font-weight:500;"><?= e($active_loan['loan_date']) ?></td>
                </tr>
                <tr>
                    <td style="padding:0.375rem 0;color:var(--clr-text-secondary);">Due Date</td>
                    <td style="padding:0.375rem 0;font-weight:600;<?= $is_overdue ? 'color:var(--clr-danger);' : '' ?>">
                        <?= e($active_loan['due_date']) ?>
                        <?php if ($is_overdue): ?>
                            <span class="overdue-days ms-1">
                                OVERDUE (<?= (int) ((time() - strtotime($active_loan['due_date'])) / 86400) ?>d)
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ($active_loan['condition_out']): ?>
                <tr>
                    <td style="padding:0.375rem 0;color:var(--clr-text-secondary);">Condition Out</td>
                    <td style="padding:0.375rem 0;font-weight:500;"><?= e($active_loan['condition_out']) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($active_loan['notes']): ?>
                <tr>
                    <td style="padding:0.375rem 0;color:var(--clr-text-secondary);vertical-align:top;">Notes</td>
                    <td style="padding:0.375rem 0;font-weight:500;white-space:pre-line;"><?= nl2br(e($active_loan['notes'])) ?></td>
                </tr>
                <?php endif; ?>
            </table>
            <div class="text-center mt-3">
                <button type="button" class="btn btn-warning btn-lg w-100" data-bs-toggle="modal" data-bs-target="#returnModal">
                    <i class="bi bi-arrow-return-left"></i> Return This Asset
                </button>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="card mb-3">
        <div class="card-body text-center" style="padding:var(--space-xl);">
            <?php if ($asset['status'] === 'available'): ?>
                <i class="bi bi-check-circle" style="font-size:2.5rem;color:var(--clr-success);"></i>
                <h5 style="color:var(--clr-success);margin:0.5rem 0 0.25rem;">Available</h5>
                <p style="color:var(--clr-text-muted);font-size:var(--fs-sm);margin:0 0 1rem;">This asset is not currently on loan.</p>
                <button type="button" class="btn btn-primary btn-lg w-100" data-bs-toggle="modal" data-bs-target="#loanModal">
                    <i class="bi bi-plus-circle"></i> Loan This Asset
                </button>
            <?php elseif ($asset['status'] === 'under_repair'): ?>
                <i class="bi bi-tools" style="font-size:2.5rem;color:var(--clr-warning);"></i>
                <h5 style="color:var(--clr-warning);margin:0.5rem 0 0.25rem;">Under Repair</h5>
                <p style="color:var(--clr-text-muted);font-size:var(--fs-sm);margin:0;">This asset is currently being repaired.</p>
            <?php elseif ($asset['status'] === 'retired'): ?>
                <i class="bi bi-x-circle" style="font-size:2.5rem;color:var(--clr-text-muted);"></i>
                <h5 style="color:var(--clr-text-secondary);margin:0.5rem 0 0.25rem;">Retired</h5>
                <p style="color:var(--clr-text-muted);font-size:var(--fs-sm);margin:0;">This asset has been retired from service.</p>
            <?php else: ?>
                <i class="bi bi-info-circle" style="font-size:2.5rem;color:var(--clr-info);"></i>
                <h5 style="margin:0.5rem 0 0.25rem;">No Active Loan</h5>
                <p style="color:var(--clr-text-muted);font-size:var(--fs-sm);margin:0;">This asset is not currently on loan.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($loan_history)): ?>
    <div class="card mb-3">
        <div class="card-header" style="background:var(--clr-bg);border-bottom:1px solid var(--clr-border);">
            <strong style="color:var(--clr-text);"><i class="bi bi-clock-history"></i> Loan History (Last 10)</strong>
        </div>
        <div class="card-body" style="padding:0;">
            <?php foreach ($loan_history as $hist): ?>
            <div style="padding:0.75rem 1rem;border-bottom:1px solid var(--clr-border-light);font-size:var(--fs-sm);">
                <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:0.5rem;">
                    <strong style="color:var(--clr-text);"><?= e($hist['borrower_name']) ?></strong>
                    <span class="badge-status badge-available" style="font-size:var(--fs-xs);">Returned</span>
                </div>
                <?php if ($hist['borrower_dept']): ?>
                <div style="color:var(--clr-text-secondary);margin-bottom:0.25rem;"><?= e($hist['borrower_dept']) ?></div>
                <?php endif; ?>
                <div style="color:var(--clr-text-muted);font-size:var(--fs-xs);">
                    <i class="bi bi-calendar3"></i> <?= e($hist['loan_date']) ?> &rarr; <?= e($hist['return_date']) ?>
                </div>
                <?php if ($hist['condition_in']): ?>
                <div style="color:var(--clr-text-secondary);font-size:var(--fs-xs);margin-top:0.25rem;">
                    <strong>Condition In:</strong> <?= e($hist['condition_in']) ?>
                </div>
                <?php endif; ?>
                <?php if ($hist['issued_by_name']): ?>
                <div style="color:var(--clr-text-muted);font-size:var(--fs-xs);margin-top:0.25rem;">
                    <i class="bi bi-person"></i> Issued by <?= e($hist['issued_by_name']) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <p class="text-center" style="color:var(--clr-text-muted);font-size:var(--fs-xs);margin-top:var(--space-md);">
        <i class="bi bi-shield-check"></i> ICT Asset Loan Management System
    </p>
</div>

<!-- Return Modal -->
<?php if ($active_loan): ?>
<div class="modal fade" id="returnModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="asset_qr.php?id=<?= $asset_id ?>&token=<?= urlencode($token) ?>">
                <div class="modal-header" style="background:var(--clr-warning-light);">
                    <h5 class="modal-title" style="color:var(--clr-warning);"><i class="bi bi-arrow-return-left"></i> Return Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="p-3 mb-3" style="background:var(--clr-bg);border-radius:var(--radius-md);font-size:var(--fs-sm);">
                        <strong><?= e($asset['asset_tag']) ?></strong> &mdash; <?= e($asset['name']) ?>
                    </div>
                    <div class="mb-3">
                        <label for="return_date" class="form-label">Return Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="return_date" name="return_date"
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="condition_in" class="form-label">Condition on Return <span class="text-danger">*</span></label>
                        <select class="form-select" id="condition_in" name="condition_in" required>
                            <option value="">Select condition...</option>
                            <option value="Good - no damage">Good - no damage</option>
                            <option value="Good - minor wear">Good - minor wear</option>
                            <option value="Damaged - needs repair">Damaged - needs repair</option>
                            <option value="Damaged - beyond repair">Damaged - beyond repair</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="new_status" class="form-label">Set Asset Status</label>
                        <select class="form-select" id="new_status" name="new_status">
                            <option value="available" selected>Available</option>
                            <option value="under_repair">Under Repair</option>
                            <option value="retired">Retired</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="action" value="return_asset" class="btn btn-warning">
                        <i class="bi bi-check-lg"></i> Confirm Return
                    </button>
                </div>
            </form>
        </div>
    </div>
    </div>
<?php endif; ?>

<!-- Loan Modal -->
<?php if (!$active_loan && $asset['status'] === 'available'): ?>
<div class="modal fade" id="loanModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background:var(--clr-info-light,#e0f2fe);">
                <h5 class="modal-title" style="color:var(--clr-primary,#0369a1);"><i class="bi bi-plus-circle"></i> Loan This Asset</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="p-3 mb-3" style="background:var(--clr-bg,#f8fafc);border-radius:var(--radius-md,0.5rem);font-size:var(--fs-sm,0.875rem);">
                    <strong><?= e($asset['asset_tag']) ?></strong> &mdash; <?= e($asset['name']) ?>
                </div>
                
                <ul class="nav nav-pills mb-3" role="tablist">
                    <li class="nav-item" role="presentation" style="flex:1;">
                        <button class="nav-link w-100 active" id="existing-tab" data-bs-toggle="pill" data-bs-target="#existing" type="button" role="tab">
                            <i class="bi bi-people"></i> Existing Borrower
                        </button>
                    </li>
                    <li class="nav-item" role="presentation" style="flex:1;">
                        <button class="nav-link w-100" id="new-tab" data-bs-toggle="pill" data-bs-target="#new" type="button" role="tab">
                            <i class="bi bi-person-plus"></i> New Borrower
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="existing" role="tabpanel">
                        <form method="post" action="asset_qr.php?id=<?= $asset_id ?>&token=<?= urlencode($token) ?>">
                            <input type="hidden" name="action" value="issue_loan">
                            <?php if (!empty($borrowers)): ?>
                                <div class="mb-3">
                                    <label for="borrower_id" class="form-label">Select Borrower <span class="text-danger">*</span></label>
                                    <select class="form-select" id="borrower_id" name="borrower_id" required>
                                        <option value="">Choose borrower...</option>
                                        <?php foreach ($borrowers as $b): ?>
                                            <option value="<?= (int) $b['id'] ?>">
                                                <?= e($b['name']) ?>
                                                <?php if ($b['department']): ?> &mdash; <?= e($b['department']) ?><?php endif; ?>
                                                <?php if ($b['id_number']): ?> (<?= e($b['id_number']) ?>)<?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label for="loan_date" class="form-label">Loan Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" name="loan_date" value="<?= date('Y-m-d') ?>" required>
                                    </div>
                                    <div class="col-6">
                                        <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" name="due_date" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="condition_out" class="form-label">Condition Out <span class="text-danger">*</span></label>
                                    <select class="form-select" name="condition_out" required>
                                        <option value="">Select condition...</option>
                                        <option value="Excellent - like new">Excellent - like new</option>
                                        <option value="Good - minor wear">Good - minor wear</option>
                                        <option value="Fair - visible wear">Fair - visible wear</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control" name="notes" rows="2" placeholder="Optional notes..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-check-lg"></i> Issue Loan
                                </button>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-people" style="font-size:2rem;color:var(--clr-text-muted);"></i>
                                    <p class="mt-2">No borrowers in system yet.</p>
                                    <small class="text-muted">Use "New Borrower" tab above to add one.</small>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                    
                    <div class="tab-pane fade" id="new" role="tabpanel">
                        <form method="post" action="asset_qr.php?id=<?= $asset_id ?>&token=<?= urlencode($token) ?>">
                            <input type="hidden" name="action" value="create_borrower_and_loan">
                            <div class="mb-3">
                                <label for="b_name" class="form-label">Borrower Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="b_name" name="b_name" required>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label for="b_id_number" class="form-label">ID Number</label>
                                    <input type="text" class="form-control" id="b_id_number" name="b_id_number">
                                </div>
                                <div class="col-6">
                                    <label for="b_department" class="form-label">Department</label>
                                    <input type="text" class="form-control" id="b_department" name="b_department">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label for="b_phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="b_phone" name="b_phone">
                                </div>
                                <div class="col-6">
                                    <label for="b_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="b_email" name="b_email">
                                </div>
                            </div>
                            <hr>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label">Loan Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="loan_date" value="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Due Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="due_date" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Condition Out <span class="text-danger">*</span></label>
                                <select class="form-select" name="condition_out" required>
                                    <option value="">Select condition...</option>
                                    <option value="Excellent - like new">Excellent - like new</option>
                                    <option value="Good - minor wear">Good - minor wear</option>
                                    <option value="Fair - visible wear">Fair - visible wear</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="2" placeholder="Optional notes..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-person-plus"></i> Create Borrower & Issue Loan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (new URLSearchParams(window.location.search).get('action') === 'return') {
        var modal = document.getElementById('returnModal');
        if (modal) { new bootstrap.Modal(modal).show(); }
    }
});
</script>
</body>
</html>
