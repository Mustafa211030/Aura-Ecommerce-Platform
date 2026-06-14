<?php
/**
 * Cart API — handles AJAX add/update/remove/mini/totals
 */

require_once __DIR__ . '/../../config.php';
require_once CORE_PATH . '/Database/Connection.php';
require_once CORE_PATH . '/Helpers/Security.php';
require_once CORE_PATH . '/Helpers/Session.php';
require_once CORE_PATH . '/Classes/Cart.php';

use Core\Helpers\Security;
use Core\Helpers\Session;
use Core\Classes\Cart;

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

Session::start();

$json = function(array $data, int $code = 200): never {
    http_response_code($code);
    echo json_encode($data);
    exit;
};

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// GET requests
if ($method === 'GET') {
    if (!Session::isLoggedIn()) {
        $json(['success' => false, 'message' => 'Not logged in'], 401);
    }

    $cartModel = new Cart();
    $userId    = Session::get('user_id');

    if ($action === 'mini') {
        $items  = $cartModel->getItems($userId);
        $totals = $cartModel->getTotals($userId);
        $count  = $cartModel->countItems($userId);
        $json(['success' => true, 'items' => $items, 'totals' => $totals, 'count' => $count]);
    }

    if ($action === 'totals') {
        $totals = $cartModel->getTotals($userId);
        $count  = $cartModel->countItems($userId);
        $json(['success' => true, 'totals' => $totals, 'count' => $count]);
    }

    $json(['success' => false, 'message' => 'Unknown action']);
}

// POST requests
if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $body['action'] ?? '';
    $csrf   = $body['_csrf'] ?? '';

    // CSRF check
    if (!hash_equals(Security::csrfToken(), $csrf)) {
        $json(['success' => false, 'message' => 'CSRF token invalid'], 403);
    }

    if (!Session::isLoggedIn()) {
        $json(['success' => false, 'message' => 'Please log in to use cart.'], 401);
    }

    $cartModel = new Cart();
    $userId    = Session::get('user_id');

    if ($action === 'add') {
        $productId = (int)($body['product_id'] ?? 0);
        $qty       = max(1, (int)($body['quantity'] ?? 1));
        if (!$productId) { $json(['success' => false, 'message' => 'Invalid product'], 400); }

        $cartModel->addItem($userId, $productId, $qty);
        $count = $cartModel->countItems($userId);
        Session::set('cart_count', $count);
        $json(['success' => true, 'count' => $count]);
    }

    if ($action === 'update') {
        $cartId = (int)($body['cart_id'] ?? 0);
        $qty    = (int)($body['quantity'] ?? 0);
        if (!$cartId) { $json(['success' => false, 'message' => 'Invalid cart item'], 400); }

        $cartModel->updateQuantity($cartId, $userId, $qty);
        $count  = $cartModel->countItems($userId);
        $totals = $cartModel->getTotals($userId);
        Session::set('cart_count', $count);
        $json(['success' => true, 'count' => $count, 'totals' => $totals]);
    }

    if ($action === 'remove') {
        $cartId = (int)($body['cart_id'] ?? 0);
        if (!$cartId) { $json(['success' => false, 'message' => 'Invalid cart item'], 400); }

        $cartModel->removeItem($cartId, $userId);
        $count  = $cartModel->countItems($userId);
        $totals = $cartModel->getTotals($userId);
        Session::set('cart_count', $count);
        $json(['success' => true, 'count' => $count, 'totals' => $totals]);
    }

    $json(['success' => false, 'message' => 'Unknown action'], 400);
}

$json(['success' => false, 'message' => 'Method not allowed'], 405);
