<?php
// Shared guest-facing ordering UI: floating cart, order modal, product modal, toast.
// Included by index.php and qr.php so both pages stay in sync.

$table_count = 12;
$preset_table = isset($preset_table) ? $preset_table : null;
?>

<!-- Floating Cart Widget -->
<div class="floating-cart-wrapper">
    <button class="floating-cart-btn" onclick="openOrderModal()" aria-label="Sepeti aç">
        <i class="fa-solid fa-basket-shopping"></i>
        <span class="cart-badge-count cart-count-badge">0</span>
    </button>

    <div class="cart-hover-preview">
        <div class="cart-preview-header">
            <span class="cart-preview-title">Sepetim</span>
            <span style="font-size:0.8rem; font-weight:700; color:var(--orange-primary);" class="cart-count-badge-text">0 ürün</span>
        </div>
        <div class="cart-preview-items-list" id="cartPreviewList">
            <div style="text-align:center; padding:1rem; color:var(--text-muted); font-size:0.85rem;">Sepetiniz henüz boş.</div>
        </div>
        <div class="cart-preview-footer">
            <div class="cart-preview-total-row">
                <span>Toplam:</span>
                <span id="cartPreviewTotal" style="color:var(--orange-primary); font-size:1.1rem;">0,00 ₺</span>
            </div>
            <button onclick="openOrderModal()" class="btn-orange btn-block" style="padding:0.55rem; font-size:0.88rem;">
                Siparişi Tamamla <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>
    </div>
</div>

