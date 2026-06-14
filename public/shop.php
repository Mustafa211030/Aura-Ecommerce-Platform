<?php
/**
 * Shop Page
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
$categories   = $productModel->getCategories();

// Handle GET params
$page     = max(1, (int)($_GET['page']   ?? 1));
$cat      = Security::clean($_GET['cat']  ?? '');
$search   = Security::clean($_GET['q']    ?? '');
$sort     = Security::clean($_GET['sort'] ?? 'newest');

// Check if viewing single product
$productSlug = Security::clean($_GET['product'] ?? '');
$single      = $productSlug ? $productModel->getBySlug($productSlug) : null;

if ($single) {
    $reviews      = $productModel->getReviews((int)$single['id']);
    $isWishlisted = Session::isLoggedIn()
        ? $productModel->isWishlisted(Session::get('user_id'), $single['id'])
        : false;
    $pageTitle = Security::h($single['name']) . ' — AURA';
} else {
    $result     = $productModel->getFiltered($page, PRODUCTS_PER_PAGE, $cat, $search, $sort);
    $products   = $result['items'];
    $totalPages = $result['pages'];
    $totalItems = $result['total'];
    $pageTitle  = 'Shop — AURA';
}

// Handle review POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    Session::requireAuth();
    if (!Security::verifyCsrf()) { die('CSRF check failed.'); }

    $pid    = (int)($_POST['product_id'] ?? 0);
    $rating = min(5, max(1, (int)($_POST['rating'] ?? 5)));
    $review = Security::clean($_POST['review'] ?? '');

    $productModel->upsertReview($pid, Session::get('user_id'), $rating, $review);
    Session::flash('success', 'Review submitted!');
    header('Location: ' . APP_URL . '/shop.php?product=' . urlencode($productSlug));
    exit;
}

function renderStars(float $avg): string {
    $full  = floor($avg);
    $half  = ($avg - $full) >= 0.5 ? 1 : 0;
    $empty = 5 - $full - $half;
    return str_repeat('★', (int)$full) . str_repeat('✦', $half) . str_repeat('☆', (int)$empty);
}

require_once VIEWS_PATH . '/partials/head.php';
require_once VIEWS_PATH . '/partials/navbar.php';
?>

<main style="padding-top:var(--nav-h)">

<?php if ($single): /* ── Single Product View ── */ ?>

