<?php
$page_title = 'QR & Masa Kartı';
$page_subtitle = 'Masa kartı tasarlayın ve PDF olarak indirin';
require_once __DIR__ . '/inc/header.php';

$db = getDB();
$categories = getCategories(true);
$qr_settings = getQRSettings();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_qr_settings'])) {
        $logo_url = $_POST['existing_logo'];

        // Handle Logo Upload if provided
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../public/uploads/logo/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $filename = 'logo_' . time() . '.' . $ext;
            $target_file = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {
                $logo_url = 'uploads/logo/' . $filename;
            }
        }

        $data = [
            'fg_color' => $_POST['fg_color'],
            'bg_color' => $_POST['bg_color'],
            'logo_url' => $logo_url,
            'header_text' => trim($_POST['header_text']),
            'subheader_text' => trim($_POST['subheader_text']),
            'table_note' => trim($_POST['table_note']),
            'footer_text' => trim($_POST['footer_text'])
        ];

        if (updateQRSettings($data)) {
            $msg = 'QR Menü ve Masa Kartı ayarları başarıyla kaydedildi!';
            $qr_settings = getQRSettings();
        }
    }
}
?>

<div class="card-header-flex">
    <h2 class="card-title"><i class="fa-solid fa-qrcode"></i> QR Menü Tasarımcısı & PDF Masa Kartı Oluşturucu</h2>
    <button type="button" class="btn btn-success" id="exportPdfBtn">
        <i class="fa-solid fa-file-pdf"></i> Masa Kartı PDF İndir
    </button>
</div>

<?php if ($msg): ?>
    <div class="alert alert-success">
        <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(330px, 1fr)); gap: 1.5rem; align-items: start;">
    <!-- Settings Form -->
    <div class="admin-card">
        <h3 style="font-family: var(--font-heading); margin-bottom: 1.25rem; color: var(--gold-accent);">
            QR & Masa Kartı Özelleştirme
        </h3>

        <form action="qr_builder.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="existing_logo" id="currentLogoUrl" value="<?= htmlspecialchars($qr_settings['logo_url']) ?>">

            <div class="form-group">
                <label class="form-label">Kategoriye Özel QR Menü Seçimi</label>
                <select id="qrCategorySelect" class="form-control">
                    <option value="all">🌐 Genel Restoran Menüsü (Tüm Kategoriler)</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>">📁 Kategori: <?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="form-hint">Seçilen kategoriye göre QR kod bağlantısı dinamik güncellenir.</small>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">QR Kod Rengi (Ön Plan)</label>
                    <input type="color" name="fg_color" id="qrFgColor" class="form-control" value="<?= htmlspecialchars($qr_settings['fg_color']) ?>" style="height: 42px; cursor: pointer;">
                </div>

                <div class="form-group">
                    <label class="form-label">Arka Plan Rengi</label>
                    <input type="color" name="bg_color" id="qrBgColor" class="form-control" value="<?= htmlspecialchars($qr_settings['bg_color']) ?>" style="height: 42px; cursor: pointer;">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Logo Yükle (QR Kod Ortası İçin)</label>
                <input type="file" name="logo" id="qrLogoUpload" class="form-control" accept="image/*">
            </div>

            <div class="form-group">
                <label class="form-label">Restoran / Kart Başlığı</label>
                <input type="text" name="header_text" id="qrHeaderText" class="form-control" value="<?= htmlspecialchars($qr_settings['header_text']) ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Alt Slogan / Açıklama</label>
                <input type="text" name="subheader_text" id="qrSubheaderText" class="form-control" value="<?= htmlspecialchars($qr_settings['subheader_text']) ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Masa Notu / Talimatı</label>
                <input type="text" name="table_note" id="qrTableNote" class="form-control" value="<?= htmlspecialchars($qr_settings['table_note']) ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Dipnot / Sosyal Medya Metni</label>
                <input type="text" name="footer_text" id="qrFooterText" class="form-control" value="<?= htmlspecialchars($qr_settings['footer_text']) ?>" required>
            </div>

            <button type="submit" name="save_qr_settings" class="btn btn-primary" style="width: 100%; padding: 0.85rem;">
                <i class="fa-solid fa-save"></i> Ayarları Kaydet
            </button>
        </form>
    </div>

    <!-- Live Preview Table Tent Card -->
    <div class="admin-card">
        <h3 style="font-family: var(--font-heading); margin-bottom: 1.25rem; color: var(--gold-accent); text-align: center;">
            <i class="fa-solid fa-eye"></i> Masa Kartı Canlı Önizleme
        </h3>

        <!-- Printable Table Tent Card Frame -->
        <div id="qrTableTentCard" class="qr-preview-box">
            
            <div style="margin-bottom: 1rem;">
                <img id="previewLogoImg" src="../public/<?= htmlspecialchars($qr_settings['logo_url']) ?>" alt="Logo" style="width: 64px; height: 64px; border-radius: 12px; object-fit: cover; border: 2px solid var(--orange-border); box-shadow: var(--shadow-sm);">
                <h2 id="previewHeader" style="font-family: 'Outfit', sans-serif; font-size: 1.5rem; font-weight: 800; margin-top: 0.5rem; letter-spacing: 1px;">
                    <?= htmlspecialchars($qr_settings['header_text']) ?>
                </h2>
                <p id="previewSubheader" style="font-size: 0.8rem; opacity: 0.85; font-weight: 500;">
                    <?= htmlspecialchars($qr_settings['subheader_text']) ?>
                </p>
            </div>

            <!-- Canvas Container for QR Code -->
            <div class="qr-canvas-container" id="qrCanvasWrapper"></div>

            <div style="margin-top: 1rem; border-top: 1px dashed var(--orange-border); padding-top: 1rem;">
                <p id="previewTableNote" style="font-size: 0.85rem; font-weight: 700; margin-bottom: 0.5rem; line-height: 1.3;">
                    <?= htmlspecialchars($qr_settings['table_note']) ?>
                </p>
                <p id="previewFooter" style="font-size: 0.75rem; opacity: 0.75;">
                    <?= htmlspecialchars($qr_settings['footer_text']) ?>
                </p>
            </div>
        </div>

    </div>
</div>

<script src="../public/assets/js/qr_builder.js"></script>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
