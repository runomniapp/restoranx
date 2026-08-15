<?php
$page_title = 'Ürün Yönetimi';
$page_subtitle = 'Menü ürünleri, fiyatlar ve indirim rozetleri';
require_once __DIR__ . '/inc/header.php';

$db = getDB();
$categories = getCategories(false);
$allergens_list = getAllergensList();

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$edit_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$msg = '';

// Handle Product Save / Update / Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_product'])) {
        $id = intval($_POST['id']);
        $category_id = intval($_POST['category_id']);
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $original_price = !empty($_POST['original_price']) ? floatval($_POST['original_price']) : null;
        $discount_tag = trim($_POST['discount_tag']);
        $calories = !empty($_POST['calories']) ? intval($_POST['calories']) : null;
        $spiciness = intval($_POST['spiciness']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        
        $allergens_arr = isset($_POST['allergens']) ? $_POST['allergens'] : [];
        $allergens_str = implode(',', $allergens_arr);

        $image_path = $_POST['existing_image'];

        // Handle Image Upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../public/uploads/products/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'prod_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $target_file = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_path = 'uploads/products/' . $filename;
            }
        }

        $slug = slugify($name);

        if ($id > 0) {
            // Update
            $sql = "UPDATE products SET category_id=?, name=?, slug=?, description=?, price=?, original_price=?, discount_tag=?, image=?, allergens=?, spiciness=?, calories=?, is_active=?, is_featured=? WHERE id=?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$category_id, $name, $slug, $description, $price, $original_price, $discount_tag, $image_path, $allergens_str, $spiciness, $calories, $is_active, $is_featured, $id]);
            $msg = 'Ürün başarıyla güncellendi!';
        } else {
            // Insert
            $sql = "INSERT INTO products (category_id, name, slug, description, price, original_price, discount_tag, image, allergens, spiciness, calories, is_active, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$category_id, $name, $slug, $description, $price, $original_price, $discount_tag, $image_path, $allergens_str, $spiciness, $calories, $is_active, $is_featured]);
            $msg = 'Yeni ürün başarıyla eklendi!';
        }
        $action = 'list';
    }
}

if ($action === 'delete' && $edit_id > 0) {
    $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$edit_id]);
    $msg = 'Ürün silindi.';
    $action = 'list';
}

$editing_product = null;
if ($action === 'edit' && $edit_id > 0) {
    $editing_product = getProductById($edit_id);
}

$products = getProducts(null, false);
?>

<div class="card-header-flex">
    <h2 class="card-title"><i class="fa-solid fa-burger"></i> Ürün Yönetimi & Kampanyalı İndirimler</h2>
    <?php if ($action === 'list'): ?>
        <a href="products.php?action=new" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Yeni Ürün Ekle</a>
    <?php else: ?>
        <a href="products.php" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Listeye Dön</a>
    <?php endif; ?>
</div>

<?php if ($msg): ?>
    <div class="alert alert-success">
        <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>

