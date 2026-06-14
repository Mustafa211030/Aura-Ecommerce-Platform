# AURA E-Commerce — Complete File-by-File Documentation
### Every file, every function, every design decision explained

---

## ═══════════════════════════════════════════════════════
## PART 1: ROOT & CONFIGURATION FILES
## ═══════════════════════════════════════════════════════

---

### 📄 `config.php` — The Brain of the Application

**What it does:**
This is the FIRST file loaded by every single page. It defines every global constant
the application uses so nothing is ever hardcoded twice.

**Key constants defined:**

| Constant          | Value example                          | Purpose                                           |
|-------------------|----------------------------------------|---------------------------------------------------|
| `APP_ENV`         | `'development'`                        | Controls error reporting (show errors in dev, hide in prod) |
| `APP_URL`         | `http://localhost/aura-ecommerce/public` | Base URL prepended to every link                 |
| `BASE_PATH`       | `/var/www/aura-ecommerce`              | Absolute server path — used for `require_once`    |
| `CORE_PATH`       | `BASE_PATH . '/core'`                  | Path to Model/Helper classes                      |
| `VIEWS_PATH`      | `BASE_PATH . '/views'`                 | Path to view partials                             |
| `DB_HOST/NAME/USER/PASS` | `localhost`, `aura_ecommerce`   | Database credentials — only here, never in code  |
| `UPLOAD_PATH`     | Full server path to uploads folder     | Where GD-resized images are saved on disk         |
| `UPLOAD_URL`      | Public URL to uploads folder           | What `<img src>` uses to load images              |
| `MAX_FILE_SIZE`   | `5242880` (5 MB)                       | Upload guard limit                                |
| `TAX_RATE`        | `0.08`                                 | 8% tax applied at checkout                       |
| `SHIPPING_FLAT`   | `9.99`                                 | Flat shipping fee per order                       |
| `SESSION_TIMEOUT` | `3600`                                 | Auto-logout after 1 hour idle                    |
| `CSRF_TOKEN_NAME` | `'_aura_csrf'`                         | Name of the hidden CSRF field in all forms        |
| `PRODUCTS_PER_PAGE`| `12`                                  | Pagination chunk size on shop page                |

**Why it matters:**
By having ONE place for configuration, you can move the site to a new server by
editing only this one file. No hunting through 30 PHP files.

---

### 📄 `setup.sql` — The Database Architect

**What it does:**
Complete MySQL script that creates the entire database from scratch. Run once via
phpMyAdmin import. Contains: CREATE TABLE, foreign keys, indexes, a VIEW, a
TRIGGER, and seed data (sample products + admin user).

**Tables created and why:**

```
users              → Who can log in and what role they have
categories         → Product groupings (Men, Women, Accessories, New Arrivals)
products           → The catalogue — linked to categories
product_ratings    → Reviews; UNIQUE(user_id, product_id) = one review per person
wishlist           → Saved items; UNIQUE(user_id, product_id) = no duplicates
cart               → Current basket; UNIQUE prevents duplicate product rows
orders             → The master order record with shipping info and total
order_items        → Line items (one row per product in the order)
newsletter         → Email subscriptions; UNIQUE(email) prevents duplicates
```

**The MySQL TRIGGER (most important concept):**
```sql
CREATE TRIGGER trg_decrement_stock
AFTER INSERT ON order_items
FOR EACH ROW
BEGIN
    UPDATE products
    SET stock = stock - NEW.quantity
    WHERE id = NEW.product_id AND stock >= NEW.quantity;
END
```
Every time an `order_items` row is inserted, MySQL AUTOMATICALLY decrements
the product stock. This happens at the database level — even if PHP crashes
mid-request, the stock is still corrected. This is ACID compliance.

**The MySQL VIEW (trending_products):**
```sql
CREATE VIEW trending_products AS
    SELECT p.id, p.name, SUM(oi.quantity) AS total_sold
    FROM products p
    JOIN order_items oi ON oi.product_id = p.id
    ...
```
A VIEW is a saved SQL query. You can do `SELECT * FROM trending_products` as if
it were a table. It auto-calculates which products have the highest order frequency.

**The image column design:**
The `image` column accepts EITHER a bare filename (`photo.jpg`) OR a full URL
(`https://images.unsplash.com/...`). The `Security::imageUrl()` helper resolves
both cases automatically in PHP.

---

### 📄 `patch_images.sql` — Live Database Image URL Fix

**What it does:**
Run this if you already have the old database with placeholder filenames.
Does `UPDATE products SET image = 'https://...' WHERE slug = '...'` for all 8
original products, then adds 3 new products using the provided image URLs.

**Why a separate patch file?**
Best practice: never drop-and-recreate a production database just to update image
paths. A targeted UPDATE script is safe — it only changes what needs changing.

---

### 📄 `README.md` — Installation & Reference Guide

Quick-start guide, folder structure diagram, feature list, security table, API
reference, and design token reference. Written for a developer who has never seen
the project before.

---

## ═══════════════════════════════════════════════════════
## PART 2: CORE — Database Layer
## ═══════════════════════════════════════════════════════

---

### 📄 `core/Database/Connection.php` — PDO Singleton

**Design Pattern: Singleton**
Only ONE database connection is created per request, no matter how many models
need it. Every class calls `Connection::getInstance()->getPDO()` and gets the
same PDO object.

**Why Singleton for DB connections?**
Opening a MySQL connection has overhead (TCP handshake, auth). Creating one per
model (User, Product, Cart, Order) would open 4 connections. Singleton = 1 connection
shared across everything.

**Key code logic:**
```php
private static ?Connection $instance = null;  // stored here once

public static function getInstance(): Connection
{
    if (self::$instance === null) {          // first call → create
        self::$instance = new self();
    }
    return self::$instance;                   // all subsequent calls → return same
}
```

