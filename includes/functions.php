<?php
// Core Helper Functions for Tezgah Burger

require_once __DIR__ . '/../config/db.php';

// Allergen Master Definitions
function getAllergensList() {
    return [
        'gluten'  => ['name' => 'Gluten (Buğday)', 'icon' => 'fa-wheat-awn', 'color' => '#e3a857'],
        'egg'     => ['name' => 'Yumurta', 'icon' => 'fa-egg', 'color' => '#f1c40f'],
        'milk'    => ['name' => 'Süt & Laktoz', 'icon' => 'fa-cow', 'color' => '#3498db'],
        'nuts'    => ['name' => 'Kuruyemiş / Fıstık', 'icon' => 'fa-seedling', 'color' => '#e67e22'],
        'mustard' => ['name' => 'Hardal', 'icon' => 'fa-jar', 'color' => '#f39c12'],
        'sesame'  => ['name' => 'Susam', 'icon' => 'fa-stroopwafel', 'color' => '#d35400'],
        'fish'    => ['name' => 'Balık / Deniz Ürünleri', 'icon' => 'fa-fish', 'color' => '#2980b9'],
        'soy'     => ['name' => 'Soya', 'icon' => 'fa-leaf', 'color' => '#2ecc71']
    ];
}

function getCategories($only_active = true) {
    $db = getDB();
    $sql = "SELECT * FROM categories " . ($only_active ? "WHERE is_active = 1 " : "") . "ORDER BY sort_order ASC, name ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getProducts($category_id = null, $only_active = true) {
    $db = getDB();
    $sql = "SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id ";
    $where = [];
    $params = [];

    if ($only_active) {
        $where[] = "p.is_active = 1";
    }
    if ($category_id) {
        $where[] = "p.category_id = ?";
        $params[] = $category_id;
    }

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY c.sort_order ASC, p.is_featured DESC, p.name ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getProductById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getCampaigns($only_active = true) {
    $db = getDB();
    $sql = "SELECT * FROM campaigns " . ($only_active ? "WHERE is_active = 1 " : "") . "ORDER BY sort_order ASC, created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getQRSettings() {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM qr_settings WHERE id = 1");
    $stmt->execute();
    $settings = $stmt->fetch();

    if (!$settings) {
        return [
            'id' => 1,
            'fg_color' => '#C87A4B',
            'bg_color' => '#121110',
            'logo_url' => 'assets/images/logo.jpg',
            'header_text' => 'TEZGAH BURGER',
            'subheader_text' => 'Kahramanmaraş\'ın Eşsiz Lezzet Durağı',
            'table_note' => 'Masanıza Hoş Geldiniz - Menümüzü İncelemek İçin QR Kodu Okutunuz',
            'footer_text' => 'Afiyet Olsun! - Instagram: @tezgahburger.ksu'
        ];
    }
    return $settings;
}

function updateQRSettings($data) {
    $db = getDB();
    $sql = "UPDATE qr_settings SET 
            fg_color = ?, 
            bg_color = ?, 
            logo_url = ?, 
            header_text = ?, 
            subheader_text = ?, 
            table_note = ?, 
            footer_text = ? 
            WHERE id = 1";
    $stmt = $db->prepare($sql);
    return $stmt->execute([
        $data['fg_color'],
        $data['bg_color'],
        $data['logo_url'],
        $data['header_text'],
        $data['subheader_text'],
        $data['table_note'],
        $data['footer_text']
    ]);
}

// Bulk Percentage Price Change Engine
function applyBulkPriceUpdate($type, $target_id, $percentage) {
    $db = getDB();
    $percentage = floatval($percentage);
    if ($percentage == 0) return 0;

    $factor = 1 + ($percentage / 100);
    $affected_count = 0;
    $target_name = '';

    if ($type === 'product') {
        $product = getProductById($target_id);
        if ($product) {
            $target_name = $product['name'];
            $stmt = $db->prepare("UPDATE products SET price = ROUND(price * ?, 2) WHERE id = ?");
            $stmt->execute([$factor, $target_id]);
            $affected_count = $stmt->rowCount();
        }
    } elseif ($type === 'category') {
        $stmtCat = $db->prepare("SELECT name FROM categories WHERE id = ?");
        $stmtCat->execute([$target_id]);
        $cat = $stmtCat->fetch();
        if ($cat) {
            $target_name = 'Kategori: ' . $cat['name'];
            $stmt = $db->prepare("UPDATE products SET price = ROUND(price * ?, 2) WHERE category_id = ?");
            $stmt->execute([$factor, $target_id]);
            $affected_count = $stmt->rowCount();
        }
    } elseif ($type === 'global') {
        $target_name = 'Tüm Ürünler (Global Menü Zamı)';
        $stmt = $db->prepare("UPDATE products SET price = ROUND(price * ?, 2)");
        $stmt->execute([$factor]);
        $affected_count = $stmt->rowCount();
    }

    // Log the price change
    if ($affected_count > 0) {
        $logStmt = $db->prepare("INSERT INTO price_logs (change_type, target_name, percentage, affected_count) VALUES (?, ?, ?, ?)");
        $logStmt->execute([$type, $target_name, $percentage, $affected_count]);
    }

    return $affected_count;
}

