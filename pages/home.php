<?php
/**
 * REGC — Home (brand landing page).
 * Built from the original REGC landing design: hero, trust, products,
 * benefits, about, testimonials, TikTok, contact.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/seo.php';

$config = public_config();
$products = get_products();

$seo = [
    'title' => APP_NAME . ' | ' . APP_TAGLINE,
    'description' => APP_DESCRIPTION,
    'keywords' => APP_KEYWORDS,
    'canonical' => '',
    'url' => '',
    'image' => APP_OG_IMAGE,
    'jsonld' => organization_jsonld(),
];
$bodyClass = 'page-home';
$activeNav = 'home';

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/site_header.php';
?>
<main class="page-content" id="app">

    <!-- HERO -->
    <section class="hero" id="home">
        <div class="hero-content">
            <div class="hero-badge"><i class="fa-solid fa-basket-shopping"></i> We shop. We pack. We deliver. You relax.</div>
            <h1>Premium Foodstuff <span>Delivery You Can Trust</span></h1>
            <p>Experience the finest selection of fresh produce, pantry essentials and exclusive Ramadan food packs from Royale Experience Global Concept — expertly sourced, meticulously packed and delivered nationwide with utmost care.</p>
            <div class="hero-buttons">
                <a href="<?= e(abs_url('shop')) ?>" class="btn primary-btn" data-spa-link>Shop Food Essentials <i class="fa-solid fa-arrow-right"></i></a>
                <a href="https://wa.me/<?= e($config['site_whatsapp'] ? preg_replace('/\D+/', '', (string)$config['site_whatsapp']) : '2348166978348') ?>" target="_blank" rel="noopener" class="btn secondary-btn">Send Your Food List</a>
            </div>
            <div class="hero-stats">
                <div class="stat"><strong>100%</strong><span>Quality Sourced</span></div>
                <div class="stat"><strong>Nationwide</strong><span>Doorstep Delivery</span></div>
                <div class="stat"><strong>Bulk</strong><span>&amp; Corporate Supply</span></div>
            </div>
        </div>
        <div class="hero-image">
            <div class="hero-image-bg"></div>
            <img src="<?= e(cloudinary_asset('regc/hero', 'jpg', 'hero.jpg', 'q_auto,f_auto')) ?>" alt="Premium foodstuff delivery from Royale Experience Global Concept" width="640" height="427" fetchpriority="high">
            <div class="floating-card card-one"><i class="fa-solid fa-leaf"></i><div><strong>Fresh Produce</strong><span>Quality Sourced</span></div></div>
            <div class="floating-card card-two"><i class="fa-solid fa-truck-fast"></i><div><strong>Fast Delivery</strong><span>Nationwide</span></div></div>
        </div>
    </section>

    <!-- FEATURES -->
    <section class="features">
        <div class="feature-box"><div class="feature-icon"><i class="fa-solid fa-leaf"></i></div><div><h3>Premium Quality Sourcing</h3><p>Best quality ingredients for your meals.</p></div></div>
        <div class="feature-box"><div class="feature-icon"><i class="fa-solid fa-box"></i></div><div><h3>Hygienic Packaging</h3><p>Packed with utmost care and hygiene.</p></div></div>
        <div class="feature-box"><div class="feature-icon"><i class="fa-solid fa-truck-fast"></i></div><div><h3>Nationwide Delivery</h3><p>Fresh food to your doorstep.</p></div></div>
        <div class="feature-box"><div class="feature-icon"><i class="fa-solid fa-handshake"></i></div><div><h3>Bulk &amp; Corporate</h3><p>Special rates for bulk orders.</p></div></div>
    </section>

    <!-- TRUST -->
    <section class="trust-section" id="trust">
        <div class="section-heading">
            <span class="section-tag"><i class="fa-solid fa-star"></i> WHY CUSTOMERS TRUST US</span>
            <h2>Quality You Can <span>Rely On</span></h2>
            <p>We shop, we pack, we deliver — so you can relax and enjoy quality foodstuff without the stress.</p>
        </div>
        <div class="trust-grid">
            <div class="trust-card"><div class="trust-icon"><i class="fa-solid fa-utensils"></i></div><div><h4>Premium Quality Sourcing</h4><p>We ensure the best quality ingredients for your meals.</p></div></div>
            <div class="trust-card"><div class="trust-icon"><i class="fa-solid fa-box"></i></div><div><h4>Hygienic Packaging</h4><p>All products are packaged with utmost care and hygiene.</p></div></div>
            <div class="trust-card"><div class="trust-icon"><i class="fa-solid fa-truck-fast"></i></div><div><h4>Nationwide Doorstep Delivery</h4><p>We deliver fresh food right to your doorstep, anywhere in the country.</p></div></div>
            <div class="trust-card"><div class="trust-icon"><i class="fa-solid fa-coins"></i></div><div><h4>Competitive Pricing</h4><p>Enjoy the best prices without compromising on quality.</p></div></div>
            <div class="trust-card"><div class="trust-icon"><i class="fa-solid fa-building"></i></div><div><h4>Bulk &amp; Corporate Supply</h4><p>Special rates for bulk orders and corporate clients.</p></div></div>
            <div class="trust-card"><div class="trust-icon"><i class="fa-solid fa-headset"></i></div><div><h4>Excellent Customer Service</h4><p>Our team is here to assist you with any inquiries.</p></div></div>
        </div>
    </section>

    <!-- FEATURED PRODUCTS -->
    <section class="products-section" id="products">
        <div class="section-heading">
            <span class="section-tag"><i class="fa-solid fa-basket-shopping"></i> SHOP FOOD ESSENTIALS</span>
            <h2>Explore Our <span>Foodstuff Collection</span></h2>
            <p>Discover fresh produce, pantry essentials, quality oils, frozen foods and more — all ready for fast delivery.</p>
        </div>

        <div class="product-filter" role="tablist" aria-label="Filter products">
            <button class="filter-btn active" data-filter="all">All Products</button>
            <button class="filter-btn" data-filter="featured">Featured</button>
            <button class="filter-btn" data-filter="staples">Staples</button>
            <button class="filter-btn" data-filter="oils">Oils</button>
            <button class="filter-btn" data-filter="produce">Produce</button>
            <button class="filter-btn" data-filter="frozen">Frozen</button>
            <button class="filter-btn" data-filter="pantry">Pantry</button>
            <button class="filter-btn" data-filter="beverages">Beverages</button>
            <button class="filter-btn" data-filter="hampers">Hampers</button>
        </div>

        <div class="product-grid" id="productGrid">
            <?php foreach ($products as $p):
                $img = product_img($p['image'] ?? '');
                $cls = implode(' ', array_filter([
                    empty($p['featured']) ? '' : 'featured',
                    'staples' === strtolower($p['category'] ?? '') ? 'staples' : '',
                    'oils' === strtolower($p['category'] ?? '') ? 'oils' : '',
                    'produce' === strtolower($p['category'] ?? '') ? 'produce' : '',
                    'frozen' === strtolower($p['category'] ?? '') ? 'frozen' : '',
                    'pantry' === strtolower($p['category'] ?? '') ? 'pantry' : '',
                    'beverages' === strtolower($p['category'] ?? '') ? 'beverages' : '',
                    'hampers' === strtolower($p['category'] ?? '') ? 'hampers' : '',
                ]));
            ?>
            <div class="product-card <?= $cls ?>" data-category="<?= e(strtolower($p['category'] ?? 'staples')) ?>" data-featured="<?= !empty($p['featured']) ? '1' : '0' ?>">
                <a class="product-image-link" href="<?= e(abs_url('product/' . $p['slug'])) ?>" data-spa-link>
                    <div class="product-image">
                        <?php if (!empty($p['badge'])): ?><span class="product-badge <?= empty($p['featured']) ? 'green' : '' ?>"><?= e($p['badge']) ?></span><?php endif; ?>
                        <img src="<?= e($img) ?>" alt="<?= e($p['name']) ?>" loading="lazy" width="400" height="300">
                    </div>
                </a>
                <div class="product-info">
                    <span class="product-category"><?= e(ucfirst($p['category'])) ?></span>
                    <h3><a href="<?= e(abs_url('product/' . $p['slug'])) ?>" data-spa-link><?= e($p['name']) ?></a></h3>
                    <p><?= e($p['short_description'] ?? '') ?></p>
                    <div class="product-bottom">
                        <strong class="price">
                            <?php if (!empty($p['old_price'])): ?><span class="old-price"><?= money($p['old_price']) ?></span><?php endif; ?>
                            <?= money($p['price']) ?>
                        </strong>
                        <button class="add-cart" onclick="addToCart(<?= (int)$p['id'] ?>, <?= e_js($p['name']) ?>, <?= (float)$p['price'] ?>, <?= e_js($img) ?>)"><i class="fa-solid fa-plus"></i> Add</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="section-cta">
            <a href="<?= e(abs_url('shop')) ?>" class="btn primary-btn" data-spa-link>Browse the Full Shop <i class="fa-solid fa-arrow-right"></i></a>
        </div>
    </section>

    <!-- BENEFITS -->
    <section class="benefits" id="benefits">
        <div class="benefits-image">
            <img src="<?= e(cloudinary_asset('regc/benefits', 'jpg', 'products/pap.jpg', 'q_auto,f_auto')) ?>" alt="REGC premium foodstuff" loading="lazy" width="450" height="338">
            <div class="experience-card"><strong>100%</strong><span>Quality<br>Foodstuff</span></div>
        </div>
        <div class="benefits-content">
            <span class="section-tag"><i class="fa-solid fa-basket-shopping"></i> WHY ORDER FROM US</span>
            <h2>Fresh, Quality Foodstuff <span>Delivered to Your Door</span></h2>
            <p>From fresh produce and pantry essentials to premium oils and exclusive Ramadan food packs, we handle the shopping, packing and delivery so you don't have to.</p>
            <div class="benefit-list">
                <div class="benefit-item"><div class="check-icon"><i class="fa-solid fa-check"></i></div><div><h3>Nationwide Doorstep Delivery</h3><p>We deliver fresh food right to your doorstep, anywhere in the country.</p></div></div>
                <div class="benefit-item"><div class="check-icon"><i class="fa-solid fa-check"></i></div><div><h3>Ramadan &amp; Gift Food Packs</h3><p>Exclusive Ramadan packs and curated gift hampers for every occasion.</p></div></div>
                <div class="benefit-item"><div class="check-icon"><i class="fa-solid fa-check"></i></div><div><h3>Bulk &amp; Corporate Supply</h3><p>Special rates for bulk orders and corporate clients — just send your food list.</p></div></div>
            </div>
        </div>
    </section>

    <!-- ABOUT -->
    <section class="about-section" id="about">
        <div class="section-heading">
            <span class="section-tag"><i class="fa-solid fa-building"></i> ABOUT REGC</span>
            <h2>Bringing Quality Foodstuff <span>Closer to You</span></h2>
        </div>
        <div class="about-content">
            <div class="about-text">
                <p>Royale Experience Global Concept is a premium foodstuff delivery business dedicated to bringing you the finest selection of fresh produce, pantry essentials and exclusive Ramadan food packs.</p>
                <p>We shop. We pack. We deliver. You relax. Our goal is simple — to take the stress out of buying foodstuff while maintaining our commitment to quality, freshness and exceptional service.</p>
                <a href="<?= e(abs_url('shop')) ?>" class="btn primary-btn" data-spa-link>Shop Now <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="about-cards">
                <div class="about-card"><i class="fa-solid fa-seedling"></i><h3>Quality</h3><p>Quality foodstuff selected with care.</p></div>
                <div class="about-card"><i class="fa-solid fa-box"></i><h3>Reliability</h3><p>Meticulously packed, delivered on time.</p></div>
                <div class="about-card"><i class="fa-solid fa-heart"></i><h3>Care</h3><p>Served with passion and attention to detail.</p></div>
            </div>
        </div>
    </section>

    <!-- TESTIMONIALS -->
    <section class="testimonials">
        <div class="section-heading">
            <span class="section-tag"><i class="fa-solid fa-star"></i> CUSTOMER REVIEWS</span>
            <h2>What Our <span>Customers Say</span></h2>
        </div>
        <div class="testimonial-grid">
            <div class="testimonial-card"><div class="stars">★★★★★</div><p>"REGC delivered my Ramadan food pack on time and everything was fresh. Highly recommended!"</p><div class="customer"><div class="customer-avatar">A</div><div><strong>Amara</strong><span>Verified Buyer</span></div></div></div>
            <div class="testimonial-card"><div class="stars">★★★★★</div><p>"I send my food list and they do everything. The rice and oil quality is excellent."</p><div class="customer"><div class="customer-avatar">K</div><div><strong>Khadija</strong><span>Verified Buyer</span></div></div></div>
            <div class="testimonial-card"><div class="stars">★★★★★</div><p>"Best foodstuff service for my restaurant. Bulk supply at great prices, always reliable."</p><div class="customer"><div class="customer-avatar">F</div><div><strong>Farouk</strong><span>Corporate Client</span></div></div></div>
        </div>
    </section>

    <!-- TIKTOK STORE -->
    <section class="tiktok-section" id="tiktok">
        <div class="tiktok-card">
            <div class="tiktok-brand">
                <i class="fa-brands fa-tiktok"></i>
                <div>
                    <span class="section-tag"><i class="fa-solid fa-circle-play"></i> WE SELL ON TIKTOK</span>
                    <h2>Watch Us, Shop With Us on <span>TikTok</span></h2>
                    <p>Follow <?= e(APP_NAME) ?> on TikTok to see our fresh foodstuff, packaging and live product updates — and shop directly with us on the platform.</p>
                    <div class="tiktok-actions">
                        <a href="<?= e($config['site_tiktok'] ?? 'https://www.tiktok.com/@royaleexperienceglobalconcept') ?>" target="_blank" rel="noopener" class="btn tiktok-btn"><i class="fa-brands fa-tiktok"></i> Follow on TikTok</a>
                        <a href="<?= e(abs_url('shop')) ?>" class="btn secondary-btn" data-spa-link>Browse the Shop</a>
                    </div>
                </div>
            </div>
            <div class="tiktok-phones">
                <div class="tt-phone"><i class="fa-brands fa-tiktok"></i><strong>@royaleexperienceglobalconcept</strong><span>Foodstuff, fresh produce &amp; more</span></div>
                <div class="tt-phone"><i class="fa-brands fa-tiktok"></i><strong>Shop on TikTok</strong><span>Order straight from our profile</span></div>
            </div>
        </div>
    </section>

    <!-- TRACK ORDER -->
    <section class="track-section" id="track">
        <div class="section-heading">
            <span class="section-tag"><i class="fa-solid fa-magnifying-glass"></i> ORDER TRACKING</span>
            <h2>Track <span>Your Order</span></h2>
            <p>Enter your order number to check the status of your delivery.</p>
        </div>
        <form class="track-form" id="trackForm">
            <div class="track-input">
                <input type="text" id="trackOrderNo" placeholder="e.g. REGC-260826-AB12C" class="form-group-input" style="flex:1;padding:15px;border:1px solid var(--border);border-radius:10px;font-family:inherit">
                <button class="btn primary-btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Track</button>
            </div>
        </form>
        <div class="track-result" id="trackResult"></div>
    </section>

    <!-- CONTACT -->
    <section class="contact-section" id="contact">
        <div class="contact-info">
            <span class="section-tag light-tag"><i class="fa-solid fa-envelope"></i> GET IN TOUCH</span>
            <h2>Let's Talk About <span>Your Order</span></h2>
            <p>Have questions about our foodstuff, bulk supply or a food list? Contact us directly or send us a message.</p>
            <div class="contact-item"><div class="contact-icon"><i class="fa-brands fa-whatsapp"></i></div><div><span>WhatsApp</span><strong><?= e($config['site_whatsapp'] ?? '0816 697 8348') ?></strong></div></div>
            <div class="contact-item"><div class="contact-icon"><i class="fa-brands fa-tiktok"></i></div><div><span>TikTok</span><strong><a href="<?= e($config['site_tiktok'] ?? 'https://www.tiktok.com/@royaleexperienceglobalconcept') ?>" target="_blank" rel="noopener" style="color:inherit;text-decoration:none">@royaleexperienceglobalconcept</a></strong></div></div>
            <div class="contact-item"><div class="contact-icon"><i class="fa-solid fa-location-dot"></i></div><div><span>Address</span><strong><?= e($config['site_address'] ?? 'Lagos, Nigeria') ?></strong></div></div>
        </div>
        <form class="contact-form" id="contactForm">
            <h3>Send Us a Message</h3>
            <div class="form-group"><input type="text" id="name" placeholder="Your Full Name" required></div>
            <div class="form-group"><input type="tel" id="phone" placeholder="Your Phone Number" required></div>
            <div class="form-group"><textarea id="message" rows="6" placeholder="Send us your food list or tell us what you need..." required></textarea></div>
            <button type="submit" class="btn primary-btn full-button">Send Message <i class="fa-brands fa-whatsapp"></i></button>
        </form>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>