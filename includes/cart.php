<?php
/**
 * Urdu Books World - Cart Logic
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Initialize session cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/**
 * Add a book to the cart.
 */
function add_to_cart($book_id, $qty = 1) {
    global $pdo;
    $book_id = (int)$book_id;
    $qty = (int)$qty;
    if ($qty <= 0) $qty = 1;
    
    // Verify book exists and has stock
    $stmt = $pdo->prepare("SELECT id, stock_quantity FROM books WHERE id = ? AND is_active = 1");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch();
    
    if (!$book) {
        return ['status' => false, 'message' => t('book_not_found')];
    }

    if ((int)$book['stock_quantity'] <= 0) {
        return ['status' => false, 'message' => t('out_of_stock')];
    }
    
    if (is_logged_in()) {
        $user_id = $_SESSION['user_id'];
        try {
            // Check if already in cart
            $stmt = $pdo->prepare("SELECT quantity FROM cart WHERE user_id = ? AND book_id = ?");
            $stmt->execute([$user_id, $book_id]);
            $item = $stmt->fetch();
            
            if ($item) {
                $new_qty = min($item['quantity'] + $qty, (int)$book['stock_quantity']);
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND book_id = ?");
                $stmt->execute([$new_qty, $user_id, $book_id]);
            } else {
                $qty = min($qty, (int)$book['stock_quantity']);
                $stmt = $pdo->prepare("INSERT INTO cart (user_id, book_id, quantity) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $book_id, $qty]);
            }
            return ['status' => true];
        } catch (PDOException $e) {
            error_log("Add to db cart failed: " . $e->getMessage());
            return ['status' => false, 'message' => t('cart_error')];
        }
    } else {
        // Guest session cart
        if (isset($_SESSION['cart'][$book_id])) {
            $_SESSION['cart'][$book_id] = min($_SESSION['cart'][$book_id] + $qty, (int)$book['stock_quantity']);
        } else {
            $_SESSION['cart'][$book_id] = min($qty, (int)$book['stock_quantity']);
        }
        return ['status' => true];
    }
}

/**
 * Update quantity in cart.
 */
function update_cart_qty($book_id, $qty) {
    global $pdo;
    $book_id = (int)$book_id;
    $qty = (int)$qty;
    
    if ($qty <= 0) {
        return remove_from_cart($book_id);
    }

    $stmt = $pdo->prepare("SELECT stock_quantity FROM books WHERE id = ? AND is_active = 1");
    $stmt->execute([$book_id]);
    $stock = $stmt->fetchColumn();
    if ($stock === false) {
        return ['status' => false, 'message' => t('book_not_found')];
    }
    if ((int)$stock <= 0) {
        return ['status' => false, 'message' => t('out_of_stock')];
    }
    $qty = min($qty, (int)$stock);
    
    if (is_logged_in()) {
        $user_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND book_id = ?");
        $stmt->execute([$qty, $user_id, $book_id]);
    } else {
        $_SESSION['cart'][$book_id] = $qty;
    }
    
    return ['status' => true];
}

/**
 * Remove an item from the cart.
 */
function remove_from_cart($book_id) {
    global $pdo;
    $book_id = (int)$book_id;
    
    if (is_logged_in()) {
        $user_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND book_id = ?");
        $stmt->execute([$user_id, $book_id]);
    } else {
        if (isset($_SESSION['cart'][$book_id])) {
            unset($_SESSION['cart'][$book_id]);
        }
    }
    
    return ['status' => true];
}

/**
 * Clear the entire cart.
 */
function clear_cart() {
    global $pdo;
    
    if (is_logged_in()) {
        $user_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
    }
    
    $_SESSION['cart'] = [];
}

/**
 * Get all items in the cart with details.
 */
function get_cart_items() {
    global $pdo;
    $items = [];
    
    if (is_logged_in()) {
        $user_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT c.quantity, b.*, a.name_en AS author_en, a.name_ur AS author_ur, p.name_en AS publisher_en, p.name_ur AS publisher_ur
            FROM cart c
            JOIN books b ON c.book_id = b.id
            JOIN authors a ON b.author_id = a.id
            JOIN publishers p ON b.publisher_id = p.id
            WHERE c.user_id = ? AND b.is_active = 1");
        $stmt->execute([$user_id]);
        $items = $stmt->fetchAll();
    } else {
        if (!empty($_SESSION['cart'])) {
            $book_ids = array_keys($_SESSION['cart']);
            $placeholders = implode(',', array_fill(0, count($book_ids), '?'));
            
            $stmt = $pdo->prepare("SELECT b.*, a.name_en AS author_en, a.name_ur AS author_ur, p.name_en AS publisher_en, p.name_ur AS publisher_ur
                FROM books b
                JOIN authors a ON b.author_id = a.id
                JOIN publishers p ON b.publisher_id = p.id
                WHERE b.id IN ($placeholders) AND b.is_active = 1");
            $stmt->execute($book_ids);
            $books = $stmt->fetchAll();
            
            foreach ($books as $book) {
                $book['quantity'] = $_SESSION['cart'][$book['id']];
                $items[] = $book;
            }
        }
    }
    
    // Calculate actual discounts and line totals
    foreach ($items as &$item) {
        $discount = (float)$item['discount_percent'];
        $price = (float)$item['price_gbp'];
        
        $final_price = $price - ($price * ($discount / 100));
        $item['discounted_price'] = $final_price;
        $item['line_total'] = $final_price * $item['quantity'];
    }
    
    return $items;
}

/**
 * Get total item count in the cart.
 */
function get_cart_count() {
    global $pdo;
    
    if (is_logged_in()) {
        $user_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return (int)$stmt->fetchColumn();
    } else {
        return (int)array_sum($_SESSION['cart']);
    }
}

/**
 * Merge guest session cart into database cart on login.
 */
function sync_session_cart_to_db() {
    global $pdo;
    
    if (!is_logged_in() || empty($_SESSION['cart'])) {
        return;
    }
    
    $user_id = $_SESSION['user_id'];
    
    foreach ($_SESSION['cart'] as $book_id => $qty) {
        // Add each item to the database cart
        add_to_cart($book_id, $qty);
    }
    
    // Clear session cart
    $_SESSION['cart'] = [];
}
