<?php
/**
 * Order Model
 *
 * Handles order creation (ACID transaction), retrieval,
 * status updates, and analytics queries.
 */

declare(strict_types=1);

namespace Core\Classes;

use Core\Database\Connection;
use PDO;
use PDOException;

class Order
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getPDO();
    }

    /**
     * Place a new order from cart items.
     * Uses a database transaction to guarantee ACID compliance.
     *
     * @param  int   $userId
     * @param  array $cartItems  Rows from Cart::getItems()
     * @param  array $totals     Result of Cart::getTotals()
     * @param  array $shipping   Shipping address fields
     * @return int  The new order ID on success
     * @throws \RuntimeException on failure
     */
    public function placeOrder(int $userId, array $cartItems, array $totals, array $shipping): int
    {
        $orderNumber = 'AURA-' . strtoupper(substr(uniqid('', true), -8));

        try {
            $this->db->beginTransaction();

            // 1) Insert master order record
            $stmt = $this->db->prepare(
                "INSERT INTO orders
                 (user_id, order_number, total_amount, tax_amount, shipping_amount, status,
                  shipping_name, shipping_email, shipping_phone,
                  shipping_address, shipping_city, shipping_country, notes)
                 VALUES (:uid, :onum, :total, :tax, :ship, 'pending',
                         :sname, :semail, :sphone, :saddr, :scity, :scountry, :notes)"
            );
            $stmt->execute([
                ':uid'      => $userId,
                ':onum'     => $orderNumber,
                ':total'    => $totals['grand_total'],
                ':tax'      => $totals['tax'],
                ':ship'     => $totals['shipping'],
                ':sname'    => $shipping['name'],
                ':semail'   => $shipping['email'],
                ':sphone'   => $shipping['phone'] ?? null,
                ':saddr'    => $shipping['address'],
                ':scity'    => $shipping['city'],
                ':scountry' => $shipping['country'],
                ':notes'    => $shipping['notes'] ?? null,
            ]);
            $orderId = (int) $this->db->lastInsertId();

            // 2) Insert order items (trigger decrements stock after each insert)
            $itemStmt = $this->db->prepare(
                "INSERT INTO order_items (order_id, product_id, quantity, price)
                 VALUES (:oid, :pid, :qty, :price)"
            );

            foreach ($cartItems as $item) {
                // Verify sufficient stock before inserting
                $stockStmt = $this->db->prepare(
                    'SELECT stock FROM products WHERE id=:pid FOR UPDATE'
                );
                $stockStmt->execute([':pid' => $item['product_id']]);
                $stock = (int) $stockStmt->fetchColumn();

                if ($stock < $item['quantity']) {
                    throw new \RuntimeException(
                        "Insufficient stock for '{$item['name']}'. Only {$stock} left."
                    );
                }

                $itemStmt->execute([
                    ':oid'   => $orderId,
                    ':pid'   => $item['product_id'],
                    ':qty'   => $item['quantity'],
                    ':price' => $item['unit_price'],
                ]);
            }

            $this->db->commit();
            return $orderId;

        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log('[AURA Order] Transaction failed: ' . $e->getMessage());
            throw new \RuntimeException($e->getMessage());
        }
    }

    /**
     * Get paginated orders for a specific user.
     *
     * @param  int $userId
     * @param  int $page
     * @param  int $perPage
     * @return array{items: array, total: int, pages: int}
     */
    public function getUserOrders(int $userId, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;

        $cStmt = $this->db->prepare('SELECT COUNT(*) FROM orders WHERE user_id=:uid');
        $cStmt->execute([':uid' => $userId]);
        $total = (int) $cStmt->fetchColumn();
        $pages = (int) ceil($total / $perPage);

        $stmt = $this->db->prepare(
            "SELECT * FROM orders WHERE user_id=:uid
             ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':uid',    $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll();

        return compact('items', 'total', 'pages');
    }

    /**
     * Get all orders (admin view) with pagination and optional status filter.
     *
     * @param  int    $page
     * @param  int    $perPage
     * @param  string $status
     * @return array{items: array, total: int, pages: int}
     */
    public function getAllOrders(int $page = 1, int $perPage = 15, string $status = ''): array
    {
        $where  = [];
        $params = [];
        if ($status !== '') {
            $where[]          = 'o.status = :status';
            $params[':status'] = $status;
        }
        $whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $offset   = ($page - 1) * $perPage;

        $cStmt = $this->db->prepare("SELECT COUNT(*) FROM orders o {$whereStr}");
        $cStmt->execute($params);
        $total = (int) $cStmt->fetchColumn();
        $pages = (int) ceil($total / $perPage);

        $stmt = $this->db->prepare(
            "SELECT o.*, u.name AS user_name, u.email AS user_email
             FROM orders o JOIN users u ON u.id = o.user_id
             {$whereStr}
             ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset"
        );
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
     * Get a single order by ID with its items.
     *
     * @param  int      $orderId
     * @param  int|null $userId  If provided, restricts to owner (user dashboard)
     * @return array|null
     */
    public function getOrderDetail(int $orderId, ?int $userId = null): ?array
    {
        $extra = $userId !== null ? 'AND o.user_id = :uid' : '';
        $stmt  = $this->db->prepare(
            "SELECT o.*, u.name AS user_name, u.email AS user_email
             FROM orders o JOIN users u ON u.id = o.user_id
             WHERE o.id = :id {$extra} LIMIT 1"
        );
        $params = [':id' => $orderId];
        if ($userId !== null) {
            $params[':uid'] = $userId;
        }
        $stmt->execute($params);
        $order = $stmt->fetch();
        if (!$order) {
            return null;
        }

        // Attach items
        $iStmt = $this->db->prepare(
            "SELECT oi.*, p.name, p.image, p.slug
             FROM order_items oi JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = :oid"
        );
        $iStmt->execute([':oid' => $orderId]);
        $order['items'] = $iStmt->fetchAll();

        return $order;
    }

    /**
     * Update the status of an order (admin only).
     *
     * @param  int    $orderId
     * @param  string $status
     * @return bool
     */
    public function updateStatus(int $orderId, string $status): bool
    {
        $allowed = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }
        $stmt = $this->db->prepare('UPDATE orders SET status=:s WHERE id=:id');
        return $stmt->execute([':s' => $status, ':id' => $orderId]);
    }

    /**
     * Count total orders.
     *
     * @return int
     */
    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    }

    /**
     * Sum total revenue from non-cancelled orders.
     *
     * @return float
     */
    public function totalRevenue(): float
    {
        $val = $this->db->query(
            "SELECT COALESCE(SUM(total_amount), 0)
             FROM orders WHERE status != 'cancelled'"
        )->fetchColumn();
        return (float) $val;
    }

    /**
     * Average order value.
     *
     * @return float
     */
    public function avgOrderValue(): float
    {
        $val = $this->db->query(
            "SELECT COALESCE(AVG(total_amount), 0)
             FROM orders WHERE status != 'cancelled'"
        )->fetchColumn();
        return (float) $val;
    }

    /**
     * Revenue per month for the last 6 months (for charts).
     *
     * @return array
     */
    public function monthlyRevenue(): array
    {
        $stmt = $this->db->query(
            "SELECT DATE_FORMAT(created_at, '%b %Y') AS month,
                    SUM(total_amount) AS revenue
             FROM orders
             WHERE status != 'cancelled'
               AND created_at >= NOW() - INTERVAL 6 MONTH
             GROUP BY DATE_FORMAT(created_at, '%Y-%m')
             ORDER BY MIN(created_at)"
        );
        return $stmt->fetchAll();
    }
}
