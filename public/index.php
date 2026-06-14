<?php
/**
 * Homepage — AURA
 */

require_once __DIR__ . '/../config.php';
require_once CORE_PATH . '/Database/Connection.php';
require_once CORE_PATH . '/Helpers/Security.php';
require_once CORE_PATH . '/Helpers/Session.php';
require_once CORE_PATH . '/Classes/Product.php';

use Core\Helpers\Security;
use Core\Helpers\Session;
use Core\Classes\Product;

Session::start();

$productModel = new Product();
$featured     = $productModel->getFeatured(8);
$categories   = $productModel->getCategories();

$pageTitle = 'AURA — Luxury Fashion';

require_once VIEWS_PATH . '/partials/head.php';
require_once VIEWS_PATH . '/partials/navbar.php';

function renderStars(float $avg): string {
    $full  = floor($avg);
    $half  = ($avg - $full) >= 0.5 ? 1 : 0;
    $empty = 5 - $full - $half;
    return str_repeat('★', (int)$full) . str_repeat('✦', $half) . str_repeat('☆', (int)$empty);
}
?>

<main>

<!-- ── Hero Section ── -->
<section class="hero" aria-label="Hero banner">
    <div class="hero__bg"></div>
    <div class="hero__parallax"></div>

    <!-- Floating gold orbs -->
    <div style="position:absolute;width:400px;height:400px;border-radius:50%;
        background:radial-gradient(circle,rgba(184,151,106,0.08) 0%,transparent 70%);
        top:20%;right:15%;pointer-events:none"></div>
    <div style="position:absolute;width:300px;height:300px;border-radius:50%;
        background:radial-gradient(circle,rgba(184,151,106,0.06) 0%,transparent 70%);
        bottom:20%;left:10%;pointer-events:none"></div>

    <div class="hero__content">
        <span class="hero__eyebrow" data-aos="fade-down">New Collection 2025</span>
        <h1 class="hero__title" data-aos="fade-up" data-aos-delay="100">
            Dress in the<br><em>Language of</em><br>Luxury
        </h1>
        <p class="hero__sub" data-aos="fade-up" data-aos-delay="200">
            Curated for the discerning few. Every stitch, every thread — a testament to timeless elegance.
        </p>
        <div class="hero__actions" data-aos="fade-up" data-aos-delay="300">
            <a href="<?= APP_URL ?>/shop.php" class="btn btn-primary btn-lg">Explore Collection</a>
            <a href="<?= APP_URL ?>/about.php" class="btn btn-outline btn-outline-light btn-lg">Our Story</a>
        </div>
    </div>

    <div class="hero__scroll" aria-hidden="true">Scroll</div>
</section>

<!-- ── Category Strip ── -->
<section style="padding:60px 0;background:var(--color-bg-2)">
    <div class="container">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px">
            <?php foreach ($categories as $i => $cat): ?>
            <a href="<?= APP_URL ?>/shop.php?cat=<?= Security::h($cat['slug']) ?>"
               data-aos="fade-up" data-aos-delay="<?= $i * 80 ?>"
               style="text-decoration:none">
                <div style="background:var(--color-surface);border:1px solid var(--color-border);
                     border-radius:var(--radius-lg);padding:32px 24px;text-align:center;
                     transition:all .3s;cursor:pointer"
                     onmouseover="this.style.borderColor='var(--color-gold)';this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--color-border)';this.style.transform=''">
                    <div style="font-size:2rem;margin-bottom:12px">
                        <?= ['men'=>'👔','women'=>'👗','accessories'=>'👜','new-arrivals'=>'✨'][$cat['slug']] ?? '🏷' ?>
                    </div>
                    <h3 style="font-size:1rem;font-weight:500;color:var(--color-text)"><?= Security::h($cat['name']) ?></h3>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ── Featured Products ── -->
