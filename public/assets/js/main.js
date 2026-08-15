// Tezgah Burger - Cart State Engine, Menu Filtering & Order Submission
// Works on both index.php (ana sayfa) and qr.php (dijital menü)

let cartState = JSON.parse(localStorage.getItem('tezgah_cart') || '[]');

// Service mode: 'dine_in' (masa), 'delivery' (adrese teslim), 'pickup' (gel-al)
let serviceMode = 'dine_in';
let selectedTable = window.TEZGAH_PRESET_TABLE || 'Masa 1';

const API_ORDERS = (window.TEZGAH_API_BASE || '') + 'api/orders.php';

document.addEventListener('DOMContentLoaded', () => {
    updateCartUI();
    initHeaderScroll();
    initMobileNav();
    initRevealObserver();
    initCategoryTabs();
    initSearch();
    initModals();
    initServiceMode();
});

/* ------------------------------------------------------------------ */
/* Layout & navigation                                                 */
/* ------------------------------------------------------------------ */

function initHeaderScroll() {
    const header = document.getElementById('mainHeader') || document.querySelector('.glass-header');
    if (!header) return;

    window.addEventListener('scroll', () => {
        header.classList.toggle('scrolled', window.scrollY > 30);
    }, { passive: true });
}

function initMobileNav() {
    const toggleBtn = document.getElementById('mobileToggleBtn');
    const navMenu = document.getElementById('navMenu');
    if (!toggleBtn || !navMenu) return;

    const setIcon = (open) => {
        const icon = toggleBtn.querySelector('i');
        if (!icon) return;
        icon.classList.toggle('fa-bars', !open);
        icon.classList.toggle('fa-xmark', open);
    };

    toggleBtn.addEventListener('click', () => {
        const open = navMenu.classList.toggle('active');
        setIcon(open);
    });

    navMenu.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', () => {
            navMenu.classList.remove('active');
            setIcon(false);
        });
    });
}

function initRevealObserver() {
    const revealElements = document.querySelectorAll('.reveal-on-scroll');
    if (!revealElements.length) return;

    if (!('IntersectionObserver' in window)) {
        revealElements.forEach(el => el.classList.add('active'));
        return;
    }

    const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('active');
                obs.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -30px 0px' });

    revealElements.forEach(el => observer.observe(el));
}

/* ------------------------------------------------------------------ */
/* Menu filtering & search                                             */
/* ------------------------------------------------------------------ */

let activeCategory = 'all';
let activeQuery = '';

function getMenuCards() {
    return document.querySelectorAll('.product-card-item');
}

function applyMenuFilters() {
    let visible = 0;

    getMenuCards().forEach(card => {
        const matchesCategory = activeCategory === 'all' || card.dataset.category === activeCategory;
        const haystack = (card.dataset.search || card.textContent).toLowerCase();
        const matchesQuery = activeQuery === '' || haystack.includes(activeQuery);
        const show = matchesCategory && matchesQuery;

        card.style.display = show ? 'flex' : 'none';
        if (show) visible++;
    });

    const emptyState = document.getElementById('menuEmptyState');
    if (emptyState) emptyState.style.display = visible === 0 ? 'block' : 'none';
}

function initCategoryTabs() {
    const tabBtns = document.querySelectorAll('.tab-btn');
    if (!tabBtns.length) return;

    const preselected = document.querySelector('.tab-btn.active');
    if (preselected) activeCategory = preselected.getAttribute('data-category') || 'all';

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            tabBtns.forEach(b => {
                b.classList.remove('active');
                b.setAttribute('aria-selected', 'false');
            });
            btn.classList.add('active');
            btn.setAttribute('aria-selected', 'true');

            activeCategory = btn.getAttribute('data-category') || 'all';
            applyMenuFilters();
        });
    });

    applyMenuFilters();
}

function initSearch() {
    const searchInput = document.getElementById('menuSearchInput');
    if (!searchInput) return;

    searchInput.addEventListener('input', (e) => {
        activeQuery = e.target.value.toLowerCase().trim();
        applyMenuFilters();
    });
}

/* ------------------------------------------------------------------ */
/* Cart engine                                                         */
/* ------------------------------------------------------------------ */

function toNumber(val) {
    return typeof val === 'number' ? val : (parseFloat(val) || 0);
}

