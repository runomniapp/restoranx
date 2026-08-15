<?php
$page_title = 'Kampanyalar';
$page_subtitle = 'İndirim ve kampanya duyurularını yönetin';
require_once __DIR__ . '/inc/header.php';

$db = getDB();
$categories = getCategories(true);
$products = getProducts(null, true);
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_campaign'])) {
        $id = intval($_POST['id']);
        $title = trim($_POST['title']);
        $subtitle = trim($_POST['subtitle']);
        $discount_percentage = !empty($_POST['discount_percentage']) ? floatval($_POST['discount_percentage']) : null;
        $target_type = $_POST['target_type'];
        $target_id = !empty($_POST['target_id']) ? intval($_POST['target_id']) : null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $image_path = $_POST['existing_image'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../public/uploads/campaigns/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'camp_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $target_file = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_path = 'uploads/campaigns/' . $filename;
            }
        }

        if ($id > 0) {
            $sql = "UPDATE campaigns SET title=?, subtitle=?, image=?, discount_percentage=?, target_type=?, target_id=?, is_active=? WHERE id=?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$title, $subtitle, $image_path, $discount_percentage, $target_type, $target_id, $is_active, $id]);
            $msg = 'Kampanya güncellendi.';
        } else {
            $sql = "INSERT INTO campaigns (title, subtitle, image, discount_percentage, target_type, target_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$title, $subtitle, $image_path, $discount_percentage, $target_type, $target_id, $is_active]);
            $msg = 'Yeni kampanya eklendi.';
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $del_id = intval($_GET['id']);
    if ($del_id > 0) {
        $stmt = $db->prepare("DELETE FROM campaigns WHERE id = ?");
        $stmt->execute([$del_id]);
        $msg = 'Kampanya silindi.';
    }
}

$campaigns = getCampaigns(false);
?>

<div class="card-header-flex">
    <h2 class="card-title"><i class="fa-solid fa-rectangle-ad"></i> Kampanya Yönetimi</h2>
</div>

<?php if ($msg): ?>
    <div class="alert alert-success">
        <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>

<div class="split-grid">
    <!-- Campaign Form -->
    <div class="admin-card">
        <h3 style="font-family: var(--font-heading); margin-bottom: 1.25rem; color: var(--gold-accent);">Kampanya Ekle / Düzenle</h3>
        
        <form action="campaigns.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" id="campId" value="0">
            <input type="hidden" name="existing_image" id="campExistingImage" value="assets/images/food/tezgah_hero_burger_1786727437034.jpg">

            <div class="form-group">
                <label class="form-label">Kampanya Başlığı *</label>
                <input type="text" name="title" id="campTitle" class="form-control" required placeholder="örn: Haftanın Özel Tezgah Fırsatı!">
            </div>

            <div class="form-group">
                <label class="form-label">Alt Açıklama</label>
                <input type="text" name="subtitle" id="campSubtitle" class="form-control" placeholder="örn: Tezgah Special Burger alana 2. İçecek Hediye!">
            </div>

            <div class="form-group">
                <label class="form-label">İndirim Oranı (%)</label>
                <input type="number" step="1" name="discount_percentage" id="campDiscount" class="form-control" placeholder="20">
            </div>

            <div class="form-group">
                <label class="form-label">İlişkili Ürün / Kategori Tipi</label>
                <select name="target_type" id="campTargetType" class="form-control" onchange="toggleTargetSelect()">
                    <option value="all">Tüm Menüde Geçerli</option>
                    <option value="category">Belirli Bir Kategoriye Özel</option>
                    <option value="product">Belirli Bir Ürüne Özel</option>
                </select>
            </div>

            <div class="form-group" id="targetIdGroup" style="display: none;">
                <label class="form-label">Hedef Seçimi</label>
                <select name="target_id" id="campTargetId" class="form-control">
                    <!-- Populated dynamically via JS -->
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Kampanya Görseli</label>
                <input type="file" name="image" class="form-control" accept="image/*">
            </div>

            <div class="form-group">
                <label class="check-row">
                    <input type="checkbox" name="is_active" id="campActive" value="1" checked> Aktif (Hero Slider'da Göster)
                </label>
            </div>

            <button type="submit" name="save_campaign" class="btn btn-primary" style="width: 100%;">
                <i class="fa-solid fa-save"></i> Kampanyayı Kaydet
            </button>
        </form>
    </div>

    <!-- Campaign List -->
    <div class="admin-card">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Görsel</th>
                        <th>Başlık</th>
                        <th>İndirim</th>
                        <th>Hedef</th>
                        <th>Durum</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($campaigns as $camp): ?>
                        <tr>
                            <td>
                                <img src="../public/<?= htmlspecialchars($camp['image'] ?: 'assets/images/food/tezgah_hero_burger_1786727437034.jpg') ?>" class="thumb-sm" alt="">
                            </td>
                            <td style="font-weight: 700;">
                                <?= htmlspecialchars($camp['title']) ?>
                                <br><small style="color: var(--text-muted); font-weight: normal;"><?= htmlspecialchars($camp['subtitle']) ?></small>
                            </td>
                            <td style="font-weight: 800; color: var(--gold-accent);">
                                <?= $camp['discount_percentage'] ? '%' . intval($camp['discount_percentage']) : '-' ?>
                            </td>
                            <td>
                                <span class="status-badge accent">
                                    <?= strtoupper($camp['target_type']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?= $camp['is_active'] ? 'active' : 'inactive' ?>">
                                    <?= $camp['is_active'] ? 'AKTİF' : 'PASİF' ?>
                                </span>
                            </td>
                            <td>
                                <a href="campaigns.php?action=delete&id=<?= $camp['id'] ?>" onclick="return confirm('Kampanyayı silmek istediğinize emin misiniz?')" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($campaigns)): ?>
                        <tr><td colspan="6" class="table-empty">Henüz kampanya eklenmemiş. Soldaki formdan ilk kampanyanızı oluşturabilirsiniz.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const categoriesList = <?= json_encode($categories) ?>;
const productsList = <?= json_encode($products) ?>;

function toggleTargetSelect() {
    const type = document.getElementById('campTargetType').value;
    const group = document.getElementById('targetIdGroup');
    const select = document.getElementById('campTargetId');
    select.innerHTML = '';

    if (type === 'category') {
        group.style.display = 'block';
        categoriesList.forEach(c => {
            select.innerHTML += `<option value="${c.id}">${c.name}</option>`;
        });
    } else if (type === 'product') {
        group.style.display = 'block';
        productsList.forEach(p => {
            select.innerHTML += `<option value="${p.id}">${p.name}</option>`;
        });
    } else {
        group.style.display = 'none';
    }
}
</script>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
