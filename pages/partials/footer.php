<?php
/**
 * REGC — Shared footer partial.
 * Includes cart drawer, checkout modal, cookie banner and JS.
 */
$config = $config ?? public_config();
$cartConfig = json_encode([
    'delivery_fee' => (float)($config['delivery_fee'] ?? 0),
    'rivo_enabled' => (bool)($config['rivo_enabled'] ?? false),
    'site_whatsapp' => preg_replace('/\D+/', '', (string)($config['site_whatsapp'] ?? '2348166978348')),
]);
$siteWhatsapp = preg_replace('/\D+/', '', (string)($config['site_whatsapp'] ?? '2348166978348'));
if (strlen($siteWhatsapp) === 10) {
    $siteWhatsapp = '234' . $siteWhatsapp;
}
$waDisplay = (string)($config['site_whatsapp'] ?? '0816 697 8348');
?>
    <footer>
        <div class="footer-content">
            <div class="footer-brand">
                <a href="<?= e(abs_url('')) ?>" class="logo">
                    <div class="logo-icon"><span>R</span></div>
                    <div><span class="logo-title"><?= e(APP_NAME) ?></span><span><?= e(APP_TAGLINE) ?></span></div>
                </a>
                <p>Premium foodstuff delivery you can trust. We source fresh produce, pantry essentials, quality oils and exclusive Ramadan food packs — expertly sourced, meticulously packed and delivered nationwide with utmost care.</p>
            </div>
            <div class="footer-links">
                <h3>Quick Links</h3>
                <a href="<?= e(abs_url('')) ?>">Home</a>
                <a href="<?= e(abs_url('shop')) ?>">Shop</a>
                <a href="<?= e(abs_url('privacy')) ?>">Privacy Policy</a>
                <a href="<?= e(abs_url('terms')) ?>">Terms of Service</a>
                <a href="<?= e(abs_url('cookies')) ?>">Cookie Policy</a>
            </div>
            <div class="footer-links">
                <h3>Contact</h3>
                <p>WhatsApp: <?= e($waDisplay) ?></p>
                <p><?= e($config['site_address'] ?? 'Lagos, Nigeria') ?></p>
                <?php if (!empty($config['site_tiktok'])): ?>
                <a href="<?= e($config['site_tiktok']) ?>" target="_blank" rel="noopener">TikTok: @royaleexperienceglobalconcept</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© <?= date('Y') ?> <?= e(APP_NAME) ?>. All Rights Reserved.</p>
            <p>Made with <i class="fa-solid fa-heart"></i> by RayyanTech</p>
        </div>
    </footer>

    <!-- CART DRAWER -->
    <div class="cart-overlay" id="cartOverlay"></div>
    <div class="shopping-cart" id="shoppingCart">
        <div class="cart-header"><h2>Your Cart</h2><button onclick="toggleCart()" aria-label="Close cart"><i class="fa-solid fa-xmark"></i></button></div>
        <div class="cart-items" id="cartItems"><div class="empty-cart"><i class="fa-solid fa-bag-shopping"></i><p>Your cart is empty.</p></div></div>
        <div class="cart-footer">
            <div class="cart-total"><span>Total</span><strong id="cartTotal">₦0</strong></div>
            <button class="checkout-button" onclick="openCheckout()"><i class="fa-solid fa-bag-shopping"></i> Proceed to Checkout</button>
        </div>
    </div>

    <!-- CHECKOUT MODAL -->
    <div class="checkout-overlay" id="checkoutOverlay">
        <div class="checkout-modal">
            <div class="checkout-header"><h2>Checkout</h2><button onclick="closeCheckout()" aria-label="Close checkout"><i class="fa-solid fa-xmark"></i></button></div>
            <div class="checkout-body">
                <div class="checkout-summary" id="checkoutSummary"></div>
                <form id="checkoutForm">
                    <div class="form-group"><input type="text" id="co_name" placeholder="Your Name" required autocomplete="name"></div>
                    <div class="form-group"><input type="email" id="co_email" placeholder="Your Email" required autocomplete="email"></div>
                    <div class="form-group"><input type="tel" id="co_phone" placeholder="Phone Number" required autocomplete="tel"></div>
                    <div class="form-group"><textarea id="co_address" rows="3" placeholder="Delivery Address" required autocomplete="street-address"></textarea></div>
                    <div class="form-group"><textarea id="co_note" rows="2" placeholder="Order note (optional)"></textarea></div>

                    <div class="payment-options">
                        <label class="pay-option">
                            <input type="radio" name="pay_method" value="bank" checked>
                            <div class="pay-box"><i class="fa-solid fa-building-columns"></i><div><strong>Bank Transfer</strong><span>Pay to our account</span></div></div>
                        </label>
                        <?php if (!empty($config['rivo_enabled'])): ?>
                        <label class="pay-option">
                            <input type="radio" name="pay_method" value="rivo">
                            <div class="pay-box"><i class="fa-solid fa-credit-card"></i><div><strong>Pay Online</strong><span>Secure online payment</span></div></div>
                        </label>
                        <?php endif; ?>
                    </div>

                    <div class="bank-details" id="bankDetails">
                        <h4>Payment Details</h4>
                        <p><?= e($config['payment_instructions'] ?? '') ?></p>
                        <div class="bank-line"><span>Bank</span><strong><?= e($config['bank_name']) ?></strong></div>
                        <div class="bank-line"><span>Account Name</span><strong><?= e($config['account_name']) ?></strong></div>
                        <div class="bank-line"><span>Account Number</span><strong class="acc-no" id="accNumber"><?= e($config['account_number']) ?></strong></div>
                        <button type="button" class="copy-btn" onclick="copyAcc()">Copy Account Number</button>
                    </div>

                    <button type="submit" class="checkout-button" id="placeOrderBtn"><i class="fa-solid fa-lock"></i> Place Order · <span id="coTotal">₦0</span></button>
                </form>
                <div id="checkoutMsg" class="checkout-msg"></div>
            </div>
        </div>
    </div>

    <?php if ($siteWhatsapp): ?>
    <a href="https://wa.me/<?= e($siteWhatsapp) ?>" target="_blank" rel="noopener" class="whatsapp-float" aria-label="Chat on WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
    <?php endif; ?>
    <button class="back-to-top" id="backToTop" aria-label="Back to top"><i class="fa-solid fa-arrow-up"></i></button>

    <!-- COOKIE CONSENT -->
    <div class="cookie-banner" id="cookieBanner" role="dialog" aria-label="Cookie consent">
        <p>We use cookies to improve your browsing experience, analyse traffic and remember your cart. Read our <a href="<?= e(abs_url('cookies')) ?>">Cookie Policy</a>.</p>
        <div class="cookie-actions">
            <button class="btn btn-small primary-btn" onclick="acceptCookies()">Accept All</button>
            <button class="btn btn-small secondary-btn" onclick="declineCookies()">Essential Only</button>
        </div>
    </div>

    <script>
        window.PUBLIC_CONFIG = <?= $cartConfig ?>;
        window.BASE_URL = <?= json_encode(APP_BASE_URL) ?>;
    </script>
    <script src="<?= e(abs_url('assets/script.js')) ?>?v=<?= e(asset_version('assets/script.js')) ?>" defer></script>
    <script src="<?= e(abs_url('assets/spa.js')) ?>?v=<?= e(asset_version('assets/spa.js')) ?>" defer></script>
</body>
</html>