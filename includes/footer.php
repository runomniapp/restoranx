<!-- Footer Section -->
<footer style="background: #FFFFFF; border-top: 1.5px solid var(--border-subtle); padding: 70px 1.5rem 35px; margin-top: 60px;">
    <div style="max-width: 1280px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 3rem;">
        <div>
            <div class="brand-logo" style="margin-bottom: 1.2rem;">
                <img src="public/assets/images/logo.jpg" alt="Tezgah Burger">
                <div>
                    <span class="brand-title">TEZGAH BURGER</span>
                    <span class="brand-subtitle">KAHRAMANMARAŞ</span>
                </div>
            </div>
            <p style="color: var(--text-body); font-size: 0.92rem; margin-bottom: 1.5rem; line-height: 1.6;">
                Özel dinlendirilmiş %100 dana kaburga etinden hazırlanan mühürlü smash burgerler, taptaze malzemeler ve Kahramanmaraş'ın en sevilen artisanal lezzet durağı.
            </p>
            <div style="display: flex; gap: 1rem; color: var(--orange-primary); font-size: 1.3rem;">
                <a href="https://instagram.com" target="_blank" aria-label="Instagram" style="width:40px; height:40px; border-radius:50%; background:var(--orange-soft); display:flex; align-items:center; justify-content:center; border:1px solid var(--orange-border); transition:var(--transition);"><i class="fa-brands fa-instagram"></i></a>
                <a href="https://facebook.com" target="_blank" aria-label="Facebook" style="width:40px; height:40px; border-radius:50%; background:var(--orange-soft); display:flex; align-items:center; justify-content:center; border:1px solid var(--orange-border); transition:var(--transition);"><i class="fa-brands fa-facebook"></i></a>
                <a href="https://whatsapp.com" target="_blank" aria-label="WhatsApp" style="width:40px; height:40px; border-radius:50%; background:var(--orange-soft); display:flex; align-items:center; justify-content:center; border:1px solid var(--orange-border); transition:var(--transition);"><i class="fa-brands fa-whatsapp"></i></a>
            </div>
        </div>

        <div>
            <h4 style="font-family: var(--font-heading); font-size: 1.2rem; font-weight: 800; color: var(--text-navy); margin-bottom: 1.4rem;">Hızlı Bağlantılar</h4>
            <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.75rem; color: var(--text-body); font-size: 0.92rem; font-weight: 600;">
                <li><a href="index.php" style="transition:var(--transition); display:inline-flex; align-items:center; gap:0.4rem;"><i class="fa-solid fa-chevron-right" style="color:var(--orange-primary); font-size:0.75rem;"></i> Ana Sayfa</a></li>
                <li><a href="qr.php" style="transition:var(--transition); display:inline-flex; align-items:center; gap:0.4rem;"><i class="fa-solid fa-chevron-right" style="color:var(--orange-primary); font-size:0.75rem;"></i> Dijital QR Menü</a></li>
                <li><a href="index.php#campaigns" style="transition:var(--transition); display:inline-flex; align-items:center; gap:0.4rem;"><i class="fa-solid fa-chevron-right" style="color:var(--orange-primary); font-size:0.75rem;"></i> Kampanyalar & Fırsatlar</a></li>
                <li><a href="admin/index.php" style="transition:var(--transition); display:inline-flex; align-items:center; gap:0.4rem;"><i class="fa-solid fa-chevron-right" style="color:var(--orange-primary); font-size:0.75rem;"></i> Yönetici Paneli</a></li>
            </ul>
        </div>

        <div>
            <h4 style="font-family: var(--font-heading); font-size: 1.2rem; font-weight: 800; color: var(--text-navy); margin-bottom: 1.4rem;">İletişim & Konum</h4>
            <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.85rem; color: var(--text-body); font-size: 0.92rem; font-weight: 600;">
                <li style="display:flex; gap:0.6rem; align-items:flex-start;"><i class="fa-solid fa-location-dot" style="color:var(--orange-primary); font-size:1.1rem; margin-top:0.2rem;"></i> <span>Kahramanmaraş Onikişubat / Avşar Mahallesi Batı Çevre Yolu Üzeri</span></li>
                <li style="display:flex; gap:0.6rem; align-items:center;"><i class="fa-solid fa-phone" style="color:var(--orange-primary); font-size:1rem;"></i> <span>+90 (344) 222 00 46</span></li>
                <li style="display:flex; gap:0.6rem; align-items:center;"><i class="fa-solid fa-clock" style="color:var(--orange-primary); font-size:1rem;"></i> <span>Haftanın 7 Günü: 11:30 - 23:30</span></li>
            </ul>
        </div>
    </div>

    <div style="max-width: 1280px; margin: 50px auto 0; padding-top: 25px; border-top: 1.5px solid var(--border-subtle); text-align: center; color: var(--text-muted); font-size: 0.88rem; font-weight: 600;">
        &copy; <?= date('Y') ?> Tezgah Burger Kahramanmaraş. Tüm Hakları Saklıdır.
    </div>
</footer>

<script src="public/assets/js/main.js"></script>
</body>
</html>
