<?php
declare(strict_types=1);

// Authentication and authorization helpers

function require_login(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function require_role(string $role): void {
    require_login();
    if ($_SESSION['user_role'] !== $role) {
        http_response_code(403);
        die('Access denied. Admin privileges required.');
    }
}

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function current_user_id(): int {
    return (int) ($_SESSION['user_id'] ?? 0);
}

function current_user_role(): string {
    return $_SESSION['user_role'] ?? '';
}

function current_user_name(): string {
    return $_SESSION['user_name'] ?? '';
}

// CSRF token generation and validation
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verify_csrf(): bool {
    $token = $_POST['csrf_token'] ?? '';
    return hash_equals(csrf_token(), $token);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
