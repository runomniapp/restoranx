<?php
require_once __DIR__ . '/includes/header.php';

$campaigns = getCampaigns(true);
$categories = getCategories(true);
$featured_products = getProducts(null, true);
?>

<!-- Hero Section (BurgVoid Inspired Layout) -->
<section class="hero-section">
    <div class="hero-grid">
        <div class="reveal-on-scroll active">
            <h1 class="hero-title">
                GERÇEK ETİN <br><span class="highlight-orange">KABURGA</span> TADI.
            </h1>
            <p class="hero-description">
                Yerli ve %100 doğal dana kaburga etlerimizin özel smashed tekniğiyle mühürlenip, ev yapımı taze brioche ekmeği ve gurme soslarımızla buluştuğu eşsiz lezzet durağı.
            </p>
            <div class="hero-actions">
                <a href="#menu" class="btn-orange" style="padding: 0.85rem 2rem; font-size: 1rem;">
                    <i class="fa-solid fa-burger"></i> Menüyü Keşfet
                </a>
                <button onclick="openOrderModal()" class="btn-navy" style="padding: 0.85rem 1.8rem; font-size: 1rem;">
                    <i class="fa-solid fa-basket-shopping" style="color: var(--orange-primary);"></i> Siparişi Tamamla
                </button>
            </div>

            <!-- Trust Badges -->
            <div class="hero-trust-row">
                <div class="trust-badge">
                    <div class="trust-badge-icon"><i class="fa-solid fa-leaf"></i></div>
                    <div class="trust-badge-text">%100 Taze<br>Malzeme</div>
                </div>
                <div class="trust-badge">
                    <div class="trust-badge-icon"><i class="fa-solid fa-fire-flame-curved"></i></div>
                    <div class="trust-badge-text">Izgara Mühürlü<br>Kaburga Eti</div>
                </div>
                <div class="trust-badge">
                    <div class="trust-badge-icon"><i class="fa-solid fa-star"></i></div>
                    <div class="trust-badge-text">El Yapımı<br>Brioche Ekmek</div>
                </div>
            </div>
        </div>

        <!-- Hero Showcase Right Side -->
        <div class="hero-showcase reveal-on-scroll active">
            <div class="hero-image-wrapper">
                <img src="public/assets/images/hero_burger_splash.jpg" alt="Tezgah Special Smash Burger">
            </div>
            <i class="fa-solid fa-arrow-turn-down hero-doodle-arrow"></i>
        </div>
    </div>
</section>

<!-- Value Proposition Horizontal Strip -->
<div class="features-strip reveal-on-scroll">
    <div class="feature-box">
        <div class="feature-icon"><i class="fa-solid fa-carrot"></i></div>
        <div>
            <div class="feature-title">TAZE MALZEME</div>
            <div class="feature-desc">Günlük taze temin edilen doğal ürünler.</div>
        </div>
    </div>
    <div class="feature-box">
        <div class="feature-icon"><i class="fa-solid fa-bolt"></i></div>
        <div>
            <div class="feature-title">HIZLI SERVİS</div>
            <div class="feature-desc">Sıcak ve taptaze masanıza teslimat.</div>
        </div>
    </div>
    <div class="feature-box">
        <div class="feature-icon"><i class="fa-solid fa-crown"></i></div>
        <div>
            <div class="feature-title">PREMİUM LEZZET</div>
            <div class="feature-desc">%100 Dana kaburga eti ve gizli soslar.</div>
        </div>
    </div>
    <div class="feature-box">
        <div class="feature-icon"><i class="fa-solid fa-tag"></i></div>
        <div>
            <div class="feature-title">UYGUN FİYAT</div>
            <div class="feature-desc">Kaliteli lezzet en avantajlı fiyatlarla.</div>
        </div>
    </div>
</div>

<!-- Featured Menu Section (Light Theme BurgVoid Style) -->
<section class="section-padding" id="menu">
    <div class="section-header reveal-on-scroll">
        <span class="section-tag">ÖNE ÇIKAN MENÜ</span>
        <h2 class="section-title">Efsane Lezzetlerimiz</h2>
    </div>

    <!-- Category Tabs Filter -->
    <div class="menu-tabs reveal-on-scroll" role="tablist" aria-label="Menü kategorileri">
        <button class="tab-btn active" data-category="all" role="tab" aria-selected="true">
            <i class="fa-solid fa-utensils"></i> Tümü
        </button>
        <?php foreach ($categories as $cat): ?>
            <button class="tab-btn" data-category="<?= $cat['id'] ?>" role="tab" aria-selected="false">
                <i class="fa-solid <?= htmlspecialchars($cat['icon'] ?: 'fa-burger') ?>"></i> <?= htmlspecialchars($cat['name']) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- Product Grid -->
    <div class="menu-grid" id="productGridHome">
        <?php foreach ($featured_products as $product) {
            echo renderMenuCard($product);
        } ?>

        <div class="menu-empty-state" id="menuEmptyState" style="display:none;">
            <i class="fa-solid fa-magnifying-glass" style="font-size:1.6rem; display:block; margin-bottom:0.6rem; color:var(--orange-primary);"></i>
            Aramanızla eşleşen ürün bulunamadı.
        </div>
    </div>
</section>

<!-- Deep Midnight Navy Block: "OUR BEST COMBOS" -->
<section class="combos-banner-section reveal-on-scroll">
    <div class="combos-banner-grid">
        <div>
            <div class="combos-title-sub">BİZİM</div>
            <h2 class="combos-title">EN İYİ<br>KOMBOLAR</h2>
            <p class="combos-desc">
                Özel smashed kaburga burgerler, çıtır patates kızartması ve soğuk meşrubat ile hazırlanan doyumsuz kombo fırsatları.
            </p>
            <button onclick="openOrderModal()" class="btn-orange" style="padding: 0.9rem 2.2rem; font-size: 1rem;">
                <i class="fa-solid fa-basket-shopping"></i> Hemen Sipariş Ver
            </button>
        </div>
        <div class="combos-img-wrapper">
            <img src="public/assets/images/best_combos_platters.jpg" alt="Tezgah Burger Best Combos">
        </div>
    </div>
