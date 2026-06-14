<?php
/**
 * Admin Sidebar Partial
 */
use Core\Helpers\Security;

$currentPage = basename($_SERVER['PHP_SELF']);
$adminLinks  = [
    'dashboard.php'       => ['icon' => '◈', 'label' => 'Dashboard'],
    'products.php'        => ['icon' => '🏷', 'label' => 'Products'],
    'orders.php'          => ['icon' => '📦', 'label' => 'Orders'],
    'users.php'           => ['icon' => '👥', 'label' => 'Users'],
];
?>
<aside class="admin-sidebar" id="admin-sidebar">
    <div class="admin-sidebar__logo">
        <span>AURA</span>
        <small>Admin Panel</small>
    </div>

    <nav class="sidebar-nav">
        <p class="sidebar-nav__group-label">Management</p>
        <?php foreach ($adminLinks as $file => $info): ?>
        <a href="<?= APP_URL ?>/admin/<?= $file ?>"
           class="<?= $currentPage === $file ? 'active' : '' ?>">
            <span class="nav-icon"><?= $info['icon'] ?></span>
            <?= Security::h($info['label']) ?>
        </a>
        <?php endforeach; ?>

        <p class="sidebar-nav__group-label" style="margin-top:16px">Account</p>
        <a href="<?= APP_URL ?>/user/profile.php">
            <span class="nav-icon">👤</span> My Profile
        </a>
        <a href="<?= APP_URL ?>/index.php">
            <span class="nav-icon">🏠</span> View Store
        </a>
        <a href="<?= APP_URL ?>/logout.php" style="color:var(--color-danger)">
            <span class="nav-icon">⏻</span> Logout
        </a>
    </nav>
</aside>
