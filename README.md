# AURA — Luxury E-Commerce Platform
### Production-Ready PHP 8.2 · MySQL · Vanilla JS

---

## 📋 Table of Contents

1. [Project Overview](#overview)
2. [Tech Stack](#tech-stack)
3. [Folder Structure](#folder-structure)
4. [Database Schema](#database-schema)
5. [Installation Guide](#installation-guide)
6. [Configuration](#configuration)
7. [Features](#features)
8. [Security Implementation](#security)
9. [Admin Credentials](#admin-credentials)
10. [API Reference](#api-reference)

---

## 🌟 Overview

**AURA** is a full-stack luxury fashion e-commerce platform built with:
- **Pure OOP PHP 8.2** following strict MVC-like separation
- **PDO Singleton** database connection with prepared statements
- **Glassmorphism + AOS animations** premium UI
- **ACID-compliant order processing** with MySQL transactions
- **CSRF + XSS + SQL injection** protection throughout

---

## 🔧 Tech Stack

| Layer      | Technology                                   |
|------------|----------------------------------------------|
| Backend    | PHP 8.2+ (OOP, no frameworks)                |
| Database   | MySQL 8.0 (InnoDB, PDO, Prepared Statements) |
| Frontend   | HTML5, CSS3 Custom Properties, ES6+ JS       |
| Animations | AOS.js (Animate on Scroll)                   |
| Charts     | Chart.js (Admin analytics)                   |
| Server     | Apache via XAMPP (htdocs)                    |
| Images     | PHP GD Library (server-side resize)          |

---

## 📁 Folder Structure

```
aura-ecommerce/
├── config.php                    ← Global constants & DB config
├── setup.sql                     ← Full DB schema + seed data
├── README.md
│
├── core/                         ← Business Logic (outside public root ideally)
│   ├── Database/
│   │   └── Connection.php        ← PDO Singleton
│   ├── Classes/
│   │   ├── User.php              ← User CRUD & auth
│   │   ├── Product.php           ← Products, categories, wishlist, reviews
│   │   ├── Cart.php              ← Cart operations & totals
│   │   └── Order.php            ← ACID order placement, analytics
│   └── Helpers/
│       ├── Security.php          ← CSRF, XSS, hashing, UUID
│       ├── Session.php           ← Session handling, flash messages
│       └── Validator.php         ← Server-side validation rules
│
├── views/                        ← UI Templates
│   ├── partials/
│   │   ├── head.php              ← HTML <head>, loader, mini-cart
│   │   ├── navbar.php            ← Glassmorphism navigation bar
│   │   └── footer.php           ← Footer with newsletter
│   └── admin/
│       └── sidebar.php           ← Admin sidebar navigation
│
└── public/                       ← Web root (point Apache here)
    ├── .htaccess                 ← URL rewriting & security headers
    ├── index.php                 ← Homepage with parallax hero
    ├── shop.php                  ← Product listing + single product
    ├── cart.php                  ← Shopping cart
    ├── checkout.php              ← Order placement
    ├── login.php                 ← Authentication
    ├── register.php              ← User registration
    ├── logout.php                ← Session destroy
    ├── about.php                 ← About page
    ├── contact.php               ← Contact form
    ├── 404.php                   ← Custom 404 page
    │
    ├── api/                      ← AJAX endpoints (JSON)
    │   ├── cart.php              ← Add/update/remove cart items
    │   ├── wishlist.php          ← Toggle wishlist
    │   └── newsletter.php        ← Email subscription
    │
    ├── user/                     ← User dashboard pages
    │   ├── dashboard.php
    │   ├── orders.php
    │   ├── profile.php
    │   └── wishlist.php
    │
    ├── admin/                    ← Admin panel (admin-only)
    │   ├── dashboard.php         ← Analytics + KPIs + chart
    │   ├── products.php          ← Product CRUD + image upload
    │   ├── orders.php            ← Order management + status updates
    │   └── users.php             ← User management + role control
    │
    └── assets/
        ├── css/
        │   └── main.css          ← Full design system (1000+ lines)
        ├── js/
        │   └── main.js           ← All interactions (AOS, cart, toasts)
        └── images/
            └── uploads/          ← Product images (GD-resized)
```

---

## 🗄️ Database Schema

### Entity Relationship

```
users ──┬──< cart ──> products ──< categories
        │
        ├──< orders ──< order_items ──> products
        │
        ├──< wishlist ──> products
        │
        └──< product_ratings ──> products
```

### Tables

| Table            | Purpose                              | Key Columns                         |
|------------------|--------------------------------------|-------------------------------------|
| users            | Authentication & profiles            | id, email (unique), password, role  |
| categories       | Product categories                   | id, name, slug (unique)             |
| products         | Product catalogue                    | id, category_id (FK), price, stock  |
| product_ratings  | Reviews (1 per user per product)     | UNIQUE(product_id, user_id)         |
| wishlist         | Saved items                          | UNIQUE(user_id, product_id)         |
| cart             | Shopping cart (DB-backed)            | UNIQUE(user_id, product_id)         |
| orders           | Order master record                  | order_number (unique), status ENUM  |
| order_items      | Line items per order                 | order_id (CASCADE), product_id      |
| newsletter       | Email subscriptions                  | email (unique)                      |

### MySQL Objects

- **View:** `trending_products` — top 10 by total units sold
- **Trigger:** `trg_decrement_stock` — auto-decrements stock AFTER INSERT on order_items (ACID)
- **Constraints:** `ON DELETE RESTRICT` on products linked to orders; `ON DELETE CASCADE` for cart/wishlist

---

## 🚀 Installation Guide

### Prerequisites

- **XAMPP** 8.2+ (or any Apache + PHP 8.2 + MySQL stack)
- PHP extensions: `pdo_mysql`, `gd`, `mbstring`

### Step 1 — Clone / Copy Project

```bash
# Copy the aura-ecommerce folder into XAMPP's htdocs:
cp -r aura-ecommerce/ /path/to/xampp/htdocs/
```

### Step 2 — Create the Database

1. Open **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Click **Import** tab
3. Select `aura-ecommerce/setup.sql`
4. Click **Go**

Or via MySQL CLI:

```bash
mysql -u root -p < /path/to/aura-ecommerce/setup.sql
```

### Step 3 — Configure the App

Edit `aura-ecommerce/config.php`:

```php
define('APP_URL',  'http://localhost/aura-ecommerce/public');
define('DB_HOST',  'localhost');
define('DB_NAME',  'aura_ecommerce');
define('DB_USER',  'root');
define('DB_PASS',  '');          // ← your MySQL password
```

### Step 4 — Create Upload Directory

```bash
mkdir -p aura-ecommerce/public/assets/images/uploads
chmod 755 aura-ecommerce/public/assets/images/uploads
```

### Step 5 — Enable Apache mod_rewrite

In `httpd.conf` (XAMPP), ensure:
```apache
AllowOverride All
```
is set for the htdocs directory, and `mod_rewrite` is enabled.

### Step 6 — Access the Site

| URL                                                   | Description         |
|-------------------------------------------------------|---------------------|
| `http://localhost/aura-ecommerce/public/`             | Homepage            |
| `http://localhost/aura-ecommerce/public/shop.php`     | Shop                |
| `http://localhost/aura-ecommerce/public/login.php`    | Login               |
| `http://localhost/aura-ecommerce/public/admin/dashboard.php` | Admin Panel  |

---

## 🔐 Admin Credentials

```
Email:    admin@aura.com
Password: Admin@1234
```

> ⚠️ **Change the admin password immediately after first login!**

---

## ⚙️ Configuration Reference

| Constant          | Default            | Description                     |
|-------------------|--------------------|---------------------------------|
| `APP_ENV`         | `development`      | Set to `production` on live     |
| `APP_URL`         | `http://localhost/…` | Full public URL               |
| `TAX_RATE`        | `0.08`             | 8% tax on orders                |
| `SHIPPING_FLAT`   | `9.99`             | Flat shipping fee               |
| `SESSION_TIMEOUT` | `3600`             | 1 hour session expiry           |
| `MAX_FILE_SIZE`   | `5242880`          | 5 MB max image upload           |
| `IMG_THUMB_W/H`   | `600 × 750`        | GD resize dimensions            |
| `PRODUCTS_PER_PAGE` | `12`             | Shop pagination                 |

---

## ✨ Features

### 🛍 Shopping
- ✅ Product catalogue with category filtering
- ✅ Search functionality
- ✅ Sort by: newest, price asc/desc, name
- ✅ Paginated results (12 per page)
- ✅ Product detail page with reviews & ratings
- ✅ Add to cart (AJAX — no page refresh)
- ✅ Mini-cart slide-in drawer
- ✅ Wishlist toggle (AJAX)
- ✅ Cart quantity controls (AJAX)
- ✅ Checkout with shipping form
- ✅ ACID-safe order placement

### 👤 User Dashboard
- ✅ Order history & tracking
- ✅ Order detail with progress bar
- ✅ Profile editor (name, address, phone)
- ✅ Password change
- ✅ Wishlist management
- ✅ Cart view

### 👑 Admin Panel
- ✅ Revenue analytics + Chart.js line graph
- ✅ KPI cards (revenue, orders, users, avg order value)
- ✅ Low stock alerts (products < 5 units highlighted red/yellow)
- ✅ Product CRUD with GD image resize (600×750px)
- ✅ Order management with status updates
- ✅ User management (promote/demote admin, delete)
- ✅ Paginated tables throughout

### 🎨 Frontend
- ✅ Full dark/light theme toggle (persisted via localStorage)
- ✅ AOS scroll animations (fade, slide, zoom)
- ✅ Parallax hero effect
- ✅ Glassmorphism navbar
- ✅ Toast notification system
- ✅ Page loader animation
- ✅ Skeleton loaders
- ✅ Fully responsive (mobile-first)
- ✅ Hamburger menu with smooth animation
- ✅ Star rating widget
- ✅ Newsletter subscription
- ✅ Animated marquee banner

---

## 🔒 Security Implementation

| Threat              | Protection                                                     |
|---------------------|----------------------------------------------------------------|
| SQL Injection       | PDO prepared statements with bound parameters                  |
| XSS                 | `Security::h()` — `htmlspecialchars` on all output           |
| CSRF                | `Security::csrfField()` + `verifyCsrf()` on all POST forms   |
| Session Hijacking   | `session_regenerate_id(true)` on login                        |
| Session Fixation    | Custom session name, HttpOnly + SameSite cookie flags         |
| Session Timeout     | Automatic expiry after 1 hour of inactivity                   |
| Password Storage    | `password_hash()` with Bcrypt cost 12                         |
| Authorization       | `Session::requireAuth()` + `Session::requireAdmin()` guards  |
| Directory Listing   | `Options -Indexes` in .htaccess                               |
| File Upload         | MIME-type validation, UUID rename, GD resize before saving    |
| Information Leakage | PDO exceptions caught; only generic errors shown to users     |

---

## 📡 API Reference

### `POST /api/cart.php`

| Action   | Body Parameters                    | Response                        |
|----------|------------------------------------|---------------------------------|
| `add`    | `product_id`, `quantity`, `_csrf` | `{success, count}`              |
| `update` | `cart_id`, `quantity`, `_csrf`    | `{success, count, totals}`      |
| `remove` | `cart_id`, `_csrf`                | `{success, count, totals}`      |

### `GET /api/cart.php`

| Action   | Response                                   |
|----------|--------------------------------------------|
| `mini`   | `{items, totals, count}`                   |
| `totals` | `{totals, count}`                          |

### `POST /api/wishlist.php`

| Body                        | Response               |
|-----------------------------|------------------------|
| `product_id`, `_csrf`       | `{success, action}`    |

### `POST /api/newsletter.php`

| Body           | Response               |
|----------------|------------------------|
| `email`, `_csrf` | `{success, message}` |

---

## 🎨 Design System

| Token            | Value            |
|------------------|------------------|
| `--color-gold`   | `#B8976A`        |
| `--color-bg`     | `#FAFAF8`        |
| `--color-text`   | `#2C2C2C`        |
| Font (Headers)   | Playfair Display |
| Font (Body)      | Montserrat       |
| Border radius    | 4px → 24px scale |

---

*Built with care by the AURA Engineering Team.*
