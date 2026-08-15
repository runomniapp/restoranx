<?php
$page_title = 'Kategoriler';
$page_subtitle = 'Menü kategorilerini düzenleyin ve sıralayın';
require_once __DIR__ . '/inc/header.php';

$db = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_category'])) {
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $icon = trim($_POST['icon']) ?: 'fa-burger';
        $sort_order = intval($_POST['sort_order']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $slug = slugify($name);

        if ($id > 0) {
            $stmt = $db->prepare("UPDATE categories SET name=?, slug=?, icon=?, sort_order=?, is_active=? WHERE id=?");
            $stmt->execute([$name, $slug, $icon, $sort_order, $is_active, $id]);
            $msg = 'Kategori güncellendi.';
        } else {
            $stmt = $db->prepare("INSERT INTO categories (name, slug, icon, sort_order, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $slug, $icon, $sort_order, $is_active]);
            $msg = 'Yeni kategori eklendi.';
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $del_id = intval($_GET['id']);
    if ($del_id > 0) {
        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$del_id]);
        $msg = 'Kategori silindi.';
    }
}

$categories = getCategories(false);
?>

<div class="card-header-flex">
    <h2 class="card-title"><i class="fa-solid fa-list-check"></i> Kategori Yönetimi</h2>
</div>

<?php if ($msg): ?>
    <div class="alert alert-success">
        <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>

<div class="split-grid">
    <!-- Category Form -->
    <div class="admin-card">
        <h3 style="font-family: var(--font-heading); margin-bottom: 1.25rem; color: var(--gold-accent);">Kategori Ekle / Düzenle</h3>
        
        <form action="categories.php" method="POST">
            <input type="hidden" name="id" id="catId" value="0">

            <div class="form-group">
                <label class="form-label">Kategori Adı *</label>
                <input type="text" name="name" id="catName" class="form-control" required placeholder="örn: Smash Burgerler">
            </div>

            <div class="form-group">
                <label class="form-label">FontAwesome Simgesi</label>
                <input type="text" name="icon" id="catIcon" class="form-control" placeholder="fa-burger" value="fa-burger">
                <small class="form-hint">Örn: fa-burger, fa-drumstick-bite, fa-wine-glass</small>
            </div>

            <div class="form-group">
                <label class="form-label">Sıralama Numarası</label>
                <input type="number" name="sort_order" id="catSort" class="form-control" value="1">
            </div>

            <div class="form-group">
                <label class="check-row">
                    <input type="checkbox" name="is_active" id="catActive" value="1" checked> Aktif (Menüde Göster)
                </label>
            </div>

            <button type="submit" name="save_category" class="btn btn-primary" style="width: 100%;">
                <i class="fa-solid fa-save"></i> Kategoriyi Kaydet
            </button>
        </form>
    </div>

    <!-- Category List -->
    <div class="admin-card">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Sıra</th>
                        <th>Simge</th>
                        <th>Kategori Adı</th>
                        <th>Durum</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $c): ?>
                        <tr>
                            <td><?= $c['sort_order'] ?></td>
                            <td><i class="fa-solid <?= htmlspecialchars($c['icon']) ?>" style="color: var(--copper-primary); font-size: 1.2rem;"></i></td>
                            <td style="font-weight: 700;"><?= htmlspecialchars($c['name']) ?></td>
                            <td>
                                <span class="status-badge <?= $c['is_active'] ? 'active' : 'inactive' ?>">
                                    <?= $c['is_active'] ? 'AKTİF' : 'PASİF' ?>
                                </span>
                            </td>
                            <td>
                                <button onclick="editCat(<?= htmlspecialchars(json_encode($c)) ?>)" class="btn btn-primary btn-sm"><i class="fa-solid fa-pen"></i></button>
                                <a href="categories.php?action=delete&id=<?= $c['id'] ?>" onclick="return confirm('Kategoriyi silmek istediğinize emin misiniz?')" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function editCat(cat) {
    document.getElementById('catId').value = cat.id;
    document.getElementById('catName').value = cat.name;
    document.getElementById('catIcon').value = cat.icon || 'fa-burger';
    document.getElementById('catSort').value = cat.sort_order || 1;
    document.getElementById('catActive').checked = (cat.is_active == 1);
}
</script>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
