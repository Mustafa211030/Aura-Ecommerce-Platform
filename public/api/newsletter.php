<?php
/**
 * Newsletter Subscription API
 */

require_once __DIR__ . '/../../config.php';
require_once CORE_PATH . '/Database/Connection.php';
require_once CORE_PATH . '/Helpers/Security.php';
require_once CORE_PATH . '/Helpers/Session.php';

use Core\Helpers\Security;
use Core\Helpers\Session;
use Core\Database\Connection;

header('Content-Type: application/json');
Session::start();

$json = fn(array $d, int $c = 200): never => (http_response_code($c) && print json_encode($d) && exit());

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $json(['success' => false], 405);
}

$body  = json_decode(file_get_contents('php://input'), true) ?? [];
$email = filter_var(trim($body['email'] ?? ''), FILTER_VALIDATE_EMAIL);

if (!$email) {
    $json(['success' => false, 'message' => 'Please enter a valid email address.']);
}

try {
    $db   = Connection::getInstance()->getPDO();
    $stmt = $db->prepare('INSERT IGNORE INTO newsletter (email) VALUES (:email)');
    $stmt->execute([':email' => $email]);

    $json(['success' => true, 'message' => 'Thank you for subscribing to AURA!']);
} catch (\Exception $e) {
    error_log('[Newsletter] ' . $e->getMessage());
    $json(['success' => false, 'message' => 'Could not subscribe. Please try again.']);
}
