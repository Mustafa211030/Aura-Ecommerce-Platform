<?php
/**
 * Main Navigation Bar Partial
 */
use Core\Helpers\Security;
use Core\Helpers\Session;

$currentUri = strtok($_SERVER['REQUEST_URI'], '?');
$isLoggedIn = Session::isLoggedIn();
$isAdmin    = Session::isAdmin();
$cartCount  = Session::get('cart_count', 0);

$navLinks = [
    APP_URL . '/index.php'    => 'Home',
    APP_URL . '/shop.php'     => 'Shop',
    APP_URL . '/about.php'    => 'About',
    APP_URL . '/contact.php'  => 'Contact',
];
?>
<!-- ── Navbar ── -->
<nav class="navbar" id="main-nav" role="navigation" aria-label="Main navigation">
    <div class="navbar__inner">

        <!-- Logo -->
        <a href="<?= APP_URL ?>/index.php" class="navbar__logo" aria-label="AURA Home">
            AUR<span>A</span>
        </a>

        <!-- Desktop Nav Links -->
        <ul class="navbar__nav" role="list">
            <?php foreach ($navLinks as $href => $label): ?>
            <li>
                <a href="<?= $href ?>"
                   class="<?= strpos($currentUri, basename($href, '.php')) !== false ? 'active' : '' ?>">
                    <?= Security::h($label) ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>

        <!-- Actions -->
        <div class="navbar__actions">
            <!-- Theme Toggle -->
            <button class="theme-toggle" id="theme-toggle" aria-label="Toggle dark mode" title="Toggle theme"></button>

            <!-- Wishlist -->
            <?php if ($isLoggedIn): ?>
            <a href="<?= APP_URL ?>/user/wishlist.php" class="nav-icon-btn" aria-label="Wishlist">
                ♡
            </a>
            <?php endif; ?>

            <!-- Cart -->
            <button class="nav-icon-btn" id="cart-toggle" aria-label="Shopping cart" aria-expanded="false">
                🛍
                <span class="nav-badge nav-cart-count" style="<?= $cartCount > 0 ? '' : 'display:none' ?>">
                    <?= $cartCount ?>
                </span>
            </button>

            <!-- User Menu -->
            <?php if ($isLoggedIn): ?>
            <div style="position:relative">
                <a href="<?= $isAdmin ? APP_URL.'/admin/dashboard.php' : APP_URL.'/user/dashboard.php' ?>"
                   class="nav-icon-btn" aria-label="Account">
                    👤
                </a>
            </div>
            <a href="<?= APP_URL ?>/logout.php" class="btn btn-sm btn-outline"
               style="font-size:.7rem;padding:7px 16px">
                Logout
            </a>
            <?php else: ?>
            <a href="<?= APP_URL ?>/login.php" class="btn btn-sm btn-outline"
               style="font-size:.7rem;padding:7px 16px">
                Login
            </a>
            <a href="<?= APP_URL ?>/register.php" class="btn btn-sm btn-primary"
               style="font-size:.7rem;padding:7px 16px">
                Sign Up
            </a>
            <?php endif; ?>

            <!-- Hamburger (Mobile) -->
            <button class="hamburger" id="hamburger" aria-label="Open menu" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</nav>

<!-- Mobile Menu -->
<nav class="mobile-menu" id="mobile-menu" aria-label="Mobile navigation">
    <?php foreach ($navLinks as $href => $label): ?>
    <a href="<?= $href ?>"><?= Security::h($label) ?></a>
    <?php endforeach; ?>
    <?php if ($isLoggedIn): ?>
    <a href="<?= $isAdmin ? APP_URL.'/admin/dashboard.php' : APP_URL.'/user/dashboard.php' ?>">Dashboard</a>
    <a href="<?= APP_URL ?>/logout.php">Logout</a>
    <?php else: ?>
    <a href="<?= APP_URL ?>/login.php">Login</a>
    <a href="<?= APP_URL ?>/register.php">Sign Up</a>
    <?php endif; ?>
</nav>
