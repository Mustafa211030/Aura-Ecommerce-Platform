<?php
/**
 * Cart Model
 *
 * Manages shopping cart operations for logged-in users.
 */

declare(strict_types=1);

namespace Core\Classes;

use Core\Database\Connection;
use PDO;

class Cart
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getPDO();
    }

    /**
     * Get all cart items for a user (with product details).
     *
     * @param  int $userId
     * @return array
     */
    public function getItems(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.id, c.quantity, c.product_id,
                    p.name, p.slug, p.image, p.stock,
                    COALESCE(p.sale_price, p.price) AS unit_price,
                    COALESCE(p.sale_price, p.price) * c.quantity AS line_total
             FROM cart c
             JOIN products p ON p.id = c.product_id
             WHERE c.user_id = :uid
             ORDER BY c.created_at DESC"
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Add a product to the cart (or increment quantity if already present).
     *
     * @param  int $userId
     * @param  int $productId
     * @param  int $quantity
     * @return bool
     */
    public function addItem(int $userId, int $productId, int $quantity = 1): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO cart (user_id, product_id, quantity)
             VALUES (:uid, :pid, :qty)
             ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)"
        );
        return $stmt->execute([
            ':uid' => $userId,
            ':pid' => $productId,
            ':qty' => $quantity,
        ]);
    }

    /**
     * Update the quantity of a specific cart item.
     *
     * @param  int $cartId  The cart row ID
     * @param  int $userId  For ownership verification
     * @param  int $quantity
     * @return bool
     */
    public function updateQuantity(int $cartId, int $userId, int $quantity): bool
    {
        if ($quantity <= 0) {
            return $this->removeItem($cartId, $userId);
        }

        $stmt = $this->db->prepare(
            'UPDATE cart SET quantity=:qty WHERE id=:id AND user_id=:uid'
        );
        return $stmt->execute([
            ':qty' => $quantity,
            ':id'  => $cartId,
            ':uid' => $userId,
        ]);
    }

    /**
     * Remove a single item from the cart.
     *
     * @param  int $cartId
     * @param  int $userId  For ownership verification
     * @return bool
     */
    public function removeItem(int $cartId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM cart WHERE id=:id AND user_id=:uid'
        );
        return $stmt->execute([':id' => $cartId, ':uid' => $userId]);
    }

    /**
     * Clear the entire cart for a user.
     *
     * @param  int $userId
     * @return bool
     */
    public function clearCart(int $userId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM cart WHERE user_id=:uid');
        return $stmt->execute([':uid' => $userId]);
    }

    /**
     * Count distinct items in the user's cart.
     *
     * @param  int $userId
     * @return int
     */
    public function countItems(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT SUM(quantity) FROM cart WHERE user_id=:uid');
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Calculate cart totals.
     *
     * @param  int $userId
     * @return array{subtotal: float, tax: float, shipping: float, grand_total: float}
     */
    public function getTotals(int $userId): array
    {
        $items    = $this->getItems($userId);
        $subtotal = array_sum(array_column($items, 'line_total'));
        $tax      = round($subtotal * TAX_RATE, 2);
        $shipping = $subtotal > 0 ? SHIPPING_FLAT : 0.00;
        $grand    = round($subtotal + $tax + $shipping, 2);

        return [
            'subtotal'    => $subtotal,
            'tax'         => $tax,
            'shipping'    => $shipping,
            'grand_total' => $grand,
        ];
    }
}
