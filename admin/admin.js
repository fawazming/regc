/* =========================================
   REGC — Admin shared JS (toast + mobile menu)
   ========================================= */
(function () {
    // Mobile sidebar toggle
    const menuBtn = document.getElementById("menuBtn");
    const sidebar = document.querySelector(".sidebar");
    const overlay = document.querySelector(".side-overlay");
    if (menuBtn && sidebar) {
        menuBtn.addEventListener("click", function (e) {
            e.stopPropagation();
            sidebar.classList.toggle("open");
            if (overlay) overlay.classList.toggle("open");
        });
    }
    if (overlay) {
        overlay.addEventListener("click", function () {
            sidebar.classList.remove("open");
            overlay.classList.remove("open");
        });
    }
    document.addEventListener("click", function (e) {
        if (sidebar && sidebar.classList.contains("open") && !sidebar.contains(e.target) && e.target !== menuBtn) {
            sidebar.classList.remove("open");
            if (overlay) overlay.classList.remove("open");
        }
    });
})();

/** Show a small toast notification. type: 'success' | 'error' | 'info' */
function showToast(msg, type) {
    type = type || "success";
    const colors = { success: "#0D1E3F", error: "#a23b3b", info: "#1B3B70" };
    let t = document.getElementById("adminToast");
    if (!t) {
        t = document.createElement("div");
        t.id = "adminToast";
        t.style.cssText = "position:fixed;top:18px;right:18px;z-index:99999;color:#fff;padding:14px 20px;border-radius:10px;font-size:14px;font-weight:600;box-shadow:0 10px 30px rgba(0,0,0,.2);opacity:0;transform:translateY(-8px);transition:.3s;max-width:90vw";
        document.body.appendChild(t);
    }
    t.textContent = msg;
    t.style.background = colors[type] || colors.success;
    t.style.opacity = "1";
    t.style.transform = "translateY(0)";
    clearTimeout(t._t);
    t._t = setTimeout(function () {
        t.style.opacity = "0";
        t.style.transform = "translateY(-8px)";
    }, 3000);
}

function escHtml(s) {
    return String(s == null ? "" : s).replace(/[&<>"']/g, function (c) {
        return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
    });
}