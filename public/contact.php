<?php
require_once __DIR__ . '/../config.php';
require_once CORE_PATH . '/Helpers/Security.php';
require_once CORE_PATH . '/Helpers/Session.php';
require_once CORE_PATH . '/Helpers/Validator.php';

use Core\Helpers\Security;
use Core\Helpers\Session;
use Core\Helpers\Validator;

Session::start();

$success = false;
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrf()) {
        $errors[] = 'Security token error.';
    } else {
        $v = (new Validator())->load($_POST)
            ->required('contact_name',    'Name')
            ->required('contact_email',   'Email')
            ->email('contact_email')
            ->required('contact_message', 'Message');

        if ($v->fails()) {
            $errors = $v->errorList();
        } else {
            // In production: send email via PHPMailer / mail()
            $success = true;
        }
    }
}

$pageTitle = 'Contact — AURA';
require_once VIEWS_PATH . '/partials/head.php';
require_once VIEWS_PATH . '/partials/navbar.php';
?>

<main style="padding-top:var(--nav-h)">

<section style="padding:80px 0 40px;background:var(--color-bg-2)">
    <div class="container" style="max-width:600px;text-align:center">
        <span class="section-header__kicker" data-aos="fade-down">Get in Touch</span>
        <h1 data-aos="fade-up" style="margin:12px 0 16px">We'd Love to Hear<br>From You</h1>
        <p data-aos="fade-up" data-aos-delay="100" style="color:var(--color-text-2)">
            Have a question about your order, a styling enquiry, or simply want to say hello? Our concierge team responds within 24 hours.
        </p>
    </div>
</section>

<section class="section">
    <div class="container" style="display:grid;grid-template-columns:1fr 1fr;gap:64px;align-items:start" class="contact-grid">

        <!-- Contact Info -->
        <div data-aos="fade-right">
            <h2 style="font-size:1.2rem;margin-bottom:28px">Contact Details</h2>
            <?php
            $contacts = [
                ['icon'=>'📍','label'=>'Atelier','value'=>'Evacuee Trust Complex, F5/2, Islamabad'],
                ['icon'=>'📧','label'=>'Email',  'value'=>'cmustafasaeed665@gmail.com'],
                ['icon'=>'📞','label'=>'Phone',  'value'=>'+92 313 6609322'],
                ['icon'=>'⏰','label'=>'Hours',  'value'=>'Mon–Fri: 9AM–4PM GMT'],
            ];
            foreach ($contacts as $c): ?>
            <div style="display:flex;gap:16px;margin-bottom:24px;align-items:flex-start">
                <div style="width:44px;height:44px;background:rgba(184,151,106,.1);border-radius:var(--radius-md);
                     display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0">
                    <?= $c['icon'] ?>
                </div>
                <div>
                    <p style="font-size:.7rem;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:var(--color-text-3);margin-bottom:3px">
                        <?= Security::h($c['label']) ?>
                    </p>
                    <p style="font-size:.9rem;color:var(--color-text-2)"><?= Security::h($c['value']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>

            <div style="margin-top:40px;padding:28px;background:linear-gradient(135deg,#1a1a18,#2d2b26);
                 border-radius:var(--radius-lg);color:rgba(250,250,248,.6)">
                <p style="font-family:var(--font-serif);font-size:1.1rem;font-style:italic;color:rgba(250,250,248,.75);margin-bottom:10px">
                    "Every garment tells a story. Let us help you find yours."
                </p>
                <p style="font-size:.75rem;letter-spacing:.1em;text-transform:uppercase;color:var(--color-gold)">
                    — The AURA Team
                </p>
            </div>
        </div>

        <!-- Contact Form -->
        <div data-aos="fade-left">
            <?php if ($success): ?>
            <div style="background:rgba(74,124,89,.1);border:1px solid rgba(74,124,89,.25);border-radius:var(--radius-lg);
                 padding:40px;text-align:center">
                <div style="font-size:2.5rem;margin-bottom:16px">✓</div>
                <h2 style="margin-bottom:10px">Message Sent!</h2>
                <p style="color:var(--color-text-2)">Thank you for reaching out. Our team will respond within 24 hours.</p>
            </div>
            <?php else: ?>

            <?php if (!empty($errors)): ?>
            <div style="background:rgba(181,87,74,.08);border:1px solid rgba(181,87,74,.2);border-radius:var(--radius-md);
                 padding:14px 18px;margin-bottom:20px">
                <?php foreach ($errors as $e): ?>
                <p style="font-size:.875rem;color:var(--color-danger)"><?= Security::h($e) ?></p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="card" novalidate>
                <?= Security::csrfField() ?>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="contact_name">Your Name</label>
                        <input type="text" id="contact_name" name="contact_name" class="form-control"
                               value="<?= Security::h($_POST['contact_name'] ?? '') ?>"
                               placeholder="Full name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="contact_email">Email Address</label>
                        <input type="email" id="contact_email" name="contact_email" class="form-control"
                               value="<?= Security::h($_POST['contact_email'] ?? '') ?>"
                               placeholder="your@email.com" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="contact_subject">Subject</label>
                    <input type="text" id="contact_subject" name="contact_subject" class="form-control"
                           value="<?= Security::h($_POST['contact_subject'] ?? '') ?>"
                           placeholder="Order enquiry, styling advice, …">
                </div>
                <div class="form-group">
                    <label class="form-label" for="contact_message">Message</label>
                    <textarea id="contact_message" name="contact_message" class="form-control" rows="6"
                              placeholder="How can we help you?" required><?= Security::h($_POST['contact_message'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:15px">
                    Send Message
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</section>

</main>

<style>@media(max-width:768px){.contact-grid{grid-template-columns:1fr !important;gap:32px !important}}</style>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>
