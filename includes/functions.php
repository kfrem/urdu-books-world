<?php
/**
 * Urdu Books World - Core Helper Functions
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Cache settings locally to avoid repeated DB hits
$GLOBALS['site_settings'] = [];

/**
 * HTML Escaping wrapper to prevent XSS.
 */
function e($text) {
    if ($text === null) return '';
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirection helper.
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * Fetch a translation string by key.
 */
function t($key) {
    static $translations = [];
    
    if (empty($translations)) {
        $langFile = __DIR__ . '/lang/' . CURRENT_LANG . '.php';
        if (file_exists($langFile)) {
            $translations = require $langFile;
        } else {
            $translations = [];
        }
    }
    
    return isset($translations[$key]) ? $translations[$key] : $key;
}

/**
 * Format a number as GBP currency.
 */
function money($amount) {
    return '£' . number_construct($amount);
}

/**
 * Convert and format GBP to PKR currency.
 */
function pkr($amount, $rate = null) {
    if ($rate === null) {
        $rate = (float) get_setting('pkr_to_gbp_rate', 360);
    }
    $pkr_amount = (float)$amount * $rate;
    return 'Rs. ' . number_format($pkr_amount, 0);
}

/**
 * Private helper to format decimal numbers nicely.
 */
function number_construct($number) {
    return number_format((float)$number, 2, '.', ',');
}

/**
 * Retrieve a setting value from the database.
 */
function get_setting($key, $default = '') {
    global $pdo;
    
    if (isset($GLOBALS['site_settings'][$key])) {
        return $GLOBALS['site_settings'][$key];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT `setting_value` FROM `settings` WHERE `setting_key` = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        if ($row) {
            $GLOBALS['site_settings'][$key] = $row['setting_value'];
            return $row['setting_value'];
        }
    } catch (PDOException $e) {
        // Fallback if settings table does not exist yet (during installation)
        return $default;
    }
    
    return $default;
}

/**
 * Update/Set a setting key-value pair in database.
 */
function set_setting($key, $value) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `setting_value` = ?");
    $stmt->execute([$key, $value, $value]);
    $GLOBALS['site_settings'][$key] = $value;
}

/**
 * Generate standard HTML CSRF token input field.
 */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . e($_SESSION['csrf_token']) . '">';
}

/**
 * Verify form POST CSRF token.
 */
function verify_csrf_token() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            http_response_code(403);
            die("CSRF Token validation failed. Access Denied.");
        }
    }
}

/**
 * Check if the active language is RTL (Urdu).
 */
function is_rtl() {
    return CURRENT_LANG === 'ur';
}

/**
 * Generate beautiful and clean URL slugs.
 */
function slugify($text) {
    // Replace non letter or digits by -
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // Transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    // Remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);
    // Trim
    $text = trim($text, '-');
    // Remove duplicate -
    $text = preg_replace('~-+~', '-', $text);
    // Lowercase
    $text = strtolower($text);
    
    if (empty($text)) {
        return 'n-a';
    }
    
    return $text;
}

/**
 * Dynamic content select helper for bilingual database columns (e.g. title, description).
 */
function lang_val($row, $fieldPrefix) {
    $field = $fieldPrefix . '_' . CURRENT_LANG;
    if (isset($row[$field]) && !empty($row[$field])) {
        return $row[$field];
    }
    // Fallback to the other language if active is empty
    $fallbackField = $fieldPrefix . '_' . (CURRENT_LANG === 'en' ? 'ur' : 'en');
    return isset($row[$fallbackField]) ? $row[$fallbackField] : '';
}

/**
 * Get the cover image URL for a book.
 */
function get_book_cover($book) {
    if (!empty($book['cover_image'])) {
        if (strpos($book['cover_image'], 'http://') === 0 || strpos($book['cover_image'], 'https://') === 0) {
            return $book['cover_image'];
        }
        return SITE_URL . '/assets/images/covers/' . $book['cover_image'];
    }
    
    if (!empty($book['isbn'])) {
        // Use Open Library Cover API
        return "https://covers.openlibrary.org/b/isbn/" . $book['isbn'] . "-L.jpg";
    }
    
    // Final text placeholder fallback
    $title = isset($book['title_en']) ? urlencode($book['title_en']) : 'Book';
    return "https://placehold.co/300x450?text=" . $title;
}

