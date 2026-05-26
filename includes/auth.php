<?php
/**
 * Urdu Books World - Authentication Layer
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

/**
 * Register a new user.
 */
function register_user($email, $password, $first_name, $last_name, $phone = '', $address1 = '', $address2 = '', $city = '', $postcode = '', $country = 'United Kingdom') {
    global $pdo;
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['status' => false, 'message' => t('email_exists')];
    }
    
    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    $preferred_lang = CURRENT_LANG;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users 
            (email, password_hash, first_name, last_name, phone, address_line1, address_line2, city, postcode, country, preferred_language) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$email, $password_hash, $first_name, $last_name, $phone, $address1, $address2, $city, $postcode, $country, $preferred_lang]);
        
        $user_id = $pdo->lastInsertId();
        
        // Log in the user immediately
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_role'] = 'customer';
        $_SESSION['user_name'] = $first_name . ' ' . $last_name;
        $_SESSION['user_email'] = $email;
        
        session_regenerate_id(true);
        return ['status' => true, 'user_id' => $user_id];
    } catch (PDOException $e) {
        error_log("Registration failed: " . $e->getMessage());
        return ['status' => false, 'message' => t('registration_error')];
    }
}

/**
 * Log in a user.
 */
function login_user($email, $password) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_email'] = $user['email'];
            
            // Sync preferences
            $_SESSION['lang'] = $user['preferred_language'];
            setcookie('lang', $user['preferred_language'], time() + (86400 * 30), "/");
            
            session_regenerate_id(true);
            return ['status' => true, 'role' => $user['role']];
        }
        
        return ['status' => false, 'message' => t('invalid_credentials')];
    } catch (PDOException $e) {
        error_log("Login failed: " . $e->getMessage());
        return ['status' => false, 'message' => t('login_error')];
    }
}

/**
 * Log out current session.
 */
function logout_user() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Helper to check login status.
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Get current user details.
 */
function current_user() {
    global $pdo;
    
    if (!is_logged_in()) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Restrict pages to authenticated users with optional specific roles.
 */
function require_login($required_roles = []) {
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect(SITE_URL . '/login.php');
    }
    
    if (!empty($required_roles)) {
        if (!in_array($_SESSION['user_role'], $required_roles)) {
            http_response_code(403);
            die("Unauthorized Access. You do not have permissions for this page.");
        }
    }
}

/**
 * Restrict pages strictly to Admins.
 */
function require_admin() {
    require_login(['admin']);
}
