<?php
/**
 * Admin — Manage Orders
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
Session::requireAdmin();

$orderModel = new Order();

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['update_status'])) {
    if (!Security::verifyCsrf()) { die('CSRF error.'); }
    $oid    = (int)($_POST['order_id'] ?? 0);
    $status = Security::clean($_POST['status'] ?? '');
    if ($oid && $status) {
        $orderModel->updateStatus($oid, $status);
        Session::flash('success', 'Order status updated.');
    }
    header('Location: ' . APP_URL . '/admin/orders.php' . (isset($_GET['id']) ? '?id='.(int)$_GET['id'] : ''));
    exit;
}

// Single order detail
$orderId = (int)($_GET['id'] ?? 0);
$order   = $orderId ? $orderModel->getOrderDetail($orderId) : null;

// List
$page       = max(1, (int)($_GET['page'] ?? 1));
$statusFilter = Security::clean($_GET['status'] ?? '');
$result     = $orderModel->getAllOrders($page, 15, $statusFilter);
$orders     = $result['items'];
$totalPg    = $result['pages'];

$pageTitle = 'Manage Orders — AURA';
require_once VIEWS_PATH . '/partials/head.php';
require_once VIEWS_PATH . '/partials/navbar.php';
?>

<div class="admin-layout">
    <?php require_once VIEWS_PATH . '/admin/sidebar.php'; ?>

    <main class="admin-main">
        <div class="admin-header">
            <h1>Orders</h1>
            <p>View and manage all customer orders.</p>
        </div>

        <?php if ($order): /* ── Single Order ── */ ?>

        <nav style="font-size:.8rem;color:var(--color-text-3);margin-bottom:28px;display:flex;gap:10px">
            <a href="<?= APP_URL ?>/admin/orders.php" style="color:var(--color-gold);text-decoration:none">← All Orders</a>
            <span>/</span><span><?= Security::h($order['order_number']) ?></span>
        </nav>

        <div style="display:grid;grid-template-columns:1fr 320px;gap:24px">
            <div>
                <div class="card" style="margin-bottom:20px">
                    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:24px">
                        <div>
                            <h2 style="font-size:1.2rem;margin-bottom:4px"><?= Security::h($order['order_number']) ?></h2>
                            <p style="font-size:.8rem;color:var(--color-text-2)">
                                <?= date('F j, Y \a\t g:i A', strtotime($order['created_at'])) ?>
                            </p>
                        </div>
                        <span class="status-badge status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
                    </div>

                    <!-- Update Status -->
                    <form method="POST" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;
                          background:var(--color-bg-2);padding:16px;border-radius:var(--radius-md);margin-bottom:24px">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="order_id"     value="<?= $order['id'] ?>">
                        <input type="hidden" name="update_status" value="1">
                        <label class="form-label" style="margin:0">Update Status:</label>
                        <select name="status" class="form-control" style="width:auto">
                            <?php foreach (['pending','processing','shipped','delivered','cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>>
                                <?= ucfirst($s) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Update</button>
                    </form>

                    <!-- Items -->
                    <h3 style="font-size:.9rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:14px">
                        Items
                    </h3>
                    <?php foreach ($order['items'] as $item): ?>
                    <div style="display:flex;gap:14px;padding:12px 0;border-bottom:1px solid var(--color-border)">
                        <img src="<?= UPLOAD_URL . '/' . Security::h($item['image']) ?>"
                             alt="" style="width:54px;height:66px;object-fit:cover;border-radius:4px;background:var(--color-bg-2)">
                        <div style="flex:1">
                            <p style="font-weight:500;margin-bottom:3px"><?= Security::h($item['name']) ?></p>
                            <p style="font-size:.8rem;color:var(--color-text-2)">
                                Qty: <?= $item['quantity'] ?> × $<?= number_format((float)$item['price'], 2) ?>
                            </p>
                        </div>
                        <strong>$<?= number_format((float)$item['price'] * $item['quantity'], 2) ?></strong>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div>
                <div class="order-summary" style="margin-bottom:16px">
                    <h3 style="font-size:.9rem;font-weight:600;margin-bottom:16px;text-transform:uppercase;letter-spacing:.06em">Summary</h3>
                    <div class="order-summary__row"><span>Subtotal</span><span>$<?= number_format((float)$order['total_amount'] - $order['tax_amount'] - $order['shipping_amount'], 2) ?></span></div>
                    <div class="order-summary__row"><span>Tax</span><span>$<?= number_format((float)$order['tax_amount'], 2) ?></span></div>
                    <div class="order-summary__row"><span>Shipping</span><span>$<?= number_format((float)$order['shipping_amount'], 2) ?></span></div>
                    <div class="order-summary__total"><span>Total</span><span>$<?= number_format((float)$order['total_amount'], 2) ?></span></div>
                </div>

                <div class="card">
                    <h3 style="font-size:.9rem;font-weight:600;margin-bottom:14px;text-transform:uppercase;letter-spacing:.06em">Customer</h3>
                    <p style="font-size:.875rem;line-height:1.9;color:var(--color-text-2)">
                        <strong style="color:var(--color-text)"><?= Security::h($order['user_name']) ?></strong><br>
                        <?= Security::h($order['user_email']) ?><br><br>
                        <strong style="color:var(--color-text)">Ships to:</strong><br>
                        <?= Security::h($order['shipping_name']) ?><br>
                        <?= Security::h($order['shipping_address']) ?><br>
                        <?= Security::h($order['shipping_city']) ?>, <?= Security::h($order['shipping_country']) ?>
                    </p>
                </div>
            </div>
        </div>

        <?php else: /* ── Orders List ── */ ?>

        <!-- Status Filter -->
        <div class="filter-bar" style="margin-bottom:28px" data-aos="fade-up">
            <?php foreach (['' => 'All', 'pending'=>'Pending','processing'=>'Processing','shipped'=>'Shipped','delivered'=>'Delivered','cancelled'=>'Cancelled'] as $val => $label): ?>
            <a href="?status=<?= $val ?>" class="filter-pill <?= $statusFilter === $val ? 'active' : '' ?>">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="card" data-aos="fade-up">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><strong style="font-size:.8rem"><?= Security::h($o['order_number']) ?></strong></td>
                            <td>
                                <div style="font-weight:500"><?= Security::h($o['user_name']) ?></div>
                                <div style="font-size:.72rem;color:var(--color-text-3)"><?= Security::h($o['user_email']) ?></div>
                            </td>
                            <td><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
                            <td><strong>$<?= number_format((float)$o['total_amount'], 2) ?></strong></td>
                            <td><span class="status-badge status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                            <td>
                                <a href="?id=<?= $o['id'] ?>" class="btn btn-outline btn-sm">Manage</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPg > 1): ?>
            <nav class="pagination" style="margin-top:24px">
                <?php for ($i = 1; $i <= $totalPg; $i++): ?>
                <a href="?page=<?= $i ?><?= $statusFilter ? '&status='.$statusFilter : '' ?>"
                   class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </nav>
            <?php endif; ?>
        </div>

        <?php endif; ?>
    </main>
</div>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>
