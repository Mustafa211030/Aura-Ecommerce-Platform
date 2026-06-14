<?php
/**
 * Admin — Manage Users
 */

require_once __DIR__ . '/../../config.php';
require_once CORE_PATH . '/Database/Connection.php';
require_once CORE_PATH . '/Helpers/Security.php';
require_once CORE_PATH . '/Helpers/Session.php';
require_once CORE_PATH . '/Classes/User.php';

use Core\Helpers\Security;
use Core\Helpers\Session;
use Core\Classes\User;

Session::start();
Session::requireAdmin();

$userModel   = new User();
$currentAdmin = Session::get('user_id');

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrf()) { die('CSRF error.'); }

    $action = $_POST['action'] ?? '';
    $uid    = (int)($_POST['user_id'] ?? 0);

    if ($uid === $currentAdmin) {
        Session::flash('error', 'You cannot modify your own account from here.');
    } elseif ($action === 'promote') {
        $userModel->setRole($uid, 'admin');
        Session::flash('success', 'User promoted to admin.');
    } elseif ($action === 'demote') {
        $userModel->setRole($uid, 'user');
        Session::flash('success', 'Admin demoted to user.');
    } elseif ($action === 'delete') {
        $userModel->delete($uid);
        Session::flash('success', 'User deleted.');
    }

    header('Location: ' . APP_URL . '/admin/users.php');
    exit;
}

$page   = max(1, (int)($_GET['page'] ?? 1));
$users  = $userModel->getAll($page, 20);
$total  = $userModel->count();
$pages  = (int)ceil($total / 20);

$pageTitle = 'Manage Users — AURA';
require_once VIEWS_PATH . '/partials/head.php';
require_once VIEWS_PATH . '/partials/navbar.php';
?>

<div class="admin-layout">
    <?php require_once VIEWS_PATH . '/admin/sidebar.php'; ?>

    <main class="admin-main">
        <div class="admin-header">
            <h1>Users <span style="font-size:1rem;color:var(--color-text-3);font-family:var(--font-sans);font-weight:400">(<?= $total ?>)</span></h1>
            <p>Manage customer accounts and admin roles.</p>
        </div>

        <div class="card" data-aos="fade-up">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px">
                                    <div class="avatar" style="flex-shrink:0">
                                        <?= strtoupper(mb_substr($u['name'], 0, 1)) ?>
                                    </div>
                                    <span style="font-weight:500"><?= Security::h($u['name']) ?></span>
                                </div>
                            </td>
                            <td style="font-size:.875rem"><?= Security::h($u['email']) ?></td>
                            <td>
                                <span class="badge" style="<?= $u['role'] === 'admin' ? 'background:rgba(74,124,89,.1);color:var(--color-success)' : '' ?>">
                                    <?= ucfirst($u['role']) ?>
                                </span>
                            </td>
                            <td style="font-size:.8rem;color:var(--color-text-2)">
                                <?= date('M j, Y', strtotime($u['created_at'])) ?>
                            </td>
                            <td>
                                <?php if ((int)$u['id'] !== $currentAdmin): ?>
                                <div style="display:flex;gap:8px;flex-wrap:wrap">
                                    <?php if ($u['role'] === 'user'): ?>
                                    <form method="POST" onsubmit="return confirm('Promote this user to admin?')">
                                        <?= Security::csrfField() ?>
                                        <input type="hidden" name="action"  value="promote">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn btn-success btn-sm">Promote</button>
                                    </form>
                                    <?php else: ?>
                                    <form method="POST" onsubmit="return confirm('Demote this admin to user?')">
                                        <?= Security::csrfField() ?>
                                        <input type="hidden" name="action"  value="demote">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn btn-outline btn-sm">Demote</button>
                                    </form>
                                    <?php endif; ?>
                                    <form method="POST" onsubmit="return confirm('Permanently delete this user? This cannot be undone.')">
                                        <?= Security::csrfField() ?>
                                        <input type="hidden" name="action"  value="delete">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </div>
                                <?php else: ?>
                                <span style="font-size:.75rem;color:var(--color-text-3)">(You)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($pages > 1): ?>
            <nav class="pagination" style="margin-top:24px">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a href="?page=<?= $i ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </nav>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>