<?php if ($action === 'new' || $action === 'edit'): ?>
    <div class="admin-card">
        <h3 style="font-family: var(--font-heading); margin-bottom: 1.25rem; color: var(--gold-accent);">
            <?= $editing_product ? 'Ürünü Düzenle' : 'Yeni Ürün Oluştur' ?>
        </h3>

        <form action="products.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $editing_product['id'] ?? 0 ?>">
            <input type="hidden" name="existing_image" value="<?= htmlspecialchars($editing_product['image'] ?? 'assets/images/food/tezgah_hero_burger_1786727437034.jpg') ?>">

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Kategori *</label>
                    <select name="category_id" class="form-control" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($editing_product['category_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Ürün Adı *</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editing_product['name'] ?? '') ?>" required placeholder="örn: Tezgah Special Double Smash Burger">
                </div>

                <div class="form-group">
                    <label class="form-label">Satış Fiyatı (₺) *</label>
                    <input type="number" step="0.01" name="price" class="form-control" value="<?= $editing_product['price'] ?? '' ?>" required placeholder="340.00">
                </div>

                <div class="form-group">
                    <label class="form-label">Önceki / İndirimsiz Fiyat (Üstü Çizili)</label>
                    <input type="number" step="0.01" name="original_price" class="form-control" value="<?= $editing_product['original_price'] ?? '' ?>" placeholder="400.00 (Opsiyonel)">
                </div>

                <div class="form-group">
                    <label class="form-label">Kampanya / Damping İndirim Rozeti</label>
                    <input type="text" name="discount_tag" class="form-control" value="<?= htmlspecialchars($editing_product['discount_tag'] ?? '') ?>" placeholder="örn: %15 DAMPİNG veya FIRSAT">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Açıklama & İçindekiler</label>
                <textarea name="description" class="form-control" rows="3" placeholder="200gr Dana Kaburga, Karamelize Soğan, Cheddar..."><?= htmlspecialchars($editing_product['description'] ?? '') ?></textarea>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Ürün Görseli</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>

                <div class="form-group">
                    <label class="form-label">Kalori (kcal)</label>
                    <input type="number" name="calories" class="form-control" value="<?= $editing_product['calories'] ?? '' ?>" placeholder="850">
                </div>

                <div class="form-group">
                    <label class="form-label">Acılık Seviyesi</label>
                    <select name="spiciness" class="form-control">
                        <option value="0" <?= ($editing_product['spiciness'] ?? 0) == 0 ? 'selected' : '' ?>>Tatlı / Acısız</option>
                        <option value="1" <?= ($editing_product['spiciness'] ?? 0) == 1 ? 'selected' : '' ?>>Hafif Acılı</option>
                        <option value="2" <?= ($editing_product['spiciness'] ?? 0) == 2 ? 'selected' : '' ?>>Orta Acılı</option>
                        <option value="3" <?= ($editing_product['spiciness'] ?? 0) == 3 ? 'selected' : '' ?>>Çok Acı (Jalapeno/Habanero)</option>
                    </select>
                </div>
            </div>

            <!-- Allergen Checklist -->
            <div class="form-group">
                <label class="form-label" style="color: var(--copper-primary);"><i class="fa-solid fa-triangle-exclamation"></i> Alerjen Uyarıları</label>
                <div class="check-grid">
                    <?php 
                    $selected_allergens = explode(',', $editing_product['allergens'] ?? '');
                    foreach ($allergens_list as $key => $al): 
                    ?>
                        <label class="check-row" style="font-size: 0.85rem;">
                            <input type="checkbox" name="allergens[]" value="<?= $key ?>" <?= in_array($key, $selected_allergens) ? 'checked' : '' ?>>
                            <i class="fa-solid <?= $al['icon'] ?>" style="color: <?= $al['color'] ?>;"></i> <?= $al['name'] ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display: flex; gap: 1.5rem; margin-top: 1.5rem;">
                <label class="check-row">
                    <input type="checkbox" name="is_active" value="1" <?= ($editing_product['is_active'] ?? 1) == 1 ? 'checked' : '' ?>>
                    Yayında (Menüde Görünsün)
                </label>
                <label class="check-row">
                    <input type="checkbox" name="is_featured" value="1" <?= ($editing_product['is_featured'] ?? 0) == 1 ? 'checked' : '' ?>>
                    Öne Çıkarılan Ürün (Ana Sayfada Göster)
                </label>
            </div>

            <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                <button type="submit" name="save_product" class="btn btn-primary"><i class="fa-solid fa-save"></i> Kaydet</button>
                <a href="products.php" class="btn btn-ghost">İptal</a>
            </div>
        </form>
    </div>
<?php else: ?>
    <div class="admin-card">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Görsel</th>
                        <th>Ürün Adı</th>
                        <th>Kategori</th>
                        <th>Fiyat & İndirim</th>
                        <th>Alerjenler</th>
                        <th>Durum</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td>
                                <img src="../public/<?= htmlspecialchars($p['image'] ?: 'assets/images/food/tezgah_hero_burger_1786727437034.jpg') ?>" class="thumb-sm" alt="">
                            </td>
                            <td style="font-weight: 700;">
                                <?= htmlspecialchars($p['name']) ?>
                                <?php if ($p['is_featured']): ?>
                                    <span class="status-badge accent" style="font-size: 0.65rem; margin-left: 0.3rem;">ÖNE ÇIKAN</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($p['category_name']) ?></td>
                            <td>
                                <span style="font-weight: 800; color: var(--gold-accent);"><?= formatPrice($p['price']) ?></span>
                                <?php if ($p['original_price']): ?>
                                    <br><small style="text-decoration: line-through; color: var(--text-muted);"><?= formatPrice($p['original_price']) ?></small>
                                    <?php if ($p['discount_tag']): ?>
                                        <span class="status-badge inactive" style="font-size: 0.65rem; margin-left: 0.2rem;"><?= htmlspecialchars($p['discount_tag']) ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td><?= renderAllergenBadges($p['allergens']) ?></td>
                            <td>
                                <span class="status-badge <?= $p['is_active'] ? 'active' : 'inactive' ?>">
                                    <?= $p['is_active'] ? 'YAYINDA' : 'PASİF' ?>
                                </span>
                            </td>
                            <td>
                                <a href="products.php?action=edit&id=<?= $p['id'] ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-pen"></i></a>
                                <a href="products.php?action=delete&id=<?= $p['id'] ?>" onclick="return confirm('Bu ürünü silmek istediğinize emin misiniz?')" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