<!-- Order Modal -->
<div class="modal-backdrop" id="orderModal" role="dialog" aria-modal="true" aria-labelledby="orderModalTitle">
    <div class="modal-card" style="max-width: 560px; display:flex; flex-direction:column; max-height:92vh;">
        <button class="modal-close" onclick="closeOrderModal()" aria-label="Kapat"><i class="fa-solid fa-xmark"></i></button>

        <div style="padding: 1.35rem 1.5rem; border-bottom: 1.5px solid var(--border-subtle); background: var(--bg-card); flex-shrink:0;">
            <h3 id="orderModalTitle" style="font-family: var(--font-heading); font-size: 1.35rem; font-weight: 900; color: var(--text-navy); display: flex; align-items: center; gap: 0.6rem;">
                <i class="fa-solid fa-basket-shopping" style="color: var(--orange-primary);"></i> Siparişi Tamamla
            </h3>
            <p style="font-size: 0.85rem; color: var(--text-body); margin-top: 2px;">
                Masanıza servis edelim ya da adresinize gönderelim — seçim sizin.
            </p>
        </div>

        <!-- Form panel -->
        <div id="orderFormPanel" style="padding: 1.35rem 1.5rem; overflow-y: auto;">

            <div class="form-block">
                <span class="form-block-label"><i class="fa-solid fa-bell-concierge"></i> Servis Tipi</span>
                <div class="service-switch" role="group" aria-label="Servis tipi seçimi">
                    <button type="button" class="service-switch-btn active" data-mode="dine_in" aria-pressed="true" onclick="setServiceMode('dine_in', this)">
                        <i class="fa-solid fa-chair"></i> Masaya Servis
                    </button>
                    <button type="button" class="service-switch-btn" data-mode="delivery" aria-pressed="false" onclick="setServiceMode('delivery', this)">
                        <i class="fa-solid fa-motorcycle"></i> Adrese Teslim
                    </button>
                    <button type="button" class="service-switch-btn" data-mode="pickup" aria-pressed="false" onclick="setServiceMode('pickup', this)">
                        <i class="fa-solid fa-bag-shopping"></i> Gel-Al Paket
                    </button>
                </div>
            </div>

            <!-- Dine-in: table picker -->
            <div class="form-block" id="dineInBlock">
                <span class="form-block-label"><i class="fa-solid fa-hashtag"></i> Masa Numaranız</span>
                <div class="table-picker-grid">
                    <?php for ($t = 1; $t <= $table_count; $t++):
                        $label = 'Masa ' . $t;
                        $is_active = $preset_table ? ($preset_table === $label) : ($t === 1);
                    ?>
                        <button type="button"
                                class="table-picker-btn <?= $is_active ? 'active' : '' ?>"
                                data-table="<?= $label ?>"
                                aria-pressed="<?= $is_active ? 'true' : 'false' ?>"
                                onclick="selectOrderTable(this)"><?= $t ?></button>
                    <?php endfor; ?>
                </div>
                <small class="field-hint">Masanızdaki QR kodu okuttuysanız masa numaranız otomatik seçilir.</small>
            </div>

            <!-- Delivery / pickup: guest details -->
            <div id="guestDetailsBlock" style="display: none;">
                <div class="form-block">
                    <label class="form-block-label" for="guestName"><i class="fa-solid fa-user"></i> Ad Soyad</label>
                    <input type="text" id="guestName" class="field-input" placeholder="örn: Ahmet Yılmaz" autocomplete="name">
                </div>

                <div class="form-block">
                    <label class="form-block-label" for="guestPhone"><i class="fa-solid fa-phone"></i> Telefon Numarası</label>
                    <input type="tel" id="guestPhone" class="field-input" placeholder="0532 000 00 00" autocomplete="tel">
                    <small class="field-hint">Siparişinizi teyit etmek için sizi arayabiliriz.</small>
                </div>

                <div class="form-block" id="addressGroup" style="display: none;">
                    <label class="form-block-label" for="guestAddress"><i class="fa-solid fa-location-dot"></i> Teslimat Adresi</label>
                    <textarea id="guestAddress" class="field-input" placeholder="Mahalle, cadde, bina ve daire numarası..." autocomplete="street-address"></textarea>
                </div>
            </div>

            <!-- Cart items -->
            <div class="form-block">
                <span class="form-block-label"><i class="fa-solid fa-burger"></i> Sepetinizdeki Ürünler</span>
                <div id="orderModalItemsList" style="display: flex; flex-direction: column; gap: 0.7rem;">
                    <div style="text-align:center; padding: 1.5rem; color: var(--text-muted); font-size: 0.9rem;">Sepetiniz boş. Menüden lezzetli burgerler ekleyebilirsiniz.</div>
                </div>
            </div>

            <div class="form-block">
                <label class="form-block-label" for="orderNote"><i class="fa-solid fa-pen"></i> Sipariş Notu (Opsiyonel)</label>
                <textarea id="orderNote" class="field-input" style="min-height:60px;" placeholder="örn: Soğansız olsun, acı sos ayrı gelsin."></textarea>
            </div>

            <!-- Summary -->
            <div style="background: var(--bg-card); border: 1.5px solid var(--border-subtle); border-radius: var(--radius-md); padding: 1rem;">
                <div style="display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 0.4rem; color: var(--text-body);">
                    <span>Ara Toplam</span>
                    <span id="modalSubtotal" style="font-weight: 700;">0,00 ₺</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 0.6rem; color: var(--text-body);">
                    <span>Servis Bedeli</span>
                    <span style="color: var(--success); font-weight: 700;">ÜCRETSİZ</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-family: var(--font-heading); font-size: 1.2rem; font-weight: 900; color: var(--text-navy); border-top: 1.5px solid var(--border-subtle); padding-top: 0.5rem;">
                    <span>Genel Toplam</span>
                    <span id="modalGrandTotal" style="color: var(--orange-primary);">0,00 ₺</span>
                </div>
            </div>

            <p class="form-error-text" id="orderFormError" role="alert"></p>
        </div>

        <!-- Success panel -->
        <div class="order-success" id="orderSuccessPanel">
            <div class="order-success-icon"><i class="fa-solid fa-check"></i></div>
            <h3 style="font-family: var(--font-heading); font-size: 1.4rem; font-weight: 900; color: var(--text-navy);">Siparişiniz Alındı!</h3>
            <p id="orderSuccessText" style="color: var(--text-body); font-size: 0.94rem; margin-top: 0.4rem;"></p>
            <div class="order-code-chip" id="orderSuccessCode">-</div>
            <p style="margin-top: 0.9rem; color: var(--text-body); font-size: 0.9rem;">
                Tutar: <strong id="orderSuccessTotal" style="color: var(--orange-primary);">-</strong>
            </p>
            <button onclick="closeOrderModal()" class="btn-orange" style="margin-top: 1.4rem; padding: 0.75rem 2rem;">
                <i class="fa-solid fa-burger"></i> Menüye Dön
            </button>
        </div>

        <div id="orderModalFooter" style="padding: 1rem 1.5rem; background: #FFFFFF; border-top: 1.5px solid var(--border-subtle); display: flex; gap: 0.85rem; flex-shrink:0;">
            <button onclick="closeOrderModal()" class="btn-navy" style="flex: 1;">Devam Et</button>
            <button onclick="submitOrderConfirmation()" class="btn-orange" style="flex: 1.4;" id="submitOrderBtn">
                <i class="fa-solid fa-paper-plane"></i> Siparişi Onayla
            </button>
        </div>
    </div>
