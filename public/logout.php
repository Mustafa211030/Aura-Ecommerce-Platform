<?php
/**
 * Logout — destroys session and redirects to login.
 */
require_once __DIR__ . '/../config.php';
require_once CORE_PATH . '/Helpers/Session.php';

use Core\Helpers\Session;

Session::start();
Session::destroy();

header('Location: ' . APP_URL . '/login.php?logged_out=1');
exit;