// =====================================================================
// MENU CARD RENDERER (shared by index.php and qr.php)
// =====================================================================

const TEZGAH_FALLBACK_IMAGE = 'assets/images/food/tezgah_hero_burger_1786727437034.jpg';

// Builds the payload the front-end modal / cart needs for one product.
function buildProductPayload($product) {
    $has_discount = !empty($product['original_price'])
        && floatval($product['original_price']) > floatval($product['price']);

    $discount_pct = $has_discount
        ? round(((floatval($product['original_price']) - floatval($product['price'])) / floatval($product['original_price'])) * 100)
        : 0;

    $discount_label = $has_discount
        ? (!empty($product['discount_tag']) ? $product['discount_tag'] : '%' . $discount_pct . ' İNDİRİM')
        : null;

    return [
        'has_discount'   => $has_discount,
        'discount_pct'   => $discount_pct,
        'discount_label' => $discount_label,
        'image'          => 'public/' . ($product['image'] ?: TEZGAH_FALLBACK_IMAGE),
        'json'           => json_encode([
            'id'                       => intval($product['id']),
            'name'                     => $product['name'],
            'description'              => $product['description'],
            'price'                    => floatval($product['price']),
            'price_formatted'          => formatPrice($product['price']),
            'original_price_formatted' => $has_discount ? formatPrice($product['original_price']) : null,
            'discount_label'           => $discount_label,
            'image'                    => 'public/' . ($product['image'] ?: TEZGAH_FALLBACK_IMAGE),
            'calories'                 => $product['calories'],
            'allergens_html'           => renderAllergenBadges($product['allergens'])
        ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE)
    ];
}

// Large image, price/discount overlaid on the photo, minimal body, single + action.
function renderMenuCard($product, $is_hidden = false) {
    $p = buildProductPayload($product);
    $name = htmlspecialchars($product['name']);
    $search_blob = htmlspecialchars(mb_strtolower($product['name'] . ' ' . ($product['description'] ?? '') . ' ' . ($product['category_name'] ?? ''), 'UTF-8'));

    $meta_parts = [];
    if (!empty($product['category_name'])) $meta_parts[] = htmlspecialchars($product['category_name']);
    if (!empty($product['calories']))      $meta_parts[] = intval($product['calories']) . ' kcal';
    $meta = implode(' &middot; ', $meta_parts);

    ob_start(); ?>
    <article class="menu-card product-card-item reveal-on-scroll active"
             data-category="<?= intval($product['category_id']) ?>"
             data-search="<?= $search_blob ?>"
             style="display: <?= $is_hidden ? 'none' : 'flex' ?>;"
             tabindex="0"
             role="button"
             aria-label="<?= $name ?> detaylarını gör"
             onclick='openProductModal(<?= $p['json'] ?>)'
             onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();this.click();}">

        <div class="menu-card-media">
            <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= $name ?>" loading="lazy">

            <?php if ($p['has_discount']): ?>
                <span class="menu-badge"><i class="fa-solid fa-bolt"></i> <?= htmlspecialchars($p['discount_label']) ?></span>
            <?php elseif (!empty($product['is_featured'])): ?>
                <span class="menu-badge badge-featured"><i class="fa-solid fa-star"></i> Şefin Seçimi</span>
            <?php endif; ?>

            <div class="menu-price-tag <?= $p['has_discount'] ? 'is-discounted' : '' ?>">
                <?php if ($p['has_discount']): ?>
                    <span class="menu-price-old"><?= formatPrice($product['original_price']) ?></span>
                <?php endif; ?>
                <span class="menu-price-new"><?= formatPrice($product['price']) ?></span>
            </div>
        </div>

        <div class="menu-card-body">
            <div class="menu-card-info">
                <h3 class="menu-card-title"><?= $name ?></h3>
                <?php if ($meta): ?>
                    <div class="menu-card-meta"><i class="fa-solid fa-utensils"></i> <?= $meta ?></div>
                <?php endif; ?>
            </div>

            <button type="button" class="menu-add-btn"
                    aria-label="<?= $name ?> ürününü sepete ekle"
                    onclick='addToCart(<?= $p['json'] ?>, event)'>
                <i class="fa-solid fa-plus"></i>
            </button>
        </div>
    </article>
    <?php
    return ob_get_clean();
}

