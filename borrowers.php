<?php
declare(strict_types=1);

require __DIR__ . '/includes/header.php';
require_login();

$action = $_GET['action'] ?? 'list';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid request.';
    } else {
        $post_action = $_POST['action'] ?? '';

        if ($post_action === 'add') {
            $data = [
                'name'       => trim($_POST['name'] ?? ''),
                'id_number'  => trim($_POST['id_number'] ?? ''),
                'department' => trim($_POST['department'] ?? ''),
                'phone'      => trim($_POST['phone'] ?? ''),
                'email'      => trim($_POST['email'] ?? ''),
            ];
            if ($data['name'] === '') {
                $error = 'Name is required.';
            } elseif ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $error = 'Invalid email address.';
            } else {
                try {
                    create_borrower($pdo, $data);
                    set_flash('success', 'Borrower "' . e($data['name']) . '" added.');
                    header('Location: borrowers.php');
                    exit;
                } catch (Exception $ex) {
                    $error = 'Failed to add borrower: ' . $ex->getMessage();
                }
            }
        }

        if ($post_action === 'edit') {
            $id = (int) ($_POST['id'] ?? 0);
            $data = [
                'name'       => trim($_POST['name'] ?? ''),
                'id_number'  => trim($_POST['id_number'] ?? ''),
                'department' => trim($_POST['department'] ?? ''),
                'phone'      => trim($_POST['phone'] ?? ''),
                'email'      => trim($_POST['email'] ?? ''),
            ];
            $borrower = get_borrower_by_id($pdo, $id);
            if (!$borrower) {
                $error = 'Borrower not found.';
            } elseif ($data['name'] === '') {
                $error = 'Name is required.';
            } elseif ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $error = 'Invalid email address.';
            } else {
                try {
                    update_borrower($pdo, $id, $data);
                    set_flash('success', 'Borrower updated successfully.');
                    header('Location: borrowers.php');
                    exit;
                } catch (Exception $ex) {
                    $error = 'Failed to update borrower: ' . $ex->getMessage();
                }
            }
        }
    }
}

$filter_search = $_GET['search'] ?? '';
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
    <h2><i class="bi bi-people"></i> Borrowers</h2>
    <a href="borrowers.php?action=add" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Add Borrower
    </a>
</div>

<form method="get" action="borrowers.php" class="filter-bar">
    <div class="filter-group">
        <label>Search</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control" name="search" placeholder="Name, ID, department, email..."
                   value="<?= e($filter_search) ?>">
        </div>
    </div>
    <div class="filter-actions">
        <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Search</button>
        <?php if ($filter_search): ?>
            <a href="borrowers.php" class="btn btn-outline-secondary">Clear</a>
        <?php endif; ?>
    </div>
</form>

<div class="table-card">
    <div class="table-responsive">
        <table class="table-modern table-card-view">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>ID Number</th>
                    <th>Department</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $borrowers = get_all_borrowers($pdo, $filter_search);
                if (empty($borrowers)): ?>
                    <tr><td colspan="6">
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>No borrowers found.</p>
                        </div>
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($borrowers as $b): ?>
                    <tr>
                        <td data-label="Name"><strong><?= e($b['name']) ?></strong></td>
                        <td data-label="ID Number"><?= e($b['id_number'] ?? '-') ?></td>
                        <td data-label="Department"><?= e($b['department'] ?? '-') ?></td>
                        <td data-label="Phone"><?= e($b['phone'] ?? '-') ?></td>
                        <td data-label="Email">
                            <?php if ($b['email']): ?>
                                <a href="mailto:<?= e($b['email']) ?>"><?= e($b['email']) ?></a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td data-label="Actions" class="td-actions">
                            <a href="borrowers.php?action=edit&id=<?= (int) $b['id'] ?>" class="btn btn-sm btn-outline-primary btn-icon" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($borrowers)): ?>
    <div class="card-footer">Showing <?= count($borrowers) ?> borrower(s)</div>
    <?php endif; ?>
</div>

<?php elseif ($action === 'add'): ?>
<div class="page-header">
    <h2><i class="bi bi-plus-lg"></i> Add Borrower</h2>
</div>
<div class="card">
    <div class="card-body">
        <form method="post" action="borrowers.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
            <?php require __DIR__ . '/includes/borrower_form.php'; ?>
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save</button>
                <a href="borrowers.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php elseif ($action === 'edit'): ?>
<?php
$borrower = get_borrower_by_id($pdo, (int) ($_GET['id'] ?? 0));
if (!$borrower) {
    echo '<div class="alert alert-danger">Borrower not found.</div>';
} else {
?>
<div class="page-header">
    <h2><i class="bi bi-pencil"></i> Edit: <?= e($borrower['name']) ?></h2>
</div>
<div class="card">
    <div class="card-body">
        <form method="post" action="borrowers.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= (int) $borrower['id'] ?>">
            <?php require __DIR__ . '/includes/borrower_form.php'; ?>
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Update</button>
                <a href="borrowers.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php } ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