function addToCart(product, event) {
    if (event) {
        event.stopPropagation();

        const btn = event.currentTarget;
        if (btn && btn.classList && btn.classList.contains('menu-add-btn')) {
            btn.classList.add('just-added');
            setTimeout(() => btn.classList.remove('just-added'), 450);
        }
    }

    const existing = cartState.find(item => item.id === product.id);
    if (existing) {
        existing.qty += 1;
    } else {
        cartState.push({
            id: product.id,
            name: product.name,
            price: toNumber(product.price),
            image: product.image || 'public/assets/images/food/tezgah_hero_burger_1786727437034.jpg',
            qty: 1
        });
    }

    saveCart();
    updateCartUI();
    showToast('"' + product.name + '" sepete eklendi!');
}

function changeQty(productId, delta) {
    const item = cartState.find(i => i.id === productId);
    if (!item) return;

    item.qty += delta;
    if (item.qty <= 0) {
        cartState = cartState.filter(i => i.id !== productId);
    }
    saveCart();
    updateCartUI();
}

function removeFromCart(productId) {
    cartState = cartState.filter(i => i.id !== productId);
    saveCart();
    updateCartUI();
}

function saveCart() {
    localStorage.setItem('tezgah_cart', JSON.stringify(cartState));
}

function formatTRY(amount) {
    return amount.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ₺';
}

function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
}

function updateCartUI() {
    const totalCount = cartState.reduce((sum, item) => sum + item.qty, 0);
    const grandTotal = cartState.reduce((sum, item) => sum + (item.price * item.qty), 0);

    document.querySelectorAll('.cart-count-badge').forEach(b => { b.textContent = totalCount; });
    document.querySelectorAll('.cart-count-badge-text').forEach(b => { b.textContent = totalCount + ' ürün'; });

    renderCartPreview(grandTotal);
    renderOrderModalItems(grandTotal);
}

function renderCartPreview(grandTotal) {
    const previewList = document.getElementById('cartPreviewList');
    const previewTotal = document.getElementById('cartPreviewTotal');

    if (previewList) {
        previewList.innerHTML = cartState.length === 0
            ? '<div style="text-align:center; padding:1.2rem; color:var(--text-muted); font-size:0.85rem;">Sepetiniz henüz boş.</div>'
            : cartState.map(item => `
                <div class="cart-preview-item">
                    <div class="cart-preview-item-info">
                        <img src="${escapeHtml(item.image)}" class="cart-preview-item-thumb" alt="">
                        <div>
                            <div style="font-weight:800; color:var(--text-navy); font-size:0.85rem;">${escapeHtml(item.name)}</div>
                            <div style="font-size:0.75rem; color:var(--text-muted);">${item.qty} adet x ${formatTRY(item.price)}</div>
                        </div>
                    </div>
                    <div style="font-weight:900; color:var(--orange-primary); font-size:0.88rem;">${formatTRY(item.price * item.qty)}</div>
                </div>
            `).join('');
    }

    if (previewTotal) previewTotal.textContent = formatTRY(grandTotal);
}

function renderOrderModalItems(grandTotal) {
    const list = document.getElementById('orderModalItemsList');
    const subtotalEl = document.getElementById('modalSubtotal');
    const totalEl = document.getElementById('modalGrandTotal');

    if (list) {
        list.innerHTML = cartState.length === 0
            ? '<div style="text-align:center; padding:2rem; color:var(--text-muted); font-size:0.9rem;">Sepetiniz boş. Menüden lezzetli burgerler ekleyebilirsiniz.</div>'
            : cartState.map(item => `
                <div style="display:flex; align-items:center; justify-content:space-between; gap:0.85rem; padding:0.7rem; background:var(--bg-card); border:1.5px solid var(--border-subtle); border-radius:var(--radius-md);">
                    <div style="display:flex; align-items:center; gap:0.7rem; min-width:0;">
                        <img src="${escapeHtml(item.image)}" style="width:46px; height:46px; border-radius:var(--radius-sm); object-fit:cover; flex-shrink:0;" alt="">
                        <div style="min-width:0;">
                            <div style="font-family:var(--font-heading); font-weight:800; color:var(--text-navy); font-size:0.92rem; overflow-wrap:anywhere;">${escapeHtml(item.name)}</div>
                            <div style="color:var(--orange-primary); font-weight:800; font-size:0.85rem;">${formatTRY(item.price)}</div>
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:0.5rem; flex-shrink:0;">
                        <button class="qty-btn" onclick="changeQty(${item.id}, -1)" aria-label="Adet azalt">-</button>
                        <span style="font-weight:800; font-size:0.95rem; min-width:20px; text-align:center;">${item.qty}</span>
                        <button class="qty-btn" onclick="changeQty(${item.id}, 1)" aria-label="Adet artır">+</button>
                        <button onclick="removeFromCart(${item.id})" aria-label="Ürünü kaldır" style="background:none; border:none; color:var(--danger); cursor:pointer; font-size:0.9rem; margin-left:0.2rem;">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                </div>
            `).join('');
    }

    if (subtotalEl) subtotalEl.textContent = formatTRY(grandTotal);
    if (totalEl) totalEl.textContent = formatTRY(grandTotal);
}

