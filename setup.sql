-- ============================================================
-- AURA E-Commerce Database Setup Script
-- Engine: InnoDB | Charset: utf8mb4
-- ============================================================

CREATE DATABASE IF NOT EXISTS aura_ecommerce
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE aura_ecommerce;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('admin','user') NOT NULL DEFAULT 'user',
    avatar      VARCHAR(255) DEFAULT NULL,
    phone       VARCHAR(20) DEFAULT NULL,
    address     TEXT DEFAULT NULL,
    city        VARCHAR(100) DEFAULT NULL,
    country     VARCHAR(100) DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role  (role)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: categories
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    slug       VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: products
-- ============================================================
CREATE TABLE IF NOT EXISTS products (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    name        VARCHAR(200) NOT NULL,
    slug        VARCHAR(200) NOT NULL UNIQUE,
    description TEXT,
    price       DECIMAL(10,2) NOT NULL,
    sale_price  DECIMAL(10,2) DEFAULT NULL,
    image       VARCHAR(255) DEFAULT 'default.jpg',
    stock       INT UNSIGNED NOT NULL DEFAULT 0,
    featured    TINYINT(1) NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    INDEX idx_category  (category_id),
    INDEX idx_featured  (featured),
    INDEX idx_price     (price)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: product_ratings
-- ============================================================
CREATE TABLE IF NOT EXISTS product_ratings (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED NOT NULL,
    rating     TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review     TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_review (product_id, user_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: wishlist
-- ============================================================
CREATE TABLE IF NOT EXISTS wishlist (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wish (user_id, product_id),
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: cart
-- ============================================================
CREATE TABLE IF NOT EXISTS cart (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity   INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cart (user_id, product_id),
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: orders
-- ============================================================
CREATE TABLE IF NOT EXISTS orders (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    order_number    VARCHAR(20) NOT NULL UNIQUE,
    total_amount    DECIMAL(10,2) NOT NULL,
    tax_amount      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    shipping_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status          ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
    shipping_name   VARCHAR(100) NOT NULL,
    shipping_email  VARCHAR(150) NOT NULL,
    shipping_phone  VARCHAR(20) DEFAULT NULL,
    shipping_address TEXT NOT NULL,
    shipping_city   VARCHAR(100) NOT NULL,
    shipping_country VARCHAR(100) NOT NULL,
    notes           TEXT DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_user   (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: order_items
-- ============================================================
CREATE TABLE IF NOT EXISTS order_items (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id   INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity   INT UNSIGNED NOT NULL,
    price      DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    INDEX idx_order (order_id)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: newsletter
-- ============================================================
CREATE TABLE IF NOT EXISTS newsletter (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(150) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- VIEW: trending_products (top 10 by order frequency)
-- ============================================================
CREATE OR REPLACE VIEW trending_products AS
    SELECT p.id, p.name, p.slug, p.price, p.image,
           SUM(oi.quantity) AS total_sold
    FROM products p
    INNER JOIN order_items oi ON oi.product_id = p.id
    INNER JOIN orders o       ON o.id = oi.order_id
    WHERE o.status != 'cancelled'
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 10;

-- ============================================================
-- TRIGGER: decrement stock on order_item insert (ACID)
-- ============================================================
DELIMITER $$
CREATE TRIGGER trg_decrement_stock
AFTER INSERT ON order_items
FOR EACH ROW
BEGIN
    UPDATE products
    SET stock = stock - NEW.quantity
    WHERE id = NEW.product_id
      AND stock >= NEW.quantity;
END$$
DELIMITER ;

-- ============================================================
-- SEED DATA
-- ============================================================

INSERT INTO categories (name, slug) VALUES
('Men', 'men'),
('Women', 'women'),
('Accessories', 'accessories'),
('New Arrivals', 'new-arrivals');

-- Admin user: password = Admin@1234
INSERT INTO users (name, email, password, role) VALUES
('AURA Admin', 'admin@aura.com',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

INSERT INTO products (category_id, name, slug, description, price, sale_price, image, stock, featured) VALUES
(1, 'Obsidian Slim Blazer', 'obsidian-slim-blazer',
 'A masterfully tailored slim-fit blazer in deep charcoal. Crafted from premium Italian wool blend with satin lining and mother-of-pearl buttons.',
 299.00, 249.00, 'blazer-men.jpg', 15, 1),

(1, 'Sovereign White Dress Shirt', 'sovereign-white-dress-shirt',
 'Impeccably cut from Egyptian cotton, this crisp white dress shirt features a spread collar and French cuffs for effortless elegance.',
 129.00, NULL, 'shirt-white.jpg', 30, 1),

(2, 'Velvet Evening Gown', 'velvet-evening-gown',
 'An exquisite floor-length velvet gown with a dramatic open back, side slit, and delicate champagne-gold embroidery at the neckline.',
 599.00, 499.00, 'gown-velvet.jpg', 8, 1),

(2, 'Silk Wrap Dress', 'silk-wrap-dress',
 'Luxurious pure silk wrap dress with a flattering V-neckline and adjustable waist tie. Available in midnight and champagne.',
 349.00, NULL, 'dress-silk.jpg', 20, 1),

(3, 'Gold-Clasp Leather Belt', 'gold-clasp-leather-belt',
 'Hand-stitched full-grain leather belt with an 18k gold-plated brass clasp engraved with the AURA monogram.',
 95.00, NULL, 'belt-gold.jpg', 50, 0),

(3, 'Structured Tote Bag', 'structured-tote-bag',
 'Architectural structured tote in pebbled calfskin leather. Features suede interior, gold hardware, and detachable strap.',
 445.00, 380.00, 'tote-bag.jpg', 12, 1),

(4, 'Cashmere Ribbed Turtleneck', 'cashmere-ribbed-turtleneck',
 'Pure Grade-A cashmere turtleneck with a relaxed ribbed silhouette. Sourced from Mongolian pashmina goats.',
 275.00, NULL, 'turtleneck-cashmere.jpg', 25, 1),

(4, 'Tailored Wide-Leg Trousers', 'tailored-wide-leg-trousers',
 'Impeccably tailored wide-leg trousers in a refined wool-crepe blend with a high waist and clean knife pleats.',
 195.00, 160.00, 'trousers-wide.jpg', 18, 0);
