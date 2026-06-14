<?php
/**
 * Admin — Manage Products (Add / Edit / Delete + GD image resize)
 */

require_once __DIR__ . '/../../config.php';
require_once CORE_PATH . '/Database/Connection.php';
require_once CORE_PATH . '/Helpers/Security.php';
require_once CORE_PATH . '/Helpers/Session.php';
require_once CORE_PATH . '/Helpers/Validator.php';
require_once CORE_PATH . '/Classes/Product.php';

use Core\Helpers\Security;
use Core\Helpers\Session;
use Core\Helpers\Validator;
use Core\Classes\Product;

Session::start();
Session::requireAdmin();

$productModel = new Product();
$categories   = $productModel->getCategories();
$errors       = [];

// ── Handle POST Actions ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrf()) { die('CSRF check failed.'); }

    $action = $_POST['action'] ?? '';

    // ── Add / Edit Product ────────────────────────────────────
    if (in_array($action, ['add', 'edit'])) {
        $data = [
            'category_id' => (int)($_POST['category_id'] ?? 0),
            'name'        => Security::clean($_POST['name']        ?? ''),
            'description' => Security::clean($_POST['description'] ?? ''),
            'price'       => (float)($_POST['price']       ?? 0),
            'sale_price'  => $_POST['sale_price'] !== '' ? (float)$_POST['sale_price'] : null,
            'stock'       => (int)($_POST['stock']         ?? 0),
            'featured'    => (int)(!empty($_POST['featured'])),
        ];

        $v = (new Validator())->load($_POST)
            ->required('name', 'Product name')
            ->required('category_id', 'Category')
            ->numericMin('price', 0.01)
            ->numericMin('stock', 0);

        if ($v->fails()) {
            $errors = $v->errorList();
        } else {
            // Handle image upload
            if (!empty($_FILES['image']['name'])) {
                $file     = $_FILES['image'];
                $mimeType = mime_content_type($file['tmp_name']);

                if (!in_array($mimeType, ALLOWED_TYPES)) {
                    $errors[] = 'Invalid image type. Use JPG, PNG, or WebP.';
                } elseif ($file['size'] > MAX_FILE_SIZE) {
                    $errors[] = 'Image must be under 5 MB.';
                } else {
                    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = Security::uuid() . '.' . strtolower($ext);
                    $dest     = UPLOAD_PATH . '/' . $filename;

                    // GD resize
                    if (resizeImage($file['tmp_name'], $dest, $mimeType, IMG_THUMB_W, IMG_THUMB_H)) {
                        $data['image'] = $filename;
                    } else {
                        $errors[] = 'Image processing failed.';
                    }
                }
            }

            if (empty($errors)) {
                if ($action === 'add') {
                    $productModel->create($data);
                    Session::flash('success', 'Product "' . $data['name'] . '" added successfully.');
                } else {
                    $pid = (int)($_POST['product_id'] ?? 0);
                    $productModel->update($pid, $data);
                    Session::flash('success', 'Product updated.');
                }
                header('Location: ' . APP_URL . '/admin/products.php');
                exit;
            }
        }
    }

    // ── Delete Product ────────────────────────────────────────
    if ($action === 'delete') {
        $pid = (int)($_POST['product_id'] ?? 0);
        if ($pid) {
            $productModel->delete($pid);
            Session::flash('success', 'Product deleted.');
        }
        header('Location: ' . APP_URL . '/admin/products.php');
        exit;
    }
}

// ── GD Image Resize ────────────────────────────────────────────
/**
 * Resize and save an uploaded image using PHP GD.
 *
 * @param  string $src       Temp file path
 * @param  string $dest      Destination path
 * @param  string $mimeType  MIME type of source
 * @param  int    $maxW      Max width
 * @param  int    $maxH      Max height
 * @return bool
 */
