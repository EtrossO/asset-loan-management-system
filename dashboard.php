<?php
declare(strict_types=1);

require __DIR__ . '/includes/header.php';
require_login();

$stats = count_assets_by_status($pdo);
$overdue_count = count_overdue_loans($pdo);
$overdue_loans = get_overdue_loans($pdo);
?>

<div class="page-header">
    <h2><i class="bi bi-speedometer2"></i> Dashboard</h2>
</div>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="card stat-card card-info h-100">
            <div class="card-body">
                <div>
                    <div class="stat-label">Total Assets</div>
                    <h3 class="stat-value"><?= (int) $stats['total'] ?></h3>
                </div>
                <div class="stat-icon"><i class="bi bi-box"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card stat-card card-success h-100">
            <div class="card-body">
                <div>
                    <div class="stat-label">Available</div>
                    <h3 class="stat-value"><?= (int) $stats['available'] ?></h3>
                </div>
                <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card stat-card card-primary h-100">
            <div class="card-body">
                <div>
                    <div class="stat-label">On Loan</div>
                    <h3 class="stat-value"><?= (int) $stats['on_loan'] ?></h3>
                </div>
                <div class="stat-icon"><i class="bi bi-arrow-right-circle"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card stat-card card-danger h-100">
            <div class="card-body">
                <div>
                    <div class="stat-label">Overdue</div>
                    <h3 class="stat-value"><?= $overdue_count ?></h3>
                </div>
                <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Overdue Loans -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="d-flex align-items-center gap-2" style="color:var(--clr-danger);">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <strong>Overdue Loans</strong>
        </span>
        <span class="badge bg-danger rounded-pill"><?= $overdue_count ?></span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($overdue_loans)): ?>
            <div class="empty-state">
                <i class="bi bi-check-circle"></i>
                <p>No overdue loans. All assets are returned on time!</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table-modern table-card-view w-100">
                    <thead>
                        <tr>
                            <th>Asset Tag</th>
                            <th>Asset Name</th>
                            <th>Borrower</th>
                            <th>Department</th>
                            <th>Due Date</th>
                            <th>Days Late</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($overdue_loans as $loan): ?>
                        <tr class="overdue-row">
                            <td data-label="Asset Tag"><strong><?= e($loan['asset_tag']) ?></strong></td>
                            <td data-label="Asset Name"><?= e($loan['asset_name']) ?></td>
                            <td data-label="Borrower"><?= e($loan['borrower_name']) ?></td>
                            <td data-label="Department"><?= e($loan['borrower_dept'] ?? '-') ?></td>
                            <td data-label="Due Date" style="color:var(--clr-danger);font-weight:600;"><?= e($loan['due_date']) ?></td>
                            <td data-label="Days Late">
                                <span class="overdue-days">
                                    <i class="bi bi-clock-fill"></i>
                                    <?= (int) $loan['days_overdue'] ?> day<?= (int) $loan['days_overdue'] !== 1 ? 's' : '' ?>
                                </span>
                            </td>
                            <td data-label="Action" class="td-actions">
                                <a href="loans.php?action=return&id=<?= (int) $loan['id'] ?>" class="btn btn-sm btn-warning">
                                    <i class="bi bi-arrow-return-left"></i> Return
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
