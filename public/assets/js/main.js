/**
 * AURA E-Commerce — Main JavaScript
 * ES6+ | Vanilla JS | No dependencies (AOS loaded separately)
 */

'use strict';

/* ── Page Loader ─────────────────────────────────────────── */
const Loader = {
    el: null,
    init() {
        this.el = document.getElementById('page-loader');
        if (!this.el) return;
        window.addEventListener('load', () => {
            setTimeout(() => {
                this.el.classList.add('hidden');
                setTimeout(() => this.el.remove(), 600);
            }, 400);
        });
    }
};

/* ── Theme Toggle ────────────────────────────────────────── */
const Theme = {
    key: 'aura-theme',
    init() {
        const stored = localStorage.getItem(this.key) || 'light';
        document.documentElement.setAttribute('data-theme', stored);
        const btn = document.getElementById('theme-toggle');
        if (!btn) return;
        btn.addEventListener('click', () => this.toggle());
    },
    toggle() {
        const current = document.documentElement.getAttribute('data-theme');
        const next    = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem(this.key, next);
    }
};

/* ── Navbar ──────────────────────────────────────────────── */
const Navbar = {
    nav: null,
    hamburger: null,
    mobileMenu: null,
    init() {
        this.nav        = document.querySelector('.navbar');
        this.hamburger  = document.getElementById('hamburger');
        this.mobileMenu = document.getElementById('mobile-menu');
        if (!this.nav) return;

        window.addEventListener('scroll', () => {
            this.nav.classList.toggle('scrolled', window.scrollY > 20);
        }, { passive: true });

        this.hamburger?.addEventListener('click', () => this.toggleMenu());
    },
    toggleMenu() {
        const open = this.hamburger.classList.toggle('open');
        this.mobileMenu.classList.toggle('open', open);
        document.body.style.overflow = open ? 'hidden' : '';
    }
};

/* ── Hero Parallax ───────────────────────────────────────── */
const Parallax = {
    layers: [],
    init() {
        this.layers = document.querySelectorAll('.hero__parallax');
        if (!this.layers.length) return;
        window.addEventListener('scroll', () => this.update(), { passive: true });
    },
    update() {
        const y = window.scrollY;
        this.layers.forEach(el => {
            el.style.transform = `translateY(${y * 0.35}px)`;
        });
    }
};

