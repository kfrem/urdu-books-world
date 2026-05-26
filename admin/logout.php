<?php
/**
 * Urdu Books World - Admin Logout
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

logout_user();

// Redirect back to admin login panel
redirect(SITE_URL . '/admin/login.php');
