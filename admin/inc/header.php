<?php
// Handle URL trailing slash fix when visiting /admin directly without slash
$uri = $_SERVER['REQUEST_URI'] ?? '';
if ($uri === '/admin') {
    header('Location: /admin/index.php');
    exit;
}

require_once __DIR__ . '/../../includes/functions.php';

$order_stats = getOrderStats();
$notification_sounds = getNotificationSounds();
$default_sound = getDefaultNotificationSound();
$page_title = $page_title ?? 'Yönetim Portalı';
$page_subtitle = $page_subtitle ?? 'Tezgah Burger Kahramanmaraş';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> | Tezgah Burger</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../public/assets/css/admin.css">

    <!-- QR Code & PDF Export Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>
<body>

<div class="admin-layout">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <div style="display: flex; align-items: center; gap: 0.9rem; min-width: 0;">
                <button class="topbar-mobile-toggle" id="sidebarToggleBtn" aria-label="Menüyü aç">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div style="min-width:0;">
                    <h1 class="topbar-title"><?= htmlspecialchars($page_title) ?></h1>
                    <div class="topbar-subtitle"><?= htmlspecialchars($page_subtitle) ?></div>
                </div>
            </div>

            <div class="topbar-actions">
                <div class="alarm-cluster">
                    <button type="button" class="alarm-toggle" id="alarmToggleBtn" aria-pressed="true"
                            title="Yeni sipariş sesli uyarısını aç / kapat">
                        <span class="alarm-dot"></span>
                        <i class="fa-solid fa-bell"></i>
                        <span class="alarm-text">Alarm Açık</span>
                    </button>

                    <?php if (!empty($notification_sounds)): ?>
                        <label class="sr-only" for="alarmSoundSelect">Bildirim sesi seçimi</label>
                        <select class="alarm-sound-select" id="alarmSoundSelect"
                                data-default="<?= htmlspecialchars($default_sound) ?>"
                                title="Yeni sipariş bildirim sesini seçin">
                            <?php foreach ($notification_sounds as $sound): ?>
                                <option value="<?= htmlspecialchars($sound['file']) ?>">
                                    <?= htmlspecialchars($sound['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button type="button" class="alarm-preview-btn" id="alarmPreviewBtn"
                                title="Seçili sesi dinle" aria-label="Seçili bildirim sesini dinle">
                            <i class="fa-solid fa-play"></i>
                        </button>
                    <?php endif; ?>
                </div>

                <a href="orders.php" class="btn btn-navy btn-sm" style="position: relative;">
                    <i class="fa-solid fa-receipt"></i> Siparişler
                    <span class="nav-badge <?= $order_stats['unseen'] > 0 ? 'show' : '' ?>"
                          data-order-badge
                          style="margin-left:0.35rem;"><?= $order_stats['unseen'] ?></span>
                </a>

                <a href="../qr.php" target="_blank" rel="noopener" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-qrcode"></i> Canlı Menü
                </a>
            </div>
        </header>

        <main class="admin-content">
