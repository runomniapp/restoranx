<?php
$page_title = 'Siparişler';
$page_subtitle = 'Masaya ve adrese gelen siparişleri canlı takip edin';
require_once __DIR__ . '/inc/header.php';

$statuses = getOrderStatuses();
$stats = $order_stats;
?>

<!-- KPI Row -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-bell"></i></div>
        <div>
            <div class="stat-label">Okunmamış Yeni</div>
            <div class="stat-value" data-stat="unseen"><?= $stats['unseen'] ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon tone-info"><i class="fa-solid fa-fire-burner"></i></div>
        <div>
            <div class="stat-label">Aktif Sipariş</div>
            <div class="stat-value" data-stat="active"><?= $stats['active'] ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon tone-purple"><i class="fa-solid fa-receipt"></i></div>
        <div>
            <div class="stat-label">Bugünkü Sipariş</div>
            <div class="stat-value" data-stat="today_count"><?= $stats['today_count'] ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon tone-success"><i class="fa-solid fa-turkish-lira-sign"></i></div>
        <div>
            <div class="stat-label">Bugünkü Ciro</div>
            <div class="stat-value" data-stat="today_revenue"><?= formatPrice($stats['today_revenue']) ?></div>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="orders-toolbar">
        <div class="filter-pills" role="group" aria-label="Sipariş durumu filtresi">
            <button class="filter-pill active" data-filter="all">
                <i class="fa-solid fa-layer-group"></i> Tümü
            </button>
            <?php foreach ($statuses as $key => $st): ?>
                <button class="filter-pill" data-filter="<?= $key ?>">
                    <i class="fa-solid <?= $st['icon'] ?>"></i> <?= $st['label'] ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div style="display:flex; align-items:center; gap:0.6rem; flex-wrap:wrap;">
            <span style="font-size:0.8rem; color:var(--text-muted); font-weight:700; white-space:nowrap;">
                <i class="fa-solid fa-circle" style="color:var(--success); font-size:0.55rem;"></i> Canlı
            </span>
            <button class="btn btn-ghost btn-sm" id="markAllSeenBtn">
                <i class="fa-solid fa-check-double"></i> Tümünü Okundu İşaretle
            </button>
            <button class="btn btn-primary btn-sm" id="refreshOrdersBtn">
                <i class="fa-solid fa-rotate"></i> Yenile
            </button>
        </div>
    </div>

    <div class="orders-grid" id="ordersGrid">
        <div class="table-empty" style="grid-column:1/-1;">
            <i class="fa-solid fa-spinner fa-spin"></i> Siparişler yükleniyor...
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    const API = '../api/orders.php';
    const grid = document.getElementById('ordersGrid');
    const pills = document.querySelectorAll('.filter-pill');

    let currentFilter = 'all';
    let orders = [];

    const STATUS_FLOW = {
        new:       [{ to: 'preparing', label: 'Hazırlamaya Başla', cls: 'btn-primary', icon: 'fa-fire-burner' },
                    { to: 'cancelled', label: 'İptal',             cls: 'btn-ghost',   icon: 'fa-xmark' }],
        preparing: [{ to: 'ready',     label: 'Hazır',             cls: 'btn-primary', icon: 'fa-bell-concierge' },
                    { to: 'cancelled', label: 'İptal',             cls: 'btn-ghost',   icon: 'fa-xmark' }],
        ready:     [{ to: 'completed', label: 'Teslim Edildi',     cls: 'btn-success', icon: 'fa-circle-check' }],
        completed: [],
        cancelled: []
    };

    function esc(str) {
        return String(str == null ? '' : str).replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    }

    function orderTargetLine(o) {
        if (o.type === 'dine_in') return esc(o.table_no || 'Masa');
        return esc(o.name || o.type_label);
    }

    function renderOrderCard(o, isFresh) {
        const actions = (STATUS_FLOW[o.status] || []).map(a =>
            `<button class="btn ${a.cls} btn-sm" data-action="status" data-id="${o.id}" data-to="${a.to}">
                <i class="fa-solid ${a.icon}"></i> ${a.label}
            </button>`
        ).join('');

        const items = o.items.map(i =>
            `<div class="order-item-row">
                <span><span class="order-item-qty">${i.qty}x</span><strong>${esc(i.name)}</strong></span>
                <span>${esc(i.line_total)}</span>
            </div>`
        ).join('');

        const contact = o.type === 'dine_in' ? '' : `
            <div class="order-contact">
                ${o.phone ? `<div><i class="fa-solid fa-phone"></i> <a href="tel:${esc(o.phone)}">${esc(o.phone)}</a></div>` : ''}
                ${o.address ? `<div><i class="fa-solid fa-location-dot"></i> ${esc(o.address)}</div>` : ''}
            </div>`;

        const note = o.note ? `<div class="order-note"><i class="fa-solid fa-pen"></i> ${esc(o.note)}</div>` : '';

        return `
        <article class="order-card ${isFresh ? 'is-fresh' : ''}" data-status="${o.status}" data-id="${o.id}">
            <div class="order-card-head">
                <div>
                    <div class="order-code">${esc(o.code)}</div>
                    <div class="order-target">${orderTargetLine(o)}</div>
                    <div class="order-meta">
                        <span><i class="fa-solid fa-clock"></i> ${esc(o.time)}</span>
                        <span>·</span>
                        <span>${esc(o.type_label)}</span>
                        ${o.is_seen ? '' : '<span class="status-badge accent">YENİ</span>'}
                    </div>
                </div>
                <span class="status-badge" style="background:${o.status_color}1A; color:${o.status_color}; border-color:${o.status_color}66;">
                    <i class="fa-solid ${o.status_icon}"></i> ${esc(o.status_label)}
                </span>
            </div>

            <div class="order-items">${items}</div>
            ${contact}
            ${note}

            <div class="order-total-row">
                <span class="order-total">${esc(o.total_text)}</span>
                <div class="order-actions">${actions}</div>
            </div>
        </article>`;
    }

    function render(freshIds) {
        const list = currentFilter === 'all' ? orders : orders.filter(o => o.status === currentFilter);

        if (list.length === 0) {
            grid.innerHTML = `<div class="table-empty" style="grid-column:1/-1;">
                <i class="fa-solid fa-receipt" style="font-size:1.6rem; display:block; margin-bottom:0.5rem; color:var(--orange-primary);"></i>
                Bu filtreye uygun sipariş bulunmuyor.
            </div>`;
            return;
        }

        grid.innerHTML = list.map(o => renderOrderCard(o, freshIds && freshIds.has(o.id))).join('');
        updatePillCounts();
    }

    function updatePillCounts() {
        pills.forEach(p => {
            const filter = p.getAttribute('data-filter');
            const count = filter === 'all' ? orders.length : orders.filter(o => o.status === filter).length;

            let badge = p.querySelector('.pill-count');
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'pill-count';
                p.appendChild(badge);
            }
            badge.textContent = count;
        });
    }

    async function loadOrders(freshIds) {
        try {
            const res = await fetch(API + '?action=list', { cache: 'no-store' });
            const data = await res.json();
            if (!data.ok) return;

            orders = data.orders;
            render(freshIds);

            if (window.TezgahOrders) window.TezgahOrders.refreshStats(data.stats);
        } catch (err) {
            grid.innerHTML = `<div class="table-empty" style="grid-column:1/-1;">
                Siparişler yüklenemedi. Bağlantınızı kontrol edip yenileyin.
            </div>`;
        }
    }

    async function changeStatus(id, status) {
        try {
            const res = await fetch(API + '?action=status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, status: status })
            });
            const data = await res.json();
            if (!data.ok) return;

            const idx = orders.findIndex(o => o.id === id);
            if (idx > -1) orders[idx] = data.order;

            render();
            if (window.TezgahOrders) window.TezgahOrders.refreshStats(data.stats);
        } catch (err) { /* sessizce geç */ }
    }

    async function markAllSeen() {
        await fetch(API + '?action=seen', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({})
        });
        loadOrders();
    }

    /* Events */
    pills.forEach(pill => {
        pill.addEventListener('click', () => {
            pills.forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            currentFilter = pill.getAttribute('data-filter');
            render();
        });
    });

    grid.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action="status"]');
        if (!btn) return;
        changeStatus(parseInt(btn.dataset.id, 10), btn.dataset.to);
    });

    document.getElementById('refreshOrdersBtn').addEventListener('click', () => loadOrders());
    document.getElementById('markAllSeenBtn').addEventListener('click', markAllSeen);

    // Yeni sipariş bildirimi geldiğinde listeyi tazele ve yeni kartı vurgula
    document.addEventListener('DOMContentLoaded', () => {
        if (window.TezgahOrders) {
            window.TezgahOrders.onNewOrders = (fresh) => {
                loadOrders(new Set(fresh.map(o => o.id)));
            };
        }
        loadOrders();
    });
})();
</script>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
