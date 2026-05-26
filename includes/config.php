<?php
/**
 * Urdu Books World - Core Configuration
 * Secure configurations for database connection, sessions, and locales.
 */

// 1. Error Reporting (Enable for development, disable in production if necessary)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Secure default, logging instead.
ini_set('log_errors', 1);

// 2. Deployment Configuration
$local_config_file = __DIR__ . '/config.local.php';
$local_config = file_exists($local_config_file) ? require $local_config_file : [];
if (!is_array($local_config)) {
    $local_config = [];
}

function config_value($key, $default = '') {
    global $local_config;
    $env = getenv($key);
    if ($env !== false && $env !== '') {
        return $env;
    }
    return array_key_exists($key, $local_config) ? $local_config[$key] : $default;
}

function detect_site_url() {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
             (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}

// 3. Database Configuration
define('DB_HOST', config_value('DB_HOST', 'localhost'));
define('DB_NAME', config_value('DB_NAME', 'urdubooks'));
define('DB_USER', config_value('DB_USER', 'root'));
define('DB_PASS', config_value('DB_PASS', ''));
define('DB_CHARSET', 'utf8mb4');

// 4. Global URL Settings
define('SITE_URL', rtrim(config_value('SITE_URL', detect_site_url()), '/'));

// 4. Session Security Settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    
    // Support HTTPS cookies if SSL is active
    $is_secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
                 (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    ini_set('session.cookie_secure', $is_secure ? 1 : 0);
    
    session_start();
}

// 5. Language Selection Logic (Session & Cookie based)
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'] === 'ur' ? 'ur' : 'en';
    $_SESSION['lang'] = $lang;
    setcookie('lang', $lang, time() + (86400 * 30), "/"); // 30 days
} elseif (isset($_SESSION['lang'])) {
    $lang = $_SESSION['lang'];
} elseif (isset($_COOKIE['lang'])) {
    $lang = $_COOKIE['lang'] === 'ur' ? 'ur' : 'en';
    $_SESSION['lang'] = $lang;
} else {
    $lang = 'en'; // English default
    $_SESSION['lang'] = $lang;
}

define('CURRENT_LANG', $lang);

// 6. CSRF Protection Token Initialization
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
