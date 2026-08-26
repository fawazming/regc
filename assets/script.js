/* =========================================
   REGC - ROYALE EXPERIENCE GLOBAL CONCEPT (storefront JS)
   ========================================= */

/* MOBILE MENU */
const menuButton = document.getElementById("menuButton");
const navMenu = document.getElementById("navMenu");
menuButton.addEventListener("click", () => navMenu.classList.toggle("active"));

document.querySelectorAll("nav a").forEach(link => {
    link.addEventListener("click", () => navMenu.classList.remove("active"));
});

/* =========================================
   CART (persisted in localStorage)
   ========================================= */
let cart = loadCart();

function loadCart() {
    try { return JSON.parse(localStorage.getItem("regc_cart")) || []; }
    catch (e) { return []; }
}
function saveCart() { localStorage.setItem("regc_cart", JSON.stringify(cart)); }

function addToCart(id, name, price, image, qty) {
    const q = Math.max(1, parseInt(qty, 10) || 1);
    const existing = cart.find(item => item.id === id);
    if (existing) { existing.quantity += q; }
    else { cart.push({ id, name, price, image, quantity: q }); }
    saveCart();
    updateCart();
    showNotification(name + " added to your cart!");
    document.getElementById("cartOverlay").classList.add("active");
    document.getElementById("shoppingCart").classList.add("active");
}

/* Product page quantity stepper */
let _pdQty = 1;
function stepQty(d) {
    const el = document.getElementById("pdQty");
    if (!el) return;
    _pdQty = Math.max(1, _pdQty + d);
    el.textContent = _pdQty;
}

function updateCart() {
    const container = document.getElementById("cartItems");
    const count = document.getElementById("cartCount");
    const totalEl = document.getElementById("cartTotal");

    container.innerHTML = "";
    if (cart.length === 0) {
        container.innerHTML = '<div class="empty-cart"><i class="fa-solid fa-bag-shopping"></i><p>Your cart is empty.</p></div>';
    } else {
        cart.forEach((item, index) => {
            const el = document.createElement("div");
            el.className = "cart-item";
            el.innerHTML = `
                <div class="cart-item-info">
                    <img class="cart-item-thumb" src="${item.image || ''}" alt="">
                    <div class="cart-item-details">
                        <h4>${escapeHtml(item.name)}</h4>
                        <p>&#8358;${Number(item.price).toLocaleString()}</p>
                        <div class="quantity-controls">
                            <button onclick="changeQuantity(${index}, -1)">-</button>
                            <span>${item.quantity}</span>
                            <button onclick="changeQuantity(${index}, 1)">+</button>
                        </div>
                    </div>
                </div>
                <button class="remove-item" onclick="removeItem(${index})"><i class="fa-solid fa-trash"></i></button>`;
            container.appendChild(el);
        });
    }

    const totalQty = cart.reduce((s, i) => s + i.quantity, 0);
    const totalPrice = cart.reduce((s, i) => s + i.price * i.quantity, 0);
    count.textContent = totalQty;
    totalEl.textContent = "₦" + totalPrice.toLocaleString();
    updateCheckoutSummary();
}

function changeQuantity(index, change) {
    cart[index].quantity += change;
    if (cart[index].quantity <= 0) cart.splice(index, 1);
    saveCart();
    updateCart();
}
function removeItem(index) {
    cart.splice(index, 1);
    saveCart();
    updateCart();
}
function clearCart() { cart = []; saveCart(); updateCart(); }

function toggleCart() {
    document.getElementById("shoppingCart").classList.toggle("active");
    document.getElementById("cartOverlay").classList.toggle("active");
}
document.getElementById("cartOverlay").addEventListener("click", toggleCart);

/* =========================================
   CHECKOUT
   ========================================= */
function cartTotal() { return cart.reduce((s, i) => s + i.price * i.quantity, 0); }
const deliveryFee = Number(PUBLIC_CONFIG.delivery_fee || 0);

function openCheckout() {
    if (cart.length === 0) { showNotification("Your cart is empty."); return; }
    document.getElementById("checkoutOverlay").classList.add("active");
    updateCheckoutSummary();
}
function closeCheckout() {
    document.getElementById("checkoutOverlay").classList.remove("active");
    document.getElementById("checkoutMsg").innerHTML = "";
}
document.getElementById("checkoutOverlay").addEventListener("click", function (e) {
    if (e.target === this) closeCheckout();
});