/* ── Toast Notifications ─────────────────────────────────── */
const Toast = {
    container: null,
    icons: { success: '✓', error: '✕', warning: '⚠', info: 'ℹ' },

    init() {
        this.container = document.querySelector('.toast-container');
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        }
        // Show PHP flash messages if present
        const flashes = window.__AURA_FLASH__ || [];
        flashes.forEach(f => this.show(f.type, f.message));
    },

    show(type = 'info', message = '', duration = 4000) {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <span class="toast-icon">${this.icons[type] || 'ℹ'}</span>
            <span>${message}</span>
            <button class="toast-close" aria-label="Close">✕</button>`;

        toast.querySelector('.toast-close').addEventListener('click', () => this.dismiss(toast));
        this.container.appendChild(toast);

        // Trigger animation
        requestAnimationFrame(() => {
            requestAnimationFrame(() => toast.classList.add('show'));
        });

        if (duration > 0) {
            setTimeout(() => this.dismiss(toast), duration);
        }
    },

    dismiss(toast) {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 350);
    }
};

/* ── Mini Cart Drawer ────────────────────────────────────── */
const MiniCart = {
    overlay: null,
    drawer: null,
    countEl: null,

    init() {
        this.overlay  = document.getElementById('cart-overlay');
        this.drawer   = document.getElementById('mini-cart');
        this.countEl  = document.querySelectorAll('.nav-cart-count');

        document.getElementById('cart-toggle')?.addEventListener('click', () => this.open());
        document.getElementById('mini-cart-close')?.addEventListener('click', () => this.close());
        this.overlay?.addEventListener('click', () => this.close());
    },

    open() {
        this.overlay?.classList.add('open');
        this.drawer?.classList.add('open');
        document.body.style.overflow = 'hidden';
        this.load();
    },

    close() {
        this.overlay?.classList.remove('open');
        this.drawer?.classList.remove('open');
        document.body.style.overflow = '';
    },

    async load() {
        const itemsEl = document.getElementById('mini-cart-items');
        const totalEl = document.getElementById('mini-cart-total');
        if (!itemsEl) return;

        itemsEl.innerHTML = `<div class="skeleton" style="height:200px;border-radius:12px;"></div>`;

        try {
            const res  = await fetch(AURA.url + '/api/cart.php?action=mini');
            const data = await res.json();

            if (!data.items?.length) {
                itemsEl.innerHTML = `
                    <div class="text-center" style="padding:40px 0;color:var(--color-text-2)">
                        <div style="font-size:2rem;margin-bottom:12px;">🛍</div>
                        <p>Your cart is empty.</p>
                    </div>`;
                if (totalEl) totalEl.textContent = '$0.00';
                return;
            }

            itemsEl.innerHTML = data.items.map(item => `
                <div class="mini-cart__item">
                    <img src="${AURA.url}/assets/images/uploads/${AURA.h(item.image)}"
                         alt="${AURA.h(item.name)}"
                         onerror="this.src='${AURA.url}/assets/images/placeholder.jpg'">
                    <div class="mini-cart__info">
                        <h4>${AURA.h(item.name)}</h4>
                        <p style="font-size:.8rem;color:var(--color-text-2);margin-bottom:8px">
                            Qty: ${item.quantity}
                        </p>
                        <strong>$${parseFloat(item.line_total).toFixed(2)}</strong>
                    </div>
                </div>`).join('');

            if (totalEl) totalEl.textContent = '$' + parseFloat(data.totals.subtotal).toFixed(2);
            this.updateCount(data.count);
        } catch (e) {
            itemsEl.innerHTML = `<p class="text-muted">Could not load cart.</p>`;
        }
    },

    updateCount(n) {
        this.countEl.forEach(el => {
            el.textContent = n;
            el.style.display = n > 0 ? 'flex' : 'none';
        });
    }
};

/* ── Add to Cart ─────────────────────────────────────────── */
const Cart = {
    init() {
        document.addEventListener('click', async e => {
            const btn = e.target.closest('[data-add-cart]');
            if (!btn) return;
            const pid = btn.dataset.addCart;
            const qty = parseInt(btn.closest('[data-qty]')?.dataset.qty || 1, 10);

            btn.disabled = true;
            const orig = btn.innerHTML;
            btn.innerHTML = '<span style="animation:spin 0.6s linear infinite;display:inline-block">⟳</span>';

            try {
                const res  = await fetch(AURA.url + '/api/cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'add', product_id: pid, quantity: qty, _csrf: AURA.csrf })
                });
                const data = await res.json();

                if (data.success) {
                    Toast.show('success', 'Added to cart!');
                    MiniCart.updateCount(data.count);
                } else {
                    Toast.show('error', data.message || 'Please log in to add items.');
                }
            } catch {
                Toast.show('error', 'Network error. Please try again.');
            } finally {
                btn.innerHTML = orig;
                btn.disabled  = false;
            }
        });
    }
};

/* ── Wishlist Toggle ─────────────────────────────────────── */
const Wishlist = {
    init() {
        document.addEventListener('click', async e => {
            const btn = e.target.closest('[data-wishlist]');
            if (!btn) return;
            const pid = btn.dataset.wishlist;

            try {
                const res  = await fetch(AURA.url + '/api/wishlist.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: pid, _csrf: AURA.csrf })
                });
                const data = await res.json();
                if (data.success) {
                    const added = data.action === 'added';
                    btn.classList.toggle('wishlisted', added);
                    btn.title = added ? 'Remove from wishlist' : 'Add to wishlist';
                    Toast.show(added ? 'success' : 'info',
                        added ? 'Added to wishlist' : 'Removed from wishlist');
                } else {
                    Toast.show('error', 'Please log in to use the wishlist.');
                }
            } catch {
                Toast.show('error', 'Network error.');
            }
        });
    }
};

/* ── Cart Quantity Controls ──────────────────────────────── */
const CartQty = {
    init() {
        document.addEventListener('click', async e => {
            const btn = e.target.closest('.qty-btn');
            if (!btn) return;
            const wrap   = btn.closest('[data-cart-item]');
            const cartId = wrap?.dataset.cartItem;
            const disp   = wrap?.querySelector('.qty-display');
            if (!cartId || !disp) return;

            let qty = parseInt(disp.textContent, 10);
            qty += btn.dataset.dir === 'up' ? 1 : -1;
            if (qty < 0) return;

            disp.textContent = qty;

            try {
                const res = await fetch(AURA.url + '/api/cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'update', cart_id: cartId, quantity: qty, _csrf: AURA.csrf })
                });
                const data = await res.json();
                if (data.success) {
                    if (qty === 0) {
                        wrap.closest('.cart-table__item')?.remove();
                    }
                    CartPage.refresh();
                } else {
                    Toast.show('error', data.message || 'Update failed.');
                }
            } catch {
                Toast.show('error', 'Network error.');
            }
        });
    }
};

/* ── Cart Page Totals Refresh ────────────────────────────── */
const CartPage = {
    async refresh() {
        const totalsEl = document.getElementById('cart-totals');
        if (!totalsEl) return;
        try {
            const res  = await fetch(AURA.url + '/api/cart.php?action=totals');
            const data = await res.json();
            if (data.totals) {
                document.getElementById('cart-subtotal').textContent = '$' + data.totals.subtotal.toFixed(2);
                document.getElementById('cart-tax').textContent      = '$' + data.totals.tax.toFixed(2);
                document.getElementById('cart-shipping').textContent  = '$' + data.totals.shipping.toFixed(2);
                document.getElementById('cart-grand').textContent     = '$' + data.totals.grand_total.toFixed(2);
            }
        } catch {}
    }
};

/* ── Product Rating Stars ────────────────────────────────── */
const StarRating = {
    init() {
        const groups = document.querySelectorAll('.star-input');
        groups.forEach(group => {
            const stars = group.querySelectorAll('.star-btn');
            const input = group.querySelector('input[type=hidden]');
            stars.forEach((star, i) => {
                star.addEventListener('mouseenter', () => this.highlight(stars, i));
                star.addEventListener('mouseleave', () => this.reset(stars, input));
                star.addEventListener('click', () => {
                    if (input) input.value = i + 1;
                    this.setSelected(stars, i);
                });
            });
        });
    },
    highlight(stars, idx) {
        stars.forEach((s, i) => s.classList.toggle('hovered', i <= idx));
    },
    reset(stars, input) {
        const val = parseInt(input?.value || 0, 10);
        stars.forEach((s, i) => {
            s.classList.remove('hovered');
            s.classList.toggle('selected', i < val);
        });
    },
    setSelected(stars, idx) {
        stars.forEach((s, i) => {
            s.classList.remove('hovered');
            s.classList.toggle('selected', i <= idx);
        });
    }
};

/* ── Newsletter ──────────────────────────────────────────── */
const Newsletter = {
    init() {
        const form = document.getElementById('newsletter-form');
        if (!form) return;
        form.addEventListener('submit', async e => {
            e.preventDefault();
            const email = form.querySelector('input[type=email]').value;
            try {
                const res  = await fetch(AURA.url + '/api/newsletter.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, _csrf: AURA.csrf })
                });
                const data = await res.json();
                Toast.show(data.success ? 'success' : 'error',
                    data.message || 'Subscription successful!');
                if (data.success) form.reset();
            } catch {
                Toast.show('error', 'Network error.');
            }
        });
    }
};

/* ── Shop Filters ────────────────────────────────────────── */
const ShopFilters = {
    init() {
        document.querySelectorAll('.filter-pill[data-cat]').forEach(pill => {
            pill.addEventListener('click', () => {
                const url = new URL(window.location);
                const cat = pill.dataset.cat;
                if (cat) url.searchParams.set('cat', cat);
                else     url.searchParams.delete('cat');
                url.searchParams.delete('page');
                window.location.href = url.toString();
            });
        });

        const sortEl = document.getElementById('sort-select');
        sortEl?.addEventListener('change', () => {
            const url = new URL(window.location);
            url.searchParams.set('sort', sortEl.value);
            window.location.href = url.toString();
        });
    }
};

/* ── Admin: Image Preview ────────────────────────────────── */
const ImagePreview = {
    init() {
        const input   = document.getElementById('product-image-input');
        const preview = document.getElementById('product-image-preview');
        if (!input || !preview) return;
        input.addEventListener('change', e => {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = ev => {
                preview.src = ev.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        });
    }
};

/* ── Utility: XSS-safe string escape ────────────────────── */
const AURA = {
    url:  window.__AURA_URL__  || '',
    csrf: window.__AURA_CSRF__ || '',
    h(str) {
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(String(str)));
        return d.innerHTML;
    }
};

/* ── CSS animation for spinner ───────────────────────────── */
const spinStyle = document.createElement('style');
spinStyle.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
document.head.appendChild(spinStyle);

/* ── Bootstrap All Modules ───────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    Loader.init();
    Theme.init();
    Navbar.init();
    Parallax.init();
    Toast.init();
    MiniCart.init();
    Cart.init();
    Wishlist.init();
    CartQty.init();
    StarRating.init();
    Newsletter.init();
    ShopFilters.init();
    ImagePreview.init();

    // AOS
    if (typeof AOS !== 'undefined') {
        AOS.init({ duration: 700, easing: 'ease-out-cubic', once: true, offset: 60 });
    }
});
