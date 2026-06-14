<?php
/**
 * User Dashboard
 */

require_once __DIR__ . '/../../config.php';
require_once CORE_PATH . '/Database/Connection.php';
require_once CORE_PATH . '/Helpers/Security.php';
require_once CORE_PATH . '/Helpers/Session.php';
require_once CORE_PATH . '/Classes/Order.php';
require_once CORE_PATH . '/Classes/Cart.php';
require_once CORE_PATH . '/Classes/Product.php';
require_once CORE_PATH . '/Classes/User.php';

use Core\Helpers\Security;
use Core\Helpers\Session;
use Core\Classes\Order;
use Core\Classes\Cart;
use Core\Classes\Product;
use Core\Classes\User;

Session::start();
Session::requireAuth();

$userId       = Session::get('user_id');
$orderModel   = new Order();
$recentOrders = $orderModel->getUserOrders($userId, 1, 5)['items'];
$totalOrders  = $orderModel->getUserOrders($userId, 1, 1)['total'];
$wishlist     = (new Product())->getWishlist($userId);
$cartCount    = (new Cart())->countItems($userId);
$user         = (new User())->findById($userId);

$pageTitle = 'My Dashboard — AURA';
require_once VIEWS_PATH . '/partials/head.php';
require_once VIEWS_PATH . '/partials/navbar.php';
?>

