/* =========================================
   REGC — SPA engine (progressive enhancement)
   =========================================
   Every URL is still a fully server-rendered, crawlable page (SEO +
   unique URLs). This script enhances navigation: clicks and GET form
   submits are fetched via AJAX and only the <main> content + <head>
   meta are swapped, with history.pushState for back/forward — giving
   the site a smooth, app-like feel without full page reloads.
*/
(function () {
    const app = document.getElementById("app");
    if (!app) return; // not an SPA-capable page

    let busy = false;
    let progressEl = null;

    /* ---------- progress bar ---------- */
    function ensureProgress() {
        if (progressEl) return progressEl;
        progressEl = document.createElement("div");
        progressEl.id = "spaProgress";
        document.body.appendChild(progressEl);
        return progressEl;
    }
    function startLoad() {
        ensureProgress();
        document.body.classList.add("spa-loading");
        requestAnimationFrame(() => { progressEl.style.width = "70%"; });
    }
    function endLoad() {
        progressEl.style.width = "100%";
        setTimeout(() => {
            document.body.classList.remove("spa-loading");
            progressEl.style.width = "0";
        }, 150);
    }

    /* ---------- URL helpers ---------- */
    function internalUrl(urlStr) {
        try {
            const url = new URL(urlStr, location.origin);
            return url.origin === location.origin;
        } catch (e) {
            return false;
        }
    }

    /* ---------- swap head meta ---------- */
    function swapHead(doc) {
        const t = doc.querySelector("title");
        if (t) document.title = t.textContent;

        ["description", "robots", "keywords"].forEach(name => {
            const src = doc.querySelector('meta[name="' + name + '"]');
            let dst = document.querySelector('meta[name="' + name + '"]');
            if (src) {
                if (!dst) { dst = document.createElement("meta"); dst.setAttribute("name", name); document.head.appendChild(dst); }
                dst.setAttribute("content", src.getAttribute("content"));
            } else if (dst) { dst.remove(); }
        });

        // canonical
        const c = doc.querySelector('link[rel="canonical"]');
        let cc = document.querySelector('link[rel="canonical"]');
        if (c) {
            if (!cc) { cc = document.createElement("link"); cc.setAttribute("rel", "canonical"); document.head.appendChild(cc); }
            cc.setAttribute("href", c.getAttribute("href"));
        } else if (cc) { cc.remove(); }

        // OG + Twitter
        document.querySelectorAll('meta[property^="og:"], meta[name^="twitter:"]').forEach(el => el.remove());
        doc.querySelectorAll('meta[property^="og:"], meta[name^="twitter:"]').forEach(el => {
            document.head.appendChild(el.cloneNode(true));
        });

        // JSON-LD structured data
        document.querySelectorAll('script[type="application/ld+json"]').forEach(el => el.remove());
        doc.querySelectorAll('script[type="application/ld+json"]').forEach(el => {
            document.head.appendChild(el.cloneNode(true));
        });

        // theme-color
        const tc = doc.querySelector('meta[name="theme-color"]');
        if (tc) { let d = document.querySelector('meta[name="theme-color"]'); if (!d) { d = document.createElement("meta"); d.setAttribute("name", "theme-color"); document.head.appendChild(d); } d.setAttribute("content", tc.getAttribute("content")); }
    }

    /* ---------- nav active state ---------- */
    function updateActiveNav(url) {
        const path = url.pathname || new URL(url, location.origin).pathname;
        const clean = path.replace(/\/+$/, "") || "/";
        const home = document.querySelector(".nav-home");
        const shop = document.querySelector(".nav-shop");
        const legal = document.querySelector(".nav-legal");
        [home, shop, legal].forEach(a => a && a.classList.remove("active"));
        if (clean === "/") { if (home) home.classList.add("active"); }
        else if (clean.startsWith("/shop") || clean.startsWith("/product")) { if (shop) shop.classList.add("active"); }
        else if (clean.startsWith("/privacy") || clean.startsWith("/terms") || clean.startsWith("/cookies")) { if (legal) legal.classList.add("active"); }
    }

    /* ---------- re-run inline scripts in swapped content ---------- */
    function executeScripts(root) {
        root.querySelectorAll("script").forEach(old => {
            const s = document.createElement("script");
            old.getAttributeNames().forEach(n => s.setAttribute(n, old.getAttribute(n)));
            s.textContent = old.textContent;
            old.replaceWith(s);
        });
    }

    /* ---------- main navigation ---------- */
    async function navigate(href, push = true) {
        if (busy) return;
        busy = true;
        startLoad();

        const target = new URL(href, location.origin);
        target.searchParams.delete("_spa");
        const targetHash = target.hash;

        try {
            const res = await fetch(target.href, {
                headers: { "X-Requested-With": "XMLHttpRequest", "Accept": "text/html" }
            });
            if (!res.ok && res.status !== 404) throw new Error("HTTP " + res.status);
            const html = await res.text();
            const doc = new DOMParser().parseFromString(html, "text/html");
            const next = doc.getElementById("app");

            if (next) {
                app.innerHTML = next.innerHTML;
                swapHead(doc);
                executeScripts(app);
                updateActiveNav(target);
                if (window.updateCart) window.updateCart(); // reflect cart badge (cart is shared)
                if (window.initProductFilter) window.initProductFilter();
            } else {
                // fallback: no #app in response (e.g. partial) — hard nav
                location.href = target.href;
                return;
            }

            if (push) history.pushState({ spa: true }, "", target.pathname + target.search + targetHash);
            if (targetHash) {
                scrollToHash(targetHash);
            } else {
                window.scrollTo({ top: 0, behavior: "instant" });
            }
            closeMobileMenu();
        } catch (err) {
            // network/parse failure → hard navigation (robust fallback)
            location.href = target.href;
            return;
        } finally {
            endLoad();
            busy = false;
        }
    }

    function currentPath() {
        return (location.pathname.replace(/\/+$/, "") || "/");
    }

    function scrollToHash(hash) {
        if (!hash) return;
        const id = decodeURIComponent(hash.slice(1));
        const el = document.getElementById(id);
        if (el) {
            el.scrollIntoView({ behavior: "smooth", block: "start" });
        } else {
            window.scrollTo({ top: 0, behavior: "instant" });
        }
    }

    function closeMobileMenu() {
        const nav = document.getElementById("navMenu");
        if (nav) nav.classList.remove("active");
    }

    /* ---------- intercept internal link clicks ---------- */
    document.addEventListener("click", function (e) {
        const a = e.target.closest("a");
        if (!a) return;
        const href = a.getAttribute("href");
        if (!href) return;
        if (a.target && a.target !== "_self") return;         // _blank etc.
        if (a.hasAttribute("download") || a.hasAttribute("data-nosw")) return;
        if (!internalUrl(href)) return;                       // external links
        if (/^(mailto:|tel:|javascript:)/i.test(href)) return;
        if (a.hasAttribute("data-hard")) return;

        // Pure in-page anchors (href="#about") scroll natively.
        if (href.startsWith("#")) return;

        const url = new URL(href, location.origin);
        const hasHash = url.hash !== "";
        const targetPath = (url.pathname.replace(/\/+$/, "") || "/");

        // Fragment navigation: scroll within the page, or navigate then scroll.
        if (hasHash) {
            e.preventDefault();
            if (targetPath === currentPath()) {
                scrollToHash(url.hash);
                history.replaceState({ spa: true }, "", url.pathname + url.search + url.hash);
            } else {
                navigate(url.pathname + url.search + url.hash);
            }
            return;
        }

        e.preventDefault();
        navigate(href);
    });

    /* ---------- intercept GET form submits (search etc.) ---------- */
    document.addEventListener("submit", function (e) {
        const form = e.target;
        if (!form || !form.hasAttribute("data-spa")) return;
        const method = (form.getAttribute("method") || "get").toLowerCase();
        if (method !== "get") return;
        e.preventDefault();
        const url = new URL(form.action || location.href, location.origin);
        new FormData(form).forEach((v, k) => url.searchParams.set(k, String(v)));
        navigate(url.href);
    });

    /* ---------- back/forward ---------- */
    window.addEventListener("popstate", function () {
        if (busy) return;
        navigate(location.href, false);
    });
})();