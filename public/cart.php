<?php
/**
 * Cart Page
 */

require_once __DIR__ . '/../config.php';
require_once CORE_PATH . '/Database/Connection.php';
require_once CORE_PATH . '/Helpers/Security.php';
require_once CORE_PATH . '/Helpers/Session.php';
require_once CORE_PATH . '/Classes/Cart.php';

use Core\Helpers\Security;
use Core\Helpers\Session;
use Core\Classes\Cart;

Session::start();
Session::requireAuth();

$cartModel = new Cart();
$userId    = Session::get('user_id');
$items     = $cartModel->getItems($userId);
$totals    = $cartModel->getTotals($userId);

$pageTitle = 'Your Cart — AURA';
require_once VIEWS_PATH . '/partials/head.php';
require_once VIEWS_PATH . '/partials/navbar.php';
?>

<main style="padding-top:var(--nav-h)">
<div class="container" style="padding-top:60px;padding-bottom:80px">
    <h1 data-aos="fade-up" style="margin-bottom:8px">Your Cart</h1>
    <p data-aos="fade-up" data-aos-delay="80" style="color:var(--color-text-2);margin-bottom:48px">
        <?= count($items) ?> item<?= count($items) !== 1 ? 's' : '' ?>
    </p>

    <?php if (empty($items)): ?>
    <div style="text-align:center;padding:80px 0" data-aos="fade-up">
        <div style="font-size:4rem;margin-bottom:20px">🛍</div>
        <h2 style="margin-bottom:12px">Your cart is empty</h2>
        <p style="color:var(--color-text-2);margin-bottom:28px">
            Discover our luxury collection and find your perfect piece.
        </p>
        <a href="<?= APP_URL ?>/shop.php" class="btn btn-primary btn-lg">Browse Collection</a>
    </div>
    <?php else: ?>
    <div class="cart-layout">

        <!-- Items -->
        <div data-aos="fade-right">
            <?php foreach ($items as $item): ?>
            <div class="cart-table__item" data-cart-item="<?= $item['id'] ?>">
                <a href="<?= APP_URL ?>/shop.php?product=<?= Security::h($item['slug']) ?>">
                    <img class="cart-table__img"
                         src="<?= UPLOAD_URL . '/' . Security::h($item['image']) ?>"
                         alt="<?= Security::h($item['name']) ?>"
                         onerror="this.src='<?= APP_URL ?>/assets/images/placeholder.jpg'">
                </a>
                <div style="flex:1">
                    <h3 style="font-size:1rem;margin-bottom:6px">
                        <a href="<?= APP_URL ?>/shop.php?product=<?= Security::h($item['slug']) ?>"
                           style="color:inherit;text-decoration:none">
                            <?= Security::h($item['name']) ?>
                        </a>
                    </h3>
                    <p style="font-size:.8rem;color:var(--color-text-3);margin-bottom:14px">
                        $<?= number_format((float)$item['unit_price'], 2) ?> each
                    </p>
                    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px">
                        <div class="qty-control">
                            <button class="qty-btn" data-dir="down" aria-label="Decrease quantity">−</button>
                            <span class="qty-display"><?= $item['quantity'] ?></span>
                            <button class="qty-btn" data-dir="up"
                                    <?= $item['quantity'] >= $item['stock'] ? 'disabled' : '' ?>
                                    aria-label="Increase quantity">+</button>
                        </div>
                        <strong style="font-family:var(--font-serif);font-size:1.1rem">
                            $<?= number_format((float)$item['line_total'], 2) ?>
                        </strong>
                        <button class="qty-btn" data-dir="remove" style="width:36px;height:36px;color:var(--color-danger)"
                                onclick="removeCartItem(this, <?= $item['id'] ?>)" aria-label="Remove item">✕</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <div style="margin-top:24px">
                <a href="<?= APP_URL ?>/shop.php" class="btn btn-ghost">← Continue Shopping</a>
            </div>
        </div>

        <!-- Summary -->
        <div data-aos="fade-left">
            <div class="order-summary" id="cart-totals">
                <h2 style="font-size:1.25rem;margin-bottom:24px">Order Summary</h2>

                <div class="order-summary__row">
                    <span>Subtotal</span>
                    <span id="cart-subtotal">$<?= number_format($totals['subtotal'], 2) ?></span>
                </div>
                <div class="order-summary__row">
                    <span>Tax (<?= TAX_RATE * 100 ?>%)</span>
                    <span id="cart-tax">$<?= number_format($totals['tax'], 2) ?></span>
                </div>
                <div class="order-summary__row">
                    <span>Shipping</span>
                    <span id="cart-shipping">$<?= number_format($totals['shipping'], 2) ?></span>
                </div>

                <div class="order-summary__total">
                    <span>Total</span>
                    <span id="cart-grand">$<?= number_format($totals['grand_total'], 2) ?></span>
                </div>

                <a href="<?= APP_URL ?>/checkout.php"
                   class="btn btn-primary" style="width:100%;justify-content:center;margin-top:20px;padding:15px">
                    Proceed to Checkout
                </a>

                <p style="font-size:.75rem;color:var(--color-text-3);text-align:center;margin-top:12px">
                    🔒 Secure checkout • Free returns
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
</main>

<script>
async function removeCartItem(btn, cartId) {
    const item = btn.closest('.cart-table__item');
    try {
        const res  = await fetch(window.__AURA_URL__ + '/api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'remove', cart_id: cartId, _csrf: window.__AURA_CSRF__ })
        });
        const data = await res.json();
        if (data.success) {
            item.style.opacity = '0';
            item.style.transform = 'translateX(20px)';
            item.style.transition = 'all .3s';
            setTimeout(() => { item.remove(); CartPage.refresh(); }, 300);
        }
    } catch {}
}
</script>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>
