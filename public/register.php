<?php
/**
 * Registration Page
 */

require_once __DIR__ . '/../config.php';
require_once CORE_PATH . '/Database/Connection.php';
require_once CORE_PATH . '/Helpers/Security.php';
require_once CORE_PATH . '/Helpers/Session.php';
require_once CORE_PATH . '/Helpers/Validator.php';
require_once CORE_PATH . '/Classes/User.php';

use Core\Helpers\Security;
use Core\Helpers\Session;
use Core\Helpers\Validator;
use Core\Classes\User;

Session::start();

if (Session::isLoggedIn()) {
    header('Location: ' . APP_URL . '/user/dashboard.php');
    exit;
}

$errors = [];
$data   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrf()) {
        $errors[] = 'Security token mismatch. Please refresh and try again.';
    } else {
        $data = [
            'name'     => Security::clean($_POST['name']     ?? ''),
            'email'    => Security::clean($_POST['email']    ?? ''),
            'password' => $_POST['password']         ?? '',
            'confirm'  => $_POST['confirm_password'] ?? '',
        ];

        $v = (new Validator())->load(array_merge($_POST, $data))
            ->required('name',     'Full name')
            ->min('name', 2)
            ->required('email',    'Email')
            ->email('email')
            ->required('password', 'Password')
            ->strongPassword('password')
            ->matches('confirm_password', 'password', 'Passwords');

        if ($v->fails()) {
            $errors = $v->errorList();
        } else {
            $userModel = new User();

            if ($userModel->findByEmail($data['email'])) {
                $errors[] = 'This email is already registered. Please log in.';
            } else {
                $newId = $userModel->create($data['name'], $data['email'], $data['password']);

                session_regenerate_id(true);
                Session::set('user_id',    $newId);
                Session::set('user_name',  $data['name']);
                Session::set('user_role',  'user');
                Session::set('user_email', $data['email']);
                Session::set('cart_count', 0);

                Session::flash('success', 'Account created! Welcome to AURA, ' . $data['name'] . '.');
                header('Location: ' . APP_URL . '/user/dashboard.php');
                exit;
            }
        }
    }
}

$pageTitle = 'Create Account — AURA';
require_once VIEWS_PATH . '/partials/head.php';
?>

<main class="auth-page">
    <!-- Visual -->
    <div class="auth-visual">
        <div class="auth-visual__logo">AUR<span>A</span></div>
        <div class="auth-visual__sub">Join the Circle</div>

        <div style="position:absolute;bottom:60px;left:60px;right:60px">
            <div style="display:flex;flex-direction:column;gap:20px">
                <?php $perks = ['Early access to new collections','Exclusive member discounts','Free shipping on orders over $200','Personalised styling advice']; ?>
                <?php foreach ($perks as $perk): ?>
                <div style="display:flex;align-items:center;gap:12px;color:rgba(250,250,248,.55);font-size:.875rem">
                    <span style="color:var(--color-gold);font-size:.8rem">✓</span>
                    <?= Security::h($perk) ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="position:absolute;width:300px;height:300px;border-radius:50%;
            border:1px solid rgba(184,151,106,.15);bottom:-80px;right:-80px"></div>
    </div>

    <!-- Form -->
    <div class="auth-form-wrap">
        <div class="auth-form">
            <h1 class="auth-form__title">Create account</h1>
            <p class="auth-form__sub">Join AURA and discover a new world of luxury</p>

            <?php if (!empty($errors)): ?>
            <div style="background:rgba(181,87,74,.08);border:1px solid rgba(181,87,74,.2);
                 border-radius:var(--radius-md);padding:14px 16px;margin-bottom:24px">
                <?php foreach ($errors as $e): ?>
                <p style="font-size:.875rem;color:var(--color-danger);margin:2px 0">
                    <?= Security::h($e) ?>
                </p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <?= Security::csrfField() ?>

                <div class="form-group">
                    <label class="form-label" for="name">Full Name</label>
                    <input type="text" id="name" name="name" class="form-control"
                           value="<?= Security::h($data['name'] ?? '') ?>"
                           placeholder="Your full name" required autocomplete="name">
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="<?= Security::h($data['email'] ?? '') ?>"
                           placeholder="your@email.com" required autocomplete="email">
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Min 8 chars, 1 uppercase, 1 number, 1 symbol"
                           required autocomplete="new-password">
                    <small style="font-size:.72rem;color:var(--color-text-3);margin-top:4px;display:block">
                        Must contain uppercase, number, and symbol.
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password"
                           class="form-control"
                           placeholder="Repeat password" required autocomplete="new-password">
                </div>

                <button type="submit" class="btn btn-primary"
                        style="width:100%;justify-content:center;padding:15px;margin-top:8px">
                    Create My Account
                </button>
            </form>

            <hr class="divider">

            <p style="text-align:center;font-size:.875rem;color:var(--color-text-2)">
                Already a member?
                <a href="<?= APP_URL ?>/login.php" style="color:var(--color-gold);font-weight:500">
                    Sign in
                </a>
            </p>

            <div style="text-align:center;margin-top:8px">
                <a href="<?= APP_URL ?>/index.php" style="font-size:.8rem;color:var(--color-text-3)">
                    ← Back to store
                </a>
            </div>
        </div>
    </div>
</main>

<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body>
</html>
