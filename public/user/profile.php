<?php
/**
 * User Profile — edit info and change password
 */

require_once __DIR__ . '/../../config.php';
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
Session::requireAuth();

$userId    = Session::get('user_id');
$userModel = new User();
$user      = $userModel->findById($userId);
$errors    = [];
$success   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrf()) {
        $errors[] = 'Security token mismatch.';
    } elseif (isset($_POST['update_profile'])) {
        $data = [
            'name'    => Security::clean($_POST['name']    ?? ''),
            'phone'   => Security::clean($_POST['phone']   ?? ''),
            'address' => Security::clean($_POST['address'] ?? ''),
            'city'    => Security::clean($_POST['city']    ?? ''),
            'country' => Security::clean($_POST['country'] ?? ''),
        ];
        $v = (new Validator())->load($_POST)->required('name', 'Full name')->min('name', 2);
        if ($v->fails()) {
            $errors = $v->errorList();
        } else {
            $userModel->updateProfile($userId, $data);
            Session::set('user_name', $data['name']);
            $user    = $userModel->findById($userId);
            $success = 'Profile updated successfully.';
        }
    } elseif (isset($_POST['change_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!Security::verifyPassword($current, $user['password'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        } elseif (!Security::isStrongPassword($new)) {
            $errors[] = 'Password must be 8+ chars with an uppercase letter, number, and symbol.';
        } else {
            $userModel->updatePassword($userId, $new);
            $success = 'Password changed successfully.';
        }
    }
}

$pageTitle = 'Edit Profile — AURA';
require_once VIEWS_PATH . '/partials/head.php';
require_once VIEWS_PATH . '/partials/navbar.php';
?>

<main style="padding-top:var(--nav-h)">
<div class="container" style="padding-top:60px;padding-bottom:80px;max-width:760px">

    <nav style="font-size:.8rem;color:var(--color-text-3);margin-bottom:32px;display:flex;gap:10px">
        <a href="<?= APP_URL ?>/user/dashboard.php" style="color:var(--color-gold);text-decoration:none">← Dashboard</a>
        <span>/</span><span>Edit Profile</span>
    </nav>

    <h1 data-aos="fade-up" style="margin-bottom:8px">Edit Profile</h1>
    <p data-aos="fade-up" data-aos-delay="80" style="color:var(--color-text-2);margin-bottom:40px">
        Update your personal details and account settings.
    </p>

    <?php if ($success): ?>
    <div style="background:rgba(74,124,89,.1);border:1px solid rgba(74,124,89,.25);border-radius:var(--radius-md);
         padding:14px 18px;margin-bottom:28px;color:var(--color-success);font-size:.875rem">
        ✓ <?= Security::h($success) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
    <div style="background:rgba(181,87,74,.08);border:1px solid rgba(181,87,74,.2);border-radius:var(--radius-md);
         padding:14px 18px;margin-bottom:28px">
        <?php foreach ($errors as $e): ?>
        <p style="font-size:.875rem;color:var(--color-danger)"><?= Security::h($e) ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Profile Info -->
    <div class="card" style="margin-bottom:24px" data-aos="fade-up">
        <h2 style="font-size:1.1rem;margin-bottom:24px">Personal Information</h2>
        <form method="POST" novalidate>
            <?= Security::csrfField() ?>
            <input type="hidden" name="update_profile" value="1">

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="name">Full Name</label>
                    <input type="text" id="name" name="name" class="form-control"
                           value="<?= Security::h($user['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="email">Email (read-only)</label>
                    <input type="email" id="email" class="form-control"
                           value="<?= Security::h($user['email']) ?>" disabled
                           style="opacity:.6;cursor:not-allowed">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" class="form-control"
                           value="<?= Security::h($user['phone'] ?? '') ?>" placeholder="+1 (555) 000-0000">
                </div>
                <div class="form-group">
                    <label class="form-label" for="country">Country</label>
                    <input type="text" id="country" name="country" class="form-control"
                           value="<?= Security::h($user['country'] ?? '') ?>" placeholder="Country">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="address">Address</label>
                <input type="text" id="address" name="address" class="form-control"
                       value="<?= Security::h($user['address'] ?? '') ?>" placeholder="Street address">
            </div>

            <div class="form-group">
                <label class="form-label" for="city">City</label>
                <input type="text" id="city" name="city" class="form-control"
                       value="<?= Security::h($user['city'] ?? '') ?>" placeholder="City">
            </div>

            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>

    <!-- Change Password -->
    <div class="card" data-aos="fade-up" data-aos-delay="100">
        <h2 style="font-size:1.1rem;margin-bottom:24px">Change Password</h2>
        <form method="POST" novalidate>
            <?= Security::csrfField() ?>
            <input type="hidden" name="change_password" value="1">

            <div class="form-group">
                <label class="form-label" for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password"
                       class="form-control" placeholder="Your current password" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password"
                           class="form-control" placeholder="New password" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password"
                           class="form-control" placeholder="Repeat new password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Update Password</button>
        </form>
    </div>
</div>
</main>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>
