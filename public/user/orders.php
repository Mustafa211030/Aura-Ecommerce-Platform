<?php
/**
 * User Orders Page — list & detail view
 */

require_once __DIR__ . '/../../config.php';
require_once CORE_PATH . '/Database/Connection.php';
require_once CORE_PATH . '/Helpers/Security.php';
require_once CORE_PATH . '/Helpers/Session.php';
require_once CORE_PATH . '/Classes/Order.php';

use Core\Helpers\Security;
use Core\Helpers\Session;
use Core\Classes\Order;

Session::start();
Session::requireAuth();

$userId     = Session::get('user_id');
$orderModel = new Order();

// Single order detail?
$orderId = (int)($_GET['id'] ?? 0);
$order   = $orderId ? $orderModel->getOrderDetail($orderId, $userId) : null;

if (!$order && $orderId) {
    Session::flash('error', 'Order not found.');
    header('Location: ' . APP_URL . '/user/orders.php');
    exit;
}

$page    = max(1, (int)($_GET['page'] ?? 1));
$result  = $orderModel->getUserOrders($userId, $page, ORDERS_PER_PAGE);
$orders  = $result['items'];
$totalPg = $result['pages'];

$pageTitle = $order ? 'Order ' . Security::h($order['order_number']) . ' — AURA' : 'My Orders — AURA';
require_once VIEWS_PATH . '/partials/head.php';
require_once VIEWS_PATH . '/partials/navbar.php';
?>

<main style="padding-top:var(--nav-h)">
<div class="container" style="padding-top:60px;padding-bottom:80px">

<?php if ($order): /* ── Single Order Detail ── */ ?>

    <nav style="font-size:.8rem;color:var(--color-text-3);margin-bottom:32px;display:flex;gap:10px;align-items:center">
        <a href="<?= APP_URL ?>/user/orders.php" style="color:var(--color-gold);text-decoration:none">← My Orders</a>
        <span>/</span>
        <span><?= Security::h($order['order_number']) ?></span>
    </nav>

    <div style="display:grid;grid-template-columns:1fr 340px;gap:32px;align-items:start" class="order-detail-grid">
        <div>
            <div class="card" style="margin-bottom:24px">
                <div style="display:flex;justify-content:space-between;align-items:start;flex-wrap:wrap;gap:16px;margin-bottom:28px">
                    <div>
                        <h1 style="font-size:1.3rem;margin-bottom:6px"><?= Security::h($order['order_number']) ?></h1>
                        <p style="font-size:.8rem;color:var(--color-text-2)">
                            Placed <?= date('F j, Y', strtotime($order['created_at'])) ?>
                        </p>
                    </div>
                    <span class="status-badge status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
                </div>

                <!-- Progress Bar -->
                <?php
                $steps    = ['pending','processing','shipped','delivered'];
                $statusIdx = array_search($order['status'], $steps);
                ?>
                <div class="order-progress" style="margin:0 0 28px">
                    <?php foreach ($steps as $i => $step): ?>
                    <div class="progress-step <?= $i < $statusIdx ? 'done' : ($i === $statusIdx ? 'active' : '') ?>">
                        <div class="progress-step__dot">
                            <?= $i < $statusIdx ? '✓' : ($i + 1) ?>
                        </div>
                        <div class="progress-step__label"><?= ucfirst($step) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Order Items -->
                <h3 style="font-size:1rem;margin-bottom:16px">Items Ordered</h3>
                <?php foreach ($order['items'] as $item): ?>
                <div style="display:flex;gap:16px;padding:14px 0;border-bottom:1px solid var(--color-border)">
                    <img src="<?= UPLOAD_URL . '/' . Security::h($item['image']) ?>"
                         alt="<?= Security::h($item['name']) ?>"
                         style="width:66px;height:80px;object-fit:cover;border-radius:var(--radius-sm);background:var(--color-bg-2)"
                         onerror="this.src='<?= APP_URL ?>/assets/images/placeholder.jpg'">
                    <div style="flex:1">
                        <p style="font-weight:500;margin-bottom:4px"><?= Security::h($item['name']) ?></p>
                        <p style="font-size:.8rem;color:var(--color-text-2)">Qty: <?= $item['quantity'] ?> × $<?= number_format((float)$item['price'], 2) ?></p>
                    </div>
                    <strong>$<?= number_format((float)$item['price'] * $item['quantity'], 2) ?></strong>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div>
            <div class="order-summary">
                <h2 style="font-size:1.1rem;margin-bottom:20px">Order Summary</h2>
                <div class="order-summary__row"><span>Subtotal</span><span>$<?= number_format((float)$order['total_amount'] - $order['tax_amount'] - $order['shipping_amount'], 2) ?></span></div>
                <div class="order-summary__row"><span>Tax</span><span>$<?= number_format((float)$order['tax_amount'], 2) ?></span></div>
                <div class="order-summary__row"><span>Shipping</span><span>$<?= number_format((float)$order['shipping_amount'], 2) ?></span></div>
                <div class="order-summary__total"><span>Total</span><span>$<?= number_format((float)$order['total_amount'], 2) ?></span></div>
            </div>

            <div class="card" style="margin-top:20px">
                <h3 style="font-size:.9rem;font-weight:600;margin-bottom:14px;text-transform:uppercase;letter-spacing:.06em">Shipping To</h3>
                <p style="font-size:.875rem;line-height:1.8;color:var(--color-text-2)">
                    <strong style="color:var(--color-text)"><?= Security::h($order['shipping_name']) ?></strong><br>
                    <?= Security::h($order['shipping_address']) ?><br>
                    <?= Security::h($order['shipping_city']) ?>, <?= Security::h($order['shipping_country']) ?><br>
                    <?= Security::h($order['shipping_email']) ?>
                </p>
            </div>
        </div>
    </div>

<?php else: /* ── Orders List ── */ ?>

    <h1 data-aos="fade-up" style="margin-bottom:8px">My Orders</h1>
    <p data-aos="fade-up" data-aos-delay="80" style="color:var(--color-text-2);margin-bottom:40px">
        Track and manage your AURA purchases.
    </p>

    <?php if (empty($orders)): ?>
    <div class="card" style="text-align:center;padding:80px 40px" data-aos="fade-up">
        <div style="font-size:3.5rem;margin-bottom:20px">📦</div>
        <h2 style="margin-bottom:12px">No orders yet</h2>
        <p style="color:var(--color-text-2);margin-bottom:28px">Start shopping and your orders will appear here.</p>
        <a href="<?= APP_URL ?>/shop.php" class="btn btn-primary btn-lg">Explore Collection</a>
    </div>
    <?php else: ?>
    <div class="table-wrap" style="border-radius:var(--radius-lg);border:1px solid var(--color-border)" data-aos="fade-up">
        <table class="table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                <tr>
                    <td><strong style="font-family:var(--font-serif)"><?= Security::h($o['order_number']) ?></strong></td>
                    <td><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
                    <td><strong>$<?= number_format((float)$o['total_amount'], 2) ?></strong></td>
                    <td><span class="status-badge status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                    <td>
                        <a href="<?= APP_URL ?>/user/orders.php?id=<?= $o['id'] ?>"
                           class="btn btn-outline btn-sm">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPg > 1): ?>
    <nav class="pagination" style="margin-top:40px">
        <?php for ($i = 1; $i <= $totalPg; $i++): ?>
        <a href="?page=<?= $i ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </nav>
    <?php endif; ?>
    <?php endif; ?>

<?php endif; ?>
</div>
</main>

<style>@media(max-width:768px){.order-detail-grid{grid-template-columns:1fr !important}}</style>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>
