<?php
/**
 * REGC — SEO / OpenGraph / Twitter / Structured-data (AEO) helpers.
 * Central place to render <head> meta so every page is consistently
 * SEO-, OG-, AEO- and social-sharing-ready.
 */

require_once __DIR__ . '/bootstrap.php';

/** Build an absolute URL from a root-relative path. */
function abs_url(string $path = ''): string
{
    $base = rtrim(APP_BASE_URL, '/');
    if ($path === '') {
        return $base . '/';
    }
    return $base . '/' . ltrim($path, '/');
}

/** Resolve an image path to an absolute URL (for OG images). */
function abs_img(string $img): string
{
    if ($img === '' || $img === APP_OG_IMAGE) {
        // Serve the branded OG image from Cloudinary when configured.
        if (cloudinary_enabled()) {
            return cloudinary_url('regc/og-image', 'jpg');
        }
        $img = APP_OG_IMAGE;
    }
    if (preg_match('#^https?://#i', $img)) {
        return $img;
    }
    return abs_url(ltrim($img, '/'));
}

/**
 * Render the standard <head> meta block.
 * @param array $o options:
 *   title, description, keywords, canonical, image, url,
 *   type (website|product|article), jsonld (array|array[]),
 *   noindex (bool), og_type, published, updated, author, price, currency
 */
function seo_head(array $o = []): void
{
    $title = $o['title'] ?? APP_NAME . ' | ' . APP_TAGLINE;
    $description = $o['description'] ?? APP_DESCRIPTION;
    $keywords = $o['keywords'] ?? APP_KEYWORDS;
    $canonical = abs_url($o['canonical'] ?? '');
    $url = abs_url($o['url'] ?? '');
    $image = abs_img($o['image'] ?? '');
    $imgW = (int)($o['image_width'] ?? 1200);
    $imgH = (int)($o['image_height'] ?? 630);
    $imgType = $o['image_type'] ?? 'image/jpeg';
    $type = $o['type'] ?? 'website';
    $ogType = $o['og_type'] ?? ($type === 'product' ? 'product' : 'website');

    // <meta charset> MUST be the first element inside <head>.
    echo '<meta charset="UTF-8">', "\n";
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">', "\n";
    echo '<title>', e($title), '</title>', "\n";
    echo '<meta name="description" content="', e($description), '">', "\n";
    if ($keywords) {
        echo '<meta name="keywords" content="', e($keywords), '">', "\n";
    }
    if (!empty($o['noindex'])) {
        echo '<meta name="robots" content="noindex,nofollow">', "\n";
    } else {
        echo '<meta name="robots" content="index,follow,max-image-preview:large">', "\n";
        echo '<link rel="canonical" href="', e($canonical), '">', "\n";
    }

    if (GOOGLE_SITE_VERIFICATION) {
        echo '<meta name="google-site-verification" content="', e(GOOGLE_SITE_VERIFICATION), '">', "\n";
    }
    if (BING_SITE_VERIFICATION) {
        echo '<meta name="msvalidate.01" content="', e(BING_SITE_VERIFICATION), '">', "\n";
    }

    // OpenGraph
    echo '<meta property="og:site_name" content="', e(APP_NAME), '">', "\n";
    echo '<meta property="og:type" content="', e($ogType), '">', "\n";
    echo '<meta property="og:title" content="', e($title), '">', "\n";
    echo '<meta property="og:description" content="', e($description), '">', "\n";
    echo '<meta property="og:url" content="', e($url ?: $canonical), '">', "\n";
    echo '<meta property="og:image" content="', e($image), '">', "\n";
    echo '<meta property="og:image:width" content="', (int)$imgW, '">', "\n";
    echo '<meta property="og:image:height" content="', (int)$imgH, '">', "\n";
    echo '<meta property="og:image:type" content="', e($imgType), '">', "\n";
    echo '<meta property="og:image:alt" content="', e($title), '">', "\n";
    echo '<meta property="og:locale" content="en_NG">', "\n";

    // Twitter
    echo '<meta name="twitter:card" content="summary_large_image">', "\n";
    echo '<meta name="twitter:site" content="', e(APP_TWITTER_HANDLE), '">', "\n";
    echo '<meta name="twitter:title" content="', e($title), '">', "\n";
    echo '<meta name="twitter:description" content="', e($description), '">', "\n";
    echo '<meta name="twitter:image" content="', e($image), '">', "\n";
    echo '<meta name="twitter:image:width" content="', (int)$imgW, '">', "\n";
    echo '<meta name="twitter:image:height" content="', (int)$imgH, '">', "\n";

    // Product structured extras
    if ($type === 'product' && !empty($o['price'])) {
        echo '<meta property="product:price:amount" content="', e($o['price']), '">', "\n";
        echo '<meta property="product:price:currency" content="', e($o['currency'] ?? 'NGN'), '">', "\n";
    }

    // Structured data (JSON-LD)
    $blocks = [];
    if (!empty($o['jsonld'])) {
        $blocks = is_array($o['jsonld']) && isset($o['jsonld'][0]) && is_array($o['jsonld'][0]) ? $o['jsonld'] : [$o['jsonld']];
    }
    foreach ($blocks as $block) {
        echo '<script type="application/ld+json">', json_encode($block, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), '</script>', "\n";
    }
}

/**
 * Standard Organization + WebSite structured data (used site-wide).
 * @param array $extra extra JSON-LD blocks to merge.
 */
function organization_jsonld(array $extra = []): array
{
    $s = get_settings();
    $social = array_values(array_filter([
        $s['site_tiktok'] ?? '',
        $s['site_instagram'] ?? '',
        $s['site_facebook'] ?? '',
    ]));

    $org = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => ORG_NAME,
        'url' => abs_url(''),
        'logo' => abs_img(APP_OG_IMAGE),
        'email' => ORG_EMAIL,
        'telephone' => ORG_PHONE,
        'address' => [
            '@type' => 'PostalAddress',
            'addressLocality' => 'Lagos',
            'addressRegion' => 'Lagos',
            'addressCountry' => 'NG',
        ],
        'sameAs' => $social,
    ];

    $website = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => APP_NAME,
        'url' => abs_url(''),
        'description' => APP_DESCRIPTION,
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => abs_url('shop?q={search_term_string}'),
            'query-input' => 'required name=search_term_string',
        ],
    ];

    $blocks = array_merge([$org, $website], $extra);
    $out = [];
    foreach ($blocks as $b) {
        $out[] = $b;
    }
    return $out;
}