/* ------------------------------------------------------------------ */
/* Order modal: service mode, validation, submission                   */
/* ------------------------------------------------------------------ */

function initServiceMode() {
    const preset = window.TEZGAH_PRESET_TABLE;
    if (preset) {
        const btn = document.querySelector(`.table-picker-btn[data-table="${preset}"]`);
        if (btn) selectOrderTable(btn);
    }
    setServiceMode(serviceMode);
}

function setServiceMode(mode, btn) {
    serviceMode = mode;

    document.querySelectorAll('.service-switch-btn').forEach(b => {
        const isActive = b.getAttribute('data-mode') === mode;
        b.classList.toggle('active', isActive);
        b.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });

    const dineBlock = document.getElementById('dineInBlock');
    const guestBlock = document.getElementById('guestDetailsBlock');
    const addressGroup = document.getElementById('addressGroup');

    if (dineBlock) dineBlock.style.display = mode === 'dine_in' ? 'block' : 'none';
    if (guestBlock) guestBlock.style.display = mode === 'dine_in' ? 'none' : 'block';
    if (addressGroup) addressGroup.style.display = mode === 'delivery' ? 'block' : 'none';

    clearOrderError();
}

function selectOrderTable(btn) {
    document.querySelectorAll('.table-picker-btn').forEach(b => {
        b.classList.remove('active');
        b.setAttribute('aria-pressed', 'false');
    });
    btn.classList.add('active');
    btn.setAttribute('aria-pressed', 'true');
    selectedTable = btn.getAttribute('data-table');
}

function showOrderError(message, fieldId) {
    const errorEl = document.getElementById('orderFormError');
    if (errorEl) {
        errorEl.textContent = message;
        errorEl.classList.add('show');
    }

    document.querySelectorAll('.field-input').forEach(f => f.classList.remove('field-error'));

    if (fieldId) {
        const field = document.getElementById(fieldId);
        if (field) {
            field.classList.add('field-error');
            field.focus();
        }
    }

    showToast(message);
}

function clearOrderError() {
    const errorEl = document.getElementById('orderFormError');
    if (errorEl) errorEl.classList.remove('show');
    document.querySelectorAll('.field-input').forEach(f => f.classList.remove('field-error'));
}

function fieldValue(id) {
    const el = document.getElementById(id);
    return el ? el.value.trim() : '';
}

async function submitOrderConfirmation() {
    clearOrderError();

    if (cartState.length === 0) {
        showOrderError('Sepetiniz boş. Lütfen önce menüden ürün ekleyin.');
        return;
    }

    const payload = {
        order_type: serviceMode,
        table_no: serviceMode === 'dine_in' ? selectedTable : null,
        customer_name: fieldValue('guestName'),
        customer_phone: fieldValue('guestPhone'),
        customer_address: serviceMode === 'delivery' ? fieldValue('guestAddress') : '',
        note: fieldValue('orderNote'),
        items: cartState.map(i => ({ id: i.id, qty: i.qty }))
    };

    if (serviceMode !== 'dine_in') {
        if (!payload.customer_name) return showOrderError('Lütfen ad soyad bilginizi girin.', 'guestName');
        if (!payload.customer_phone) return showOrderError('Lütfen telefon numaranızı girin.', 'guestPhone');
        if (serviceMode === 'delivery' && !payload.customer_address) {
            return showOrderError('Adrese teslim için teslimat adresi gereklidir.', 'guestAddress');
        }
    }

    const btn = document.getElementById('submitOrderBtn');
    const originalHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Gönderiliyor...';
    }

    try {
        const res = await fetch(API_ORDERS + '?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (!data.ok) {
            showOrderError(data.error || 'Sipariş gönderilemedi. Lütfen tekrar deneyin.');
            return;
        }

        cartState = [];
        saveCart();
        updateCartUI();
        renderOrderSuccess(data.order);
    } catch (err) {
        showOrderError('Bağlantı hatası. Lütfen internetinizi kontrol edip tekrar deneyin.');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }
}

