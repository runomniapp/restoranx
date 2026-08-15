<?php
$page_title = 'Yüzdesel Zam';
$page_subtitle = 'Ürün, kategori veya tüm menüye toplu fiyat güncellemesi';
require_once __DIR__ . '/inc/header.php';

$db = getDB();
$categories = getCategories(true);
$products = getProducts(null, true);
$msg = '';
$msg_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['apply_price_change'])) {
        $change_type = $_POST['change_type']; // 'product', 'category', 'global'
        $percentage = floatval($_POST['percentage']);
        $target_id = 0;

        if ($change_type === 'product') {
            $target_id = intval($_POST['target_product_id']);
        } elseif ($change_type === 'category') {
            $target_id = intval($_POST['target_category_id']);
        }

        if ($percentage == 0) {
            $msg = 'Lütfen geçerli bir yüzdesel oran giriniz!';
            $msg_type = 'error';
        } else {
            $count = applyBulkPriceUpdate($change_type, $target_id, $percentage);
            if ($count > 0) {
                $msg = "Başarılı! Toplam {$count} adet ürünün fiyatına %{$percentage} oranında zam uygulandı.";
                $msg_type = 'success';
                // Refresh products list
                $products = getProducts(null, true);
            } else {
                $msg = 'Zam uygulanacak uygun ürün bulunamadı veya işlem başarısız oldu.';
                $msg_type = 'error';
            }
        }
    }
}
?>

<div class="card-header-flex">
    <h2 class="card-title"><i class="fa-solid fa-percent"></i> Yüzdesel Zam Yapma Aracı</h2>
</div>

<?php if ($msg): ?>
    <div class="alert <?= $msg_type === 'success' ? 'alert-success' : 'alert-error' ?>">
        <i class="fa-solid <?= $msg_type === 'success' ? 'fa-check-circle' : 'fa-triangle-exclamation' ?>"></i> <?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>

<div class="split-grid">
    <!-- Price Change Form -->
    <div class="admin-card">
        <h3 style="font-family: var(--font-heading); margin-bottom: 1rem; color: var(--gold-accent);">
            Zam Parametreleri
        </h3>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem;">
            Aşağıdaki form üzerinden seçtiğiniz kapsama yüzdesel zam (% artış) uygulayabilirsiniz.
        </p>

        <form action="price_update.php" method="POST" onsubmit="return confirm('Seçilen kapsama yüzdesel zam uygulanacaktır. Onaylıyor musunuz?')">
            
            <div class="form-group">
                <label class="form-label">Zam Kapsamı *</label>
                <select name="change_type" id="changeType" class="form-control" onchange="toggleScopeInputs()" required>
                    <option value="global">🔥 TÜM MENÜYE (Tüm Ürünlere Zam)</option>
                    <option value="category">📁 KATEGORİ BAZLI ZAM</option>
                    <option value="product">🍔 ÜRÜN BAZLI ZAM</option>
                </select>
            </div>

            <!-- Category Scope Selector -->
            <div class="form-group" id="categoryScopeGroup" style="display: none;">
                <label class="form-label">Hedef Kategori *</label>
                <select name="target_category_id" id="targetCategorySelect" class="form-control" onchange="updateSimulation()">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Product Scope Selector -->
            <div class="form-group" id="productScopeGroup" style="display: none;">
                <label class="form-label">Hedef Ürün *</label>
                <select name="target_product_id" id="targetProductSelect" class="form-control" onchange="updateSimulation()">
                    <?php foreach ($products as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= formatPrice($p['price']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Zam Oranı (%) *</label>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="number" step="0.5" min="0.5" max="500" name="percentage" id="percentageInput" class="form-control" value="10" required oninput="updateSimulation()">
                    <span style="font-weight: 800; font-size: 1.2rem; color: var(--gold-accent);">%</span>
                </div>
                <small class="form-hint">Örn: 10 yazarsanız tüm fiyatlar %10 artacaktır.</small>
            </div>

            <button type="submit" name="apply_price_change" class="btn btn-primary" style="width: 100%; padding: 0.85rem; margin-top: 1rem;">
                <i class="fa-solid fa-percent"></i> Yüzdesel Zamı Uygula
            </button>
        </form>
    </div>

    <!-- Live Simulation Preview Table -->
    <div class="admin-card">
        <h3 style="font-family: var(--font-heading); margin-bottom: 1rem; color: var(--gold-accent);">
            <i class="fa-solid fa-calculator"></i> Zam Simülasyonu & Önizleme Tablosu
        </h3>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem;">
            Girilen zam oranına göre oluşacak tahmini yeni fiyatlar aşağıda simüle edilmektedir.
        </p>

        <div class="table-responsive">
            <table class="admin-table" id="simTable">
                <thead>
                    <tr>
                        <th>Ürün Adı</th>
                        <th>Kategori</th>
                        <th>Mevcut Fiyat</th>
                        <th>Yeni Fiyat (Tahmini)</th>
                        <th>Fark (₺)</th>
                    </tr>
                </thead>
                <tbody id="simTableBody">
                    <!-- Populated via JS -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const allProductsList = <?= json_encode($products) ?>;

function toggleScopeInputs() {
    const type = document.getElementById('changeType').value;
    document.getElementById('categoryScopeGroup').style.display = (type === 'category') ? 'block' : 'none';
    document.getElementById('productScopeGroup').style.display = (type === 'product') ? 'block' : 'none';
    updateSimulation();
}

function updateSimulation() {
    const type = document.getElementById('changeType').value;
    const percentage = parseFloat(document.getElementById('percentageInput').value) || 0;
    const factor = 1 + (percentage / 100);
    const tbody = document.getElementById('simTableBody');
    tbody.innerHTML = '';

    const catId = parseInt(document.getElementById('targetCategorySelect').value);
    const prodId = parseInt(document.getElementById('targetProductSelect').value);

    let count = 0;

    allProductsList.forEach(p => {
        let match = false;
        if (type === 'global') {
            match = true;
        } else if (type === 'category' && parseInt(p.category_id) === catId) {
            match = true;
        } else if (type === 'product' && parseInt(p.id) === prodId) {
            match = true;
        }

        if (match) {
            count++;
            const currentPrice = parseFloat(p.price);
            const newPrice = Math.round(currentPrice * factor * 100) / 100;
            const diff = Math.round((newPrice - currentPrice) * 100) / 100;

            tbody.innerHTML += `
                <tr>
                    <td style="font-weight: 700;">${p.name}</td>
                    <td>${p.category_name}</td>
                    <td>${currentPrice.toFixed(2)} ₺</td>
                    <td style="font-weight: 800; color: var(--success);">${newPrice.toFixed(2)} ₺</td>
                    <td style="color: var(--gold-accent); font-weight: 700;">+${diff.toFixed(2)} ₺</td>
                </tr>
            `;
        }
    });

    if (count === 0) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:var(--text-muted);">Eşleşen ürün bulunamadı.</td></tr>`;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    updateSimulation();
});
</script>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
