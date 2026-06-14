<?php
/**
 * Product Model
 *
 * Database operations for products, categories, ratings and wishlist.
 */

declare(strict_types=1);

namespace Core\Classes;

use Core\Database\Connection;
use Core\Helpers\Security;
use PDO;

class Product
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getPDO();
    }

    // ── Products ──────────────────────────────────────────────

    /**
     * Get paginated products with optional filters.
     *
     * @param  int    $page
     * @param  int    $perPage
     * @param  string $category  Slug
     * @param  string $search
     * @param  string $sort      price_asc|price_desc|newest|name
     * @return array{items: array, total: int, pages: int}
     */
    public function getFiltered(
        int    $page     = 1,
        int    $perPage  = 12,
        string $category = '',
        string $search   = '',
        string $sort     = 'newest'
    ): array {
        $where  = ['1=1'];
        $params = [];

        if ($category !== '') {
            $where[]              = 'c.slug = :cat';
            $params[':cat']       = $category;
        }
        if ($search !== '') {
            $where[]              = '(p.name LIKE :s OR p.description LIKE :s)';
            $params[':s']         = '%' . $search . '%';
        }

        $orderMap = [
            'price_asc'  => 'p.price ASC',
            'price_desc' => 'p.price DESC',
            'name'       => 'p.name ASC',
            'newest'     => 'p.created_at DESC',
        ];
        $order = $orderMap[$sort] ?? 'p.created_at DESC';

        $whereStr = implode(' AND ', $where);

        // Total count
        $countSql = "SELECT COUNT(*) FROM products p
                     JOIN categories c ON c.id = p.category_id
                     WHERE {$whereStr}";
        $cStmt = $this->db->prepare($countSql);
        $cStmt->execute($params);
        $total = (int) $cStmt->fetchColumn();
        $pages = (int) ceil($total / $perPage);

        // Items
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT p.*, c.name AS cat_name, c.slug AS cat_slug,
                       COALESCE(AVG(r.rating), 0) AS avg_rating,
                       COUNT(r.id) AS review_count
                FROM products p
                JOIN categories c ON c.id = p.category_id
                LEFT JOIN product_ratings r ON r.product_id = p.id
                WHERE {$whereStr}
                GROUP BY p.id
                ORDER BY {$order}
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll();

        return compact('items', 'total', 'pages');
    }

    /**
     * Get a single product by its slug.
     *
     * @param  string $slug
     * @return array|null
     */
    public function getBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, c.name AS cat_name, c.slug AS cat_slug,
                    COALESCE(AVG(r.rating), 0) AS avg_rating,
                    COUNT(r.id) AS review_count
             FROM products p
             JOIN categories c ON c.id = p.category_id
             LEFT JOIN product_ratings r ON r.product_id = p.id
             WHERE p.slug = :slug
             GROUP BY p.id LIMIT 1"
        );
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Get a single product by ID.
     *
     * @param  int $id
     * @return array|null
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, c.name AS cat_name, c.slug AS cat_slug
             FROM products p
             JOIN categories c ON c.id = p.category_id
             WHERE p.id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Get featured products.
     *
     * @param  int $limit
     * @return array
     */
    public function getFeatured(int $limit = 8): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, c.name AS cat_name,
                    COALESCE(AVG(r.rating), 0) AS avg_rating
             FROM products p
             JOIN categories c ON c.id = p.category_id
             LEFT JOIN product_ratings r ON r.product_id = p.id
             WHERE p.featured = 1
             GROUP BY p.id
             ORDER BY p.created_at DESC LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Create a new product.
     *
     * @param  array $data
     * @return int  New product ID
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO products
             (category_id, name, slug, description, price, sale_price, image, stock, featured)
             VALUES (:cat, :name, :slug, :desc, :price, :sale, :img, :stock, :feat)"
        );
        $stmt->execute([
            ':cat'   => $data['category_id'],
            ':name'  => $data['name'],
            ':slug'  => $this->makeSlug($data['name']),
            ':desc'  => $data['description'] ?? '',
            ':price' => $data['price'],
            ':sale'  => $data['sale_price'] ?: null,
            ':img'   => $data['image'] ?? 'default.jpg',
            ':stock' => $data['stock'],
            ':feat'  => (int) ($data['featured'] ?? 0),
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update an existing product.
     *
     * @param  int   $id
     * @param  array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE products SET
             category_id=:cat, name=:name, description=:desc,
             price=:price, sale_price=:sale, stock=:stock, featured=:feat
             " . (isset($data['image']) ? ', image=:img' : '') . "
             WHERE id=:id"
        );
        $params = [
            ':cat'   => $data['category_id'],
            ':name'  => $data['name'],
            ':desc'  => $data['description'] ?? '',
            ':price' => $data['price'],
            ':sale'  => $data['sale_price'] ?: null,
            ':stock' => $data['stock'],
            ':feat'  => (int) ($data['featured'] ?? 0),
            ':id'    => $id,
        ];
        if (isset($data['image'])) {
            $params[':img'] = $data['image'];
        }
        return $stmt->execute($params);
    }

    /**
     * Delete a product by ID.
     *
     * @param  int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM products WHERE id=:id');
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Count total products.
     *
     * @return int
     */
    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM products')->fetchColumn();
    }

    /**
     * Count products with stock below threshold (for admin alert).
     *
     * @param  int $threshold
     * @return int
     */
    public function countLowStock(int $threshold = 5): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM products WHERE stock < :t AND stock > 0'
        );
        $stmt->execute([':t' => $threshold]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get all products with low stock.
     *
     * @param  int $threshold
     * @return array
     */
    public function getLowStock(int $threshold = 5): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, c.name AS cat_name FROM products p
             JOIN categories c ON c.id = p.category_id
             WHERE p.stock < :t ORDER BY p.stock ASC'
        );
        $stmt->execute([':t' => $threshold]);
        return $stmt->fetchAll();
    }

    // ── Categories ────────────────────────────────────────────

    /**
     * Get all categories.
     *
     * @return array
     */
    public function getCategories(): array
    {
        return $this->db->query('SELECT * FROM categories ORDER BY name')->fetchAll();
    }

    // ── Ratings & Reviews ─────────────────────────────────────

    /**
     * Get reviews for a product.
     *
     * @param  int $productId
     * @return array
     */
    public function getReviews(int $productId): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, u.name AS user_name
             FROM product_ratings r
             JOIN users u ON u.id = r.user_id
             WHERE r.product_id = :pid
             ORDER BY r.created_at DESC"
        );
        $stmt->execute([':pid' => $productId]);
        return $stmt->fetchAll();
    }

    /**
     * Add or update a review.
     *
     * @param  int    $productId
     * @param  int    $userId
     * @param  int    $rating
     * @param  string $review
     * @return bool
     */
    public function upsertReview(int $productId, int $userId, int $rating, string $review): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO product_ratings (product_id, user_id, rating, review)
             VALUES (:pid, :uid, :rating, :review)
             ON DUPLICATE KEY UPDATE rating=VALUES(rating), review=VALUES(review)"
        );
        return $stmt->execute([
            ':pid'    => $productId,
            ':uid'    => $userId,
            ':rating' => $rating,
            ':review' => $review,
        ]);
    }

    // ── Wishlist ──────────────────────────────────────────────

    /**
     * Get wishlist items for a user.
     *
     * @param  int $userId
     * @return array
     */
    public function getWishlist(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, c.name AS cat_name, w.created_at AS wished_at
             FROM wishlist w
             JOIN products p ON p.id = w.product_id
             JOIN categories c ON c.id = p.category_id
             WHERE w.user_id = :uid
             ORDER BY w.created_at DESC"
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Toggle a wishlist entry (add if absent, remove if present).
     *
     * @param  int $userId
     * @param  int $productId
     * @return string  'added'|'removed'
     */
    public function toggleWishlist(int $userId, int $productId): string
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM wishlist WHERE user_id=:uid AND product_id=:pid'
        );
        $stmt->execute([':uid' => $userId, ':pid' => $productId]);

        if ($stmt->fetch()) {
            $del = $this->db->prepare(
                'DELETE FROM wishlist WHERE user_id=:uid AND product_id=:pid'
            );
            $del->execute([':uid' => $userId, ':pid' => $productId]);
            return 'removed';
        }

        $ins = $this->db->prepare(
            'INSERT INTO wishlist (user_id, product_id) VALUES (:uid, :pid)'
        );
        $ins->execute([':uid' => $userId, ':pid' => $productId]);
        return 'added';
    }

    /**
     * Check if a product is in a user's wishlist.
     *
     * @param  int $userId
     * @param  int $productId
     * @return bool
     */
    public function isWishlisted(int $userId, int $productId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM wishlist WHERE user_id=:uid AND product_id=:pid LIMIT 1'
        );
        $stmt->execute([':uid' => $userId, ':pid' => $productId]);
        return (bool) $stmt->fetch();
    }

    // ── Private helpers ───────────────────────────────────────

    /**
     * Generate a URL-safe slug from a product name.
     *
     * @param  string $name
     * @return string
     */
    private function makeSlug(string $name): string
    {
        $slug = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $name));
        $slug = trim($slug, '-');

        // Ensure uniqueness
        $base = $slug;
        $i    = 1;
        while ($this->slugExists($slug)) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    /**
     * Check if a slug already exists in the database.
     *
     * @param  string $slug
     * @return bool
     */
    private function slugExists(string $slug): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM products WHERE slug=:slug LIMIT 1');
        $stmt->execute([':slug' => $slug]);
        return (bool) $stmt->fetch();
    }
}