**PDO options configured:**
```php
PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION  // throw exceptions on SQL errors
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC        // return rows as ['column' => value]
PDO::ATTR_EMULATE_PREPARES   => false                   // REAL prepared statements (SQL injection safe)
```

**Security:** If the DB connection fails, it logs the REAL error internally but
throws a generic RuntimeException to the caller — database credentials are never
exposed to the browser.

---

## ═══════════════════════════════════════════════════════
## PART 3: CORE — Helper Classes
## ═══════════════════════════════════════════════════════

---

### 📄 `core/Helpers/Security.php` — The Security Arsenal

Every security-sensitive operation goes through this class. Static methods — no
instantiation needed, just call `Security::methodName()` anywhere.

**Method-by-method breakdown:**

**`Security::h($value)` — XSS Prevention**
```php
return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
```
Converts `<script>alert('xss')</script>` into the safe string
`&lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;`. Call this on EVERY
piece of user data before printing it in HTML. Used hundreds of times in views.

**`Security::csrfToken()` — CSRF Protection**
```php
$_SESSION['_aura_csrf'] = bin2hex(random_bytes(32));
```
Generates a 64-character cryptographically random token stored in the session.
This token is embedded in every form as a hidden field.

**`Security::verifyCsrf()` — CSRF Verification**
```php
return hash_equals($stored, $submitted);
```
Uses `hash_equals()` (NOT `==`) to prevent timing attacks where an attacker
could guess the token character-by-character by measuring response time differences.

**`Security::hashPassword($plain)` — Password Hashing**
```php
return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
```
Bcrypt cost 12 means the hash takes ~250ms to compute. This makes brute-force
attacks impractical (attacker can only try ~4 passwords/second).

**`Security::verifyPassword($plain, $hash)` — Login Check**
```php
return password_verify($password, $hash);
```
PHP's built-in timing-safe comparison. Never use `==` or `===` to compare password hashes.

**`Security::isStrongPassword($password)` — Validation**
```php
'/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/'
```
Regex enforces: min 8 chars, at least 1 uppercase, 1 digit, 1 special character.

**`Security::uuid()` — File Naming**
```php
$data = random_bytes(16); // cryptographically secure random
```
Generates a UUID v4 like `550e8400-e29b-41d4-a716-446655440000`.
Used to rename uploaded images — prevents filename collisions and stops users
from guessing uploaded file paths.

**`Security::imageUrl($image)` — Smart Image Resolution (NEW FIX)**
```php
if (filter_var($image, FILTER_VALIDATE_URL)) {
    return $image;              // already https:// — use directly
}
return UPLOAD_URL . '/' . $image;  // local file — prepend base URL
```
This is the fix for the image display issue. The database column can now hold
EITHER an external URL (Unsplash, Pexels, etc.) OR a local filename. All image
rendering code now calls this instead of blindly prepending UPLOAD_URL.

---

### 📄 `core/Helpers/Session.php` — Session Manager

Manages everything related to PHP sessions — starting them securely, checking
auth state, timeout, and flash messages.

**`Session::start()` — Secure Session Initialization**
Called on every page. Sets cookie flags:
- `httponly: true` → JavaScript cannot read session cookie (blocks XSS cookie theft)
- `samesite: Lax`  → Blocks cross-site request forgery via cookie
- `secure: false`  → Set to `true` on HTTPS (production)

Also calls `checkTimeout()` — if the session has been idle for >1 hour, it
destroys it automatically.

**`Session::flash($type, $message)` — Flash Messages**
```php
$_SESSION['_flash']['success'][] = 'Product added!';
```
Stores a message that survives ONE redirect. After the redirect, `getFlash()`
retrieves and DELETES them. They're passed to JavaScript as `window.__AURA_FLASH__`
and displayed as toast notifications.

**`Session::requireAuth()` — Route Guard**
```php
if (!self::isLoggedIn()) {
    header('Location: ' . APP_URL . '/login.php');
    exit;
}
```
Every user-only page calls this at the top. If not logged in, immediate redirect.
`exit` after the header is critical — without it, PHP continues executing the page.

**`Session::requireAdmin()` — Admin Guard**
Calls `requireAuth()` first (must be logged in), then additionally checks if
`user_role === 'admin'`. Double layer.

**`session_regenerate_id(true)` on login:**
After successful login, the session ID is changed. This prevents Session Fixation
attacks where an attacker pre-sets a session ID before the user logs in.

---

### 📄 `core/Helpers/Validator.php` — Input Validation Chain

Chainable validation rules. The same validator handles registration, checkout,
contact form, profile edit — all in one consistent API.

**Usage pattern:**
```php
$v = (new Validator())->load($_POST)
    ->required('email', 'Email')
    ->email('email')
    ->min('password', 8)
    ->strongPassword('password')
    ->matches('confirm_password', 'password', 'Passwords');

if ($v->fails()) {
    $errors = $v->errorList(); // ['Email is required', 'Passwords do not match']
}
```

**Why server-side validation even with client-side?**
Browser validation (HTML5 `required`, `type="email"`) can be bypassed by
disabling JavaScript or using tools like curl/Postman. Server-side is the only
validation that counts for security.

**`matches($field, $other)` — Password Confirm:**
Compares `$_POST['password']` with `$_POST['confirm_password']` as strings.
If they differ, adds an error. Used in both register.php and profile.php.

---

## ═══════════════════════════════════════════════════════
## PART 4: CORE — Model Classes
## ═══════════════════════════════════════════════════════

---

### 📄 `core/Classes/User.php` — User Model

All database operations for the `users` table. Every method uses PDO prepared
statements — parameters are ALWAYS bound separately from the SQL string, making
SQL injection structurally impossible.

**Key methods:**

