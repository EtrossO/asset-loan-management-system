<?php
declare(strict_types=1);

require __DIR__ . '/includes/header.php';
require_login();

$action = $_GET['action'] ?? 'active';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid request.';
    } else {
        $post_action = $_POST['action'] ?? '';

        if ($post_action === 'new_loan') {
            $asset_id     = (int) ($_POST['asset_id'] ?? 0);
            $borrower_id  = (int) ($_POST['borrower_id'] ?? 0);
            $loan_date    = trim($_POST['loan_date'] ?? date('Y-m-d'));
            $due_date     = trim($_POST['due_date'] ?? '');
            $condition_out = trim($_POST['condition_out'] ?? '');
            $notes        = trim($_POST['notes'] ?? '');

            $asset = get_asset_by_id($pdo, $asset_id);
            $borrower = get_borrower_by_id($pdo, $borrower_id);

            if (!$asset) {
                $error = 'Asset not found.';
            } elseif ($asset['status'] !== 'available') {
                $error = 'Asset is not available for loan (current status: ' . $asset['status'] . ').';
            } elseif (!$borrower) {
                $error = 'Borrower not found.';
            } elseif (!is_valid_date($loan_date)) {
                $error = 'Invalid loan date.';
            } elseif (!is_valid_date($due_date)) {
                $error = 'Invalid due date.';
            } elseif (strtotime($due_date) <= strtotime($loan_date)) {
                $error = 'Due date must be after the loan date.';
            } elseif ($condition_out === '') {
                $error = 'Condition out is required.';
            } else {
                try {
                    create_loan($pdo, $asset_id, $borrower_id, $loan_date, $due_date, $condition_out, $notes, current_user_id());
                    set_flash('success', 'Loan created for asset ' . e($asset['asset_tag']) . ' to ' . e($borrower['name']) . '.');
                    header('Location: loans.php?action=active');
                    exit;
                } catch (Exception $ex) {
                    $error = 'Failed to create loan: ' . $ex->getMessage();
                }
            }
        }

        if ($post_action === 'return_loan') {
            $loan_id      = (int) ($_POST['loan_id'] ?? 0);
            $return_date  = trim($_POST['return_date'] ?? date('Y-m-d'));
            $condition_in = trim($_POST['condition_in'] ?? '');
            $notes        = trim($_POST['notes'] ?? '');
            $new_status   = $_POST['new_status'] ?? 'available';

            $loan = get_loan_by_id($pdo, $loan_id);

            if (!$loan) {
                $error = 'Loan not found.';
            } elseif ($loan['return_date'] !== null) {
                $error = 'This loan has already been returned.';
            } elseif (!is_valid_date($return_date)) {
                $error = 'Invalid return date.';
            } elseif ($condition_in === '') {
                $error = 'Condition on return is required.';
            } else {
                try {
                    return_loan($pdo, $loan_id, $return_date, $condition_in, $notes, $new_status);
                    set_flash('success', 'Asset ' . e($loan['asset_tag']) . ' returned successfully.');
                    header('Location: loans.php?action=active');
                    exit;
                } catch (Exception $ex) {
                    $error = 'Failed to return loan: ' . $ex->getMessage();
                }
            }
        }
    }
}

$available_assets = get_available_assets($pdo);
$all_borrowers = get_all_borrowers($pdo);

function render_loan_badge(array $loan): string {
    if ($loan['return_date'] !== null) {
        return '<span class="badge-status badge-returned">Returned</span>';
    }
    if (strtotime($loan['due_date']) < strtotime('today')) {
        return '<span class="badge-status badge-overdue">Overdue</span>';
    }
    return '<span class="badge-status badge-active">Active</span>';
}
?>