// =====================================================================
// ORDERS MODULE HELPERS
// =====================================================================

function getOrderStatuses() {
    return [
        'new'       => ['label' => 'Yeni Sipariş', 'icon' => 'fa-bell',          'color' => '#FF8A00'],
        'preparing' => ['label' => 'Hazırlanıyor', 'icon' => 'fa-fire-burner',   'color' => '#3B82F6'],
        'ready'     => ['label' => 'Hazır',        'icon' => 'fa-bell-concierge','color' => '#8B5CF6'],
        'completed' => ['label' => 'Teslim Edildi','icon' => 'fa-circle-check',  'color' => '#10B981'],
        'cancelled' => ['label' => 'İptal',        'icon' => 'fa-circle-xmark',  'color' => '#EF4444']
    ];
}

// notification/ klasöründeki ses dosyalarını tarar.
// Klasöre yeni bir dosya atıldığında panelde otomatik listelenir.
function getNotificationSounds() {
    $dir = __DIR__ . '/../notification';
    if (!is_dir($dir)) return [];

    $allowed = ['mp3', 'wav', 'ogg', 'm4a'];
    $sounds = [];

    foreach (scandir($dir) as $file) {
        if ($file === '.' || $file === '..') continue;

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) continue;

        $name = pathinfo($file, PATHINFO_FILENAME);
        $sounds[] = [
            'file'  => $file,
            'label' => is_numeric($name) ? ($name . '. Melodi') : $name
        ];
    }

    // "10.mp3" > "9.mp3" olacak şekilde doğal sıralama
    usort($sounds, function ($a, $b) {
        return strnatcasecmp($a['file'], $b['file']);
    });

    return $sounds;
}

function getDefaultNotificationSound() {
    $sounds = getNotificationSounds();
    if (empty($sounds)) return null;

    // Varsayılan 1 numaralı melodi; yoksa listedeki ilk dosya
    foreach ($sounds as $s) {
        if (pathinfo($s['file'], PATHINFO_FILENAME) === '1') return $s['file'];
    }
    return $sounds[0]['file'];
}

function getOrderTypeLabel($type) {
    $map = [
        'dine_in'  => 'Masa Siparişi',
        'delivery' => 'Adrese Teslim',
        'pickup'   => 'Gel-Al Paket'
    ];
    return $map[$type] ?? 'Sipariş';
}

// Creates an order + its items inside a transaction. Returns the new order row.
function createOrder($data, $items) {
    $db = getDB();

    $order_type = in_array($data['order_type'] ?? '', ['dine_in', 'delivery', 'pickup'], true)
        ? $data['order_type'] : 'dine_in';

    $total = 0;
    $item_count = 0;
    $clean_items = [];

    foreach ($items as $item) {
        $product = isset($item['id']) ? getProductById(intval($item['id'])) : null;
        $qty = max(1, min(50, intval($item['qty'] ?? 1)));

        // Price always comes from the database, never from the client payload.
        if (!$product) continue;

        $unit_price = floatval($product['price']);
        $line_total = $unit_price * $qty;

        $clean_items[] = [
            'product_id'   => $product['id'],
            'product_name' => $product['name'],
            'unit_price'   => $unit_price,
            'qty'          => $qty,
            'line_total'   => $line_total
        ];

        $total += $line_total;
        $item_count += $qty;
    }

    if (empty($clean_items)) {
        return null;
    }

    $order_code = 'TZ' . date('md') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("INSERT INTO orders
            (order_code, order_type, table_no, customer_name, customer_phone, customer_address, note, item_count, total, status, is_seen)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'new', 0)");
        $stmt->execute([
            $order_code,
            $order_type,
            $order_type === 'dine_in' ? mb_substr(trim($data['table_no'] ?? ''), 0, 50) : null,
            mb_substr(trim($data['customer_name'] ?? ''), 0, 120) ?: null,
            mb_substr(trim($data['customer_phone'] ?? ''), 0, 40) ?: null,
            mb_substr(trim($data['customer_address'] ?? ''), 0, 500) ?: null,
            mb_substr(trim($data['note'] ?? ''), 0, 500) ?: null,
            $item_count,
            $total
        ]);

        $order_id = intval($db->lastInsertId());

        $itemStmt = $db->prepare("INSERT INTO order_items
            (order_id, product_id, product_name, unit_price, qty, line_total) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($clean_items as $ci) {
            $itemStmt->execute([$order_id, $ci['product_id'], $ci['product_name'], $ci['unit_price'], $ci['qty'], $ci['line_total']]);
        }

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        return null;
    }

    return getOrderById($order_id);
}

