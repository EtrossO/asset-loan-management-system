<?php
declare(strict_types=1);

// Database configuration
$DB_HOST = 'localhost';
$DB_NAME = 'ict_asset_loan';
$DB_USER = 'root';
$DB_PASS = '';
$DB_CHARSET = 'utf8mb4';

// Base URL for QR code generation (auto-detect)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
$BASE_URL = $protocol . '://' . $host . '/asset-loan';

// CSRF token secret (change this to a random string in production)
$CSRF_SECRET = 'change-me-to-a-random-string-in-production';

// QR code signing secret
$QR_SECRET = 'change-me-to-a-random-qr-secret';

try {
    $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}
