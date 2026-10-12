<?php
declare(strict_types=1);

// ============================================================
// FLASH MESSAGES
// ============================================================

function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function render_flash(): void {
    $flash = get_flash();
    if ($flash) {
        $type = htmlspecialchars($flash['type']);
        $message = htmlspecialchars($flash['message']);
        echo "<div class=\"alert alert-{$type} alert-dismissible fade show\">";
        echo $message;
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}

// ============================================================
// USER FUNCTIONS
// ============================================================

function get_user_by_username(PDO $pdo, string $username): ?array {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
    $stmt->execute([$username]);
    return $stmt->fetch() ?: null;
}

function get_all_users(PDO $pdo): array {
    return $pdo->query("SELECT id, name, username, role, is_active, created_at FROM users ORDER BY name")->fetchAll();
}

function create_user(PDO $pdo, string $name, string $username, string $password, string $role): int {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, username, password_hash, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $username, $hash, $role]);
    return (int) $pdo->lastInsertId();
}

function update_user(PDO $pdo, int $id, string $name, string $username, string $role): void {
    $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ?, role = ? WHERE id = ?");
    $stmt->execute([$name, $username, $role, $id]);
}

function reset_user_password(PDO $pdo, int $id, string $password): void {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$hash, $id]);
}

function toggle_user_active(PDO $pdo, int $id, int $active): void {
    $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
    $stmt->execute([$active, $id]);
}

function get_user_by_id(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

// ============================================================
// ASSET FUNCTIONS
// ============================================================

function get_all_assets(PDO $pdo, string $search = '', string $category = ''): array {
    $sql = "SELECT * FROM assets WHERE 1=1";
    $params = [];

    if ($search !== '') {
        $sql .= " AND (asset_tag LIKE ? OR name LIKE ? OR brand LIKE ? OR serial_no LIKE ?)";
        $like = "%{$search}%";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
    if ($category !== '') {
        $sql .= " AND category = ?";
        $params[] = $category;
    }
    $sql .= " ORDER BY asset_tag ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_asset_by_id(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT * FROM assets WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function get_available_assets(PDO $pdo): array {
    return $pdo->query("SELECT * FROM assets WHERE status = 'available' ORDER BY asset_tag")->fetchAll();
}

function create_asset(PDO $pdo, array $data): int {
    $stmt = $pdo->prepare(
        "INSERT INTO assets (asset_tag, name, category, brand, model, serial_no, status, notes)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $data['asset_tag'],
        $data['name'],
        $data['category'],
        $data['brand'] ?: null,
        $data['model'] ?: null,
        $data['serial_no'] ?: null,
        $data['status'] ?? 'available',
        $data['notes'] ?: null,
    ]);
    return (int) $pdo->lastInsertId();
}

function update_asset(PDO $pdo, int $id, array $data): void {
    $stmt = $pdo->prepare(
        "UPDATE assets SET asset_tag = ?, name = ?, category = ?, brand = ?, model = ?, serial_no = ?, status = ?, notes = ?
         WHERE id = ?"
    );
    $stmt->execute([
        $data['asset_tag'],
        $data['name'],
        $data['category'],
        $data['brand'] ?: null,
        $data['model'] ?: null,
        $data['serial_no'] ?: null,
        $data['status'],
        $data['notes'] ?: null,
        $id,
    ]);
}

function delete_asset(PDO $pdo, int $id): void {
    $stmt = $pdo->prepare("DELETE FROM assets WHERE id = ?");
    $stmt->execute([$id]);
}

function asset_has_loans(PDO $pdo, int $asset_id): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM loans WHERE asset_id = ?");
    $stmt->execute([$asset_id]);
    return (int) $stmt->fetchColumn() > 0;
}

function asset_tag_is_unique(PDO $pdo, string $tag, int $exclude_id = 0): bool {
    $sql = "SELECT COUNT(*) FROM assets WHERE asset_tag = ?";
    $params = [$tag];
    if ($exclude_id > 0) {
        $sql .= " AND id != ?";
        $params[] = $exclude_id;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn() === 0;
}

function update_asset_status(PDO $pdo, int $id, string $status): void {
    $stmt = $pdo->prepare("UPDATE assets SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
}

// ============================================================
// BORROWER FUNCTIONS
// ============================================================

function get_all_borrowers(PDO $pdo, string $search = ''): array {
    $sql = "SELECT * FROM borrowers WHERE 1=1";
    $params = [];

    if ($search !== '') {
        $sql .= " AND (name LIKE ? OR id_number LIKE ? OR department LIKE ? OR email LIKE ?)";
        $like = "%{$search}%";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
    $sql .= " ORDER BY name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_borrower_by_id(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT * FROM borrowers WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function create_borrower(PDO $pdo, array $data): int {
    $stmt = $pdo->prepare(
        "INSERT INTO borrowers (name, id_number, department, phone, email) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $data['name'],
        $data['id_number'] ?: null,
        $data['department'] ?: null,
        $data['phone'] ?: null,
        $data['email'] ?: null,
    ]);
    return (int) $pdo->lastInsertId();
}

function update_borrower(PDO $pdo, int $id, array $data): void {
    $stmt = $pdo->prepare(
        "UPDATE borrowers SET name = ?, id_number = ?, department = ?, phone = ?, email = ? WHERE id = ?"
    );
    $stmt->execute([
        $data['name'],
        $data['id_number'] ?: null,
        $data['department'] ?: null,
        $data['phone'] ?: null,
        $data['email'] ?: null,
        $id,
    ]);
}

function borrower_has_active_loans(PDO $pdo, int $borrower_id): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM loans WHERE borrower_id = ? AND return_date IS NULL");
    $stmt->execute([$borrower_id]);
    return (int) $stmt->fetchColumn() > 0;
}

function borrower_has_loans(PDO $pdo, int $borrower_id): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM loans WHERE borrower_id = ?");
    $stmt->execute([$borrower_id]);
    return (int) $stmt->fetchColumn() > 0;
}