function getOrderById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([intval($id)]);
    $order = $stmt->fetch();
    if (!$order) return null;

    $order['items'] = getOrderItems($order['id']);
    return $order;
}

function getOrderItems($order_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC");
    $stmt->execute([intval($order_id)]);
    return $stmt->fetchAll();
}

// $status: null = all, or one of the keys from getOrderStatuses()
function getOrders($status = null, $limit = 60, $with_items = true) {
    $db = getDB();
    $sql = "SELECT * FROM orders";
    $params = [];

    if ($status && array_key_exists($status, getOrderStatuses())) {
        $sql .= " WHERE status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY created_at DESC, id DESC LIMIT " . max(1, intval($limit));
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    if ($with_items) {
        foreach ($orders as &$o) {
            $o['items'] = getOrderItems($o['id']);
        }
        unset($o);
    }

    return $orders;
}

function updateOrderStatus($id, $status) {
    if (!array_key_exists($status, getOrderStatuses())) return false;
    $db = getDB();
    $stmt = $db->prepare("UPDATE orders SET status = ?, is_seen = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    return $stmt->execute([$status, intval($id)]);
}

function markOrdersSeen($ids = null) {
    $db = getDB();
    if (is_array($ids) && !empty($ids)) {
        $ids = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("UPDATE orders SET is_seen = 1 WHERE id IN ($placeholders)");
        return $stmt->execute($ids);
    }
    return $db->exec("UPDATE orders SET is_seen = 1 WHERE is_seen = 0") !== false;
}

function getOrderStats() {
    $db = getDB();
    $today = date('Y-m-d');

    $unseen = $db->query("SELECT COUNT(*) AS c FROM orders WHERE is_seen = 0 AND status = 'new'")->fetch()['c'];
    $active = $db->query("SELECT COUNT(*) AS c FROM orders WHERE status IN ('new','preparing','ready')")->fetch()['c'];

    $stmt = $db->prepare("SELECT COUNT(*) AS c, COALESCE(SUM(total), 0) AS revenue
                          FROM orders WHERE status != 'cancelled' AND created_at >= ?");
    $stmt->execute([$today . ' 00:00:00']);
    $today_row = $stmt->fetch();

    return [
        'unseen'        => intval($unseen),
        'active'        => intval($active),
        'today_count'   => intval($today_row['c']),
        'today_revenue' => floatval($today_row['revenue'])
    ];
}

function formatPrice($price) {
    return number_format(floatval($price), 2, ',', '.') . ' ₺';
}

function slugify($text) {
    $turkish = ['I', 'ı', 'Ş', 'ş', 'Ğ', 'ğ', 'Ü', 'ü', 'Ö', 'ö', 'Ç', 'ç'];
    $english = ['i', 'i', 's', 's', 'g', 'g', 'u', 'u', 'o', 'o', 'c', 'c'];
    $text = str_replace($turkish, $english, $text);
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

function renderAllergenBadges($allergen_str) {
    if (empty($allergen_str)) return '';
    $list = getAllergensList();
    $items = explode(',', $allergen_str);
    $html = '<div class="allergen-badges-list">';
    foreach ($items as $item) {
        $item = trim($item);
        if (isset($list[$item])) {
            $info = $list[$item];
            $html .= '<span class="allergen-badge" title="' . htmlspecialchars($info['name']) . '" style="border-color: ' . $info['color'] . '; color: ' . $info['color'] . ';">';
            $html .= '<i class="fa-solid ' . $info['icon'] . '"></i> ' . htmlspecialchars($info['name']);
            $html .= '</span>';
        }
    }
    $html .= '</div>';
    return $html;
}
