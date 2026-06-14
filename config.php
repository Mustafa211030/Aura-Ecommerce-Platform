<?php
/**
 * AURA E-Commerce — Global Configuration
 */

declare(strict_types=1);

// ── Error Reporting ──────────────────────────────────────────
define('APP_ENV', 'development'); // change to 'production' when live

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// ── Core Paths (FIXED) ───────────────────────────────────────
define('BASE_PATH', __DIR__);                // aura-ecommerce
define('PUBLIC_PATH', BASE_PATH . '/public');
define('CORE_PATH', BASE_PATH . '/core');
define('VIEWS_PATH', BASE_PATH . '/views');

// ── App Info ──────────────────────────────────────────────────
define('APP_NAME',    'AURA');
define('APP_VERSION', '1.0.0');
define('APP_URL',     'http://localhost/aura-ecommerce/public');

// ── Database ──────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NAME',    'aura_ecommerce');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// ── Upload Settings ───────────────────────────────────────────
define('UPLOAD_PATH', PUBLIC_PATH . '/assets/images/uploads');
define('UPLOAD_URL',  APP_URL . '/assets/images/uploads');

define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

define('IMG_THUMB_W', 600);
define('IMG_THUMB_H', 750);

// ── Session & Security ────────────────────────────────────────
define('SESSION_TIMEOUT', 3600);
define('CSRF_TOKEN_NAME', '_aura_csrf');
define('TAX_RATE', 0.08);
define('SHIPPING_FLAT', 9.99);

// ── Pagination ────────────────────────────────────────────────
define('PRODUCTS_PER_PAGE', 12);
define('ORDERS_PER_PAGE', 10);