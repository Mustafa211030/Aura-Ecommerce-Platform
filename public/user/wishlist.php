<?php
/**
 * User Wishlist Page
 */

require_once __DIR__ . '/../../config.php';
require_once CORE_PATH . '/Database/Connection.php';
require_once CORE_PATH . '/Helpers/Security.php';
require_once CORE_PATH . '/Helpers/Session.php';
require_once CORE_PATH . '/Classes/Product.php';

use Core\Helpers\Security;
use Core\Helpers\Session;
use Core\Classes\Product;

Session::start();
Session::requireAuth();

$userId   = Session::get('user_id');
$wishlist = (new Product())->getWishlist($userId);

$pageTitle = 'My Wishlist — AURA';
require_once VIEWS_PATH . '/partials/head.php';
require_once VIEWS_PATH . '/partials/navbar.php';
?>

<main style="padding-top:var(--nav-h)">
<div class="container" style="padding-top:60px;padding-bottom:80px">
    <h1 data-aos="fade-up" style="margin-bottom:8px">My Wishlist</h1>
    <p data-aos="fade-up" data-aos-delay="80" style="color:var(--color-text-2);margin-bottom:48px">
        <?= count($wishlist) ?> saved piece<?= count($wishlist) !== 1 ? 's' : '' ?>
    </p>

    <?php if (empty($wishlist)): ?>
    <div style="text-align:center;padding:80px 0" data-aos="fade-up">
        <div style="font-size:4rem;margin-bottom:20px">♡</div>
        <h2 style="margin-bottom:12px">Nothing saved yet</h2>
        <p style="color:var(--color-text-2);margin-bottom:28px">Browse our collection and save your favourites.</p>
        <a href="<?= APP_URL ?>/shop.php" class="btn btn-primary btn-lg">Explore Collection</a>
    </div>
    <?php else: ?>
    <div class="products-grid">
        <?php foreach ($wishlist as $i => $p): ?>
        <?php $isSale = !empty($p['sale_price']); $price = $isSale ? $p['sale_price'] : $p['price']; ?>
        <div class="product-card" data-aos="fade-up" data-aos-delay="<?= min($i % 4 * 60, 300) ?>">
            <div class="product-card__image">
                <a href="<?= APP_URL ?>/shop.php?product=<?= Security::h($p['slug']) ?>">
                    <img src="<?= UPLOAD_URL . '/' . Security::h($p['image']) ?>"
                         alt="<?= Security::h($p['name']) ?>" loading="lazy"
                         onerror="this.src='<?= APP_URL ?>/assets/images/placeholder.jpg'">
                </a>
                <?php if ($isSale): ?><span class="product-card__badge badge-sale">Sale</span><?php endif; ?>
                <div class="product-card__actions">
                    <button class="product-card__action-btn wishlisted"
                            data-wishlist="<?= $p['id'] ?>" title="Remove from wishlist">♥</button>
                </div>
            </div>
            <div class="product-card__body">
                <p class="product-card__cat"><?= Security::h($p['cat_name']) ?></p>
                <h3 class="product-card__name">
                    <a href="<?= APP_URL ?>/shop.php?product=<?= Security::h($p['slug']) ?>"
                       style="color:inherit;text-decoration:none"><?= Security::h($p['name']) ?></a>
                </h3>
                <div class="product-card__price">
                    <span class="price-current <?= $isSale ? 'price-sale' : '' ?>">
                        $<?= number_format((float)$price, 2) ?>
                    </span>
                    <?php if ($isSale): ?>
                    <span class="price-original">$<?= number_format((float)$p['price'], 2) ?></span>
                    <?php endif; ?>
                </div>
                <button class="btn btn-primary btn-sm" style="width:100%"
                        data-add-cart="<?= $p['id'] ?>"
                        <?= $p['stock'] == 0 ? 'disabled' : '' ?>>
                    <?= $p['stock'] == 0 ? 'Out of Stock' : 'Add to Cart' ?>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
</main>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>