</div>

<!-- Product Detail Modal -->
<div class="modal-backdrop" id="productDetailModal" role="dialog" aria-modal="true" aria-labelledby="modalProductTitle">
    <div class="modal-card" style="max-height: 92vh; display:flex; flex-direction:column;">
        <button class="modal-close" onclick="closeProductModal()" aria-label="Kapat"><i class="fa-solid fa-xmark"></i></button>

        <div style="height: 235px; overflow: hidden; position: relative; background: var(--bg-card); flex-shrink:0;">
            <img id="modalProductImg" src="" alt="" style="width: 100%; height: 100%; object-fit: cover;">
            <span id="modalDiscountBadge" class="menu-badge" style="top: 14px; left: 14px; font-size: 0.8rem; padding: 0.35rem 0.85rem; display: none;"></span>
        </div>

        <div style="padding: 1.4rem; overflow-y:auto;">
            <h3 id="modalProductTitle" style="font-family: var(--font-heading); font-size: 1.4rem; font-weight: 900; color: var(--text-navy); margin-bottom: 0.4rem;"></h3>
            <p id="modalProductDesc" style="color: var(--text-body); font-size: 0.92rem; margin-bottom: 1.2rem; line-height: 1.55;"></p>

            <div style="background: var(--bg-card); border: 1.5px solid var(--border-subtle); padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.2rem;">
                <span style="font-size: 0.74rem; font-weight: 800; color: var(--orange-primary); text-transform: uppercase; display: block; margin-bottom: 0.5rem; letter-spacing:0.4px;">
                    <i class="fa-solid fa-circle-exclamation"></i> Alerjen ve Besin Bilgisi
                </span>
                <div id="modalProductAllergens"></div>
                <div style="margin-top: 0.6rem; font-size: 0.88rem; color: var(--text-body); font-weight: 600;">
                    Kalori Değeri: <strong id="modalProductCalories" style="color: var(--orange-primary);">-</strong>
                </div>
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                <div style="flex-shrink: 0;">
                    <span id="modalProductOrigPrice" style="font-size: 0.86rem; color: var(--text-muted); text-decoration: line-through; font-weight:600;"></span>
                    <div id="modalProductPrice" style="font-family: var(--font-heading); font-size: 1.55rem; font-weight: 900; color: var(--orange-primary); white-space: nowrap;"></div>
                </div>
                <button id="modalAddToCartBtn" class="btn-orange" style="padding: 0.75rem 1.5rem; flex-grow: 1; justify-content: center;">
                    <i class="fa-solid fa-plus"></i> Sepete Ekle
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="toast-notification" id="toastNotification" role="status" aria-live="polite">
    <i class="fa-solid fa-circle-check" style="color: var(--orange-primary); font-size: 1.2rem;"></i>
    <span id="toastMessage">Ürün sepete eklendi!</span>
</div>
