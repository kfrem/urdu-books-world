<?php
/**
 * Urdu Books World - Database Connection
 * PDO connection layer with UTF-8 config.
 */

require_once __DIR__ . '/config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Log the error and show a user-friendly message
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please check back later or run the installer at /admin/install.php.");
}