<section class="section">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <span class="section-header__kicker">Handpicked for You</span>
            <h2 class="section-header__title">Featured Pieces</h2>
            <div class="section-header__line"></div>
            <p class="section-header__sub">
                Each garment selected for its exceptional craftsmanship and timeless appeal.
            </p>
        </div>

        <div class="products-grid">
            <?php foreach ($featured as $i => $p): ?>
            <?php
            /* FIX: was UPLOAD_URL . '/' . Security::h($p['image']) — broken for external URLs */
            $img        = Security::imageUrl($p['image']);
            $isSale     = !empty($p['sale_price']);
            $price      = $isSale ? $p['sale_price'] : $p['price'];
            $wishlisted = Session::isLoggedIn()
                ? (new Product())->isWishlisted(Session::get('user_id'), $p['id'])
                : false;
            ?>
            <div class="product-card" data-aos="fade-up" data-aos-delay="<?= min($i * 60, 300) ?>">
                <div class="product-card__image">
                    <a href="<?= APP_URL ?>/shop.php?product=<?= Security::h($p['slug']) ?>">
                        <img src="<?= Security::h($img) ?>"
                             alt="<?= Security::h($p['name']) ?>"
                             loading="lazy"
                             onerror="this.src='<?= APP_URL ?>/assets/images/placeholder.jpg'">
                    </a>

                    <?php if ($isSale): ?>
                    <span class="product-card__badge badge-sale">Sale</span>
                    <?php elseif ($p['featured']): ?>
                    <span class="product-card__badge">Featured</span>
                    <?php endif; ?>

                    <div class="product-card__actions">
                        <button class="product-card__action-btn <?= $wishlisted ? 'wishlisted' : '' ?>"
                                data-wishlist="<?= $p['id'] ?>"
                                title="<?= $wishlisted ? 'Remove from wishlist' : 'Add to wishlist' ?>"
                                aria-label="Wishlist">♡</button>
                        <a href="<?= APP_URL ?>/shop.php?product=<?= Security::h($p['slug']) ?>"
                           class="product-card__action-btn" title="Quick view" aria-label="View product">👁</a>
                    </div>
                </div>

                <div class="product-card__body">
                    <p class="product-card__cat"><?= Security::h($p['cat_name']) ?></p>
                    <h3 class="product-card__name">
                        <a href="<?= APP_URL ?>/shop.php?product=<?= Security::h($p['slug']) ?>"
                           style="color:inherit;text-decoration:none">
                            <?= Security::h($p['name']) ?>
                        </a>
                    </h3>

                    <?php if ($p['review_count'] > 0): ?>
                    <div class="product-card__rating">
                        <span class="stars"><?= renderStars((float)$p['avg_rating']) ?></span>
                        <span>(<?= $p['review_count'] ?>)</span>
                    </div>
                    <?php endif; ?>

                    <div class="product-card__price">
                        <span class="price-current <?= $isSale ? 'price-sale' : '' ?>">
                            $<?= number_format((float)$price, 2) ?>
                        </span>
                        <?php if ($isSale): ?>
                        <span class="price-original">$<?= number_format((float)$p['price'], 2) ?></span>
                        <?php endif; ?>
                    </div>

                    <p class="product-card__stock <?= $p['stock'] <= 5 ? 'stock-low' : '' ?> <?= $p['stock'] == 0 ? 'stock-out' : '' ?>">
                        <?php if ($p['stock'] == 0): ?>
                            Out of Stock
                        <?php elseif ($p['stock'] <= 5): ?>
                            Only <?= $p['stock'] ?> left
                        <?php else: ?>
                            In Stock
                        <?php endif; ?>
                    </p>

                    <button class="btn btn-primary" style="width:100%"
                            <?= $p['stock'] == 0 ? 'disabled' : '' ?>
                            data-add-cart="<?= $p['id'] ?>"
                            aria-label="Add <?= Security::h($p['name']) ?> to cart">
                        <?= $p['stock'] == 0 ? 'Out of Stock' : 'Add to Cart' ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="text-align:center;margin-top:48px" data-aos="fade-up">
            <a href="<?= APP_URL ?>/shop.php" class="btn btn-ghost btn-lg">View All Collection</a>
        </div>
    </div>
</section>

