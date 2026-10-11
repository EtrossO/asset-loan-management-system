<?php
declare(strict_types=1);

require __DIR__ . '/includes/header.php';
require_login();

$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $loans = get_loan_history($pdo, '', $date_from, $date_to);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=loan_report_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Asset Tag', 'Asset Name', 'Borrower', 'Department', 'Loan Date', 'Due Date', 'Return Date', 'Condition Out', 'Condition In', 'Issued By', 'Status']);
    foreach ($loans as $loan) {
        $status = 'Active';
        if ($loan['return_date']) { $status = 'Returned'; }
        elseif (strtotime($loan['due_date']) < strtotime('today')) { $status = 'Overdue'; }
        fputcsv($output, [
            $loan['asset_tag'], $loan['asset_name'], $loan['borrower_name'],
            $loan['borrower_dept'] ?? '', $loan['loan_date'], $loan['due_date'],
            $loan['return_date'] ?? '', $loan['condition_out'] ?? '',
            $loan['condition_in'] ?? '', $loan['issued_by_name'], $status,
        ]);
    }
    fclose($output);
    exit;
}

$preview = get_loan_history($pdo, '', $date_from, $date_to);

function render_report_badge(array $loan): string {
    if ($loan['return_date']) return '<span class="badge-status badge-returned">Returned</span>';
    if (strtotime($loan['due_date']) < strtotime('today')) return '<span class="badge-status badge-overdue">Overdue</span>';
    return '<span class="badge-status badge-active">Active</span>';
}
?>

<?php render_flash(); ?>

<div class="page-header">
    <h2><i class="bi bi-file-earmark-text"></i> Reports</h2>
    <a href="reports.php?export=csv&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>"
       class="btn btn-success">
        <i class="bi bi-download"></i> Export CSV
    </a>
</div>

<div class="filter-bar">
    <div class="filter-group">
        <label>From Date</label>
        <input type="date" class="form-control" name="date_from" value="<?= e($date_from) ?>"
               id="report_from">
    </div>
    <div class="filter-group">
        <label>To Date</label>
        <input type="date" class="form-control" name="date_to" value="<?= e($date_to) ?>"
               id="report_to">
    </div>
    <div class="filter-actions">
        <button type="button" class="btn btn-primary" onclick="applyReportFilter()">
            <i class="bi bi-funnel"></i> Filter
        </button>
        <?php if ($date_from || $date_to): ?>
            <a href="reports.php" class="btn btn-outline-secondary">Clear</a>
        <?php endif; ?>
    </div>
</div>

<div class="table-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Loan Report Preview</strong>
        <span class="badge bg-secondary rounded-pill"><?= count($preview) ?> records</span>
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
                    <th>Return Date</th>
                    <th>Issued By</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($preview)): ?>
                    <tr><td colspan="9">
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>No loans match the selected criteria.</p>
                        </div>
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($preview as $loan): ?>
                    <tr>
                        <td data-label="Asset Tag"><strong><?= e($loan['asset_tag']) ?></strong></td>
                        <td data-label="Asset Name"><?= e($loan['asset_name']) ?></td>
                        <td data-label="Borrower"><?= e($loan['borrower_name']) ?></td>
                        <td data-label="Department"><?= e($loan['borrower_dept'] ?? '-') ?></td>
                        <td data-label="Loan Date"><?= e($loan['loan_date']) ?></td>
                        <td data-label="Due Date"><?= e($loan['due_date']) ?></td>
                        <td data-label="Return Date"><?= $loan['return_date'] ? e($loan['return_date']) : '-' ?></td>
                        <td data-label="Issued By"><?= e($loan['issued_by_name']) ?></td>
                        <td data-label="Status"><?= render_report_badge($loan) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function applyReportFilter() {
    var from = document.getElementById('report_from').value;
    var to = document.getElementById('report_to').value;
    var url = 'reports.php?';
    if (from) url += 'date_from=' + from + '&';
    if (to) url += 'date_to=' + to;
    window.location.href = url;
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
