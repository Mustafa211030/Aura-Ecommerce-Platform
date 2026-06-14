<?php
/**
 * Wishlist API — toggle add/remove
 */

require_once __DIR__ . '/../../config.php';
require_once CORE_PATH . '/Database/Connection.php';
require_once CORE_PATH . '/Helpers/Security.php';
require_once CORE_PATH . '/Helpers/Session.php';
require_once CORE_PATH . '/Classes/Product.php';

use Core\Helpers\Security;
use Core\Helpers\Session;
use Core\Classes\Product;

header('Content-Type: application/json');
Session::start();

$json = fn(array $d, int $c = 200): never => (http_response_code($c) && print json_encode($d) && exit());

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $json(['success' => false, 'message' => 'Method not allowed'], 405);
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];

if (!hash_equals(Security::csrfToken(), $body['_csrf'] ?? '')) {
    $json(['success' => false, 'message' => 'CSRF error'], 403);
}

if (!Session::isLoggedIn()) {
    $json(['success' => false, 'message' => 'Login required'], 401);
}

$productId = (int)($body['product_id'] ?? 0);
if (!$productId) {
    $json(['success' => false, 'message' => 'Invalid product'], 400);
}

$action = (new Product())->toggleWishlist(Session::get('user_id'), $productId);
$json(['success' => true, 'action' => $action]);
