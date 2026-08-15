<?php
// Tezgah Burger - Orders JSON API
// Guest side  : action=create
// Admin side  : action=poll | list | status | seen | detail

require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

function respond($payload, $code = 200) {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function readJsonBody() {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

// Shapes an order row for the client (adds labels + formatted values).
function presentOrder($order) {
    $statuses = getOrderStatuses();
    $status = $statuses[$order['status']] ?? $statuses['new'];

    $items = array_map(function ($i) {
        return [
            'name'       => $i['product_name'],
            'qty'        => intval($i['qty']),
            'unit_price' => formatPrice($i['unit_price']),
            'line_total' => formatPrice($i['line_total'])
        ];
    }, $order['items'] ?? []);

    return [
        'id'           => intval($order['id']),
        'code'         => $order['order_code'],
        'type'         => $order['order_type'],
        'type_label'   => getOrderTypeLabel($order['order_type']),
        'table_no'     => $order['table_no'],
        'name'         => $order['customer_name'],
        'phone'        => $order['customer_phone'],
        'address'      => $order['customer_address'],
        'note'         => $order['note'],
        'item_count'   => intval($order['item_count']),
        'total'        => floatval($order['total']),
        'total_text'   => formatPrice($order['total']),
        'status'       => $order['status'],
        'status_label' => $status['label'],
        'status_color' => $status['color'],
        'status_icon'  => $status['icon'],
        'is_seen'      => intval($order['is_seen']),
        'created_at'   => $order['created_at'],
        'time'         => date('H:i', strtotime($order['created_at'])),
        'date'         => date('d.m.Y', strtotime($order['created_at'])),
        'items'        => $items
    ];
}

try {
    switch ($action) {

        // ---------------------------------------------------------
        // Guest places an order
        // ---------------------------------------------------------
        case 'create':
            if ($method !== 'POST') respond(['ok' => false, 'error' => 'POST bekleniyor.'], 405);

            $body  = readJsonBody();
            $items = $body['items'] ?? [];

            if (!is_array($items) || count($items) === 0) {
                respond(['ok' => false, 'error' => 'Sepetiniz boş görünüyor.'], 422);
            }
            if (count($items) > 40) {
                respond(['ok' => false, 'error' => 'Tek siparişte en fazla 40 farklı ürün gönderilebilir.'], 422);
            }

            $type = $body['order_type'] ?? 'dine_in';

            if ($type === 'dine_in' && trim($body['table_no'] ?? '') === '') {
                respond(['ok' => false, 'error' => 'Lütfen masa numaranızı seçin.'], 422);
            }
            if ($type !== 'dine_in') {
                if (trim($body['customer_name'] ?? '') === '' || trim($body['customer_phone'] ?? '') === '') {
                    respond(['ok' => false, 'error' => 'Ad soyad ve telefon numarası zorunludur.'], 422);
                }
                if ($type === 'delivery' && trim($body['customer_address'] ?? '') === '') {
                    respond(['ok' => false, 'error' => 'Adrese teslim için teslimat adresi zorunludur.'], 422);
                }
            }

            $order = createOrder($body, $items);
            if (!$order) {
                respond(['ok' => false, 'error' => 'Sipariş kaydedilemedi. Lütfen tekrar deneyin.'], 500);
            }

            respond(['ok' => true, 'order' => presentOrder($order)]);

        // ---------------------------------------------------------
        // Admin polls for new orders since a known id
        // ---------------------------------------------------------
        case 'poll':
            $since = intval($_GET['since'] ?? 0);
            $db = getDB();

            $stmt = $db->prepare("SELECT * FROM orders WHERE id > ? ORDER BY id ASC LIMIT 20");
            $stmt->execute([$since]);
            $rows = $stmt->fetchAll();

            $fresh = [];
            foreach ($rows as $row) {
                $row['items'] = getOrderItems($row['id']);
                $fresh[] = presentOrder($row);
            }

            $maxRow = $db->query("SELECT COALESCE(MAX(id), 0) AS m FROM orders")->fetch();

            respond([
                'ok'         => true,
                'new_orders' => $fresh,
                'last_id'    => intval($maxRow['m']),
                'stats'      => getOrderStats()
            ]);

        // ---------------------------------------------------------
        // Admin order list (optionally filtered)
        // ---------------------------------------------------------
        case 'list':
            $status = $_GET['status'] ?? null;
            if ($status === 'all' || $status === '') $status = null;

            $orders = array_map('presentOrder', getOrders($status, 80));
            respond(['ok' => true, 'orders' => $orders, 'stats' => getOrderStats()]);

        case 'detail':
            $order = getOrderById(intval($_GET['id'] ?? 0));
            if (!$order) respond(['ok' => false, 'error' => 'Sipariş bulunamadı.'], 404);
            respond(['ok' => true, 'order' => presentOrder($order)]);

        // ---------------------------------------------------------
        // Admin updates a status
        // ---------------------------------------------------------
        case 'status':
            if ($method !== 'POST') respond(['ok' => false, 'error' => 'POST bekleniyor.'], 405);
            $body = readJsonBody();

            $id     = intval($body['id'] ?? 0);
            $status = $body['status'] ?? '';

            if (!updateOrderStatus($id, $status)) {
                respond(['ok' => false, 'error' => 'Durum güncellenemedi.'], 422);
            }

            respond(['ok' => true, 'order' => presentOrder(getOrderById($id)), 'stats' => getOrderStats()]);

        // ---------------------------------------------------------
        // Admin acknowledges the alarm
        // ---------------------------------------------------------
        case 'seen':
            if ($method !== 'POST') respond(['ok' => false, 'error' => 'POST bekleniyor.'], 405);
            $body = readJsonBody();
            markOrdersSeen($body['ids'] ?? null);
            respond(['ok' => true, 'stats' => getOrderStats()]);

        default:
            respond(['ok' => false, 'error' => 'Geçersiz istek.'], 400);
    }
} catch (Exception $e) {
    respond(['ok' => false, 'error' => 'Sunucu hatası oluştu.'], 500);
}
