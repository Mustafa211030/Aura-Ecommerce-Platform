<?php
/**
 * Login Page
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

// Already logged in?
if (Session::isLoggedIn()) {
    header('Location: ' . (Session::isAdmin() ? APP_URL.'/admin/dashboard.php' : APP_URL.'/user/dashboard.php'));
    exit;
}

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrf()) {
        $errors[] = 'Security check failed. Please try again.';
    } else {
        $email    = Security::clean($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $v = (new Validator())->load($_POST)
            ->required('email', 'Email')
            ->email('email')
            ->required('password', 'Password');

        if ($v->fails()) {
            $errors = $v->errorList();
        } else {
            $userModel = new User();
            $user      = $userModel->findByEmail($email);

            if ($user && Security::verifyPassword($password, $user['password'])) {
                session_regenerate_id(true);
                Session::set('user_id',   (int)$user['id']);
                Session::set('user_name', $user['name']);
                Session::set('user_role', $user['role']);
                Session::set('user_email',$user['email']);

                // Cache cart count
                require_once CORE_PATH . '/Classes/Cart.php';
                $cartCount = (new \Core\Classes\Cart())->countItems((int)$user['id']);
                Session::set('cart_count', $cartCount);

                Session::flash('success', 'Welcome back, ' . $user['name'] . '!');

                $redirect = Security::clean($_GET['redirect'] ?? '');
                if ($redirect && strpos($redirect, APP_URL) === 0) {
                    header('Location: ' . $redirect);
                } elseif ($user['role'] === 'admin') {
                    header('Location: ' . APP_URL . '/admin/dashboard.php');
                } else {
                    header('Location: ' . APP_URL . '/user/dashboard.php');
                }
                exit;
            } else {
                $errors[] = 'Invalid email or password.';
            }
        }
    }
}

$pageTitle = 'Login — AURA';
require_once VIEWS_PATH . '/partials/head.php';
?>

<main class="auth-page">
    <!-- Visual Panel -->
    <div class="auth-visual">
        <div class="auth-visual__logo">AUR<span>A</span></div>
        <div class="auth-visual__sub">Luxury Fashion</div>

        <div style="position:absolute;bottom:60px;left:60px;right:60px;color:rgba(250,250,248,.3)">
            <div style="font-family:var(--font-serif);font-size:1.25rem;font-style:italic;margin-bottom:12px;color:rgba(250,250,248,.55)">
                "Elegance is not about being noticed, it's about being remembered."
            </div>
            <div style="font-size:.75rem;letter-spacing:.1em;text-transform:uppercase">
                — Giorgio Armani
            </div>
        </div>

        <!-- Decorative circles -->
        <div style="position:absolute;width:300px;height:300px;border-radius:50%;
            border:1px solid rgba(184,151,106,.15);top:10%;right:-50px"></div>
        <div style="position:absolute;width:200px;height:200px;border-radius:50%;
            border:1px solid rgba(184,151,106,.1);bottom:15%;left:-40px"></div>
    </div>

    <!-- Form Panel -->
    <div class="auth-form-wrap">
        <div class="auth-form">
            <h1 class="auth-form__title">Welcome back</h1>
            <p class="auth-form__sub">Sign in to your AURA account</p>

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
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="<?= Security::h($email) ?>"
                           placeholder="your@email.com" required autocomplete="email">
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Your password" required autocomplete="current-password">
                </div>

                <div style="display:flex;justify-content:flex-end;margin-bottom:24px">
                    <a href="#" style="font-size:.8rem;color:var(--color-gold);text-decoration:none">
                        Forgot password?
                    </a>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:15px">
                    Sign In
                </button>
            </form>

            <hr class="divider">

            <p style="text-align:center;font-size:.875rem;color:var(--color-text-2)">
                Don't have an account?
                <a href="<?= APP_URL ?>/register.php" style="color:var(--color-gold);font-weight:500">
                    Create one
                </a>
            </p>

            <div style="text-align:center;margin-top:12px">
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
