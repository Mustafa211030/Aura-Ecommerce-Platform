<?php
/**
 * Footer Partial
 */
use Core\Helpers\Security;
?>
<footer class="footer" role="contentinfo">
    <div class="container">
        <div class="footer__grid">

            <!-- Brand -->
            <div>
                <div class="footer__brand-name">AUR<span>A</span></div>
                <p class="footer__tagline">
                    Where craftsmanship meets contemporary elegance. Every piece tells a story of timeless luxury.
                </p>
                <div class="footer__social">
                    <a href="#" class="footer__social-btn" aria-label="Instagram">IG</a>
                    <a href="#" class="footer__social-btn" aria-label="Pinterest">PI</a>
                    <a href="#" class="footer__social-btn" aria-label="Facebook">FB</a>
                    <a href="#" class="footer__social-btn" aria-label="Twitter">TW</a>
                </div>
            </div>

            <!-- Shop -->
            <div>
                <h3 class="footer__col-title">Shop</h3>
                <div class="footer__links">
                    <a href="<?= APP_URL ?>/shop.php">All Products</a>
                    <a href="<?= APP_URL ?>/shop.php?cat=men">Men</a>
                    <a href="<?= APP_URL ?>/shop.php?cat=women">Women</a>
                    <a href="<?= APP_URL ?>/shop.php?cat=accessories">Accessories</a>
                    <a href="<?= APP_URL ?>/shop.php?cat=new-arrivals">New Arrivals</a>
                </div>
            </div>

            <!-- Company -->
            <div>
                <h3 class="footer__col-title">Company</h3>
                <div class="footer__links">
                    <a href="<?= APP_URL ?>/about.php">About AURA</a>
                    <a href="<?= APP_URL ?>/contact.php">Contact</a>
                    <a href="#">Careers</a>
                    <a href="#">Press</a>
                    <a href="#">Sustainability</a>
                </div>
            </div>

            <!-- Newsletter -->
            <div>
                <h3 class="footer__col-title">Newsletter</h3>
                <p style="font-size:.85rem;margin-bottom:16px;line-height:1.6">
                    Be the first to know about new collections and exclusive offers.
                </p>
                <form class="footer__newsletter-form" id="newsletter-form" novalidate>
                    <input type="email" class="footer__newsletter-input"
                           placeholder="Your email" required aria-label="Email for newsletter">
                    <button type="submit" class="footer__newsletter-btn" aria-label="Subscribe">→</button>
                </form>
                <p style="font-size:.72rem;margin-top:10px;opacity:.4">
                    No spam. Unsubscribe anytime.
                </p>
            </div>
        </div>

        <div class="footer__bottom">
            <p>&copy; <?= date('Y') ?> AURA. All rights reserved.</p>
            <div style="display:flex;gap:20px;font-size:.8rem">
                <a href="#" style="color:rgba(250,250,248,.4);transition:color .3s"
                   onmouseover="this.style.color='#B8976A'" onmouseout="this.style.color='rgba(250,250,248,.4)'">
                    Privacy Policy
                </a>
                <a href="#" style="color:rgba(250,250,248,.4);transition:color .3s"
                   onmouseover="this.style.color='#B8976A'" onmouseout="this.style.color='rgba(250,250,248,.4)'">
                    Terms of Use
                </a>
            </div>
        </div>
    </div>
</footer>

<!-- AOS -->
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<!-- Main JS -->
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body>
</html>
