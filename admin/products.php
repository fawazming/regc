<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | <?= e(APP_NAME) ?> Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .modal-overlay { position: fixed; inset: 0; background: rgba(0, 0, 0, .5); display: none; align-items: center; justify-content: center; padding: 20px; z-index: 3000; }
        .modal-overlay.active { display: flex; }
        .modal { background: white; border-radius: 16px; width: min(680px, 100%); max-height: 92vh; overflow-y: auto; padding: 26px; }
        .modal h2 { margin-bottom: 18px; color: #0D1E3F; }
        .modal-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
        .modal-head button { border: none; background: none; font-size: 24px; cursor: pointer; }
        .toggle-row { display: flex; gap: 24px; align-items: center; margin-bottom: 14px; flex-wrap: wrap; }
        .toggle-row label { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; }
        .row-actions { display: flex; gap: 6px; justify-content: flex-end; }
        .mini-btn.danger { background: #fdeaea; color: #a23b3b; }
        .mini-btn.danger:hover { background: #a23b3b; color: #fff; }
        .search-bar { display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
        .search-bar input { flex: 1; min-width: 200px; padding: 10px 14px; border: 1px solid #e3e3e3; border-radius: 9px; font-family: inherit; font-size: 14px; }
        .tab-btns { display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; }
        .tab-btn { border: 1px solid #e3e3e3; background: #fff; padding: 9px 16px; border-radius: 30px; cursor: pointer; font-weight: 600; font-size: 13px; }
        .tab-btn.active { background: #0D1E3F; color: #fff; border-color: #0D1E3F; }
        .count-badge { font-size: 11px; opacity: .7; }
        @media (max-width: 640px) {
            .modal { padding: 18px; }
            .row-actions { flex-direction: column; gap: 6px; }
            .mini-btn { text-align: center; }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="side-brand"><div class="icon">R</div><div><strong><?= e(APP_NAME) ?></strong><span>ADMIN</span></div></div>
        <nav>
            <a href="index.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a href="orders.php"><i class="fa-solid fa-bag-shopping"></i> Orders</a>
            <a href="products.php" class="active"><i class="fa-solid fa-box"></i> Products</a>
            <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
            <a href="https://app.supabase.com" target="_blank"><i class="fa-solid fa-database"></i> Database</a>
        </nav>
        <form method="post" class="logout-form"><input type="hidden" name="action" value="logout"><button type="submit"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</button></form>
    </aside>
    <div class="side-overlay"></div>

    <main class="main">
        <header class="topbar">
            <button class="menu-btn" id="menuBtn" aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
            <h1>Products</h1>
            <button class="btn btn-primary" onclick="openModal()"><i class="fa-solid fa-plus"></i> Add Product</button>
        </header>

        <div class="search-bar">
            <input type="search" id="searchInput" placeholder="Search products..." oninput="render()">
        </div>
        <div class="tab-btns">
            <button class="tab-btn active" data-tab="all" onclick="setTab('all')">All <span class="count-badge" id="cAll"></span></button>
            <button class="tab-btn" data-tab="active" onclick="setTab('active')">Active <span class="count-badge" id="cActive"></span></button>
            <button class="tab-btn" data-tab="inactive" onclick="setTab('inactive')">Inactive <span class="count-badge" id="cInactive"></span></button>
        </div>

        <div class="card">
            <h2 id="listTitle">Products</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th></th><th>Name</th><th>Category</th><th>Price</th><th>Featured</th><th>Stock</th><th style="text-align:right">Actions</th></tr></thead>
                    <tbody id="productsBody">
                        <tr><td colspan="7" style="text-align:center;color:#999">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Product Modal -->
    <div class="modal-overlay" id="modal">
        <div class="modal">
            <div class="modal-head">
                <h2 id="modalTitle">Add Product</h2>
                <button onclick="closeModal()">&times;</button>
            </div>
            <form id="productForm">
                <input type="hidden" name="id" id="f_id">
                <div class="form-grid">
                    <div class="form-group"><label>Name *</label><input name="name" id="f_name" required></div>
                    <div class="form-group"><label>Price (₦) *</label><input name="price" id="f_price" type="number" step="0.01" required></div>
                    <div class="form-group"><label>Old Price (₦)</label><input name="old_price" id="f_old_price" type="number" step="0.01"></div>
                    <div class="form-group"><label>Category</label>
                        <select name="category" id="f_category">
                            <option value="staples">Staples</option>
                            <option value="oils">Oils</option>
                            <option value="produce">Produce</option>
                            <option value="frozen">Frozen</option>
                            <option value="pantry">Pantry</option>
                            <option value="beverages">Beverages</option>
                            <option value="hampers">Hampers</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Badge</label><input name="badge" id="f_badge" placeholder="e.g. BEST SELLER"></div>
                    <div class="form-group"><label>Stock</label><input name="stock" id="f_stock" type="number"></div>
                </div>
                <div class="form-group"><label>Short Description</label><input name="short_description" id="f_short"></div>
                <div class="form-group"><label>Full Description</label><textarea name="description" id="f_description" rows="3"></textarea></div>
                <div class="form-group"><label>Image path (or paste URL)</label><input name="image" id="f_image" placeholder="/products/xxx.jpg or https://..."></div>
                <div class="form-group">
                    <label>Upload image (Cloudinary)</label>
                    <input type="file" id="f_file" accept="image/jpeg,image/png,image/webp,image/gif">
                    <img id="f_preview" alt="Preview" style="max-width:140px;margin-top:10px;border-radius:8px;border:1px solid #e3e3e3;display:none">
                </div>
                <div class="toggle-row">
                    <label><input type="checkbox" name="featured" id="f_featured" value="1"> Featured</label>
                    <label><input type="checkbox" name="active" id="f_active" value="1" checked> Active (visible on store)</label>
                </div>
                <div class="form-row">
                    <button class="btn btn-primary" type="submit">Save Product</button>
                    <button class="btn btn-secondary" type="button" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const CSRF = "<?= e($csrf) ?>";
        let products = [];
        let tab = "all";
        let search = "";

        async function load() {
            try {
                const res = await fetch("api/products.php", { headers: { "X-Requested-With": "XMLHttpRequest" } });
                const data = await res.json();
                if (data.ok) { products = data.products || []; render(); }
                else showToast(data.error || "Failed to load products", "error");
            } catch (e) { showToast("Network error loading products", "error"); }
        }

        function setTab(t) {
            tab = t;
            document.querySelectorAll(".tab-btn").forEach(b => b.classList.toggle("active", b.getAttribute("data-tab") === t));
            render();
        }

        function render() {
            document.getElementById("cAll").textContent = products.length;
            document.getElementById("cActive").textContent = products.filter(p => p.active).length;
            document.getElementById("cInactive").textContent = products.filter(p => !p.active).length;

            const q = search.trim().toLowerCase();
            let list = products;
            if (tab === "active") list = list.filter(p => p.active);
            if (tab === "inactive") list = list.filter(p => !p.active);
            if (q) list = list.filter(p => (p.name || "").toLowerCase().includes(q) || (p.category || "").toLowerCase().includes(q));

            document.getElementById("listTitle").textContent =
                (tab === "active" ? "Active Products" : tab === "inactive" ? "Inactive Products" : "All Products") +
                " (" + list.length + ")";

            const body = document.getElementById("productsBody");
            if (!list.length) {
                body.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#999">No products found.</td></tr>';
                return;
            }
            body.innerHTML = list.map(p => {
                const img = /^https?:\/\//i.test(p.image || "") ? p.image : "../" + (p.image || "/products/garri.jpg").replace(/^\//, "");
                return `<tr>
                    <td><img class="thumb" src="${escHtml(img)}" alt="" loading="lazy"></td>
                    <td><strong>${escHtml(p.name)}</strong>${p.badge ? '<br><small style="color:#999">' + escHtml(p.badge) + '</small>' : ''}</td>
                    <td>${escHtml(p.category)}</td>
                    <td>₦${Number(p.price).toLocaleString()}</td>
                    <td>${p.featured ? 'Yes' : 'No'}</td>
                    <td>${Number(p.stock) || 0}</td>
                    <td><div class="row-actions">
                        <button class="mini-btn" onclick="editProduct(${p.id})"><i class="fa-solid fa-pen"></i> Edit</button>
                        <button class="mini-btn danger" onclick="deleteProduct(${p.id}, ${JSON.stringify(escHtml(p.name))})"><i class="fa-solid fa-trash"></i> Delete</button>
                    </div></td>
                </tr>`;
            }).join("");
        }

        function editProduct(id) {
            const p = products.find(x => x.id === id);
            if (!p) return showToast("Product not found", "error");
            openModal();
            document.getElementById("modalTitle").textContent = "Edit Product";
            document.getElementById("f_id").value = p.id;
            document.getElementById("f_name").value = p.name || "";
            document.getElementById("f_price").value = p.price || "";
            document.getElementById("f_old_price").value = p.old_price || "";
            document.getElementById("f_category").value = p.category || "staples";
            document.getElementById("f_badge").value = p.badge || "";
            document.getElementById("f_stock").value = p.stock || 0;
            document.getElementById("f_short").value = p.short_description || "";
            document.getElementById("f_description").value = p.description || "";
            document.getElementById("f_image").value = p.image || "";
            document.getElementById("f_file").value = "";
            document.getElementById("f_featured").checked = !!p.featured;
            document.getElementById("f_active").checked = p.active !== false;
            const prev = document.getElementById("f_preview");
            if (p.image) {
                prev.src = /^https?:\/\//i.test(p.image) ? p.image : "../" + p.image.replace(/^\//, "");
                prev.style.display = "block";
            } else {
                prev.style.display = "none";
            }
        }

        function openModal() {
            document.getElementById("modalTitle").textContent = "Add Product";
            document.getElementById("productForm").reset();
            document.getElementById("f_id").value = "";
            document.getElementById("f_category").value = "staples";
            document.getElementById("f_active").checked = true;
            document.getElementById("f_stock").value = 0;
            document.getElementById("f_file").value = "";
            document.getElementById("f_preview").style.display = "none";
            document.getElementById("modal").classList.add("active");
        }
        function closeModal() { document.getElementById("modal").classList.remove("active"); }
        document.getElementById("modal").addEventListener("click", function (e) { if (e.target === this) closeModal(); });
        document.getElementById("searchInput").addEventListener("input", function (e) { search = e.target.value; render(); });

        // Live preview of the selected upload
        document.getElementById("f_file").addEventListener("change", function () {
            const f = this.files[0];
            const prev = document.getElementById("f_preview");
            if (!f) { prev.style.display = "none"; return; }
            const reader = new FileReader();
            reader.onload = function (e) { prev.src = e.target.result; prev.style.display = "block"; };
            reader.readAsDataURL(f);
        });

        async function uploadImage(file) {
            const fd = new FormData();
            fd.append("file", file);
            fd.append("csrf_token", CSRF);
            const res = await fetch("api/upload.php", {
                method: "POST",
                headers: { "X-CSRF-Token": CSRF },
                body: fd
            });
            const data = await res.json();
            if (!data.ok) throw new Error(data.error || "Upload failed");
            return data.url;
        }

        document.getElementById("productForm").addEventListener("submit", async function (e) {
            e.preventDefault();
            const btn = this.querySelector("button[type=submit]");
            btn.disabled = true;
            let image = formVal("f_image");
            const file = document.getElementById("f_file").files[0];
            if (file) {
                try {
                    showToast("Uploading image…", "info");
                    image = await uploadImage(file);
                } catch (err) {
                    showToast(err.message, "error");
                    btn.disabled = false;
                    return;
                }
            }
            const payload = {
                id: formVal("f_id"),
                name: formVal("f_name"),
                price: Number(formVal("f_price")),
                old_price: formVal("f_old_price") ? Number(formVal("f_old_price")) : null,
                category: formVal("f_category"),
                badge: formVal("f_badge"),
                stock: Number(formVal("f_stock")) || 0,
                short_description: formVal("f_short"),
                description: formVal("f_description"),
                image: image,
                featured: document.getElementById("f_featured").checked,
                active: document.getElementById("f_active").checked
            };
            const isEdit = !!payload.id;
            try {
                const res = await fetch("api/products.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json", "X-CSRF-Token": CSRF },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.ok) {
                    closeModal();
                    showToast(isEdit ? "Product updated." : "Product created.");
                    load();
                } else {
                    showToast(data.error || "Save failed", "error");
                    btn.disabled = false;
                }
            } catch (err) { showToast("Network error", "error"); btn.disabled = false; }
        });

        function formVal(id) { return (document.getElementById(id).value || "").trim(); }

        async function deleteProduct(id, name) {
            if (!confirm("Delete \"" + name + "\" permanently?")) return;
            try {
                const res = await fetch("api/products.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json", "X-CSRF-Token": CSRF },
                    body: JSON.stringify({ action: "delete", id: id })
                });
                const data = await res.json();
                if (data.ok) { showToast("Product deleted."); load(); }
                else showToast(data.error || "Delete failed", "error");
            } catch (e) { showToast("Network error", "error"); }
        }

        load();
    </script>
    <script src="admin.js"></script>
</body>
</html>