function resizeImage(string $src, string $dest, string $mimeType, int $maxW, int $maxH): bool
{
    if (!extension_loaded('gd')) {
        return move_uploaded_file($src, $dest);
    }

    $srcImg = match ($mimeType) {
        'image/jpeg' => @imagecreatefromjpeg($src),
        'image/png'  => @imagecreatefrompng($src),
        'image/webp' => @imagecreatefromwebp($src),
        default      => false,
    };

    if (!$srcImg) { return false; }

    [$origW, $origH] = getimagesize($src);
    $ratio  = min($maxW / $origW, $maxH / $origH, 1);
    $newW   = (int)round($origW * $ratio);
    $newH   = (int)round($origH * $ratio);

    $dst    = imagecreatetruecolor($newW, $newH);

    // Preserve PNG / WebP transparency
    if (in_array($mimeType, ['image/png', 'image/webp'])) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $trans = imagecolorallocatealpha($dst, 255, 255, 255, 127);
        imagefilledrectangle($dst, 0, 0, $newW, $newH, $trans);
    }

    imagecopyresampled($dst, $srcImg, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

    $result = match ($mimeType) {
        'image/jpeg' => imagejpeg($dst, $dest, 85),
        'image/png'  => imagepng($dst, $dest, 6),
        'image/webp' => imagewebp($dst, $dest, 85),
        default      => false,
    };

    imagedestroy($srcImg);
    imagedestroy($dst);

    return (bool)$result;
}

// Editing existing product?
$editId  = (int)($_GET['edit'] ?? 0);
$editProd = $editId ? $productModel->getById($editId) : null;

// Listing
$page    = max(1, (int)($_GET['page'] ?? 1));
$result  = $productModel->getFiltered($page, 20);
$products = $result['items'];
$totalPg  = $result['pages'];

$pageTitle = 'Manage Products — AURA';
require_once VIEWS_PATH . '/partials/head.php';
require_once VIEWS_PATH . '/partials/navbar.php';
?>

