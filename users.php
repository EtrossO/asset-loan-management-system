<?php
declare(strict_types=1);

require __DIR__ . '/includes/header.php';
require_role('admin');

$action = $_GET['action'] ?? 'list';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid request.';
    } else {
        $post_action = $_POST['action'] ?? '';

        if ($post_action === 'add_user') {
            $name     = trim($_POST['name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $role     = $_POST['role'] ?? 'staff';
            if ($name === '' || $username === '' || $password === '') {
                $error = 'All fields are required.';
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters.';
            } elseif (!in_array($role, ['admin', 'staff'], true)) {
                $error = 'Invalid role.';
            } else {
                $existing = get_user_by_username($pdo, $username);
                if ($existing) {
                    $error = 'Username already exists.';
                } else {
                    try {
                        create_user($pdo, $name, $username, $password, $role);
                        set_flash('success', 'User "' . e($username) . '" created successfully.');
                        header('Location: users.php');
                        exit;
                    } catch (Exception $ex) {
                        $error = 'Failed to create user: ' . $ex->getMessage();
                    }
                }
            }
        }

        if ($post_action === 'reset_password') {
            $id = (int) ($_POST['id'] ?? 0);
            $password = $_POST['password'] ?? '';
            $user = get_user_by_id($pdo, $id);
            if (!$user) {
                $error = 'User not found.';
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters.';
            } else {
                try {
                    reset_user_password($pdo, $id, $password);
                    set_flash('success', 'Password reset for user "' . e($user['username']) . '".');
                    header('Location: users.php');
                    exit;
                } catch (Exception $ex) {
                    $error = 'Failed to reset password: ' . $ex->getMessage();
                }
            }
        }

        if ($post_action === 'toggle_active') {
            $id = (int) ($_POST['id'] ?? 0);
            $active = (int) ($_POST['is_active'] ?? 0);
            $user = get_user_by_id($pdo, $id);
            if (!$user) {
                $error = 'User not found.';
            } elseif ($id === current_user_id()) {
                $error = 'You cannot deactivate your own account.';
            } else {
                try {
                    toggle_user_active($pdo, $id, $active);
                    $status = $active ? 'activated' : 'deactivated';
                    set_flash('success', 'User "' . e($user['username']) . '" ' . $status . '.');
                    header('Location: users.php');
                    exit;
                } catch (Exception $ex) {
                    $error = 'Failed to update user: ' . $ex->getMessage();
                }
            }
        }
    }
}
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
    <h2><i class="bi bi-person-gear"></i> User Management</h2>
    <a href="users.php?action=add" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Add User
    </a>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table-modern table-card-view">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $users = get_all_users($pdo);
                foreach ($users as $u):
                ?>
                <tr>
                    <td data-label="Name"><strong><?= e($u['name']) ?></strong></td>
                    <td data-label="Username"><?= e($u['username']) ?></td>
                    <td data-label="Role">
                        <span class="badge bg-<?= $u['role'] === 'admin' ? 'danger' : 'primary' ?> rounded-pill">
                            <?= e(ucfirst($u['role'])) ?>
                        </span>
                    </td>
                    <td data-label="Status">
                        <?php if ($u['is_active']): ?>
                            <span class="badge-status badge-available">Active</span>
                        <?php else: ?>
                            <span class="badge-status badge-retired">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Created"><?= e($u['created_at']) ?></td>
                    <td data-label="Actions" class="td-actions">
                        <div class="d-flex flex-wrap gap-1">
                            <button type="button" class="btn btn-sm btn-outline-primary btn-icon"
                                    data-bs-toggle="modal" data-bs-target="#resetPwModal<?= (int) $u['id'] ?>" title="Reset Password">
                                <i class="bi bi-key"></i>
                            </button>
                            <form method="post" action="users.php" class="d-inline"
                                  onsubmit="return confirm('<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?> this user?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="toggle_active">
                                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                <input type="hidden" name="is_active" value="<?= $u['is_active'] ? 0 : 1 ?>">
                                <button type="submit" class="btn btn-sm btn-icon <?= $u['is_active'] ? 'btn-outline-secondary' : 'btn-outline-primary' ?>"
                                        title="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                    <i class="bi bi-<?= $u['is_active'] ? 'x-circle' : 'check-circle' ?>"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>

                <div class="modal fade" id="resetPwModal<?= (int) $u['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="post" action="users.php">
                                <div class="modal-header">
                                    <h5 class="modal-title">Reset Password: <?= e($u['username']) ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="reset_password">
                                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                    <div class="mb-3">
                                        <label for="password_<?= (int) $u['id'] ?>" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="password_<?= (int) $u['id'] ?>"
                                               name="password" minlength="6" required>
                                        <small class="form-text">Minimum 6 characters</small>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-warning">
                                        <i class="bi bi-key"></i> Reset Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($action === 'add'): ?>
<div class="page-header">
    <h2><i class="bi bi-plus-lg"></i> Add User</h2>
</div>
<div class="card">
    <div class="card-body">
        <form method="post" action="users.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_user">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="col-md-6">
                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="col-md-6">
                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                    <small class="form-text">Minimum 6 characters</small>
                </div>
                <div class="col-md-6">
                    <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Create User</button>
                <a href="users.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