<main style="padding-top:var(--nav-h);min-height:100vh">
<div class="container" style="padding-top:60px;padding-bottom:80px">

    <!-- Welcome Banner -->
    <div data-aos="fade-up" style="background:linear-gradient(135deg,#1a1a18 0%,#2d2b26 100%);
         border-radius:var(--radius-xl);padding:40px 48px;margin-bottom:40px;
         display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:24px;
         position:relative;overflow:hidden">
        <div style="position:absolute;inset:0;background:radial-gradient(ellipse 50% 80% at 80% 50%,rgba(184,151,106,.12) 0%,transparent 70%)"></div>
        <div style="position:relative;z-index:1">
            <p style="color:var(--color-gold-lt);font-size:.75rem;letter-spacing:.2em;text-transform:uppercase;margin-bottom:8px">
                Welcome back
            </p>
            <h1 style="color:#FAFAF8;font-size:clamp(1.5rem,3vw,2rem);margin-bottom:6px">
                <?= Security::h($user['name'] ?? 'Member') ?>
            </h1>
            <p style="color:rgba(250,250,248,.45);font-size:.875rem">
                Member since <?= date('F Y', strtotime($user['created_at'] ?? 'now')) ?>
            </p>
        </div>
        <div style="position:relative;z-index:1;display:flex;gap:16px;flex-wrap:wrap">
            <a href="<?= APP_URL ?>/shop.php"         class="btn btn-primary">Shop Now</a>
            <a href="<?= APP_URL ?>/user/orders.php"  class="btn btn-outline btn-outline-light">My Orders</a>
        </div>
    </div>

    <!-- Stats -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:20px;margin-bottom:40px">
        <?php
        $stats = [
            ['icon'=>'📦','label'=>'Total Orders',   'value'=>$totalOrders],
            ['icon'=>'🛍','label'=>'Cart Items',     'value'=>$cartCount],
            ['icon'=>'♡', 'label'=>'Wishlist Items', 'value'=>count($wishlist)],
        ];
        foreach ($stats as $i => $s): ?>
        <div class="stat-card" data-aos="fade-up" data-aos-delay="<?= $i * 80 ?>">
            <div class="stat-card__icon"><?= $s['icon'] ?></div>
            <div>
                <div class="stat-card__value"><?= $s['value'] ?></div>
                <div class="stat-card__label"><?= Security::h($s['label']) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:32px" class="dash-grid">

        <!-- Recent Orders -->
        <div data-aos="fade-right">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
                <h2 style="font-size:1.2rem">Recent Orders</h2>
                <a href="<?= APP_URL ?>/user/orders.php" class="btn btn-ghost btn-sm">View all</a>
            </div>

            <?php if (empty($recentOrders)): ?>
            <div class="card" style="text-align:center;padding:48px">
                <p style="font-size:2.5rem;margin-bottom:14px">📦</p>
                <p style="color:var(--color-text-2);margin-bottom:20px">No orders yet.</p>
                <a href="<?= APP_URL ?>/shop.php" class="btn btn-primary btn-sm">Start Shopping</a>
            </div>
            <?php else: ?>
            <div class="table-wrap" style="border-radius:var(--radius-lg);border:1px solid var(--color-border)">
                <table class="table">
                    <thead>
                        <tr><th>Order #</th><th>Date</th><th>Total</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $o): ?>
                        <tr style="cursor:pointer"
                            onclick="window.location='<?= APP_URL ?>/user/orders.php?id=<?= $o['id'] ?>'">
                            <td><strong style="font-size:.8rem"><?= Security::h($o['order_number']) ?></strong></td>
                            <td style="font-size:.8rem"><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
                            <td>$<?= number_format((float)$o['total_amount'], 2) ?></td>
                            <td><span class="status-badge status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Wishlist Preview -->
        <div data-aos="fade-left">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
                <h2 style="font-size:1.2rem">Saved Items</h2>
                <a href="<?= APP_URL ?>/user/wishlist.php" class="btn btn-ghost btn-sm">View all</a>
            </div>

            <?php if (empty($wishlist)): ?>
            <div class="card" style="text-align:center;padding:48px">
                <p style="font-size:2.5rem;margin-bottom:14px">♡</p>
                <p style="color:var(--color-text-2);margin-bottom:20px">Your wishlist is empty.</p>
                <a href="<?= APP_URL ?>/shop.php" class="btn btn-primary btn-sm">Browse Collection</a>
            </div>
            <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:12px">
                <?php foreach (array_slice($wishlist, 0, 4) as $w): ?>
                <div class="card" style="padding:14px 16px;display:flex;align-items:center;gap:14px">
                    <img src="<?= UPLOAD_URL . '/' . Security::h($w['image']) ?>"
                         alt="<?= Security::h($w['name']) ?>"
                         style="width:50px;height:62px;object-fit:cover;border-radius:var(--radius-sm);background:var(--color-bg-2);flex-shrink:0"
                         onerror="this.src='<?= APP_URL ?>/assets/images/placeholder.jpg'">
                    <div style="flex:1;min-width:0">
                        <p style="font-size:.8rem;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            <?= Security::h($w['name']) ?>
                        </p>
                        <p style="font-size:.75rem;color:var(--color-gold)">
                            $<?= number_format((float)(!empty($w['sale_price']) ? $w['sale_price'] : $w['price']), 2) ?>
                        </p>
                    </div>
                    <button class="btn btn-primary btn-sm" data-add-cart="<?= $w['id'] ?>"
                            <?= $w['stock'] == 0 ? 'disabled' : '' ?>>
                        <?= $w['stock'] == 0 ? 'OOS' : '+ Cart' ?>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Navigation Links -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;margin-top:36px"
         data-aos="fade-up">
        <?php
        $links = [
            ['href'=>APP_URL.'/user/profile.php',  'icon'=>'👤','label'=>'Edit Profile'],
            ['href'=>APP_URL.'/user/orders.php',   'icon'=>'📦','label'=>'My Orders'],
            ['href'=>APP_URL.'/user/wishlist.php', 'icon'=>'♡', 'label'=>'Wishlist'],
            ['href'=>APP_URL.'/cart.php',          'icon'=>'🛍','label'=>'My Cart'],
        ];
        foreach ($links as $l): ?>
        <a href="<?= $l['href'] ?>" class="card" style="text-align:center;padding:28px 16px;text-decoration:none;
           transition:all .3s;display:block"
           onmouseover="this.style.borderColor='var(--color-gold)';this.style.transform='translateY(-3px)'"
           onmouseout="this.style.borderColor='var(--color-border)';this.style.transform=''">
            <div style="font-size:1.75rem;margin-bottom:10px"><?= $l['icon'] ?></div>
            <p style="font-size:.8rem;font-weight:500;color:var(--color-text)"><?= Security::h($l['label']) ?></p>
        </a>
        <?php endforeach; ?>
    </div>

</div>
</main>

<style>@media(max-width:768px){.dash-grid{grid-template-columns:1fr !important}}</style>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>