function delete_borrower(PDO $pdo, int $id): void {
    $stmt = $pdo->prepare("DELETE FROM borrowers WHERE id = ?");
    $stmt->execute([$id]);
}

// ============================================================
// LOAN FUNCTIONS
// ============================================================

function create_loan(PDO $pdo, int $asset_id, int $borrower_id, string $loan_date, string $due_date, string $condition_out, string $notes, int $issued_by): int {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO loans (asset_id, borrower_id, loan_date, due_date, condition_out, notes, issued_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$asset_id, $borrower_id, $loan_date, $due_date, $condition_out, $notes, $issued_by]);
        $loan_id = (int) $pdo->lastInsertId();

        update_asset_status($pdo, $asset_id, 'on_loan');
        $pdo->commit();
        return $loan_id;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function return_loan(PDO $pdo, int $loan_id, string $return_date, string $condition_in, string $notes, string $new_status): void {
    $pdo->beginTransaction();
    try {
        $loan = get_loan_by_id($pdo, $loan_id);
        if (!$loan) {
            throw new RuntimeException('Loan not found');
        }

        $stmt = $pdo->prepare(
            "UPDATE loans SET return_date = ?, condition_in = ?, notes = CONCAT(COALESCE(notes, ''), IF(notes IS NOT NULL AND notes != '', '\n', ''), ?)
             WHERE id = ?"
        );
        $update_notes = $notes ?: '';
        $stmt->execute([$return_date, $condition_in, $update_notes, $loan_id]);

        // Validate the new_status value
        $valid_statuses = ['available', 'under_repair', 'retired'];
        if (!in_array($new_status, $valid_statuses, true)) {
            $new_status = 'available';
        }
        update_asset_status($pdo, (int) $loan['asset_id'], $new_status);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function get_loan_by_id(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare(
        "SELECT l.*, a.asset_tag, a.name AS asset_name, b.name AS borrower_name, b.department AS borrower_dept,
                u.name AS issued_by_name
         FROM loans l
         JOIN assets a ON l.asset_id = a.id
         JOIN borrowers b ON l.borrower_id = b.id
         JOIN users u ON l.issued_by = u.id
         WHERE l.id = ?"
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function get_active_loans(PDO $pdo): array {
    return $pdo->query(
        "SELECT l.*, a.asset_tag, a.name AS asset_name, b.name AS borrower_name, b.department AS borrower_dept,
                u.name AS issued_by_name
         FROM loans l
         JOIN assets a ON l.asset_id = a.id
         JOIN borrowers b ON l.borrower_id = b.id
         JOIN users u ON l.issued_by = u.id
         WHERE l.return_date IS NULL
         ORDER BY l.due_date ASC"
    )->fetchAll();
}

function get_loan_history(PDO $pdo, string $status = '', string $date_from = '', string $date_to = ''): array {
    $sql = "SELECT l.*, a.asset_tag, a.name AS asset_name, b.name AS borrower_name, b.department AS borrower_dept,
                   u.name AS issued_by_name
            FROM loans l
            JOIN assets a ON l.asset_id = a.id
            JOIN borrowers b ON l.borrower_id = b.id
            JOIN users u ON l.issued_by = u.id
            WHERE 1=1";
    $params = [];

    if ($status === 'active') {
        $sql .= " AND l.return_date IS NULL AND l.due_date >= CURDATE()";
    } elseif ($status === 'returned') {
        $sql .= " AND l.return_date IS NOT NULL";
    } elseif ($status === 'overdue') {
        $sql .= " AND l.return_date IS NULL AND l.due_date < CURDATE()";
    }

    if ($date_from !== '') {
        $sql .= " AND l.loan_date >= ?";
        $params[] = $date_from;
    }
    if ($date_to !== '') {
        $sql .= " AND l.loan_date <= ?";
        $params[] = $date_to;
    }

    $sql .= " ORDER BY l.loan_date DESC, l.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_overdue_loans(PDO $pdo): array {
    return $pdo->query(
        "SELECT l.*, a.asset_tag, a.name AS asset_name, b.name AS borrower_name, b.department AS borrower_dept,
                DATEDIFF(CURDATE(), l.due_date) AS days_overdue
         FROM loans l
         JOIN assets a ON l.asset_id = a.id
         JOIN borrowers b ON l.borrower_id = b.id
         WHERE l.return_date IS NULL AND l.due_date < CURDATE()
         ORDER BY l.due_date ASC"
    )->fetchAll();
}

// ============================================================
// DASHBOARD FUNCTIONS
// ============================================================

function count_assets_by_status(PDO $pdo): array {
    $stmt = $pdo->query(
        "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) AS available,
            SUM(CASE WHEN status = 'on_loan' THEN 1 ELSE 0 END) AS on_loan,
            SUM(CASE WHEN status = 'under_repair' THEN 1 ELSE 0 END) AS under_repair,
            SUM(CASE WHEN status = 'retired' THEN 1 ELSE 0 END) AS retired
         FROM assets"
    );
    return $stmt->fetch();
}

function count_overdue_loans(PDO $pdo): int {
    $stmt = $pdo->query("SELECT COUNT(*) FROM loans WHERE return_date IS NULL AND due_date < CURDATE()");
    return (int) $stmt->fetchColumn();
}

// ============================================================
// QR CODE HELPER
// ============================================================

function generate_qr_token(int $asset_id): string {
    global $QR_SECRET;
    return hash('sha256', $QR_SECRET . ':' . $asset_id);
}

function verify_qr_token(int $asset_id, string $token): bool {
    return hash_equals(generate_qr_token($asset_id), $token);
}

function get_asset_by_token(PDO $pdo, int $asset_id, string $token): ?array {
    if (!verify_qr_token($asset_id, $token)) {
        return null;
    }
    return get_asset_by_id($pdo, $asset_id);
}

function get_active_loan_for_asset(PDO $pdo, int $asset_id): ?array {
    $stmt = $pdo->prepare(
        "SELECT l.*, b.name AS borrower_name, b.department AS borrower_dept, 
                b.phone AS borrower_phone, b.email AS borrower_email, b.id_number AS borrower_id_number,
                u.name AS issued_by_name
         FROM loans l
         JOIN borrowers b ON l.borrower_id = b.id
         LEFT JOIN users u ON l.issued_by = u.id
         WHERE l.asset_id = ? AND l.return_date IS NULL
         ORDER BY l.id DESC LIMIT 1"
    );
    $stmt->execute([$asset_id]);
    return $stmt->fetch() ?: null;
}

function get_asset_loan_history(PDO $pdo, int $asset_id): array {
    $stmt = $pdo->prepare(
        "SELECT l.*, b.name AS borrower_name, b.department AS borrower_dept,
                u.name AS issued_by_name
         FROM loans l
         JOIN borrowers b ON l.borrower_id = b.id
         LEFT JOIN users u ON l.issued_by = u.id
         WHERE l.asset_id = ? AND l.return_date IS NOT NULL
         ORDER BY l.return_date DESC LIMIT 10"
    );
    $stmt->execute([$asset_id]);
    return $stmt->fetchAll();
}

function get_all_active_borrowers(PDO $pdo): array {
    return $pdo->query("SELECT id, name, id_number, department, phone FROM borrowers ORDER BY name ASC")->fetchAll();
}

// ============================================================
// HELPER FUNCTIONS
// ============================================================

function status_badge(string $status): string {
    $classes = [
        'available'    => 'bg-success',
        'on_loan'      => 'bg-primary',
        'under_repair' => 'bg-warning text-dark',
        'retired'      => 'bg-secondary',
    ];
    $class = $classes[$status] ?? 'bg-secondary';
    $label = str_replace('_', ' ', ucfirst($status));
    return "<span class=\"badge {$class}\">{$label}</span>";
}

function loan_status(array $loan): string {
    if ($loan['return_date'] !== null) {
        return '<span class="badge bg-success">Returned</span>';
    }
    if (strtotime($loan['due_date']) < strtotime('today')) {
        return '<span class="badge bg-danger">Overdue</span>';
    }
    return '<span class="badge bg-primary">Active</span>';
}

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function is_valid_date(string $date): bool {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function asset_categories(): array {
    return ['Laptop', 'Projector', 'Tablet', 'Camera', 'Cable/Accessory', 'Other'];
}

function asset_brands(): array {
    return [
        'Apple', 'Dell', 'HP', 'Lenovo', 'Acer', 'ASUS', 'MSI',
        'Samsung', 'Sony', 'LG', 'Toshiba', 'Fujitsu', 'Panasonic',
        'Canon', 'Nikon', 'Epson', 'BenQ', 'ViewSonic', 'Logitech',
        'Microsoft', 'Google', 'Huawei', 'Xiaomi', 'Realme',
        'Other',
    ];
}
