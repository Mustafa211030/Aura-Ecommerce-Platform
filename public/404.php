<?php
require_once __DIR__ . '/../../config.php';
require_once CORE_PATH . '/Helpers/Security.php';
require_once CORE_PATH . '/Helpers/Session.php';

use Core\Helpers\Session;

Session::start();

http_response_code(404);
$pageTitle = '404 — Page Not Found — AURA';
require_once VIEWS_PATH . '/partials/head.php';
require_once VIEWS_PATH . '/partials/navbar.php';
?>

<main style="padding-top:var(--nav-h);min-height:80vh;display:flex;align-items:center">
    <div class="container" style="text-align:center;padding:80px 24px">
        <div data-aos="fade-up" style="font-family:var(--font-serif);font-size:clamp(6rem,20vw,12rem);
             font-weight:700;color:var(--color-border);line-height:1;margin-bottom:24px;
             background:linear-gradient(135deg,var(--color-border),var(--color-bg-2));
             -webkit-background-clip:text;-webkit-text-fill-color:transparent">
            404
        </div>
        <h1 data-aos="fade-up" data-aos-delay="100" style="font-size:clamp(1.5rem,3vw,2rem);margin-bottom:16px">
            This Page Has Left the Collection
        </h1>
        <p data-aos="fade-up" data-aos-delay="200" style="color:var(--color-text-2);max-width:400px;margin:0 auto 36px;font-size:.9375rem">
            The page you're looking for may have been moved, renamed, or removed. Let's get you back on track.
        </p>
        <div data-aos="fade-up" data-aos-delay="300" style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap">
            <a href="<?= APP_URL ?>/index.php" class="btn btn-primary btn-lg">Go Home</a>
            <a href="<?= APP_URL ?>/shop.php"  class="btn btn-outline btn-lg">Browse Shop</a>
        </div>
    </div>
</main>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>