function renderOrderSuccess(order) {
    const form = document.getElementById('orderFormPanel');
    const footer = document.getElementById('orderModalFooter');
    const success = document.getElementById('orderSuccessPanel');
    if (!success) return;

    if (form) form.style.display = 'none';
    if (footer) footer.style.display = 'none';

    const target = order.type === 'dine_in'
        ? order.table_no + ' için siparişiniz mutfağa iletildi.'
        : (order.type === 'delivery'
            ? 'Siparişiniz hazırlanıp belirttiğiniz adrese yola çıkacak.'
            : 'Siparişiniz hazırlanıyor, restoranımızdan teslim alabilirsiniz.');

    const codeEl = document.getElementById('orderSuccessCode');
    const textEl = document.getElementById('orderSuccessText');
    const totalEl = document.getElementById('orderSuccessTotal');

    if (codeEl) codeEl.textContent = order.code;
    if (textEl) textEl.textContent = target;
    if (totalEl) totalEl.textContent = order.total_text;

    success.classList.add('show');
}

function resetOrderModal() {
    const form = document.getElementById('orderFormPanel');
    const footer = document.getElementById('orderModalFooter');
    const success = document.getElementById('orderSuccessPanel');

    if (success) success.classList.remove('show');
    if (form) form.style.display = 'block';
    if (footer) footer.style.display = 'flex';
    clearOrderError();
}

/* ------------------------------------------------------------------ */
/* Modals                                                              */
/* ------------------------------------------------------------------ */

function initModals() {
    const modals = document.querySelectorAll('.modal-backdrop');

    modals.forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.classList.remove('active');
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') modals.forEach(m => m.classList.remove('active'));
    });
}

function openOrderModal() {
    resetOrderModal();
    const modal = document.getElementById('orderModal');
    if (modal) modal.classList.add('active');
}

function closeOrderModal() {
    const modal = document.getElementById('orderModal');
    if (modal) modal.classList.remove('active');
}

function openProductModal(product) {
    const modal = document.getElementById('productDetailModal');
    if (!modal) return;

    const set = (id, fn) => { const el = document.getElementById(id); if (el) fn(el); };

    set('modalProductImg', el => {
        el.src = product.image || 'public/assets/images/food/tezgah_hero_burger_1786727437034.jpg';
        el.alt = product.name;
    });
    set('modalProductTitle', el => { el.textContent = product.name; });
    set('modalProductDesc', el => { el.textContent = product.description || 'Detaylı içerik bilgisi mevcut değil.'; });
    set('modalProductPrice', el => { el.textContent = product.price_formatted; });
    set('modalProductOrigPrice', el => { el.textContent = product.original_price_formatted || ''; });
    set('modalProductCalories', el => { el.textContent = product.calories ? product.calories + ' kcal' : '-'; });
    set('modalProductAllergens', el => {
        el.innerHTML = product.allergens_html
            || '<span style="color:var(--text-body); font-size:0.88rem;">Bilinen alerjen içermemektedir.</span>';
    });

    set('modalDiscountBadge', el => {
        if (product.discount_label) {
            el.textContent = product.discount_label;
            el.style.display = 'inline-flex';
        } else {
            el.style.display = 'none';
        }
    });

    set('modalAddToCartBtn', el => {
        el.onclick = function (e) {
            addToCart(product, e);
            closeProductModal();
        };
    });

    modal.classList.add('active');
}

function closeProductModal() {
    const modal = document.getElementById('productDetailModal');
    if (modal) modal.classList.remove('active');
}

/* ------------------------------------------------------------------ */
/* Toast                                                               */
/* ------------------------------------------------------------------ */

let toastTimer = null;

function showToast(message) {
    const toast = document.getElementById('toastNotification');
    const msgEl = document.getElementById('toastMessage');
    if (!toast || !msgEl) return;

    msgEl.textContent = message;
    toast.classList.add('active');

    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('active'), 3000);
}