<?php render_flash(); ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= e($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Sub-navigation tabs -->
<div class="nav-tabs-modern">
    <a href="loans.php?action=active" class="nav-link <?= $action === 'active' || $action === 'return' ? 'active' : '' ?>">
        <i class="bi bi-arrow-left-right"></i> Active Loans
    </a>
    <a href="loans.php?action=new" class="nav-link <?= $action === 'new' ? 'active' : '' ?>">
        <i class="bi bi-plus-lg"></i> New Loan
    </a>
    <a href="loans.php?action=history" class="nav-link <?= $action === 'history' ? 'active' : '' ?>">
        <i class="bi bi-clock-history"></i> History
    </a>
</div>

<?php if ($action === 'active' || $action === 'return'): ?>
<?php
$active_loans = get_active_loans($pdo);
?>
<div class="table-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Active Loans</strong>
        <span class="badge bg-primary rounded-pill"><?= count($active_loans) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table-modern table-card-view">
            <thead>
                <tr>
                    <th>Asset Tag</th>
                    <th>Asset Name</th>
                    <th>Borrower</th>
                    <th>Department</th>
                    <th>Loan Date</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($active_loans)): ?>
                    <tr><td colspan="8">
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>No active loans.</p>
                        </div>
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($active_loans as $loan): ?>
                    <tr class="<?= strtotime($loan['due_date']) < strtotime('today') ? 'overdue-row' : '' ?>">
                        <td data-label="Asset Tag"><strong><?= e($loan['asset_tag']) ?></strong></td>
                        <td data-label="Asset Name"><?= e($loan['asset_name']) ?></td>
                        <td data-label="Borrower"><?= e($loan['borrower_name']) ?></td>
                        <td data-label="Department"><?= e($loan['borrower_dept'] ?? '-') ?></td>
                        <td data-label="Loan Date"><?= e($loan['loan_date']) ?></td>
                        <td data-label="Due Date">
                            <?= e($loan['due_date']) ?>
                            <?php if (strtotime($loan['due_date']) < strtotime('today')): ?>
                                <span class="overdue-days ms-1">
                                    <?= (int) ((time() - strtotime($loan['due_date'])) / 86400) ?>d late
                                </span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Status"><?= render_loan_badge($loan) ?></td>
                        <td data-label="Action" class="td-actions">
                            <a href="loans.php?action=return&id=<?= (int) $loan['id'] ?>" class="btn btn-sm btn-warning">
                                <i class="bi bi-arrow-return-left"></i> Return
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Return Modal -->
<?php
$return_loan = null;
if ($action === 'return' && !empty($_GET['id'])) {
    $return_loan = get_loan_by_id($pdo, (int) $_GET['id']);
}
if ($return_loan && $return_loan['return_date'] === null):
?>
<div class="modal fade show" id="returnModal" tabindex="-1" style="display:block;background:rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="loans.php?action=active">
                <div class="modal-header" style="background:var(--clr-warning-light);">
                    <h5 class="modal-title" style="color:var(--clr-warning);"><i class="bi bi-arrow-return-left"></i> Return Asset</h5>
                    <a href="loans.php?action=active" class="btn-close"></a>
                </div>
                <div class="modal-body">
                    <div class="p-3 mb-3" style="background:var(--clr-bg);border-radius:var(--radius-md);">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted" style="font-size:var(--fs-sm);">Asset</span>
                            <strong style="font-size:var(--fs-sm);"><?= e($return_loan['asset_tag']) ?> &mdash; <?= e($return_loan['asset_name']) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted" style="font-size:var(--fs-sm);">Borrower</span>
                            <strong style="font-size:var(--fs-sm);"><?= e($return_loan['borrower_name']) ?></strong>
                        </div>
                    </div>

                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="return_loan">
                    <input type="hidden" name="loan_id" value="<?= (int) $return_loan['id'] ?>">

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
                    <a href="loans.php?action=active" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-lg"></i> Confirm Return
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php elseif ($action === 'new'): ?>
<div class="card">
    <div class="card-header">
        <strong><i class="bi bi-plus-lg"></i> Issue New Loan</strong>
    </div>
    <div class="card-body">
        <?php if (empty($available_assets)): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i> No assets are currently available for loan.
            </div>
        <?php else: ?>
        <form method="post" action="loans.php?action=new">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="new_loan">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="asset_id" class="form-label">Asset <span class="text-danger">*</span></label>
                    <select class="form-select" id="asset_id" name="asset_id" required>
                        <option value="">Select an available asset...</option>
                        <?php foreach ($available_assets as $a): ?>
                            <option value="<?= (int) $a['id'] ?>">
                                <?= e($a['asset_tag']) ?> - <?= e($a['name']) ?> (<?= e($a['category']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="borrower_id" class="form-label">Borrower <span class="text-danger">*</span></label>
                    <select class="form-select" id="borrower_id" name="borrower_id" required>
                        <option value="">Select borrower...</option>
                        <?php foreach ($all_borrowers as $b): ?>
                            <option value="<?= (int) $b['id'] ?>">
                                <?= e($b['name']) ?><?= $b['department'] ? ' (' . e($b['department']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="loan_date" class="form-label">Loan Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="loan_date" name="loan_date"
                           value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="due_date" name="due_date"
                           value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="condition_out" class="form-label">Condition Out <span class="text-danger">*</span></label>
                    <select class="form-select" id="condition_out" name="condition_out" required>
                        <option value="">Select...</option>
                        <option value="Good">Good</option>
                        <option value="Good - minor wear">Good - minor wear</option>
                        <option value="Fair">Fair</option>
                    </select>
                </div>
                <div class="col-12">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                </div>
            </div>
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-clipboard-check"></i> Issue Loan
                </button>
                <a href="loans.php?action=active" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($action === 'history'): ?>
<?php
$filter_status = $_GET['status'] ?? '';
$filter_from = $_GET['date_from'] ?? '';
$filter_to = $_GET['date_to'] ?? '';
$history = get_loan_history($pdo, $filter_status, $filter_from, $filter_to);
?>

<form method="get" action="loans.php" class="filter-bar">
    <input type="hidden" name="action" value="history">
    <div class="filter-group" style="min-width:140px;flex:0.4;">
        <label>Status</label>
        <select class="form-select" name="status">
            <option value="">All Statuses</option>
            <option value="active" <?= $filter_status === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="returned" <?= $filter_status === 'returned' ? 'selected' : '' ?>>Returned</option>
            <option value="overdue" <?= $filter_status === 'overdue' ? 'selected' : '' ?>>Overdue</option>
        </select>
    </div>
    <div class="filter-group">
        <label>From Date</label>
        <input type="date" class="form-control" name="date_from" value="<?= e($filter_from) ?>">
    </div>
    <div class="filter-group">
        <label>To Date</label>
        <input type="date" class="form-control" name="date_to" value="<?= e($filter_to) ?>">
    </div>
    <div class="filter-actions">
        <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
        <?php if ($filter_status || $filter_from || $filter_to): ?>
            <a href="loans.php?action=history" class="btn btn-outline-secondary">Clear</a>
        <?php endif; ?>
    </div>
</form>

<div class="table-card">
    <div class="table-responsive">
        <table class="table-modern table-card-view">
            <thead>
                <tr>
                    <th>Asset Tag</th>
                    <th>Borrower</th>
                    <th>Loan Date</th>
                    <th>Due Date</th>
                    <th>Return Date</th>
                    <th>Condition Out</th>
                    <th>Condition In</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($history)): ?>
                    <tr><td colspan="8">
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>No loans found.</p>
                        </div>
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($history as $loan): ?>
                    <tr>
                        <td data-label="Asset Tag"><strong><?= e($loan['asset_tag']) ?></strong></td>
                        <td data-label="Borrower"><?= e($loan['borrower_name']) ?></td>
                        <td data-label="Loan Date"><?= e($loan['loan_date']) ?></td>
                        <td data-label="Due Date"><?= e($loan['due_date']) ?></td>
                        <td data-label="Return Date"><?= $loan['return_date'] ? e($loan['return_date']) : '-' ?></td>
                        <td data-label="Cond. Out"><?= e($loan['condition_out'] ?? '-') ?></td>
                        <td data-label="Cond. In"><?= $loan['condition_in'] ? e($loan['condition_in']) : '-' ?></td>
                        <td data-label="Status"><?= render_loan_badge($loan) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($history)): ?>
    <div class="card-footer">Showing <?= count($history) ?> loan(s)</div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