<div class="container" style="padding-top:60px;padding-bottom:80px">
    <!-- Breadcrumb -->
    <nav style="font-size:.8rem;color:var(--color-text-3);margin-bottom:40px;display:flex;gap:10px;align-items:center">
        <a href="<?= APP_URL ?>/shop.php" style="color:var(--color-gold);text-decoration:none">← Back to Shop</a>
        <span>/</span>
        <span><?= Security::h($single['name']) ?></span>
    </nav>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:64px;align-items:start" class="responsive-product-grid">

        <!-- Image — FIX 1: restored full <img> tag with Security::imageUrl() -->
        <div style="position:sticky;top:calc(var(--nav-h) + 20px)">
            <div style="aspect-ratio:3/4;overflow:hidden;border-radius:var(--radius-lg);background:var(--color-bg-2)">
                <img src="<?= Security::h(Security::imageUrl($single['image'])) ?>"
                     alt="<?= Security::h($single['name']) ?>"
                     style="width:100%;height:100%;object-fit:cover"
                     onerror="this.src='<?= APP_URL ?>/assets/images/placeholder.jpg'">
            </div>
        </div>

        <!-- Details -->
        <div data-aos="fade-left">
            <p style="font-size:.7rem;letter-spacing:.15em;text-transform:uppercase;color:var(--color-gold);margin-bottom:8px">
                <?= Security::h($single['cat_name']) ?>
            </p>
            <h1 style="font-size:clamp(1.5rem,3vw,2.25rem);margin-bottom:16px">
                <?= Security::h($single['name']) ?>
            </h1>

            <?php if ($single['review_count'] > 0): ?>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;font-size:.875rem">
                <span style="color:var(--color-gold);font-size:1rem"><?= renderStars((float)$single['avg_rating']) ?></span>
                <span style="color:var(--color-text-2)"><?= round($single['avg_rating'], 1) ?> (<?= $single['review_count'] ?> reviews)</span>
            </div>
            <?php endif; ?>

            <div style="display:flex;align-items:center;gap:16px;margin-bottom:28px">
                <?php $isSale = !empty($single['sale_price']); ?>
                <span style="font-family:var(--font-serif);font-size:2rem;color:<?= $isSale ? 'var(--color-danger)' : 'var(--color-text)' ?>">
                    $<?= number_format((float)($isSale ? $single['sale_price'] : $single['price']), 2) ?>
                </span>
                <?php if ($isSale): ?>
                <span style="font-size:1.1rem;color:var(--color-text-3);text-decoration:line-through">
                    $<?= number_format((float)$single['price'], 2) ?>
                </span>
                <span style="background:rgba(181,87,74,.1);color:var(--color-danger);font-size:.7rem;font-weight:600;padding:4px 10px;border-radius:999px">
                    SALE
                </span>
                <?php endif; ?>
            </div>

            <p style="color:var(--color-text-2);line-height:1.8;margin-bottom:32px;font-size:.9375rem">
                <?= nl2br(Security::h($single['description'])) ?>
            </p>

            <p style="font-size:.8rem;font-weight:500;margin-bottom:20px;
                color:<?= $single['stock'] == 0 ? 'var(--color-danger)' : ($single['stock'] <= 5 ? 'var(--color-warning)' : 'var(--color-success)') ?>">
                <?php if ($single['stock'] == 0): ?>● Out of Stock
                <?php elseif ($single['stock'] <= 5): ?>● Only <?= $single['stock'] ?> left
                <?php else: ?>● In Stock (<?= $single['stock'] ?> available)
                <?php endif; ?>
            </p>

            <div style="display:flex;gap:12px;margin-bottom:16px">
                <button class="btn btn-primary" style="flex:1;justify-content:center"
                        data-add-cart="<?= $single['id'] ?>"
                        <?= $single['stock'] == 0 ? 'disabled' : '' ?>>
                    <?= $single['stock'] == 0 ? 'Out of Stock' : 'Add to Cart' ?>
                </button>
                <button class="btn btn-outline btn-icon"
                        data-wishlist="<?= $single['id'] ?>"
                        title="<?= $isWishlisted ? 'Remove from wishlist' : 'Add to wishlist' ?>">
                    <?= $isWishlisted ? '♥' : '♡' ?>
                </button>
            </div>

            <hr class="divider">

            <!-- Reviews -->
            <div>
                <h2 style="font-size:1.25rem;margin-bottom:24px">Customer Reviews</h2>

                <?php if (Session::isLoggedIn()): ?>
                <form method="POST" style="background:var(--color-bg-2);border-radius:var(--radius-lg);padding:24px;margin-bottom:32px">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="product_id" value="<?= $single['id'] ?>">
                    <input type="hidden" name="submit_review" value="1">

                    <h3 style="font-size:1rem;margin-bottom:16px">Write a Review</h3>

                    <div class="star-input" style="margin-bottom:16px">
                        <input type="hidden" name="rating" value="5">
                        <div style="display:flex;gap:4px">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button type="button" class="star-btn <?= $i <= 5 ? 'selected' : '' ?>"
                                    style="background:none;border:none;font-size:1.5rem;cursor:pointer;
                                           color:<?= $i <= 5 ? 'var(--color-gold)' : 'var(--color-border)' ?>;
                                           transition:color .2s;padding:2px">★</button>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <textarea name="review" class="form-control" rows="4"
                              placeholder="Share your experience with this piece..."
                              style="margin-bottom:12px"></textarea>
                    <button type="submit" class="btn btn-primary btn-sm">Submit Review</button>
                </form>
                <?php else: ?>
                <p style="font-size:.875rem;color:var(--color-text-2);margin-bottom:24px">
                    <a href="<?= APP_URL ?>/login.php" style="color:var(--color-gold)">Log in</a> to write a review.
                </p>
                <?php endif; ?>

                <?php if (empty($reviews)): ?>
                <p style="color:var(--color-text-3);font-size:.875rem">No reviews yet. Be the first!</p>
                <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:20px">
                    <?php foreach ($reviews as $r): ?>
                    <div style="padding:20px;background:var(--color-surface);border:1px solid var(--color-border);border-radius:var(--radius-md)">
                        <div style="display:flex;justify-content:space-between;margin-bottom:8px">
                            <strong style="font-size:.875rem"><?= Security::h($r['user_name']) ?></strong>
                            <span style="color:var(--color-gold);font-size:.9rem"><?= renderStars((float)$r['rating']) ?></span>
                        </div>
                        <p style="font-size:.875rem;color:var(--color-text-2);line-height:1.7">
                            <?= Security::h($r['review']) ?>
                        </p>
                        <p style="font-size:.7rem;color:var(--color-text-3);margin-top:8px">
                            <?= date('M j, Y', strtotime($r['created_at'])) ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
@media(max-width:768px){
    .responsive-product-grid{grid-template-columns:1fr !important;gap:32px !important}
}
</style>

<?php else: /* ── Product Listing ── */ ?>

<!-- Shop Header -->
<section style="background:var(--color-bg-2);padding:60px 0 40px">
    <div class="container">
        <h1 data-aos="fade-up" style="margin-bottom:8px">The Collection</h1>
        <p data-aos="fade-up" data-aos-delay="100" style="color:var(--color-text-2)">
            <?= $totalItems ?> piece<?= $totalItems !== 1 ? 's' : '' ?> crafted for the discerning eye
        </p>
    </div>
</section>