function updateCheckoutSummary() {
    const el = document.getElementById("checkoutSummary");
    const totalEl = document.getElementById("coTotal");
    const total = cartTotal() + deliveryFee;
    if (!el) return;
    let items = "";
    cart.forEach(i => { items += `<div class="cs-row"><span>${escapeHtml(i.name)} × ${i.quantity}</span><span>₦${(i.price * i.quantity).toLocaleString()}</span></div>`; });
    el.innerHTML = items
        + `<div class="cs-row"><span>Delivery</span><span>₦${deliveryFee.toLocaleString()}</span></div>`
        + `<div class="cs-total"><span>Total</span><span>₦${total.toLocaleString()}</span></div>`;
    if (totalEl) totalEl.textContent = "₦" + total.toLocaleString();
}

/* Toggle bank details visibility with payment method */
document.addEventListener("change", function (e) {
    if (e.target.name === "pay_method") {
        const bank = e.target.value === "bank";
        document.getElementById("bankDetails").style.display = bank ? "block" : "none";
    }
});

function copyAcc() {
    const acc = document.getElementById("accNumber").textContent;
    navigator.clipboard.writeText(acc).then(() => showNotification("Account number copied!"));
}

/* Place order */
const checkoutForm = document.getElementById("checkoutForm");
checkoutForm.addEventListener("submit", async function (e) {
    e.preventDefault();
    if (cart.length === 0) return;

    const btn = document.getElementById("placeOrderBtn");
    const msg = document.getElementById("checkoutMsg");
    msg.className = "checkout-msg info";
    msg.textContent = "Placing your order...";
    btn.disabled = true;

    const method = document.querySelector('input[name="pay_method"]:checked').value;

    try {
        const res = await fetch(BASE_URL + "/api/checkout.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                name: document.getElementById("co_name").value,
                email: document.getElementById("co_email").value,
                phone: document.getElementById("co_phone").value,
                address: document.getElementById("co_address").value,
                note: document.getElementById("co_note").value,
                payment_method: method,
                items: cart.map(i => ({ id: i.id, quantity: i.quantity }))
            })
        });
        const data = await res.json();

        if (!data.ok) {
            msg.className = "checkout-msg error";
            msg.textContent = data.error || "Could not place order.";
            btn.disabled = false;
            return;
        }

        clearCart();
        closeCheckout();

        if (data.authorization_url) {
            // Rivo online payment
            msg.className = "checkout-msg info";
            msg.textContent = "Redirecting to secure payment...";
            window.location.href = data.authorization_url;
            return;
        }

        // Bank transfer flow: show order success + account details
        document.getElementById("checkoutOverlay").classList.add("active");
        document.getElementById("checkoutForm").style.display = "none";
        document.getElementById("checkoutMsg").className = "checkout-msg success";
        document.getElementById("checkoutMsg").innerHTML =
            "<strong>Order placed successfully!</strong><br>Order No: <span class='order-no'>" + escapeHtml(data.order_no) + "</span>" +
            "<br><br>Kindly transfer <strong>₦" + Number(data.total).toLocaleString() + "</strong> to:" +
            "<br><strong>" + escapeHtml(data.bank.account_name) + "</strong>" +
            "<br>" + escapeHtml(data.bank.bank_name) + " — <strong>" + escapeHtml(data.bank.account_number) + "</strong>" +
            "<br><br>A confirmation email has been sent to you.";
        btn.disabled = false;
    } catch (err) {
        msg.className = "checkout-msg error";
        msg.textContent = "Network error. Please try again.";
        btn.disabled = false;
    }
});

/* =========================================
   ORDER TRACKING (present only on track pages)
   ========================================= */
const trackForm = document.getElementById("trackForm");
if (trackForm) {
trackForm.addEventListener("submit", async function (e) {
    e.preventDefault();
    const no = document.getElementById("trackOrderNo").value.trim();
    const result = document.getElementById("trackResult");
    result.innerHTML = '<p style="color:var(--light-text)">Checking...</p>';

    try {
        const res = await fetch(BASE_URL + "/api/order_status.php?order_no=" + encodeURIComponent(no));
        const data = await res.json();
        if (!data.ok || !data.order) {
            result.innerHTML = '<div class="track-card"><h3>Not found</h3><p>' + escapeHtml(data.error || "Order not found.") + "</p></div>";
            return;
        }
        const o = data.order;
        const statusClass = o.status.toLowerCase();
        result.innerHTML =
            '<div class="track-card">' +
            '<h3>Order ' + escapeHtml(o.order_no) + '</h3>' +
            '<span class="track-status ' + statusClass + '">' + escapeHtml(o.status) + '</span> ' +
            '<span class="track-status ' + o.payment_status.toLowerCase() + '">Payment: ' + escapeHtml(o.payment_status) + '</span>' +
            '<div class="track-meta">' +
            '<div>Name: <strong>' + escapeHtml(o.name) + '</strong></div>' +
            '<div>Total: <strong>₦' + Number(o.total).toLocaleString() + '</strong></div>' +
            '<div>Placed: <strong>' + escapeHtml(new Date(o.created_at).toLocaleString()) + '</strong></div>' +
            '</div></div>';
    } catch (err) {
        result.innerHTML = '<p style="color:var(--light-text)">Could not check order. Please try again.</p>';
    }
});
}

