<?php
/**
 * Checkout Page
 */

require_once __DIR__ . '/../config.php';
require_once CORE_PATH . '/Database/Connection.php';
require_once CORE_PATH . '/Helpers/Security.php';
require_once CORE_PATH . '/Helpers/Session.php';
require_once CORE_PATH . '/Helpers/Validator.php';
require_once CORE_PATH . '/Classes/Cart.php';
require_once CORE_PATH . '/Classes/Order.php';
require_once CORE_PATH . '/Classes/User.php';

use Core\Helpers\Security;
use Core\Helpers\Session;
use Core\Helpers\Validator;
use Core\Classes\Cart;
use Core\Classes\Order;
use Core\Classes\User;

Session::start();
Session::requireAuth();

$userId    = Session::get('user_id');
$cartModel = new Cart();
$items     = $cartModel->getItems($userId);
$totals    = $cartModel->getTotals($userId);

// Redirect if cart is empty
if (empty($items)) {
    Session::flash('info', 'Your cart is empty.');
    header('Location: ' . APP_URL . '/cart.php');
    exit;
}

$errors = [];
$user   = (new User())->findById($userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrf()) {
        $errors[] = 'Security token mismatch.';
    } else {
        $shipping = [
            'name'    => Security::clean($_POST['ship_name']    ?? ''),
            'email'   => Security::clean($_POST['ship_email']   ?? ''),
            'phone'   => Security::clean($_POST['ship_phone']   ?? ''),
            'address' => Security::clean($_POST['ship_address'] ?? ''),
            'city'    => Security::clean($_POST['ship_city']    ?? ''),
            'country' => Security::clean($_POST['ship_country'] ?? ''),
            'notes'   => Security::clean($_POST['notes']        ?? ''),
        ];

        $v = (new Validator())->load($_POST)
            ->required('ship_name',    'Full name')
            ->required('ship_email',   'Email')
            ->email('ship_email')
            ->required('ship_address', 'Address')
            ->required('ship_city',    'City')
            ->required('ship_country', 'Country');

        if ($v->fails()) {
            $errors = $v->errorList();
        } else {
            try {
                $orderModel = new Order();
                $orderId    = $orderModel->placeOrder($userId, $items, $totals, $shipping);

                // Clear cart
                $cartModel->clearCart($userId);
                Session::set('cart_count', 0);

                Session::flash('success', 'Order placed successfully! 🎉');
                header('Location: ' . APP_URL . '/user/orders.php?id=' . $orderId);
                exit;
            } catch (\RuntimeException $e) {
                $errors[] = $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Checkout — AURA';
require_once VIEWS_PATH . '/partials/head.php';
require_once VIEWS_PATH . '/partials/navbar.php';
?>

<main style="padding-top:var(--nav-h)">
<div class="container" style="padding-top:60px;padding-bottom:80px">
    <h1 data-aos="fade-up" style="margin-bottom:8px">Checkout</h1>
    <p data-aos="fade-up" data-aos-delay="80" style="color:var(--color-text-2);margin-bottom:48px">
        Complete your order — secure and hassle-free.
    </p>

    <?php if (!empty($errors)): ?>
    <div style="background:rgba(181,87,74,.08);border:1px solid rgba(181,87,74,.2);
         border-radius:var(--radius-md);padding:16px 20px;margin-bottom:32px" data-aos="fade-up">
        <?php foreach ($errors as $e): ?>
        <p style="font-size:.875rem;color:var(--color-danger);margin:2px 0"><?= Security::h($e) ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <?= Security::csrfField() ?>

        <div class="cart-layout">

            <!-- Shipping Form -->
            <div data-aos="fade-right">
                <div class="card">
                    <h2 style="font-size:1.2rem;margin-bottom:28px;display:flex;align-items:center;gap:10px">
                        <span style="width:28px;height:28px;background:var(--color-gold);border-radius:50%;
                               display:flex;align-items:center;justify-content:center;font-size:.8rem;color:#fff;flex-shrink:0">1</span>
                        Shipping Information
                    </h2>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="ship_name">Full Name</label>
                            <input type="text" id="ship_name" name="ship_name" class="form-control"
                                   value="<?= Security::h($_POST['ship_name'] ?? $user['name'] ?? '') ?>"
                                   placeholder="Full name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="ship_email">Email</label>
                            <input type="email" id="ship_email" name="ship_email" class="form-control"
                                   value="<?= Security::h($_POST['ship_email'] ?? $user['email'] ?? '') ?>"
                                   placeholder="Email address" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="ship_phone">Phone (optional)</label>
                            <input type="tel" id="ship_phone" name="ship_phone" class="form-control"
                                   value="<?= Security::h($_POST['ship_phone'] ?? $user['phone'] ?? '') ?>"
                                   placeholder="+1 (555) 000-0000">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="ship_country">Country</label>
                            <input type="text" id="ship_country" name="ship_country" class="form-control"
                                   value="<?= Security::h($_POST['ship_country'] ?? $user['country'] ?? '') ?>"
                                   placeholder="Country" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="ship_address">Street Address</label>
                        <input type="text" id="ship_address" name="ship_address" class="form-control"
                               value="<?= Security::h($_POST['ship_address'] ?? $user['address'] ?? '') ?>"
                               placeholder="123 Main Street, Apt 4B" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="ship_city">City</label>
                        <input type="text" id="ship_city" name="ship_city" class="form-control"
                               value="<?= Security::h($_POST['ship_city'] ?? $user['city'] ?? '') ?>"
                               placeholder="City" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="notes">Order Notes (optional)</label>
                        <textarea id="notes" name="notes" class="form-control" rows="3"
                                  placeholder="Special instructions for delivery…"><?= Security::h($_POST['notes'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Payment Notice -->
                <div class="card" style="margin-top:24px;background:rgba(184,151,106,.05);border-color:rgba(184,151,106,.2)">
                    <h2 style="font-size:1.2rem;margin-bottom:16px;display:flex;align-items:center;gap:10px">
                        <span style="width:28px;height:28px;background:var(--color-gold);border-radius:50%;
                               display:flex;align-items:center;justify-content:center;font-size:.8rem;color:#fff;flex-shrink:0">2</span>
                        Payment
                    </h2>
                    <div style="background:rgba(184,151,106,.08);border-radius:var(--radius-md);padding:20px;
                         display:flex;align-items:center;gap:16px">
                        <span style="font-size:1.5rem">🏦</span>
                        <div>
                            <p style="font-weight:500;margin-bottom:4px">Cash on Delivery</p>
                            <p style="font-size:.8rem;color:var(--color-text-2)">
                                Payment collected upon delivery. We accept cash and card.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div data-aos="fade-left">
                <div class="order-summary" style="position:sticky;top:calc(var(--nav-h) + 20px)">
                    <h2 style="font-size:1.2rem;margin-bottom:24px">Order Summary</h2>

                    <!-- Items preview -->
                    <div style="margin-bottom:20px;border-bottom:1px solid var(--color-border);padding-bottom:20px">
                        <?php foreach ($items as $item): ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;
                             margin-bottom:12px;gap:16px">
                            <div style="display:flex;align-items:center;gap:10px;min-width:0">
                                <img src="<?= UPLOAD_URL . '/' . Security::h($item['image']) ?>"
                                     alt="" style="width:44px;height:54px;object-fit:cover;border-radius:6px;flex-shrink:0"
                                     onerror="this.src='<?= APP_URL ?>/assets/images/placeholder.jpg'">
                                <div style="min-width:0">
                                    <p style="font-size:.8rem;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                        <?= Security::h($item['name']) ?>
                                    </p>
                                    <p style="font-size:.72rem;color:var(--color-text-3)">Qty: <?= $item['quantity'] ?></p>
                                </div>
                            </div>
                            <span style="font-size:.875rem;font-weight:500;flex-shrink:0">
                                $<?= number_format((float)$item['line_total'], 2) ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="order-summary__row"><span>Subtotal</span><span>$<?= number_format($totals['subtotal'], 2) ?></span></div>
                    <div class="order-summary__row"><span>Tax (<?= TAX_RATE*100 ?>%)</span><span>$<?= number_format($totals['tax'], 2) ?></span></div>
                    <div class="order-summary__row"><span>Shipping</span><span>$<?= number_format($totals['shipping'], 2) ?></span></div>
                    <div class="order-summary__total">
                        <span>Total</span>
                        <span style="color:var(--color-gold)">$<?= number_format($totals['grand_total'], 2) ?></span>
                    </div>

                    <button type="submit" class="btn btn-primary"
                            style="width:100%;justify-content:center;margin-top:20px;padding:16px;font-size:.875rem">
                        🔒 Place Order — $<?= number_format($totals['grand_total'], 2) ?>
                    </button>

                    <p style="font-size:.72rem;color:var(--color-text-3);text-align:center;margin-top:10px">
                        By placing this order you agree to our Terms of Service.
                    </p>
                </div>
            </div>
        </div>
    </form>
</div>
</main>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>