<div class="admin-layout">
    <?php require_once VIEWS_PATH . '/admin/sidebar.php'; ?>

    <main class="admin-main">
        <div class="admin-header">
            <h1>Products</h1>
            <p>Add, edit, and manage your product catalogue.</p>
        </div>

        <!-- Errors -->
        <?php if (!empty($errors)): ?>
        <div style="background:rgba(181,87,74,.08);border:1px solid rgba(181,87,74,.2);border-radius:var(--radius-md);
             padding:14px 18px;margin-bottom:24px">
            <?php foreach ($errors as $e): ?>
            <p style="font-size:.875rem;color:var(--color-danger)"><?= Security::h($e) ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Add / Edit Form -->
        <div class="card" style="margin-bottom:32px" data-aos="fade-up">
            <h2 style="font-size:1rem;margin-bottom:24px">
                <?= $editProd ? 'Edit Product: ' . Security::h($editProd['name']) : 'Add New Product' ?>
            </h2>

            <form method="POST" enctype="multipart/form-data" novalidate>
                <?= Security::csrfField() ?>
                <input type="hidden" name="action"     value="<?= $editProd ? 'edit' : 'add' ?>">
                <?php if ($editProd): ?>
                <input type="hidden" name="product_id" value="<?= $editProd['id'] ?>">
                <?php endif; ?>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label" for="p-name">Product Name</label>
                        <input type="text" id="p-name" name="name" class="form-control"
                               value="<?= Security::h($editProd['name'] ?? '') ?>" required placeholder="e.g. Obsidian Slim Blazer">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="p-cat">Category</label>
                        <select id="p-cat" name="category_id" class="form-control" required>
                            <option value="">Select category</option>
                            <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>"
                                <?= ($editProd['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                <?= Security::h($c['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="p-stock">Stock Quantity</label>
                        <input type="number" id="p-stock" name="stock" class="form-control"
                               value="<?= Security::h($editProd['stock'] ?? '0') ?>" min="0" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="p-price">Price ($)</label>
                        <input type="number" id="p-price" name="price" class="form-control"
                               value="<?= Security::h($editProd['price'] ?? '') ?>" step="0.01" min="0.01" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="p-sale">Sale Price (optional)</label>
                        <input type="number" id="p-sale" name="sale_price" class="form-control"
                               value="<?= Security::h($editProd['sale_price'] ?? '') ?>" step="0.01" min="0" placeholder="Leave blank for no sale">
                    </div>

                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label" for="p-desc">Description</label>
                        <textarea id="p-desc" name="description" class="form-control" rows="4"
                                  placeholder="Product description…"><?= Security::h($editProd['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="product-image-input">Product Image</label>
                        <input type="file" id="product-image-input" name="image" class="form-control"
                               accept="image/jpeg,image/png,image/webp">
                        <?php if ($editProd && $editProd['image']): ?>
                        <img id="product-image-preview"
                             src="<?= UPLOAD_URL . '/' . Security::h($editProd['image']) ?>"
                             style="margin-top:10px;max-width:120px;border-radius:var(--radius-sm)">
                        <?php else: ?>
                        <img id="product-image-preview" style="display:none;margin-top:10px;max-width:120px;border-radius:var(--radius-sm)">
                        <?php endif; ?>
                    </div>

                    <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:20px">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
                            <input type="checkbox" name="featured" value="1"
                                   <?= !empty($editProd['featured']) ? 'checked' : '' ?>
                                   style="width:16px;height:16px;accent-color:var(--color-gold)">
                            <span style="font-size:.875rem">Feature this product on homepage</span>
                        </label>
                    </div>
                </div>

                <div style="display:flex;gap:12px;margin-top:8px">
                    <button type="submit" class="btn btn-primary">
                        <?= $editProd ? 'Save Changes' : 'Add Product' ?>
                    </button>
                    <?php if ($editProd): ?>
                    <a href="<?= APP_URL ?>/admin/products.php" class="btn btn-outline">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Products Table -->
        <div class="card" data-aos="fade-up">
            <h2 style="font-size:1rem;margin-bottom:20px">All Products (<?= $result['total'] ?>)</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Featured</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                        <tr style="<?= $p['stock'] < 5 ? 'background:rgba(199,136,45,.04)' : '' ?>">
                            <td>
                                <div style="display:flex;align-items:center;gap:10px">
                                    <img src="<?= UPLOAD_URL . '/' . Security::h($p['image']) ?>"
                                         alt="" style="width:40px;height:48px;object-fit:cover;border-radius:4px;background:var(--color-bg-2)"
                                         onerror="this.style.display='none'">
                                    <span style="font-weight:500;font-size:.875rem"><?= Security::h($p['name']) ?></span>
                                </div>
                            </td>
                            <td><?= Security::h($p['cat_name']) ?></td>
                            <td>
                                $<?= number_format((float)$p['price'], 2) ?>
                                <?php if ($p['sale_price']): ?>
                                <br><small style="color:var(--color-danger)">Sale: $<?= number_format((float)$p['sale_price'], 2) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-weight:600;color:<?= $p['stock'] == 0 ? 'var(--color-danger)' : ($p['stock'] < 5 ? 'var(--color-warning)' : 'var(--color-success)') ?>">
                                    <?= $p['stock'] ?>
                                </span>
                            </td>
                            <td><?= $p['featured'] ? '⭐' : '—' ?></td>
                            <td>
                                <div style="display:flex;gap:8px">
                                    <a href="?edit=<?= $p['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                                    <form method="POST" onsubmit="return confirm('Delete this product?')">
                                        <?= Security::csrfField() ?>
                                        <input type="hidden" name="action"     value="delete">
                                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPg > 1): ?>
            <nav class="pagination" style="margin-top:24px">
                <?php for ($i = 1; $i <= $totalPg; $i++): ?>
                <a href="?page=<?= $i ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </nav>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>