<!-- ── Marquee Banner ── -->
<section style="background:#1a1a18;padding:20px 0;overflow:hidden">
    <div style="display:flex;gap:40px;white-space:nowrap;animation:marquee 25s linear infinite">
        <?php for ($i = 0; $i < 4; $i++): ?>
        <span style="color:rgba(250,250,248,.25);font-size:.7rem;letter-spacing:.2em;text-transform:uppercase">
            NEW ARRIVALS 25 &nbsp;·&nbsp; FREE SHIPPING OVER $200 &nbsp;·&nbsp;
            EXCLUSIVE MEMBERS DISCOUNT &nbsp;·&nbsp; HANDCRAFTED IN ITALY &nbsp;·
        </span>
        <?php endfor; ?>
    </div>
</section>
<style>@keyframes marquee{0%{transform:translateX(0)}100%{transform:translateX(-25%)}}</style>

<!-- ── Why AURA ── -->
<section class="section" style="background:var(--color-bg-2)">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <span class="section-header__kicker">The AURA Promise</span>
            <h2 class="section-header__title">Crafted with Purpose</h2>
            <div class="section-header__line"></div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:32px">
            <?php
            $pillars = [
                ['icon'=>'🧵','title'=>'Italian Craftsmanship','desc'=>'Each garment is handcrafted by master artisans using time-honoured techniques.'],
                ['icon'=>'🌿','title'=>'Sustainable Sourcing','desc'=>'Premium natural fabrics sourced ethically from certified suppliers worldwide.'],
                ['icon'=>'📦','title'=>'Signature Packaging','desc'=>'Your order arrives in our iconic branded box, ready to gift or treasure.'],
                ['icon'=>'↩','title'=>'30-Day Returns','desc'=>'Not perfect? Return anything within 30 days, no questions asked.'],
            ];
            foreach ($pillars as $i => $p): ?>
            <div class="card" data-aos="fade-up" data-aos-delay="<?= $i * 80 ?>"
                 style="text-align:center;padding:36px 28px">
                <div style="font-size:2.25rem;margin-bottom:16px"><?= $p['icon'] ?></div>
                <h3 style="font-size:1rem;margin-bottom:10px"><?= Security::h($p['title']) ?></h3>
                <p style="font-size:.875rem;color:var(--color-text-2);line-height:1.7"><?= Security::h($p['desc']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ── CTA Banner ── -->
<section style="padding:100px 0;background:linear-gradient(135deg,#1a1a18 0%,#2d2b26 100%);position:relative;overflow:hidden">
    <div style="position:absolute;inset:0;background:radial-gradient(ellipse 60% 60% at 50% 50%,rgba(184,151,106,0.1) 0%,transparent 70%)"></div>
    <div class="container" style="position:relative;z-index:1;text-align:center">
        <p data-aos="fade-down" style="color:var(--color-gold-lt);font-size:.75rem;letter-spacing:.25em;text-transform:uppercase;margin-bottom:16px">Members Only</p>
        <h2 data-aos="fade-up" style="color:#FAFAF8;font-size:clamp(1.75rem,4vw,3rem);margin-bottom:20px">
            Join the AURA Circle
        </h2>
        <p data-aos="fade-up" data-aos-delay="100" style="color:rgba(250,250,248,.55);max-width:460px;margin:0 auto 36px;font-size:.9375rem">
            Get early access to new collections, exclusive members-only discounts, and personalised styling advice.
        </p>
        <div data-aos="fade-up" data-aos-delay="200" style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap">
            <?php if (Session::isLoggedIn()): ?>
            <a href="<?= APP_URL ?>/user/dashboard.php" class="btn btn-primary btn-lg">My Dashboard</a>
            <?php else: ?>
            <a href="<?= APP_URL ?>/register.php" class="btn btn-primary btn-lg">Create Account</a>
            <a href="<?= APP_URL ?>/login.php" class="btn btn-outline btn-outline-light btn-lg">Sign In</a>
            <?php endif; ?>
        </div>
    </div>
</section>

</main>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>