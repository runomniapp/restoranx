<?php
$page_title = 'Dashboard';
$page_subtitle = 'İşletmenizin anlık durumu';
require_once __DIR__ . '/inc/header.php';

$categories = getCategories(false);
$products = getProducts(null, false);
$campaigns = getCampaigns(false);
$recent_orders = getOrders(null, 6);
$statuses = getOrderStatuses();

$db = getDB();
$logs = $db->query("SELECT * FROM price_logs ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>

<!-- KPI Row -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-bell"></i></div>
        <div>
            <div class="stat-label">Bekleyen Sipariş</div>
            <div class="stat-value" data-stat="unseen"><?= $order_stats['unseen'] ?></div>
            <a href="orders.php" class="stat-link">Siparişlere git <i class="fa-solid fa-arrow-right"></i></a>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon tone-success"><i class="fa-solid fa-turkish-lira-sign"></i></div>
        <div>
            <div class="stat-label">Bugünkü Ciro</div>
            <div class="stat-value" data-stat="today_revenue"><?= formatPrice($order_stats['today_revenue']) ?></div>
            <span class="stat-link" style="color:var(--text-muted);"><?= $order_stats['today_count'] ?> sipariş</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon tone-info"><i class="fa-solid fa-burger"></i></div>
        <div>
            <div class="stat-label">Menüdeki Ürün</div>
            <div class="stat-value"><?= count($products) ?></div>
            <a href="products.php" class="stat-link">Yönet <i class="fa-solid fa-arrow-right"></i></a>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon tone-warning"><i class="fa-solid fa-rectangle-ad"></i></div>
        <div>
            <div class="stat-label">Aktif Kampanya</div>
            <div class="stat-value"><?= count($campaigns) ?></div>
            <a href="campaigns.php" class="stat-link">Yönet <i class="fa-solid fa-arrow-right"></i></a>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon tone-purple"><i class="fa-solid fa-list-check"></i></div>
        <div>
            <div class="stat-label">Kategori</div>
            <div class="stat-value"><?= count($categories) ?></div>
            <a href="categories.php" class="stat-link">Yönet <i class="fa-solid fa-arrow-right"></i></a>
        </div>
    </div>
</div>

<div class="split-grid" style="grid-template-columns: 2fr 1fr;">
    <!-- Son siparişler -->
    <div class="admin-card">
        <div class="card-header-flex">
            <div>
                <h2 class="card-title"><i class="fa-solid fa-receipt"></i> Son Siparişler</h2>
                <div class="card-subtitle">Yeni sipariş geldiğinde bu panelde sesli uyarı alırsınız.</div>
            </div>
            <a href="orders.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-arrow-right"></i> Tümünü Gör</a>
        </div>

        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Kod</th>
                        <th>Hedef</th>
                        <th>Ürünler</th>
                        <th>Tutar</th>
                        <th>Durum</th>
                        <th>Saat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recent_orders)): ?>
                        <?php foreach ($recent_orders as $o):
                            $st = $statuses[$o['status']] ?? $statuses['new'];
                        ?>
                            <tr>
                                <td style="font-weight:800; color:var(--text-navy);"><?= htmlspecialchars($o['order_code']) ?></td>
                                <td>
                                    <strong style="color:var(--text-navy);">
                                        <?= htmlspecialchars($o['order_type'] === 'dine_in' ? $o['table_no'] : ($o['customer_name'] ?: '-')) ?>
                                    </strong><br>
                                    <small style="color:var(--text-muted);"><?= htmlspecialchars(getOrderTypeLabel($o['order_type'])) ?></small>
                                </td>
                                <td><?= intval($o['item_count']) ?> ürün</td>
                                <td style="font-weight:800; color:var(--text-navy);"><?= formatPrice($o['total']) ?></td>
                                <td>
                                    <span class="status-badge" style="background:<?= $st['color'] ?>1A; color:<?= $st['color'] ?>; border-color:<?= $st['color'] ?>66;">
                                        <i class="fa-solid <?= $st['icon'] ?>"></i> <?= $st['label'] ?>
                                    </span>
                                </td>
                                <td style="color:var(--text-muted);"><?= date('H:i', strtotime($o['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="table-empty">Henüz sipariş bulunmuyor.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Hızlı işlemler -->
    <div>
        <div class="admin-card">
            <h2 class="card-title" style="margin-bottom: 1rem;"><i class="fa-solid fa-bolt"></i> Hızlı İşlemler</h2>
            <div style="display: flex; flex-direction: column; gap: 0.65rem;">
                <a href="products.php?action=new" class="btn btn-primary" style="width: 100%;">
                    <i class="fa-solid fa-plus"></i> Yeni Ürün Ekle
                </a>
                <a href="price_update.php" class="btn btn-navy" style="width: 100%;">
                    <i class="fa-solid fa-percent"></i> Menüye Zam Yap
                </a>
                <a href="qr_builder.php" class="btn btn-ghost" style="width: 100%;">
                    <i class="fa-solid fa-print"></i> Masa Kartı PDF
                </a>
                <a href="../qr.php" target="_blank" rel="noopener" class="btn btn-ghost" style="width: 100%;">
                    <i class="fa-solid fa-mobile-screen"></i> Misafir Menüsünü Aç
                </a>
            </div>
        </div>

        <div class="admin-card">
            <h2 class="card-title" style="margin-bottom: 0.85rem;"><i class="fa-solid fa-clock-rotate-left"></i> Son Zam Kayıtları</h2>
            <?php if (!empty($logs)): ?>
                <div style="display:flex; flex-direction:column; gap:0.7rem;">
                    <?php foreach ($logs as $log): ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; gap:0.75rem; padding-bottom:0.7rem; border-bottom:1px solid var(--border-neutral);">
                            <div style="min-width:0;">
                                <div style="font-weight:700; color:var(--text-navy); font-size:0.88rem;"><?= htmlspecialchars($log['target_name']) ?></div>
                                <div style="font-size:0.76rem; color:var(--text-muted);">
                                    <?= date('d.m.Y H:i', strtotime($log['created_at'])) ?> · <?= $log['affected_count'] ?> ürün
                                </div>
                            </div>
                            <span class="status-badge accent">+%<?= number_format($log['percentage'], 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color:var(--text-muted); font-size:0.88rem;">Henüz yüzdesel zam kaydı bulunmuyor.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
