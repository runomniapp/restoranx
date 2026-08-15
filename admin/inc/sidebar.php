<?php
$current_page = basename($_SERVER['PHP_SELF']);
$unseen = $order_stats['unseen'] ?? 0;

$nav_groups = [
    'İŞLETME' => [
        ['file' => 'index.php',        'icon' => 'fa-chart-line',   'label' => 'Dashboard'],
        ['file' => 'orders.php',       'icon' => 'fa-receipt',      'label' => 'Siparişler', 'badge' => true],
    ],
    'MENÜ YÖNETİMİ' => [
        ['file' => 'products.php',     'icon' => 'fa-burger',       'label' => 'Ürünler'],
        ['file' => 'categories.php',   'icon' => 'fa-list-check',   'label' => 'Kategoriler'],
        ['file' => 'campaigns.php',    'icon' => 'fa-rectangle-ad', 'label' => 'Kampanyalar'],
        ['file' => 'price_update.php', 'icon' => 'fa-percent',      'label' => 'Yüzdesel Zam'],
    ],
    'ARAÇLAR' => [
        ['file' => 'qr_builder.php',   'icon' => 'fa-qrcode',       'label' => 'QR & Masa Kartı'],
    ]
];
?>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <img src="../public/assets/images/logo.jpg" alt="Tezgah Burger logosu">
        <div>
            <div class="sidebar-title">TEZGAH BURGER</div>
            <div class="sidebar-sub">Yönetim Paneli</div>
        </div>
    </div>

    <nav class="sidebar-nav" aria-label="Panel menüsü">
        <?php foreach ($nav_groups as $group_label => $items): ?>
            <div class="sidebar-group-label"><?= $group_label ?></div>
            <?php foreach ($items as $item):
                $is_active = $current_page === $item['file'];
            ?>
                <a href="<?= $item['file'] ?>"
                   class="nav-item-link <?= $is_active ? 'active' : '' ?>"
                   <?= $is_active ? 'aria-current="page"' : '' ?>>
                    <i class="fa-solid <?= $item['icon'] ?>"></i>
                    <span><?= $item['label'] ?></span>
                    <?php if (!empty($item['badge'])): ?>
                        <span class="nav-badge <?= $unseen > 0 ? 'show' : '' ?>" data-order-badge><?= $unseen ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="../index.php" target="_blank" rel="noopener">
            <i class="fa-solid fa-arrow-up-right-from-square"></i> Siteyi Görüntüle
        </a>
    </div>
</aside>