<div class="container" style="padding-top:48px;padding-bottom:80px">

    <!-- Search + Sort -->
    <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:32px;align-items:center" data-aos="fade-up">
        <form method="GET" style="flex:1;min-width:240px;display:flex;gap:8px">
            <?php if ($cat): ?><input type="hidden" name="cat" value="<?= Security::h($cat) ?>"><?php endif; ?>
            <input type="search" name="q" class="form-control" placeholder="Search the collection…"
                   value="<?= Security::h($search) ?>" style="max-width:360px">
            <button type="submit" class="btn btn-primary btn-sm">Search</button>
            <?php if ($search): ?>
            <a href="<?= APP_URL ?>/shop.php<?= $cat ? '?cat='.Security::h($cat) : '' ?>" class="btn btn-outline btn-sm">Clear</a>
            <?php endif; ?>
        </form>

        <select id="sort-select" class="form-control" style="width:auto;min-width:160px"
                aria-label="Sort products">
            <?php $sorts = ['newest'=>'Newest','price_asc'=>'Price: Low–High','price_desc'=>'Price: High–Low','name'=>'Name']; ?>
            <?php foreach ($sorts as $val => $label): ?>
            <option value="<?= $val ?>" <?= $sort === $val ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Category Filter Pills -->
    <div class="filter-bar" data-aos="fade-up">
        <button class="filter-pill <?= !$cat ? 'active' : '' ?>" data-cat="">All</button>
        <?php foreach ($categories as $c): ?>
        <button class="filter-pill <?= $cat === $c['slug'] ? 'active' : '' ?>"
                data-cat="<?= Security::h($c['slug']) ?>">
            <?= Security::h($c['name']) ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- Product Grid -->
    <?php if (empty($products)): ?>
    <div style="text-align:center;padding:80px 0">
        <div style="font-size:3rem;margin-bottom:20px">🔍</div>
        <h2 style="font-size:1.5rem;margin-bottom:12px">Nothing found</h2>
        <p style="color:var(--color-text-2);margin-bottom:24px">Try adjusting your search or filters.</p>
        <a href="<?= APP_URL ?>/shop.php" class="btn btn-primary">Browse All</a>
    </div>
    <?php else: ?>
    <div class="products-grid">
        <?php foreach ($products as $i => $p): ?>
        <?php
        /* FIX 2: use Security::imageUrl() with $p['image'] — the current loop product */
        $img    = Security::imageUrl($p['image']);
        $isSale = !empty($p['sale_price']);
        $price  = $isSale ? $p['sale_price'] : $p['price'];
        ?>
        <div class="product-card" data-aos="fade-up" data-aos-delay="<?= min($i % 4 * 60, 300) ?>">
            <div class="product-card__image">
                <a href="<?= APP_URL ?>/shop.php?product=<?= Security::h($p['slug']) ?>">
                    <!-- FIX 3: src uses $img (from $p), never $single['image'] -->
                    <img src="<?= Security::h($img) ?>"
                         alt="<?= Security::h($p['name']) ?>"
                         loading="lazy"
                         onerror="this.src='<?= APP_URL ?>/assets/images/placeholder.jpg'">
                </a>

                <?php if ($isSale): ?>
                <span class="product-card__badge badge-sale">Sale</span>
                <?php endif; ?>
                <?php if ($p['stock'] <= 5 && $p['stock'] > 0): ?>
                <span class="product-card__badge badge-low" style="<?= $isSale ? 'top:48px' : '' ?>">Low Stock</span>
                <?php endif; ?>

                <div class="product-card__actions">
                    <button class="product-card__action-btn" data-wishlist="<?= $p['id'] ?>"
                            title="Add to wishlist" aria-label="Wishlist">♡</button>
                    <a href="<?= APP_URL ?>/shop.php?product=<?= Security::h($p['slug']) ?>"
                       class="product-card__action-btn" title="View details" aria-label="View">👁</a>
                </div>
            </div>

            <div class="product-card__body">
                <p class="product-card__cat"><?= Security::h($p['cat_name']) ?></p>
                <h3 class="product-card__name">
                    <a href="<?= APP_URL ?>/shop.php?product=<?= Security::h($p['slug']) ?>"
                       style="color:inherit;text-decoration:none"><?= Security::h($p['name']) ?></a>
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

                <button class="btn btn-primary btn-sm" style="width:100%"
                        data-add-cart="<?= $p['id'] ?>"
                        <?= $p['stock'] == 0 ? 'disabled' : '' ?>>
                    <?= $p['stock'] == 0 ? 'Out of Stock' : 'Add to Cart' ?>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <nav class="pagination" role="navigation" aria-label="Page navigation">
        <?php
        $buildUrl = function(int $p) use ($cat, $search, $sort): string {
            $q = array_filter(compact('cat','search','sort'));
            $q['page'] = $p;
            return APP_URL . '/shop.php?' . http_build_query($q);
        };
        ?>
        <a href="<?= $buildUrl($page - 1) ?>"
           class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>"
           aria-label="Previous">‹</a>

        <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
        <a href="<?= $buildUrl($i) ?>"
           class="page-btn <?= $i === $page ? 'active' : '' ?>"
           aria-current="<?= $i === $page ? 'page' : 'false' ?>">
            <?= $i ?>
        </a>
        <?php endfor; ?>

        <a href="<?= $buildUrl($page + 1) ?>"
           class="page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>"
           aria-label="Next">›</a>
    </nav>
    <?php endif; ?>
    <?php endif; ?>

</div>
<?php endif; ?>
</main>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>