**`findByEmail($email)`** — Called during login:
```php
$stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
$stmt->execute([':email' => $email]);
```
`:email` is a placeholder. PDO replaces it safely — even if someone enters
`' OR 1=1 --` as their email, it's treated as a literal string, not SQL.

**`create($name, $email, $password)`** — Registration:
Hashes the password via `Security::hashPassword()` BEFORE storing. Raw passwords
NEVER touch the database.

**`updateProfile($id, $data)`** — Profile Edit:
Only updates: name, phone, address, city, country. Email is deliberately excluded
(users can't change email — prevents account takeover via email change).

**`updatePassword($id, $newPassword)`** — Password Change:
Re-hashes with bcrypt before storing. The old hash is overwritten.

**`setRole($id, $role)`** — Admin Promote/Demote:
Admin-only. Takes `'admin'` or `'user'`. The admin dashboard prevents an admin
from demoting themselves (checked by comparing `$uid !== $currentAdmin`).

**`countNewLast30Days()`** — Analytics:
```sql
WHERE created_at >= NOW() - INTERVAL 30 DAY
```
Used on the admin dashboard KPI card. MySQL date arithmetic — no PHP date juggling.

---

### 📄 `core/Classes/Product.php` — Product Model

The largest model. Handles the catalogue, filtering, categories, reviews, and wishlist.

**`getFiltered($page, $perPage, $category, $search, $sort)`** — Shop Query Engine:
Builds a dynamic SQL query:
1. Starts with `WHERE 1=1` (always true — allows appending ANDs safely)
2. If `$category` given → adds `AND c.slug = :cat`
3. If `$search` given → adds `AND (p.name LIKE :s OR p.description LIKE :s)`
4. Maps sort option to ORDER BY clause

Also runs a COUNT query first for pagination math:
```php
$total = $cStmt->fetchColumn();
$pages = (int) ceil($total / $perPage);
```

**`LEFT JOIN product_ratings`** — Gets average rating in same query:
```sql
COALESCE(AVG(r.rating), 0) AS avg_rating,
COUNT(r.id) AS review_count
```
`COALESCE(AVG(...), 0)` — if no reviews, returns 0 instead of NULL.
`GROUP BY p.id` — needed because of the aggregate functions (AVG, COUNT).

**`upsertReview($productId, $userId, $rating, $review)`** — Review System:
```sql
INSERT INTO product_ratings ...
ON DUPLICATE KEY UPDATE rating=VALUES(rating), review=VALUES(review)
```
`ON DUPLICATE KEY UPDATE` = "if this user_id + product_id combination already
exists (UNIQUE constraint), UPDATE instead of INSERT." One query handles both
first-time reviews and review edits.

**`toggleWishlist($userId, $productId)`** — Wishlist Toggle:
1. SELECT to check if row exists
2. If exists → DELETE (remove from wishlist), return 'removed'
3. If not → INSERT, return 'added'
The JavaScript uses the returned action to update the heart icon and toast message.

**`makeSlug($name)`** — URL Slug Generator:
```php
$slug = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $name));
```
Converts "Obsidian Slim Blazer" → "obsidian-slim-blazer". Then checks if slug
already exists in DB and appends `-1`, `-2` etc. if needed (uniqueness guarantee).

---

### 📄 `core/Classes/Cart.php` — Cart Model

Database-backed cart (not cookie/session-based). Persists across browsers and
sessions. Tied to `user_id`.

**`addItem($userId, $productId, $qty)`** — Add to Cart:
```sql
INSERT INTO cart (user_id, product_id, quantity)
VALUES (:uid, :pid, :qty)
ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
```
The UNIQUE constraint on `(user_id, product_id)` means if you add the same
product twice, MySQL INCREMENTS quantity rather than creating a duplicate row.

**`getItems($userId)`** — Load Cart with Live Prices:
```sql
COALESCE(p.sale_price, p.price) AS unit_price,
COALESCE(p.sale_price, p.price) * c.quantity AS line_total
```
`COALESCE` returns the FIRST non-null value. If `sale_price` is NULL, it falls
back to `price`. Line total is calculated in SQL — no PHP arithmetic loop needed.

**`getTotals($userId)`** — Checkout Math:
```php
$subtotal = array_sum(array_column($items, 'line_total'));
$tax      = round($subtotal * TAX_RATE, 2);
$shipping = $subtotal > 0 ? SHIPPING_FLAT : 0.00;
$grand    = round($subtotal + $tax + $shipping, 2);
```
Tax rate and shipping fee come from `config.php` constants — change them once,
they update everywhere.

**`updateQuantity($cartId, $userId, $qty)`** — Safe Update:
Note it checks BOTH `id` AND `user_id`:
```sql
WHERE id=:id AND user_id=:uid
```
Without the `user_id` check, User A could manipulate User B's cart by guessing
cart row IDs. The double check prevents cross-user cart tampering.

---

### 📄 `core/Classes/Order.php` — Order Model (ACID Transactions)

The most critical class — handles money. Uses database transactions to guarantee
data integrity.

**`placeOrder($userId, $cartItems, $totals, $shipping)`** — Transaction Flow:

```php
$this->db->beginTransaction();

    // Step 1: Insert master order record → get $orderId
    // Step 2: For each cart item:
    //   a. SELECT stock ... FOR UPDATE  (locks the row — prevents race conditions)
    //   b. Check stock >= quantity      (throw exception if insufficient)
    //   c. INSERT order_item            (MySQL TRIGGER auto-decrements stock)

$this->db->commit();
```

**Why `FOR UPDATE`?**
If two users buy the last item simultaneously, both PHP threads read "stock = 1"
and both think they can proceed. `FOR UPDATE` locks the row — the second thread
waits until the first commits or rolls back, then reads the updated (0) stock and
correctly fails.

**`$this->db->rollBack()` on exception:**
If ANY step fails (stock insufficient, DB error, anything), the ENTIRE transaction
is rolled back. No partial order is created. No half-decremented stock. All or nothing.

**`getAllOrders($page, $perPage, $status)`** — Admin Filtering:
Dynamic WHERE clause:
```php
if ($status !== '') {
    $where[] = 'o.status = :status';
    $params[':status'] = $status;
}
```
Admin can filter by Pending / Processing / Shipped / Delivered / Cancelled.

**`monthlyRevenue()`** — Chart Data:
```sql
DATE_FORMAT(created_at, '%b %Y') AS month,
SUM(total_amount) AS revenue
WHERE created_at >= NOW() - INTERVAL 6 MONTH
GROUP BY DATE_FORMAT(created_at, '%Y-%m')
```
Returns `[{month: 'Jan 2025', revenue: 4823.00}, ...]` — fed directly into Chart.js.

---

## ═══════════════════════════════════════════════════════
## PART 5: VIEWS — Partials (Reusable HTML Fragments)
## ═══════════════════════════════════════════════════════

---

### 📄 `views/partials/head.php` — HTML Head + Mini-Cart + Toast Container

**Loaded at the top of every page.** Does several jobs:

1. **Sets up PHP session data** — retrieves flash messages, CSRF token, cart count
2. **Outputs `<head>`** — fonts, AOS CSS, main.css
3. **Injects JS globals** — passes PHP data to JavaScript:
   ```html
   <script>
     window.__AURA_URL__   = '<?= APP_URL ?>';
     window.__AURA_CSRF__  = '<?= $csrf ?>';
     window.__AURA_FLASH__ = <?= $flashJson ?>;
   </script>
   ```
   This is the clean way to pass server-side data to client-side JS without
   inline JS scattered throughout HTML.
4. **Renders the page loader** — the AURA animated loader div
5. **Renders the mini-cart drawer** — always present in DOM, shown/hidden by JS
6. **Renders the toast container** — JS appends toasts here

**Flash → Toast pipeline:**
PHP `Session::flash('success', 'Order placed!')` → stored in `$_SESSION`
→ `head.php` reads and JSON-encodes to `window.__AURA_FLASH__`
→ `main.js` `Toast.init()` reads the array and shows each as a toast notification.

---

### 📄 `views/partials/navbar.php` — Navigation Bar

Renders the glassmorphism sticky navbar. Uses `$currentUri` to add `class="active"`
to the current page's nav link (underline animation).

**Glassmorphism:** Achieved entirely in CSS:
```css
backdrop-filter: blur(16px) saturate(1.5);
background: rgba(250,250,248,0.88);
```
The background is 88% opaque white — you can see through it slightly. The `blur`
blurs whatever content is behind the navbar as you scroll past it.

**Conditional rendering:**
```php
if ($isLoggedIn) {
    // Show: Account icon, Logout button
} else {
    // Show: Login button, Sign Up button
}
```
Admin users see a link to `/admin/dashboard.php`, regular users see `/user/dashboard.php`.

**Cart badge:** `nav-badge` shows the cart item count from `Session::get('cart_count')`.
This is cached in the session on login and updated by AJAX calls — no DB query
per page load just for the badge number.

---

### 📄 `views/partials/footer.php` — Footer + Script Loading

Renders the full footer grid (brand, shop links, company links, newsletter).

**Newsletter form:** Sends to `/api/newsletter.php` via AJAX (in main.js) without
page reload. Shows toast on success/error.

**Script loading order:**
```html
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>  <!-- AOS library -->
<script src="<?= APP_URL ?>/assets/js/main.js"></script>          <!-- Our app JS -->
```
Scripts are at the bottom of `<body>` — page renders first, then JS loads.
AOS must load before main.js because main.js calls `AOS.init()`.

---

### 📄 `views/admin/sidebar.php` — Admin Sidebar Navigation

Renders the left sidebar in the admin layout. Uses `basename($_SERVER['PHP_SELF'])`
to detect the current admin page and apply `class="active"` to highlight the
correct nav item with a gold left border.

---

## ═══════════════════════════════════════════════════════
## PART 6: PUBLIC — Main Pages
## ═══════════════════════════════════════════════════════

---

### 📄 `public/index.php` — Homepage

**Sections rendered:**

1. **Hero Section** — Full-viewport parallax. Three layers:
   - `.hero__bg` — Static dark gradient background
   - `.hero__parallax` — Gold radial gradient, moves at 35% scroll speed (JS)
   - `.hero__content` — Text + CTA buttons (z-index above both layers)

2. **Category Strip** — Dynamic grid from `$productModel->getCategories()`.
   Each card links to `/shop.php?cat={slug}`. Emoji icons mapped per slug.

3. **Featured Products Grid** — `$productModel->getFeatured(8)` — 8 products
   with `featured = 1`. Renders product cards with:
   - Hover zoom on image (CSS `transform: scale(1.07)`)
   - Wishlist heart button (AJAX)
   - "Add to Cart" button (AJAX)
   - Star rating display (`renderStars()` function)
   - Sale badge, low stock badge

4. **Marquee Banner** — Pure CSS animation scrolling product benefits.

5. **Why AURA Grid** — 4 brand pillar cards with hover effects.

6. **CTA Banner** — Dark gradient section. Shows "Shop Now" for logged-in users,
   "Create Account / Sign In" for guests.

**`renderStars($avg)`** — Star Rating Helper:
```php
$full  = floor($avg);      // e.g., avg=3.7 → 3 full stars
$half  = ($avg - $full) >= 0.5 ? 1 : 0;  // → 1 half star
$empty = 5 - $full - $half;              // → 1 empty star
```
Returns Unicode star characters: ★★★✦☆

---

### 📄 `public/shop.php` — Shop + Single Product View

**Dual-mode page:**
- If `?product=slug` → single product detail view
- Otherwise → product grid listing

**Listing Mode:**
1. Reads GET params: `page`, `cat` (category slug), `q` (search), `sort`
2. Calls `$productModel->getFiltered(...)` which runs the dynamic SQL query
3. Renders filter pills (AOS animated, AJAX-less — clicking reloads page with URL params)
4. Renders products grid with skeleton loader placeholders
5. Renders pagination — builds URLs with `http_build_query()` preserving all existing filters

**Single Product Mode:**
1. Calls `$productModel->getBySlug($slug)` — 404-level redirect if not found
2. Renders sticky product image (CSS `position: sticky`)
3. Shows live price (sale vs. regular with `COALESCE`)
4. Stock indicator colored by threshold (green/orange/red)
5. Wishlist button with pre-loaded state (`isWishlisted()`)
6. Review form (only if logged in) — POST to same page, redirect on success
7. Review list with dates and star ratings

**Review POST Handler (on same page):**
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    Session::requireAuth();
    if (!Security::verifyCsrf()) { die('CSRF check failed.'); }
    $productModel->upsertReview($pid, $userId, $rating, $review);
    // redirect back to same product page (Post-Redirect-Get pattern)
}
```
The redirect after POST prevents the "resubmit form?" browser prompt on refresh.

---

### 📄 `public/login.php` — Authentication

**Login Flow:**
1. Check if already logged in → redirect immediately
2. If POST: verify CSRF → validate fields → `User::findByEmail()` → `Security::verifyPassword()`
3. On success:
   - `session_regenerate_id(true)` — NEW session ID (prevents session fixation)
   - Store: `user_id`, `user_name`, `user_role`, `user_email` in session
   - Pre-cache `cart_count` (DB query) so navbar badge shows immediately
   - Redirect to `?redirect=` URL if provided, otherwise role-based redirect

**Split-screen layout:**
- Left panel: Dark luxury background with decorative circles and a fashion quote
- Right panel: The actual login form
- On mobile: Left panel hidden (`display: none`), form takes full width

---

### 📄 `public/register.php` — User Registration

**Registration Flow:**
1. CSRF check
2. Validator chain: `required → email → strongPassword → matches`
3. `User::findByEmail()` — check for duplicate email before INSERT
4. `User::create()` — hashes password, inserts user
5. Immediately logs the new user in (session setup) → redirect to dashboard

**Why log in immediately after register?**
Better UX. User just gave you their info — don't make them log in again.

**Password strength enforced:**
`Security::isStrongPassword()` runs the regex on server side regardless of
what client-side JS says. The error message is shown inline above the form.

---

### 📄 `public/logout.php` — Session Destruction

Three lines of real work:
```php
Session::start();   // Need the session open to destroy it
Session::destroy(); // Clears $_SESSION, expires cookie, calls session_destroy()
header('Location: ' . APP_URL . '/login.php?logged_out=1');
exit;               // MUST exit after redirect header
```

`Session::destroy()` also calls `setcookie(session_name(), '', time()-42000)` —
this sends the browser an expired cookie, causing the browser to delete it immediately.

---

### 📄 `public/cart.php` — Shopping Cart Page

**Requires auth:** `Session::requireAuth()` at the top — guests can't have a cart.

**Renders:**
- Cart items list with product thumbnail, name, quantity controls, line total
- Quantity `+/-` buttons — these trigger AJAX calls to `/api/cart.php`
- Remove button — calls `removeCartItem()` inline JS function, then `CartPage.refresh()`
- Order summary sidebar: subtotal, tax, shipping, grand total — updated live by AJAX

**AJAX quantity update flow:**
User clicks `+` → `CartQty` module in main.js sends POST to `/api/cart.php` with
`{action: 'update', cart_id: X, quantity: Y}` → PHP updates DB → returns new totals
→ JS updates displayed totals without page reload.

---

### 📄 `public/checkout.php` — Order Placement

**Requires auth + non-empty cart.** If cart is empty, flash message + redirect to cart.

**Pre-fills shipping form** from user profile (`$userModel->findById($userId)`)
so returning customers don't have to type their address again.

**Order placement (POST):**
1. CSRF verify
2. Validator: name, email, address, city, country all required
3. `$orderModel->placeOrder($userId, $items, $totals, $shipping)` — ACID transaction
4. `$cartModel->clearCart($userId)` — empties DB cart after successful order
5. `Session::set('cart_count', 0)` — immediately updates nav badge
6. Redirect to `/user/orders.php?id={newOrderId}` — user sees their order confirmation

**Why ACID matters here:**
If the DB crashes after inserting the order but before clearing the cart, the
transaction rollback means the order NEVER got placed — so the cart still has
the items and the user can try again. Without transactions, you could have an
order placed AND a non-cleared cart (user charged but cart shows items).

---

### 📄 `public/about.php` — About Page

Static marketing page. Demonstrates AOS animations:
- `data-aos="fade-right"` on left panel content
- `data-aos="fade-left"` on right panel content
- `data-aos-delay="80"` staggers animation entry by 80ms per element

**Sticky image column:** `position: sticky; top: calc(var(--nav-h) + 20px)` keeps the
product image pinned while you scroll through the description below it.

---

### 📄 `public/contact.php` — Contact Form

Uses `Validator` for: name (required), email (valid format), message (required).
On success sets `$success = true` and renders a "Message Sent" confirmation card
instead of the form (same page, no redirect — success state is ephemeral).

In production: replace the comment with `mail()` or PHPMailer integration.

---

### 📄 `public/404.php` — Custom Error Page

Sets HTTP 404 status: `http_response_code(404)` — this is important for SEO.
Without this, Google treats your 404 page as a valid 200 page and indexes it.

Styled with the brand's design system — gradient "404" text, two CTA buttons.

---

## ═══════════════════════════════════════════════════════
## PART 7: PUBLIC/API — AJAX Endpoints
## ═══════════════════════════════════════════════════════

These files return JSON only (`Content-Type: application/json`). Never HTML.
Called by JavaScript using `fetch()`, not by users typing URLs.

---

### 📄 `public/api/cart.php` — Cart AJAX API

**GET requests (no CSRF needed — read-only):**
- `?action=mini` → returns all cart items + totals + count (for mini drawer)
- `?action=totals` → returns only totals (for cart page live update)

**POST requests (CSRF required):**
All POST bodies are JSON (`Content-Type: application/json`), read with:
```php
$body = json_decode(file_get_contents('php://input'), true);
```
This is how modern AJAX APIs work — not `$_POST`.

CSRF token sent in the JSON body (`_csrf` field), verified with `hash_equals()`.

**Actions:**
- `add` → `Cart::addItem()` with ON DUPLICATE KEY UPDATE (increment qty)
- `update` → `Cart::updateQuantity()` — if qty=0, calls `removeItem()`
- `remove` → `Cart::removeItem()` — verifies `user_id` ownership
- All responses update `Session::set('cart_count', $newCount)` so the nav badge stays current

---

### 📄 `public/api/wishlist.php` — Wishlist Toggle API

Minimal endpoint — one action only: toggle.
Returns `{success: true, action: 'added'}` or `{success: true, action: 'removed'}`.
JavaScript uses `data.action` to switch the heart from ♡ to ♥ and show the right toast.

---

### 📄 `public/api/newsletter.php` — Newsletter Subscription

Uses `filter_var($email, FILTER_VALIDATE_EMAIL)` — PHP's built-in email format checker.
```sql
INSERT IGNORE INTO newsletter (email) VALUES (:email)
```
`INSERT IGNORE` silently skips if email already exists (UNIQUE constraint) — no
error thrown, just returns 0 rows affected. We detect this and could show "already
subscribed" but currently just say "thank you" either way.

---

## ═══════════════════════════════════════════════════════
## PART 8: PUBLIC/USER — User Dashboard Pages
## ═══════════════════════════════════════════════════════

All user pages start with:
```php
Session::start();
Session::requireAuth();  // redirects to login if not logged in
```

---

### 📄 `public/user/dashboard.php` — User Dashboard

**Overview page showing:**
- Welcome banner with member-since date (dark gradient, gold accents)
- 3 stat cards: Total Orders, Cart Items, Wishlist Count
- Recent orders table (5 most recent, clickable rows)
- Wishlist preview (4 items, "Add to Cart" buttons)
- Quick navigation grid (Profile, Orders, Wishlist, Cart)

Data loaded: `Order::getUserOrders()`, `Product::getWishlist()`, `Cart::countItems()`, `User::findById()`

---

### 📄 `public/user/orders.php` — Order History + Tracking

**Dual-mode (like shop.php):**

**List mode:** Table of all orders with pagination. Clicking a row navigates to detail.

**Detail mode** (`?id=X`):
- Order number, date, status badge
- **Progress bar** — shows the 4 stages: Pending → Processing → Shipped → Delivered
  ```php
  $statusIdx = array_search($order['status'], $steps);
  // steps before current → 'done' class (gold filled circle)
  // current step → 'active' class (gold + glow ring)
  // future steps → default (grey)
  ```
- Items list with images and line totals
- Shipping address in sidebar
- Order totals breakdown

`getOrderDetail($orderId, $userId)` — the `$userId` parameter ensures users
can ONLY see their OWN orders. An attempt to view someone else's order ID
returns null → redirect with error.

---

### 📄 `public/user/profile.php` — Profile Editor

**Two forms on one page, distinguished by hidden field:**
```html
<input type="hidden" name="update_profile" value="1">
<!-- OR -->
<input type="hidden" name="change_password" value="1">
```
PHP checks `isset($_POST['update_profile'])` or `isset($_POST['change_password'])`.

**Profile update:** Name, phone, address, city, country. Email is DISABLED (read-only)
in the HTML and excluded from the UPDATE query — can't be changed.

**Password change:**
1. Verify current password with `Security::verifyPassword()`
2. Check new vs confirm match
3. Run `Security::isStrongPassword()` regex
4. Call `User::updatePassword()` which re-hashes with bcrypt

---

### 📄 `public/user/wishlist.php` — Wishlist Page

Full-page product grid using the same `.product-card` component as the shop page.
Heart button pre-loaded as `wishlisted` class (red heart).

Clicking the heart calls `/api/wishlist.php` → `Product::toggleWishlist()` →
returns 'removed' → JS can fade out the card or just change the icon.

---

## ═══════════════════════════════════════════════════════
## PART 9: PUBLIC/ADMIN — Admin Panel Pages
## ═══════════════════════════════════════════════════════

All admin pages start with:
```php
Session::start();
Session::requireAdmin(); // must be logged in AND role='admin'
```

---

### 📄 `public/admin/dashboard.php` — Analytics Dashboard

**6 KPI cards** from direct model calls:
- `Order::totalRevenue()` — SUM of all non-cancelled order totals
- `Order::count()` — total orders ever
- `User::count()` — total registered users
- `User::countNewLast30Days()` — growth metric
- `Product::count()` — catalogue size
- `Order::avgOrderValue()` — revenue per transaction

**Revenue Chart (Chart.js):**
`Order::monthlyRevenue()` returns 6 months of data. PHP encodes to JSON arrays:
```php
const labels = <?= json_encode(array_column($monthlyRev, 'month')) ?>;
const values = <?= json_encode(array_map(fn($r) => (float)$r['revenue'], $monthlyRev)) ?>;
```
Chart.js renders a filled line graph with gold color, responsive sizing.

**Low Stock Alert:**
`Product::getLowStock(5)` — products where `stock < 5`.
Displayed as compact list in sidebar-style card. Products with 0 stock have red
background tint, 1-4 have amber tint.

**Recent Orders table** — last 8 orders across all customers. "Manage" button
links to order detail in `admin/orders.php`.

---

### 📄 `public/admin/products.php` — Product CRUD + Image Upload

**The most complex admin page.** Handles three POST actions via `$_POST['action']`:

**`action=add` / `action=edit`:**
1. Validates form fields with `Validator`
2. If image uploaded:
   - Checks MIME type with `mime_content_type()` (not just file extension — prevents
     uploading a PHP file named `evil.jpg`)
   - Checks file size against `MAX_FILE_SIZE`
   - Generates UUID filename with `Security::uuid()`
   - Calls `resizeImage()` — GD library resize to 600×750px
3. Calls `Product::create()` or `Product::update()`

**`resizeImage($src, $dest, $mimeType, $maxW, $maxH)`** — GD Image Processing:
```php
$srcImg = imagecreatefromjpeg($src);    // load source
[$origW, $origH] = getimagesize($src); // get dimensions
$ratio = min($maxW/$origW, $maxH/$origH, 1); // scale factor (never upscale)
$dst = imagecreatetruecolor($newW, $newH);   // create blank canvas
imagecopyresampled($dst, $src, ...);          // high-quality resize
imagejpeg($dst, $dest, 85);                   // save at 85% quality
```
This converts any uploaded image to a consistent 600×750px at 85% JPEG quality
— reducing a 4MB phone photo to ~80KB for fast page loads.

**`action=delete`:**
```php
$productModel->delete($pid);
```
Note: MySQL `ON DELETE RESTRICT` on `order_items` means if a product has been
ordered, it CANNOT be deleted (referential integrity). The admin must handle this.

**Products table:** Stock column color-coded:
- 0 → red (danger)
- 1-4 → amber (warning)
- 5+ → green (success)

---

### 📄 `public/admin/orders.php` — Order Management

**Status Update Flow:**
```html
<select name="status">
  <option value="pending" selected>Pending</option>
  <option value="processing">Processing</option>
  ...