</section>

<!-- App / QR Mobile Ordering Banner -->
<section class="app-banner-section reveal-on-scroll" id="campaigns">
    <div class="app-banner-grid">
        <div>
            <h2 class="app-banner-title">
                CANIN BURGER Mİ ÇEKTİ?<br>MASANDAN ANINDA SİPARİŞ ET!
            </h2>
            <p class="app-banner-desc">
                Masandaki QR kodu okut, garson beklemeden dijital menümüzü incele veya web sitemiz üzerinden hemen sepetini oluştur!
            </p>
            <div class="app-badges">
                <button onclick="openOrderModal()" class="app-badge-btn" style="border:none; cursor:pointer;">
                    <i class="fa-solid fa-basket-shopping" style="font-size: 1.4rem; color: var(--orange-primary);"></i>
                    <div style="text-align:left;">
                        <div style="font-size:0.65rem; color: #A0AEC0;">HIZLI SİPARİŞ</div>
                        <div style="font-size:0.95rem;">Sepetimi Aç</div>
                    </div>
                </button>
                <a href="qr.php" class="app-badge-btn">
                    <i class="fa-solid fa-qrcode" style="font-size: 1.4rem; color: var(--orange-primary);"></i>
                    <div style="text-align:left;">
                        <div style="font-size:0.65rem; color: #A0AEC0;">BAĞIMSIZ KATALOG</div>
                        <div style="font-size:0.95rem;">QR Menüyü Gör</div>
                    </div>
                </a>
            </div>
        </div>
        <div class="app-mockup-wrapper">
            <img src="public/assets/images/app_qr_mockup.jpg" alt="Tezgah Burger Mobil QR Sipariş Uygulaması">
        </div>
    </div>
</section>

<!-- Ambience Showcase Gallery -->
<section class="section-padding" id="venue">
    <div class="section-header reveal-on-scroll">
        <span class="section-tag">AMBİYANS & MİMARİ</span>
        <h2 class="section-title">Tezgah Burger Kahramanmaraş</h2>
        <p style="color: var(--text-body); max-width: 600px; margin: 0.6rem auto 0; font-size: 0.98rem;">
            Sıcak bakır detaylar ve ferah mimarimizle sizleri ağırlamaktan mutluluk duyuyoruz.
        </p>
    </div>

    <div class="venue-grid reveal-on-scroll">
        <div class="venue-card">
            <img src="public/assets/images/venue/media_1786726876335.jpg" alt="Tezgah Burger İç Mekan">
        </div>
        <div class="venue-card">
            <img src="public/assets/images/venue/media_1786726876344.jpg" alt="Tezgah Burger Dış Mekan">
        </div>
        <div class="venue-card">
            <img src="public/assets/images/food/tezgah_hero_burger_1786727437034.jpg" alt="Lezzet Tezgahı">
        </div>
    </div>
</section>

<!-- Contact & Map Section -->
<section class="section-padding" id="contact" style="background: #FFFFFF; border-top: 1.5px solid var(--border-subtle); border-radius: var(--radius-lg); margin-top: 40px;">
    <div class="section-header reveal-on-scroll">
        <span class="section-tag">BİZE ULAŞIN</span>
        <h2 class="section-title">Kahramanmaraş Tezgahı</h2>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2.5rem; align-items: center;" class="reveal-on-scroll">
        <div style="background: var(--bg-card); padding: 2.2rem; border-radius: var(--radius-md); border: 1.5px solid var(--border-subtle);">
            <h3 style="font-family: var(--font-heading); font-size: 1.5rem; font-weight: 800; color: var(--text-navy); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.6rem;">
                <i class="fa-solid fa-store" style="color: var(--orange-primary);"></i> Tezgah Burger K.Maraş
            </h3>
            <p style="color: var(--text-body); margin-bottom: 1.6rem; font-size: 0.95rem;">
                Kahramanmaraş Onikişubat bölgesinde benzersiz atmosferimiz ve taptaze mühürlü burgerlerimiz ile hizmetinizdeyiz.
            </p>
            <div style="display: flex; flex-direction: column; gap: 1.1rem; color: var(--text-navy); font-size: 0.95rem; font-weight: 600;">
                <div><strong style="color: var(--orange-primary);">Adres:</strong> Avşar Mah. Batı Çevre Yolu Üzeri, Onikişubat / Kahramanmaraş</div>
                <div><strong style="color: var(--orange-primary);">Rezervasyon & Sipariş:</strong> +90 (344) 222 00 46</div>
                <div><strong style="color: var(--orange-primary);">Çalışma Saatleri:</strong> 11:30 - 23:30 (Haftanın 7 Günü)</div>
            </div>
        </div>

        <div style="height: 340px; border-radius: var(--radius-md); overflow: hidden; border: 1.5px solid var(--border-subtle); box-shadow: var(--shadow-sm);">
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d101230.2291583279!2d36.852441950000005!3d37.57527335!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x152ddd6b5a38b1d9%3A0x6b772c67c5e2d6bf!2sKahramanmara%C5%9F!5e0!3m2!1str!2str!4v1700000000000!5m2!1str!2str" 
                width="100%" 
                height="100%" 
                style="border:0;" 
                allowfullscreen="" 
                loading="lazy">
            </iframe>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/order_modal.php'; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
