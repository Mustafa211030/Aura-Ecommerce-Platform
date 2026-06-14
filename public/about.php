<?php
require_once __DIR__ . '/../config.php';
require_once CORE_PATH . '/Helpers/Security.php';
require_once CORE_PATH . '/Helpers/Session.php';

use Core\Helpers\Security;
use Core\Helpers\Session;

Session::start();

Session::start();
$pageTitle = 'About AURA — Our Story';
require_once VIEWS_PATH . '/partials/head.php';
require_once VIEWS_PATH . '/partials/navbar.php';
?>

<main style="padding-top:var(--nav-h)">

<!-- About Hero -->
<section style="padding:100px 0;background:linear-gradient(145deg,#1a1a18 0%,#2d2b26 100%);position:relative;overflow:hidden">
    <div style="position:absolute;inset:0;background:radial-gradient(ellipse 60% 80% at 30% 50%,rgba(184,151,106,.1) 0%,transparent 70%)"></div>
    <div class="container" style="position:relative;z-index:1;max-width:700px">
        <span data-aos="fade-down" style="color:var(--color-gold-lt);font-size:.7rem;letter-spacing:.25em;text-transform:uppercase;display:block;margin-bottom:16px">
            Est. 2018
        </span>
        <h1 data-aos="fade-up" style="color:#FAFAF8;font-size:clamp(2rem,5vw,3.5rem);margin-bottom:24px">
            The Art of<br><em style="color:var(--color-gold-lt)">Timeless</em> Fashion
        </h1>
        <p data-aos="fade-up" data-aos-delay="100" style="color:rgba(250,250,248,.6);font-size:1.0625rem;line-height:1.8">
            AURA was founded on a singular belief: that true luxury is not about price — it's about the way a garment makes you feel. Enduring, intentional, extraordinary.
        </p>
    </div>
</section>

<!-- Story -->
<section class="section">
    <div class="container">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:80px;align-items:center" class="about-grid">
            <div data-aos="fade-right">
                <span class="section-header__kicker">Our Philosophy</span>
                <h2 style="margin:12px 0 20px">Crafted for the<br>Discerning Few</h2>
                <p style="color:var(--color-text-2);line-height:1.9;margin-bottom:20px">
                    Every AURA piece begins as a sketch in our atelier in Milan. Our master craftspeople — many with decades of experience — bring each design to life using only the finest natural fibres and hand-sourced fabrics.
                </p>
                <p style="color:var(--color-text-2);line-height:1.9;margin-bottom:32px">
                    We reject fast fashion. Every collection is deliberately small, meticulously planned, and designed to outlast trends by generations. When you wear AURA, you wear a commitment to quality over quantity.
                </p>
                <a href="<?= APP_URL ?>/shop.php" class="btn btn-primary">Explore Collection</a>
            </div>
            <div data-aos="fade-left" style="background:var(--color-bg-2);aspect-ratio:4/5;border-radius:var(--radius-xl);
                 display:flex;align-items:center;justify-content:center;font-size:5rem">
                ✦
            </div>
        </div>
    </div>
</section>

<!-- Values -->
<section class="section" style="background:var(--color-bg-2)">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <span class="section-header__kicker">Our Values</span>
            <h2 class="section-header__title">What Defines Us</h2>
            <div class="section-header__line"></div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:28px">
            <?php
            $values = [
                ['icon'=>'✦','title'=>'Craftsmanship','desc'=>'Every stitch placed with intention. Our artisans average 20+ years of experience in their craft.'],
                ['icon'=>'♻','title'=>'Sustainability','desc'=>'We source all fabrics from certified sustainable suppliers and use eco-conscious packaging.'],
                ['icon'=>'∞','title'=>'Timelessness','desc'=>'We design for decades, not seasons. An AURA piece should still feel relevant in 30 years.'],
                ['icon'=>'◇','title'=>'Exclusivity','desc'=>'Limited runs ensure your AURA piece is truly rare. We never mass-produce.'],
            ];
            foreach ($values as $i => $v): ?>
            <div class="card" data-aos="fade-up" data-aos-delay="<?= $i * 80 ?>" style="padding:36px 28px">
                <div style="font-size:2rem;color:var(--color-gold);margin-bottom:16px;font-family:var(--font-serif)"><?= $v['icon'] ?></div>
                <h3 style="font-size:1rem;margin-bottom:10px"><?= Security::h($v['title']) ?></h3>
                <p style="font-size:.875rem;color:var(--color-text-2);line-height:1.7"><?= Security::h($v['desc']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Team -->
<section class="section">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <span class="section-header__kicker">Leadership</span>
            <h2 class="section-header__title">The Visionaries</h2>
            <div class="section-header__line"></div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:28px">
            <?php
            $team = [
                ['name'=>'Sofia Marchetti','role'=>'Founder & Creative Director','avatar'=>'SM'],
                ['name'=>'Ethan Rivers','role'=>'Head of Craftsmanship','avatar'=>'ER'],
                ['name'=>'Aiko Tanaka','role'=>'Lead Designer','avatar'=>'AT'],
                ['name'=>'Claude Beaumont','role'=>'Global Brand Director','avatar'=>'CB'],
            ];
            foreach ($team as $i => $t): ?>
            <div style="text-align:center" data-aos="fade-up" data-aos-delay="<?= $i * 80 ?>">
                <div style="width:100px;height:100px;border-radius:50%;background:linear-gradient(135deg,#1a1a18,#2d2b26);
                     display:flex;align-items:center;justify-content:center;margin:0 auto 16px;
                     font-family:var(--font-serif);font-size:1.5rem;color:var(--color-gold-lt);letter-spacing:.05em">
                    <?= Security::h($t['avatar']) ?>
                </div>
                <h3 style="font-size:.9375rem;margin-bottom:4px"><?= Security::h($t['name']) ?></h3>
                <p style="font-size:.775rem;color:var(--color-text-3);letter-spacing:.05em;text-transform:uppercase">
                    <?= Security::h($t['role']) ?>
                </p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

</main>

<style>@media(max-width:768px){.about-grid{grid-template-columns:1fr !important;gap:40px !important}}</style>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>
