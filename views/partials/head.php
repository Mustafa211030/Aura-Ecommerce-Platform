<?php
/**
 * Head Partial — injected at top of every view.
 *
 * Expects $pageTitle to be set before including.
 */
use Core\Helpers\Security;
use Core\Helpers\Session;

$pageTitle = $pageTitle ?? 'AURA — Luxury Fashion';
$csrf      = Security::csrfToken();
$isLogged  = Session::isLoggedIn();
$isAdmin   = Session::isAdmin();
$flash     = Session::getFlash();

// Cart count (cached in session to avoid DB hit per page)
$cartCount = Session::get('cart_count', 0);

// JSON-encode flash for JS Toast
$flashJson = json_encode(array_merge(
    array_map(fn($m) => ['type'=>'success', 'message'=>$m], $flash['success'] ?? []),
    array_map(fn($m) => ['type'=>'error',   'message'=>$m], $flash['error']   ?? []),
    array_map(fn($m) => ['type'=>'info',    'message'=>$m], $flash['info']    ?? []),
    array_map(fn($m) => ['type'=>'warning', 'message'=>$m], $flash['warning'] ?? [])
));
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="AURA — Where luxury meets timeless fashion.">
    <title><?= Security::h($pageTitle) ?></title>

    <!-- Favicon -->
    <link rel="icon" href="<?= APP_URL ?>/assets/images/favicon.ico" type="image/x-icon">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- AOS -->
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css">

    <!-- Main CSS -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/main.css">

    <!-- Global JS config -->
    <script>
        window.__AURA_URL__   = '<?= APP_URL ?>';
        window.__AURA_CSRF__  = '<?= $csrf ?>';
        window.__AURA_FLASH__ = <?= $flashJson ?>;
    </script>
</head>
<body>

<!-- Page Loader -->
<div id="page-loader">
    <div class="loader-logo">AURA</div>
    <div class="loader-bar"></div>
</div>

<!-- Toast Container -->
<div class="toast-container"></div>

<!-- Cart Overlay -->
<div class="cart-overlay" id="cart-overlay"></div>

<!-- Mini Cart Drawer -->
<div class="mini-cart" id="mini-cart" role="dialog" aria-label="Shopping cart">
    <div class="mini-cart__header">
        <h2 class="mini-cart__title">Your Cart</h2>
        <button class="mini-cart__close" id="mini-cart-close" aria-label="Close cart">✕</button>
    </div>
    <div class="mini-cart__items" id="mini-cart-items"></div>
    <div class="mini-cart__footer">
        <div class="mini-cart__total">
            <span>Subtotal</span>
            <span id="mini-cart-total">$0.00</span>
        </div>
        <a href="<?= APP_URL ?>/cart.php" class="btn btn-primary" style="width:100%;margin-bottom:10px;justify-content:center">
            View Cart
        </a>
        <a href="<?= APP_URL ?>/checkout.php" class="btn btn-outline" style="width:100%;justify-content:center">
            Checkout
        </a>
    </div>
</div>
