<?php
/**
 * REGC — Product detail page.
 * Pretty URL: /product/<slug>
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/seo.php';

$slug = $_GET['slug'] ?? '';
$product = $slug !== '' ? get_product($slug) : null;

$config = public_config();

if (!$product) {
    http_response_code(404);
    $seo = ['title' => 'Product Not Found | ' . APP_NAME, 'noindex' => true, 'canonical' => '404'];
    $activeNav = 'shop';
    require __DIR__ . '/partials/header.php';
    require __DIR__ . '/partials/site_header.php';
    echo '<main class="page-content" id="app"><div class="page-404"><i class="fa-solid fa-basket-shopping"></i><h1>Product not found</h1><p>The product you are looking for is unavailable.</p><a class="btn primary-btn" href="' . e(abs_url('shop')) . '" data-spa-link>Back to Shop</a></div></main>';
    require __DIR__ . '/partials/footer.php';
    exit;
}

$img = product_img($product['image'] ?? '');
$related = get_products_by_category($product['category']);
$related = array_values(array_filter($related, fn($r) => $r['slug'] !== $product['slug']));
$related = array_slice($related, 0, 3);

// Detect product image dimensions for accurate og:image metadata
$imgW = 1200; $imgH = 630; $imgType = 'image/jpeg';
$localImg = dirname(__DIR__) . DIRECTORY_SEPARATOR . ltrim($product['image'] ?? '', '/');
if (is_file($localImg)) {
    $d = @getimagesize($localImg);
    if (is_array($d) && isset($d[0], $d[1], $d['mime'])) {
        $imgW = $d[0]; $imgH = $d[1]; $imgType = $d['mime'];
    }
}

$seo = [
    'title' => $product['name'] . ' — ' . money($product['price']) . ' | ' . APP_NAME,
    'description' => ($product['short_description'] ?? $product['description'] ?? '') . ' Order ' . $product['name'] . ' online from ' . APP_NAME . '.',
    'keywords' => APP_KEYWORDS,
    'canonical' => 'product/' . $product['slug'],
    'url' => 'product/' . $product['slug'],
    'image' => $product['image'] ?? APP_OG_IMAGE,
    'image_width' => $imgW,
    'image_height' => $imgH,
    'image_type' => $imgType,
    'type' => 'product',
    'og_type' => 'product',
    'price' => (float)$product['price'],
    'currency' => 'NGN',
    'jsonld' => array_merge(organization_jsonld(), [
        [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product['name'],
            'description' => $product['description'] ?? $product['short_description'] ?? '',
            'image' => [$img],
            'sku' => $product['slug'],
            'brand' => ['@type' => 'Brand', 'name' => ORG_NAME],
            'category' => $product['category'],
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => 'NGN',
                'price' => number_format((float)$product['price'], 2, '.', ''),
                'availability' => 'https://schema.org/InStock',
                'url' => abs_url('product/' . $product['slug']),
                'seller' => ['@type' => 'Organization', 'name' => ORG_NAME],
            ],
        ],
        [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => abs_url('')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Shop', 'item' => abs_url('shop')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $product['name'], 'item' => abs_url('product/' . $product['slug'])],
            ],
        ],
    ]),
];
$bodyClass = 'page-product';
$activeNav = 'shop';

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/site_header.php';
?>
<main class="page-content" id="app">

    <!-- BREADCRUMB -->
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="<?= e(abs_url('')) ?>">Home</a> <span>/</span>
        <a href="<?= e(abs_url('shop')) ?>">Shop</a> <span>/</span>
        <a href="<?= e(abs_url('shop/category/' . rawurlencode($product['category']))) ?>"><?= e(ucfirst($product['category'])) ?></a> <span>/</span>
        <span><?= e($product['name']) ?></span>
    </nav>

    <!-- PRODUCT DETAIL -->
    <section class="product-detail">
        <div class="pd-media">
            <div class="product-image">
                <?php if (!empty($product['badge'])): ?><span class="product-badge"><?= e($product['badge']) ?></span><?php endif; ?>
                <img src="<?= e($img) ?>" alt="<?= e($product['name']) ?>" width="600" height="450" fetchpriority="high">
            </div>
        </div>
        <div class="pd-info">
            <span class="product-category"><?= e(ucfirst($product['category'])) ?></span>
            <h1><?= e($product['name']) ?></h1>
            <div class="pd-price">
                <?php if (!empty($product['old_price'])): ?><span class="old-price"><?= money($product['old_price']) ?></span><?php endif; ?>
                <strong><?= money($product['price']) ?></strong>
                <?php if (!empty($product['old_price'])): ?><span class="pd-save">Save <?= money($product['old_price'] - $product['price']) ?></span><?php endif; ?>
            </div>
            <div class="pd-stock <?= ($product['stock'] ?? 0) > 0 ? 'in' : 'out' ?>">
                <?= ($product['stock'] ?? 0) > 0 ? '<i class="fa-solid fa-circle-check"></i> In stock — ready to ship' : '<i class="fa-solid fa-circle-xmark"></i> Currently out of stock' ?>
            </div>

            <div class="pd-qty">
                <label>Quantity</label>
                <div class="qty-selector">
                    <button type="button" onclick="stepQty(-1)" aria-label="Decrease quantity">-</button>
                    <span id="pdQty">1</span>
                    <button type="button" onclick="stepQty(1)" aria-label="Increase quantity">+</button>
                </div>
            </div>

            <button class="btn primary-btn pd-add" id="pdAddBtn"
                onclick="addToCart(<?= (int)$product['id'] ?>, <?= e_js($product['name']) ?>, <?= (float)$product['price'] ?>, <?= e_js($img) ?>, document.getElementById('pdQty').textContent)">
                <i class="fa-solid fa-cart-plus"></i> Add to Cart
            </button>

            <div class="pd-description">
                <h3>About this product</h3>
                <p><?= e($product['description'] ?? $product['short_description'] ?? '') ?></p>
            </div>

            <div class="pd-trust">
                <div><i class="fa-solid fa-truck-fast"></i><span>Fast, easy delivery</span></div>
                <div><i class="fa-solid fa-shield-heart"></i><span>Quality assured</span></div>
                <div><i class="fa-solid fa-basket-shopping"></i><span>Bulk &amp; corporate supply</span></div>
            </div>
        </div>
    </section>

    <?php if ($related): ?>
    <!-- RELATED -->
    <section class="products-section" id="related">
        <div class="section-heading">
            <span class="section-tag"><i class="fa-solid fa-basket-shopping"></i> YOU MAY ALSO LIKE</span>
            <h2>Related <span>Products</span></h2>
        </div>
        <div class="product-grid">
            <?php foreach ($related as $p):
                $ri = product_img($p['image'] ?? '');
            ?>
            <div class="product-card">
                <a class="product-image-link" href="<?= e(abs_url('product/' . $p['slug'])) ?>" data-spa-link>
                    <div class="product-image">
                        <img src="<?= e($ri) ?>" alt="<?= e($p['name']) ?>" loading="lazy" width="400" height="300">
                    </div>
                </a>
                <div class="product-info">
                    <span class="product-category"><?= e(ucfirst($p['category'])) ?></span>
                    <h3><a href="<?= e(abs_url('product/' . $p['slug'])) ?>" data-spa-link><?= e($p['name']) ?></a></h3>
                    <p><?= e($p['short_description'] ?? '') ?></p>
                    <div class="product-bottom">
                        <strong class="price"><?= money($p['price']) ?></strong>
                        <button class="add-cart" onclick="addToCart(<?= (int)$p['id'] ?>, <?= e_js($p['name']) ?>, <?= (float)$p['price'] ?>, <?= e_js($ri) ?>)"><i class="fa-solid fa-plus"></i> Add</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="contact-section" id="contact">
        <div class="contact-info">
            <span class="section-tag light-tag"><i class="fa-solid fa-envelope"></i> NEED HELP?</span>
            <h2>Questions About <span>Your Order?</span></h2>
            <p>Contact us directly on WhatsApp or visit our store.</p>
            <div class="contact-item"><div class="contact-icon"><i class="fa-brands fa-whatsapp"></i></div><div><span>WhatsApp</span><strong><?= e($config['site_whatsapp'] ?? '0816 697 8348') ?></strong></div></div>
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