/* =========================================
   CONTACT FORM (WhatsApp) — delegated so it survives SPA swaps
   ========================================= */
document.addEventListener("submit", function (event) {
    const form = event.target.closest(".contact-form");
    if (!form) return;
    event.preventDefault();
    const name = form.querySelector("#name").value;
    const phone = form.querySelector("#phone").value;
    const message = form.querySelector("#message").value;
    const waNumber = (window.PUBLIC_CONFIG && window.PUBLIC_CONFIG.site_whatsapp) ? window.PUBLIC_CONFIG.site_whatsapp : "2348166978348";
    const msg = "Hello REGC!%0A%0A*New Customer Message*%0A%0A*Name:* " + name + "%0A*Phone:* " + phone + "%0A%0A*Message:* " + message;
    window.open("https://wa.me/" + waNumber + "?text=" + encodeURIComponent(msg), "_blank");
    form.reset();
});

/* =========================================
   PRODUCT FILTER (homepage ui-style grid)
   ========================================= */
function initProductFilter() {
    const buttons = document.querySelectorAll(".filter-btn");
    const cards = document.querySelectorAll("#productGrid .product-card");
    if (!buttons.length || !cards.length) return;

    buttons.forEach(btn => {
        btn.addEventListener("click", function () {
            buttons.forEach(b => b.classList.remove("active"));
            btn.classList.add("active");
            const filter = btn.getAttribute("data-filter");
            cards.forEach(card => {
                const cat = card.getAttribute("data-category") || "";
                const feat = card.getAttribute("data-featured") === "1";
                let show = filter === "all";
                if (filter === "featured") show = feat;
                else if (["staples", "oils", "produce", "frozen", "pantry", "beverages", "hampers"].indexOf(filter) !== -1) show = cat === filter;
                card.style.display = show ? "" : "none";
            });
        });
    });
}
initProductFilter();
window.initProductFilter = initProductFilter;

/* =========================================
   COOKIE CONSENT
   ========================================= */
(function () {
    const banner = document.getElementById("cookieBanner");
    if (!banner) return;
    const choice = localStorage.getItem("regc_cookies");
    if (choice) return; // already decided
    setTimeout(() => banner.classList.add("show"), 1200);
})();
function acceptCookies() {
    localStorage.setItem("regc_cookies", "all");
    hideCookie();
}
function declineCookies() {
    localStorage.setItem("regc_cookies", "essential");
    hideCookie();
}
function hideCookie() {
    const b = document.getElementById("cookieBanner");
    if (b) b.classList.remove("show");
}

/* =========================================
   BACK TO TOP
   ========================================= */
const backToTop = document.getElementById("backToTop");
window.addEventListener("scroll", () => {
    backToTop.classList.toggle("show", window.scrollY > 500);
});
backToTop.addEventListener("click", () => window.scrollTo({ top: 0, behavior: "smooth" }));

/* ACTIVE NAVIGATION */
const navLinks = document.querySelectorAll("nav a");
window.addEventListener("scroll", () => {
    let current = "";
    document.querySelectorAll("section[id]").forEach(section => {
        if (scrollY >= section.offsetTop - 150) current = section.getAttribute("id");
    });
    navLinks.forEach(link => {
        link.classList.toggle("active", link.getAttribute("href") === "#" + current);
    });
});

/* NOTIFICATION */
function showNotification(message) {
    let n = document.querySelector(".notification");
    if (!n) { n = document.createElement("div"); n.className = "notification"; document.body.appendChild(n); }
    n.textContent = message;
    n.style.opacity = "1";
    clearTimeout(n._t);
    n._t = setTimeout(() => { n.style.opacity = "0"; }, 2500);
}

function escapeHtml(s) {
    return String(s ?? "").replace(/[&<>"']/g, c => ({
        "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
    }[c]));
}

/* Init */
updateCart();