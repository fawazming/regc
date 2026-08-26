<?php
/**
 * REGC — Shared <head> partial.
 * Expects $seo (array) with options for seo_head(), $config (public_config),
 * and optional $bodyClass.
 */
if (!isset($seo)) {
    $seo = [];
}
$config = $config ?? public_config();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php seo_head($seo); ?>

    <!-- Fonts & icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preload" as="style" href="<?= e(abs_url('assets/style.css')) ?>">

    <!-- Favicon set (served via Cloudinary when configured) -->
    <link rel="icon" href="<?= e(cloudinary_asset('regc/favicon-32', 'png', 'assets/favicon-32x32.png')) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= e(cloudinary_asset('regc/favicon', 'svg', 'assets/favicon.svg')) ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= e(cloudinary_asset('regc/favicon-32', 'png', 'assets/favicon-32x32.png')) ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= e(cloudinary_asset('regc/favicon-16', 'png', 'assets/favicon-16x16.png')) ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= e(cloudinary_asset('regc/apple-touch-icon', 'png', 'assets/apple-touch-icon.png')) ?>">
    <link rel="manifest" href="<?= e(abs_url('assets/site.webmanifest')) ?>">
    <meta name="theme-color" content="#0D1E3F">

    <link rel="stylesheet" href="<?= e(abs_url('assets/style.css')) ?>?v=<?= e(asset_version('assets/style.css')) ?>">
</head>
<body class="<?= e($bodyClass ?? '') ?>">