</select>
<button type="submit">Update</button>
```
POST → `$orderModel->updateStatus($oid, $status)` → validates status is in
allowed list → UPDATE query → redirect with flash message.

**Status filter bar:** Links with `?status=pending`, `?status=shipped`, etc.
PHP builds the WHERE clause dynamically based on the GET param.

**Order detail:** Same progress bar as user view. Admin additionally sees
full customer info and email. The status dropdown defaults to CURRENT status.

---

### 📄 `public/admin/users.php` — User Management

**Three actions:**
- `promote` → `User::setRole($uid, 'admin')`
- `demote` → `User::setRole($uid, 'user')`
- `delete` → `User::delete($uid)` — permanent, with JS `confirm()` dialog

**Self-protection:**
```php
if ($uid === $currentAdmin) {
    Session::flash('error', 'You cannot modify your own account from here.');
}
```
Prevents the logged-in admin from accidentally demoting or deleting themselves
(which would lock them out of the system).

**Avatar initials:**
```php
strtoupper(mb_substr($u['name'], 0, 1))
```
Shows first letter of name in a gold circle — no image needed. `mb_substr`
handles multi-byte UTF-8 characters correctly (Arabic names, etc.).

---

## ═══════════════════════════════════════════════════════
## PART 10: ASSETS — CSS & JavaScript
## ═══════════════════════════════════════════════════════

---

### 📄 `public/assets/css/main.css` — Design System (1000+ lines)

**CSS Custom Properties (Variables):**
```css
:root {
    --color-gold:  #B8976A;
    --color-bg:    #FAFAF8;
    --transition:  0.3s cubic-bezier(0.4, 0, 0.2, 1);
    --radius-lg:   16px;
    --shadow-xl:   0 24px 64px rgba(44,44,44,.18);
}
```
Change one variable → updates every element using it. Dark mode overrides in `[data-theme="dark"]`.

**Dark Mode:**
```css
[data-theme="dark"] {
    --color-bg:      #181816;
    --color-surface: #282825;
    --color-text:    #F0EDE8;
}
```
Every color in the system references a variable → dark mode works by swapping
16 variables. No duplicate CSS rules.

**Glassmorphism Navbar:**
```css
backdrop-filter: blur(16px) saturate(1.5);
background: rgba(250,250,248,0.88);
```
The blur effect only works when content scrolls behind the navbar.

**AOS Integration:**
AOS adds `data-aos="fade-up"` attributes in HTML. The library adds/removes
a `aos-animate` class when elements enter the viewport. CSS transitions handle the animation.

**Product Card Depth Illusion:**
```css
.product-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 24px 64px rgba(44,44,44,.18);
}
```
Card lifts 6px and casts a deeper shadow — creates the illusion of the card
floating off the page toward the viewer.

**Order Progress Bar:**
```css
.progress-step::before {
    content: '';
    position: absolute; top: 16px;
    left: -50%; right: 50%;  /* line connects to previous dot */
    height: 2px;
    background: var(--color-border);
}
.progress-step.done::before { background: var(--color-gold); }
```
The connecting line is a pseudo-element on each step. Gold for completed steps.

**Responsive breakpoints:**
- 1024px: Admin sidebar collapses (off-canvas)
- 768px: Navbar hamburger, auth page drops visual panel, 2-col forms go 1-col
- 480px: Product grid goes 2 columns, reduced padding

---

### 📄 `public/assets/js/main.js` — Application JavaScript (ES6+)

Organized into named module objects. Each has an `init()` method called at DOMContentLoaded.

**`Loader`** — Page loader:
Waits for `window` load event (all resources), adds `.hidden` class after 400ms.
CSS transition fades it out over 600ms, then `remove()` deletes it from DOM.

**`Theme`** — Dark/Light toggle:
Reads `localStorage.getItem('aura-theme')` on init → applies to `<html data-theme="">`.
Toggle flips the value and persists. CSS variables do the rest.

**`Navbar`** — Scroll + hamburger:
```js
window.addEventListener('scroll', () => {
    this.nav.classList.toggle('scrolled', window.scrollY > 20);
}, { passive: true });
```
`passive: true` tells the browser this scroll listener won't call `preventDefault()`
→ browser doesn't wait for JS before scrolling → smoother performance.

**`Parallax`** — Hero scroll effect:
```js
el.style.transform = `translateY(${y * 0.35}px)`;
```
As you scroll down `y` pixels, the parallax layer moves only `y * 0.35` pixels.
The hero background moves slower than the content → depth illusion.
`will-change: transform` on the element hints to browser to use GPU layer.

**`Toast`** — Notification system:
Creates a `<div class="toast">` element, appends to container, triggers CSS animation
via `requestAnimationFrame` double-call (needed for transition to fire on newly added elements).
Auto-removes after `duration` ms.

**`MiniCart`** — Slide-in drawer:
Uses `fetch()` to call `/api/cart.php?action=mini`. Renders items from JSON response.
Overlay click closes it. Prevents body scroll when open (`document.body.style.overflow = 'hidden'`).

**`Cart` (add to cart):**
Event delegation — one listener on `document` catches all `[data-add-cart]` clicks.
Disables button during request, shows spinner, re-enables after.
```js
document.addEventListener('click', async e => {
    const btn = e.target.closest('[data-add-cart]');
```
`closest()` searches up the DOM tree — works even if user clicks the button's text child.

**`CartQty` (quantity controls):**
Reads `data-dir="up"` / `data-dir="down"` from buttons. If quantity reaches 0,
the item is removed. After each change, calls `CartPage.refresh()` to update totals.

**`StarRating`** — Interactive star input:
Three events per star: `mouseenter` (highlight), `mouseleave` (reset to saved value),
`click` (save value). The hidden input stores the integer rating (1-5).

**`ShopFilters`** — Category pills + sort:
Clicking a pill builds a new URL with `URLSearchParams` and navigates.
```js
const url = new URL(window.location);
url.searchParams.set('cat', pill.dataset.cat);
window.location.href = url.toString();
```
This preserves any existing `?q=search` parameter while changing the category.

**`ImagePreview`** — Admin product form:
When an image file is selected, reads it with `FileReader.readAsDataURL()` and
sets the preview `<img>` src — instant preview without uploading.

**`AURA` global object:**
```js
const AURA = {
    url:  window.__AURA_URL__,
    csrf: window.__AURA_CSRF__,
    h(str) { /* DOM-based XSS escape */ }
}
```
Centralizes the PHP-injected values so all modules can access them.
`AURA.h()` uses DOM TextNode creation for XSS-safe JS string output.

---

## ═══════════════════════════════════════════════════════
## PART 11: SECURITY SUMMARY
## ═══════════════════════════════════════════════════════

| Attack Vector        | How AURA Prevents It                                                      |
|----------------------|---------------------------------------------------------------------------|
| **SQL Injection**    | PDO prepared statements everywhere. Parameters NEVER concatenated into SQL |
| **XSS**             | `Security::h()` on ALL output. `htmlspecialchars` converts `<` `>` `"` `'` |
| **CSRF**            | Every POST form has hidden CSRF field. Verified with `hash_equals()`      |
| **Session Fixation** | `session_regenerate_id(true)` on login changes session ID                 |
| **Session Hijacking**| HttpOnly cookie (no JS access), SameSite=Lax (blocks cross-site)         |
| **Session Timeout** | Auto-destroy after 1 hour idle via `$_SESSION['_last_activity']`         |
| **Brute Force**     | Bcrypt cost 12 (~250ms per hash) makes bulk password guessing impractical |
| **File Upload RCE** | MIME check + UUID rename + GD re-encode — a PHP file disguised as .jpg    |
|                     | gets re-encoded through GD, destroying any PHP code inside                |
| **IDOR**            | All user data queries include `user_id` filter (can't access others' data)|
| **Race Conditions** | `SELECT ... FOR UPDATE` in order transaction locks stock rows             |
| **Info Leakage**    | PDO exceptions caught; generic error to user, full error to error log     |
| **Directory Listing**| `Options -Indexes` in .htaccess                                          |
| **Privilege Escalation**| Double-check: `requireAuth()` + `requireAdmin()` on every admin page  |
| **Timing Attacks**  | `hash_equals()` for constant-time string comparison (CSRF, password)     |

---

## ═══════════════════════════════════════════════════════
## PART 12: IMAGE FIX EXPLAINED
## ═══════════════════════════════════════════════════════

**The Problem:**
The original code always did:
```php
$img = UPLOAD_URL . '/' . $p['image'];
// → "http://localhost/uploads/https://images.unsplash.com/..."  ← BROKEN
```

**The Fix (`Security::imageUrl()`):**
```php
public static function imageUrl(string $image): string
{
    if (filter_var($image, FILTER_VALIDATE_URL)) {
        return $image;  // Full URL → use directly
    }
    return UPLOAD_URL . '/' . $image;  // Filename → prepend base
}
```

**Where it's called** (every image tag in the project):
```php
// BEFORE (broken):
<img src="<?= UPLOAD_URL . '/' . Security::h($p['image']) ?>">

// AFTER (smart):
<img src="<?= Security::h(Security::imageUrl($p['image'])) ?>">
// Or the shorthand used in the fix:
<img src="<?= Security::imageUrl($p['image']) ?>">
```

**To fix your existing database:** Run `patch_images.sql` in phpMyAdmin.
It does `UPDATE products SET image = 'https://...' WHERE slug = '...'`
for all 8 products and inserts 3 new products with the remaining URLs.

---

*This document covers all 39 files, 100+ functions, and every major design
decision in the AURA E-Commerce Platform.*
