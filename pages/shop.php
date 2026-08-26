<?php
/**
 * REGC — Shop (ecommerce storefront).
 * Pretty URL: /shop, /shop/category/<cat>, /shop?q=...&sort=...
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/seo.php';

$config = public_config();
$q = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$sort = trim($_GET['sort'] ?? '');

$all = get_products();

// Filter by category
$products = $all;
if ($category !== '') {
    $products = array_values(array_filter($products, fn($p) => strtolower($p['category'] ?? '') === strtolower($category)));
}

// Search
if ($q !== '') {
    $qL = strtolower($q);
    $products = array_values(array_filter($products, function ($p) use ($qL) {
        return str_contains(strtolower($p['name'] ?? ''), $qL)
            || str_contains(strtolower($p['short_description'] ?? ''), $qL)
            || str_contains(strtolower($p['description'] ?? ''), $qL);
    }));
}

// Sort
switch ($sort) {
    case 'price-asc':
        usort($products, fn($a, $b) => $a['price'] <=> $b['price']);
        break;
    case 'price-desc':
        usort($products, fn($a, $b) => $b['price'] <=> $a['price']);
        break;
    case 'name':
        usort($products, fn($a, $b) => strcmp($a['name'], $b['name']));
        break;
    default:
        usort($products, fn($a, $b) => ($b['featured'] ?? false) <=> ($a['featured'] ?? false));
}

// Categories present
$cats = [];
foreach ($all as $p) {
    $c = strtolower($p['category'] ?? 'staples');
    $cats[$c] = ($cats[$c] ?? 0) + 1;
}

$catTitle = $category !== '' ? ucfirst($category) . ' — ' : '';
$seo = [
    'title' => $catTitle . 'Shop Foodstuff | ' . APP_NAME,
    'description' => 'Shop ' . APP_NAME . ' foodstuff — fresh produce, pantry essentials, quality oils, frozen foods and Ramadan packs. Order online with fast, easy checkout and nationwide delivery.',
    'keywords' => APP_KEYWORDS,
    'canonical' => $category !== '' ? 'shop/category/' . rawurlencode($category) : 'shop',
    'url' => $category !== '' ? 'shop/category/' . rawurlencode($category) : 'shop',
    'image' => APP_OG_IMAGE,
    'jsonld' => array_merge(organization_jsonld(), [
        [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => ($category !== '' ? ucfirst($category) . ' products' : 'All products'),
            'itemListElement' => array_map(function ($p, $i) {
                return [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'url' => abs_url('product/' . $p['slug']),
                    'name' => $p['name'],
                ];
            }, array_slice($products, 0, 20), array_keys(array_slice($products, 0, 20))),
        ],
    ]),
];
$bodyClass = 'page-shop';
$activeNav = 'shop';

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/site_header.php';
?>
<main class="page-content" id="app">
    <?php
    $qStatus = $_GET['status'] ?? '';
    $qOrderNo = $_GET['order_no'] ?? '';
    if ($qStatus === 'success'):
    ?>
    <div class="page-banner success"><p><i class="fa-solid fa-circle-check"></i> Thank you! Your order <strong><?= e($qOrderNo) ?></strong> has been received and paid for. A confirmation email is on its way.</p><button onclick="this.parentElement.remove()">&times;</button></div>
    <?php elseif ($qStatus === 'payment'): ?>
    <div class="page-banner info"><p><i class="fa-solid fa-circle-info"></i> Your order <strong><?= e($qOrderNo) ?></strong> was placed. Please complete payment to confirm your order.</p><button onclick="this.parentElement.remove()">&times;</button></div>
    <?php elseif ($qStatus === 'error'): ?>
    <div class="page-banner error"><p><i class="fa-solid fa-triangle-exclamation"></i> Something went wrong. Please try again.</p><button onclick="this.parentElement.remove()">&times;</button></div>
    <?php endif; ?>

    <!-- SHOP HERO -->
    <section class="shop-hero">
        <div class="shop-hero-inner">
            <span class="section-tag"><i class="fa-solid fa-store"></i> OUR STORE</span>
            <h1>Shop <?= $category !== '' ? e(ucfirst($category)) : 'Foodstuff Essentials' ?></h1>
            <p>Fresh produce, pantry essentials, quality oils, frozen foods and exclusive Ramadan packs — delivered nationwide with care.</p>

            <!-- SEARCH -->
            <form class="shop-search" method="get" action="<?= e(abs_url('shop')) ?>" data-spa>
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search rice, oil, garri, dates..." aria-label="Search products">
                    <button class="btn btn-small primary-btn" type="submit">Search</button>
                </div>
            </form>
        </div>
    </section>

    <!-- SHOP CONTENT -->
    <section class="shop-section">
        <div class="shop-layout">
            <!-- FILTER SIDEBAR -->
            <aside class="shop-filters">
                <h3>Categories</h3>
                <a href="<?= e(abs_url('shop')) ?>" class="<?= $category === '' ? 'active' : '' ?>">All Products <span>(<?= count($all) ?>)</span></a>
                <?php foreach ($cats as $c => $n): ?>
                <a href="<?= e(abs_url('shop/category/' . rawurlencode($c))) ?>" class="<?= $category === $c ? 'active' : '' ?>"><?= e(ucfirst($c)) ?> <span>(<?= (int)$n ?>)</span></a>
                <?php endforeach; ?>

                <h3>Sort</h3>
                <div class="sort-links">
                    <a href="<?= e(abs_url('shop') . ($q ? '?q=' . urlencode($q) : '')) ?>" class="<?= $sort === '' ? 'active' : '' ?>">Featured</a>
                    <a href="<?= e(abs_url('shop') . '?sort=price-asc' . ($q ? '&q=' . urlencode($q) : '')) ?>">Price: Low to High</a>
                    <a href="<?= e(abs_url('shop') . '?sort=price-desc' . ($q ? '&q=' . urlencode($q) : '')) ?>">Price: High to Low</a>
                    <a href="<?= e(abs_url('shop') . '?sort=name' . ($q ? '&q=' . urlencode($q) : '')) ?>">Name A–Z</a>
                </div>
            </aside>

            <!-- PRODUCT GRID -->
            <div class="shop-main">
                <?php if ($q !== ''): ?>
                <p class="result-count"><?= count($products) ?> result<?= count($products) === 1 ? '' : 's' ?> for "<strong><?= e($q) ?></strong>"</p>
                <?php endif; ?>

                <?php if (count($products) === 0): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-box-open"></i>
                    <h3>No products found</h3>
                    <p>Try a different search term or browse all products.</p>
                    <a href="<?= e(abs_url('shop')) ?>" class="btn primary-btn" data-spa-link>View All Products</a>
                </div>
                <?php endif; ?>

                <div class="product-grid">
                    <?php foreach ($products as $p):
                        $img = product_img($p['image'] ?? '');
                    ?>
                    <div class="product-card">
                        <a class="product-image-link" href="<?= e(abs_url('product/' . $p['slug'])) ?>" data-spa-link>
                            <div class="product-image">
                                <?php if (!empty($p['badge'])): ?><span class="product-badge <?= empty($p['featured']) ? 'green' : '' ?>"><?= e($p['badge']) ?></span><?php endif; ?>
                                <?php if (!empty($p['old_price'])): ?><span class="product-badge sale">-<?= e(round((1 - $p['price'] / $p['old_price']) * 100)) ?>%</span><?php endif; ?>
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
            </div>
        </div>
    </section>

    <!-- CONTACT CTA -->
    <section class="contact-section" id="contact">
        <div class="contact-info">
            <span class="section-tag light-tag"><i class="fa-solid fa-envelope"></i> NEED HELP?</span>
            <h2>Questions About <span>Your Order?</span></h2>
            <p>Contact us directly on WhatsApp or visit our store.</p>
            <div class="contact-item"><div class="contact-icon"><i class="fa-brands fa-whatsapp"></i></div><div><span>WhatsApp</span><strong><?= e($config['site_whatsapp'] ?? '0816 697 8348') ?></strong></div></div>
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