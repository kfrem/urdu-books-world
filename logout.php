<?php
/**
 * Urdu Books World - Logout Page
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

logout_user();

// Redirect back home with a fresh session
redirect(SITE_URL . '/index.php');
