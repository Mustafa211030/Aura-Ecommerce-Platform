<?php
/**
 * Admin Dashboard
 */

require_once __DIR__ . '/../../config.php';
require_once CORE_PATH . '/Database/Connection.php';
require_once CORE_PATH . '/Helpers/Security.php';
require_once CORE_PATH . '/Helpers/Session.php';
require_once CORE_PATH . '/Classes/Order.php';
require_once CORE_PATH . '/Classes/Product.php';
require_once CORE_PATH . '/Classes/User.php';

use Core\Helpers\Security;
use Core\Helpers\Session;
use Core\Classes\Order;
use Core\Classes\Product;
use Core\Classes\User;

Session::start();
Session::requireAdmin();

$orderModel   = new Order();
$productModel = new Product();
$userModel    = new User();

// Analytics
$totalRevenue  = $orderModel->totalRevenue();
$avgOrder      = $orderModel->avgOrderValue();
$totalOrders   = $orderModel->count();
$totalUsers    = $userModel->count();
$newUsers30d   = $userModel->countNewLast30Days();
$totalProducts = $productModel->count();
$lowStock      = $productModel->getLowStock(5);
$recentOrders  = $orderModel->getAllOrders(1, 8)['items'];
$monthlyRev    = $orderModel->monthlyRevenue();

$pageTitle = 'Admin Dashboard — AURA';
require_once VIEWS_PATH . '/partials/head.php';
require_once VIEWS_PATH . '/partials/navbar.php';
?>

<div class="admin-layout">
    <?php require_once VIEWS_PATH . '/admin/sidebar.php'; ?>

    <main class="admin-main">
        <div class="admin-header">
            <h1>Dashboard</h1>
            <p>Welcome back! Here's what's happening with AURA.</p>
        </div>

        <!-- KPI Cards -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:36px">
            <?php
            $kpis = [
                ['icon'=>'💰','label'=>'Total Revenue',     'value'=>'$'.number_format($totalRevenue,2),  'color'=>'rgba(184,151,106,.1)'],
                ['icon'=>'📦','label'=>'Total Orders',      'value'=>number_format($totalOrders),          'color'=>'rgba(74,110,142,.1)'],
                ['icon'=>'👥','label'=>'Total Users',       'value'=>number_format($totalUsers),           'color'=>'rgba(74,124,89,.1)'],
                ['icon'=>'🏷','label'=>'Products',          'value'=>number_format($totalProducts),        'color'=>'rgba(199,136,45,.1)'],
                ['icon'=>'✨','label'=>'New Users (30d)',   'value'=>$newUsers30d,                         'color'=>'rgba(74,124,89,.1)'],
                ['icon'=>'📊','label'=>'Avg Order Value',   'value'=>'$'.number_format($avgOrder,2),       'color'=>'rgba(184,151,106,.1)'],
            ];
            foreach ($kpis as $i => $kpi): ?>
            <div class="stat-card" data-aos="fade-up" data-aos-delay="<?= $i * 50 ?>">
                <div class="stat-card__icon" style="background:<?= $kpi['color'] ?>"><?= $kpi['icon'] ?></div>
                <div>
                    <div class="stat-card__value" style="font-size:1.5rem"><?= $kpi['value'] ?></div>
                    <div class="stat-card__label"><?= Security::h($kpi['label']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="display:grid;grid-template-columns:1fr 340px;gap:28px;margin-bottom:28px">

            <!-- Revenue Chart -->
            <div class="card" data-aos="fade-right">
                <h2 style="font-size:1rem;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between">
                    Revenue (Last 6 Months)
                    <span style="font-size:.75rem;color:var(--color-text-3);font-family:var(--font-sans)">Monthly</span>
                </h2>
                <canvas id="revenueChart" height="200"></canvas>
            </div>

            <!-- Low Stock Alert -->
            <div class="card" data-aos="fade-left">
                <h2 style="font-size:1rem;margin-bottom:20px;color:var(--color-warning)">
                    ⚠ Low Stock Alert
                </h2>
                <?php if (empty($lowStock)): ?>
                <p style="color:var(--color-text-2);font-size:.875rem">All products are well-stocked.</p>
                <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:10px;max-height:260px;overflow-y:auto">
                    <?php foreach ($lowStock as $p): ?>
                    <div style="display:flex;align-items:center;justify-content:space-between;
                         padding:10px;background:<?= $p['stock'] == 0 ? 'rgba(181,87,74,.06)' : 'rgba(199,136,45,.06)' ?>;
                         border-radius:var(--radius-sm);gap:12px">
                        <div style="min-width:0">
                            <p style="font-size:.8rem;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                <?= Security::h($p['name']) ?>
                            </p>
                            <p style="font-size:.7rem;color:var(--color-text-3)"><?= Security::h($p['cat_name']) ?></p>
                        </div>
                        <span style="font-size:.75rem;font-weight:600;flex-shrink:0;
                              color:<?= $p['stock'] == 0 ? 'var(--color-danger)' : 'var(--color-warning)' ?>">
                            <?= $p['stock'] ?> left
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <a href="<?= APP_URL ?>/admin/products.php" class="btn btn-outline btn-sm" style="margin-top:16px;display:block;text-align:center">
                    Manage Products
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="card" data-aos="fade-up">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
                <h2 style="font-size:1rem">Recent Orders</h2>
                <a href="<?= APP_URL ?>/admin/orders.php" class="btn btn-ghost btn-sm">View all</a>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $o): ?>
                        <tr>
                            <td><strong style="font-size:.8rem"><?= Security::h($o['order_number']) ?></strong></td>
                            <td>
                                <div style="font-size:.85rem;font-weight:500"><?= Security::h($o['user_name']) ?></div>
                                <div style="font-size:.72rem;color:var(--color-text-3)"><?= Security::h($o['user_email']) ?></div>
                            </td>
                            <td><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
                            <td><strong>$<?= number_format((float)$o['total_amount'], 2) ?></strong></td>
                            <td><span class="status-badge status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                            <td>
                                <a href="<?= APP_URL ?>/admin/orders.php?id=<?= $o['id'] ?>"
                                   class="btn btn-outline btn-sm">Manage</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
    const labels  = <?= json_encode(array_column($monthlyRev, 'month')) ?>;
    const values  = <?= json_encode(array_map(fn($r) => (float)$r['revenue'], $monthlyRev)) ?>;
    const isDark  = document.documentElement.getAttribute('data-theme') === 'dark';
    const gridC   = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
    const textC   = isDark ? 'rgba(240,237,232,0.5)'  : 'rgba(44,44,44,0.5)';

    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Revenue ($)',
                data: values,
                borderColor: '#B8976A',
                backgroundColor: 'rgba(184,151,106,0.08)',
                borderWidth: 2,
                pointBackgroundColor: '#B8976A',
                pointRadius: 4,
                tension: 0.4,
                fill: true,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: gridC }, ticks: { color: textC, font: { size: 11 } } },
                y: { grid: { color: gridC }, ticks: { color: textC, font: { size: 11 }, callback: v => '$'+v } }
            }
        }
    });
})();
</script>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>
