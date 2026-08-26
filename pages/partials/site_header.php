<?php
/**
 * REGC — Shared site header (top bar + nav).
 * Persistent chrome in the SPA: only the <main> content changes.
 * Expects $activeNav ('home'|'shop'|'legal') and $config.
 */
$config = $config ?? public_config();
$activeNav = $activeNav ?? '';
?>
    <!-- TOP BAR -->
    <div class="top-bar">
        <p><i class="fa-solid fa-truck-fast"></i> Premium Foodstuff Delivery <span>•</span> We shop. We pack. We deliver. You relax.</p>
    </div>

    <!-- NAV -->
    <header class="site-header">
        <div class="navbar">
            <a href="<?= e(abs_url('')) ?>" class="logo" data-spa-link>
                <div class="logo-icon"><span>R</span></div>
                <div><span class="logo-title"><?= e(APP_NAME) ?></span><span><?= e(APP_TAGLINE) ?></span></div>
            </a>
            <nav id="navMenu" aria-label="Main navigation">
                <a href="<?= e(abs_url('')) ?>" data-spa-link class="nav-home <?= $activeNav === 'home' ? 'active' : '' ?>">Home</a>
                <a href="<?= e(abs_url('shop')) ?>" data-spa-link class="nav-shop <?= $activeNav === 'shop' ? 'active' : '' ?>">Shop</a>
                <a href="<?= e(abs_url('')) ?>#products">Products</a>
                <a href="<?= e(abs_url('')) ?>#about">About Us</a>
                <a href="<?= e(abs_url('')) ?>#contact">Contact</a>
                <a href="<?= e(abs_url('privacy')) ?>" data-spa-link class="nav-legal <?= $activeNav === 'legal' ? 'active' : '' ?>">Policy</a>
            </nav>
            <div class="nav-actions">
                <button class="cart-button" onclick="toggleCart()" aria-label="Open cart">
                    <i class="fa-solid fa-bag-shopping"></i><span class="cart-count" id="cartCount">0</span>
                </button>
                <button class="menu-button" id="menuButton" aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
            </div>
        </div>
    </header>