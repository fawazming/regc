<?php
/**
 * REGC — Legal pages (privacy, terms, cookie policy).
 * Pretty URLs: /privacy, /terms, /cookies
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/seo.php';

$page = $_GET['page'] ?? 'privacy';
$config = public_config();

$meta = [
    'privacy' => [
        'title' => 'Privacy Policy | ' . APP_NAME,
        'desc' => 'How ' . APP_NAME . ' collects, uses and protects your personal information when you shop with us.',
        'canonical' => 'privacy',
        'h1' => 'Privacy Policy',
        'updated' => 'January 2026',
    ],
    'terms' => [
        'title' => 'Terms of Service | ' . APP_NAME,
        'desc' => 'The terms and conditions that govern your use of the ' . APP_NAME . ' store and purchases.',
        'canonical' => 'terms',
        'h1' => 'Terms of Service',
        'updated' => 'January 2026',
    ],
    'cookies' => [
        'title' => 'Cookie Policy | ' . APP_NAME,
        'desc' => 'How ' . APP_NAME . ' uses cookies and similar technologies on our website.',
        'canonical' => 'cookies',
        'h1' => 'Cookie Policy',
        'updated' => 'January 2026',
    ],
];

$m = $meta[$page] ?? $meta['privacy'];

$seo = [
    'title' => $m['title'],
    'description' => $m['desc'],
    'canonical' => $m['canonical'],
    'url' => $m['canonical'],
    'image' => APP_OG_IMAGE,
    'jsonld' => organization_jsonld(),
];
$bodyClass = 'page-legal';
$activeNav = 'legal';

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/site_header.php';
?>
<main class="page-content" id="app">

    <section class="legal-section">
        <div class="legal-card">
            <span class="section-tag"><i class="fa-solid fa-file-lines"></i> <?= e(ucfirst($page)) ?></span>
            <h1><?= e($m['h1']) ?></h1>
            <p class="legal-updated">Last updated: <?= e($m['updated']) ?></p>

            <?php if ($page === 'privacy'): ?>
                <h2>1. Introduction</h2>
                <p>Royale Experience Global Concept ("we", "our", "us") respects your privacy and is committed to protecting your personal information. This Privacy Policy explains how we collect, use, disclose and safeguard your information when you visit our website or place an order.</p>

                <h2>2. Information We Collect</h2>
                <p>When you place an order, we collect only the information needed to process and deliver it:</p>
                <ul>
                    <li><strong>Name</strong> — to address and identify your order.</li>
                    <li><strong>Email address</strong> — to send order confirmations and updates.</li>
                    <li><strong>Phone number</strong> — to contact you regarding delivery.</li>
                    <li><strong>Delivery address</strong> — to ship your order.</li>
                    <li><strong>Order details</strong> — the products, quantities and amounts you purchase.</li>
                </ul>
                <p>We do not collect or store your card or bank account details. Payments are handled securely by our payment provider (Rivo) or via bank transfer to our account.</p>

                <h2>3. How We Use Your Information</h2>
                <ul>
                    <li>To process and fulfil your orders.</li>
                    <li>To communicate order confirmations, tracking and delivery updates.</li>
                    <li>To provide customer support.</li>
                    <li>To improve our website, products and services.</li>
                </ul>

                <h2>4. Sharing of Information</h2>
                <p>We do not sell your personal information. We only share data with trusted third parties that help us operate, such as our payment processor (Rivo), email/notification providers and delivery partners, and only to the extent necessary to provide our services.</p>

                <h2>5. Data Security</h2>
                <p>We take reasonable technical and organisational measures to protect your information from unauthorised access, alteration, disclosure or destruction. Data is stored in secure, encrypted cloud infrastructure.</p>

                <h2>6. Your Rights</h2>
                <p>Depending on your location, you may have the right to access, correct, update or request deletion of your personal information. To exercise any of these rights, contact us using the details below.</p>

                <h2>7. Data Retention</h2>
                <p>We retain order records as long as necessary to provide our services, comply with legal obligations and resolve disputes.</p>

                <h2>8. Contact Us</h2>
                <p>If you have any questions about this Privacy Policy or your data, contact us at <strong><?= e(ORG_EMAIL) ?></strong> or <?= e($config['site_whatsapp'] ?? '0816 697 8348') ?>.</p>
            <?php endif; ?>

            <?php if ($page === 'terms'): ?>
                <h2>1. Acceptance of Terms</h2>
                <p>By accessing our website or placing an order, you agree to these Terms of Service and our <a href="<?= e(abs_url('privacy')) ?>">Privacy Policy</a>.</p>

                <h2>2. Products &amp; Availability</h2>
                <p>All products are foodstuff and grocery items intended for household and catering use. Availability may vary, and we reserve the right to substitute items of equal or better quality where necessary. Fresh produce availability depends on market supply.</p>

                <h2>3. Ordering &amp; Payment</h2>
                <p>Orders are confirmed after you complete checkout and provide accurate delivery details. Prices are shown in Nigerian Naira (₦). We accept payment by bank transfer and, where available, secure online payment via our payment provider.</p>

                <h2>4. Pricing</h2>
                <p>Prices are subject to change without notice. The price at the time of order is the price you pay. Delivery fees, where applicable, are shown at checkout.</p>

                <h2>5. Delivery</h2>
                <p>We deliver to addresses across Nigeria. Delivery timelines depend on your location and may vary. It is your responsibility to provide a correct and complete delivery address; we are not responsible for packages undelivered due to incorrect details.</p>

                <h2>6. Bulk &amp; Corporate Orders</h2>
                <p>For bulk and corporate supply, kindly contact us with your food list for a personalised quotation. Special rates apply.</p>

                <h2>7. Intellectual Property</h2>
                <p>All content on this website, including text, graphics, logos and images, is the property of Royale Experience Global Concept and may not be reproduced without permission.</p>

                <h2>8. Limitation of Liability</h2>
                <p>To the maximum extent permitted by law, Royale Experience Global Concept shall not be liable for any indirect, incidental or consequential damages arising from your use of the website or products.</p>

                <h2>9. Changes to These Terms</h2>
                <p>We may update these Terms from time to time. Continued use of the site after changes constitutes acceptance of the updated Terms.</p>

                <h2>10. Contact</h2>
                <p>Questions about these Terms can be sent to <strong><?= e(ORG_EMAIL) ?></strong>.</p>
            <?php endif; ?>

            <?php if ($page === 'cookies'): ?>
                <h2>1. What Are Cookies?</h2>
                <p>Cookies are small text files stored on your device when you visit a website. They help the site remember your preferences and improve your experience.</p>

                <h2>2. How We Use Cookies</h2>
                <ul>
                    <li><strong>Essential cookies</strong> — required for the website to function, such as remembering your shopping cart and security tokens.</li>
                    <li><strong>Performance cookies</strong> — help us understand how visitors use the site so we can improve it (anonymised).</li>
                    <li><strong>Functional cookies</strong> — remember choices you make, such as your language or region.</li>
                </ul>

                <h2>3. Managing Cookies</h2>
                <p>You can accept or decline non-essential cookies using our cookie banner, and you can change your browser settings to block or delete cookies at any time. Note that disabling essential cookies may affect how the site functions (for example, your cart may not be saved).</p>

                <h2>4. Third-Party Cookies</h2>
                <p>Some third parties (such as analytics or payment providers) may set their own cookies when you use our site. We do not control these cookies. For example, Google Fonts and Font Awesome may load resources from their CDNs.</p>

                <h2>5. Consent</h2>
                <p>By clicking "Accept All" on our cookie banner, you consent to the use of the cookies described in this policy. You may withdraw consent at any time by clearing your browser cookies.</p>

                <h2>6. Contact</h2>
                <p>For questions about our use of cookies, contact <strong><?= e(ORG_EMAIL) ?></strong>.</p>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>