<?php
require_once __DIR__ . '/includes/functions.php';

$selected_cat_id = isset($_GET['cat']) ? intval($_GET['cat']) : null;

// Masadaki QR kodu "?masa=5" ile gelir -> sipariş modalinde masa otomatik seçilir.
$table_param = isset($_GET['masa']) ? intval($_GET['masa']) : (isset($_GET['table']) ? intval($_GET['table']) : 0);
$preset_table = ($table_param > 0 && $table_param <= 99) ? 'Masa ' . $table_param : null;

$categories = getCategories(true);
$products = getProducts(null, true);
$qr_settings = getQRSettings();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#F59E0B">
    <title>Dijital Menü | Tezgah Burger Kahramanmaraş</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="public/assets/css/style.css">

    <style>
        .glass-header { display: none !important; }

        /* -----------------------------------------------------------------
           1. MOBILE (< 769px) — Golden amber guest menu
           ----------------------------------------------------------------- */
        @media (max-width: 768px) {
            body {
                background-color: #F59E0B !important;
                background-image: none !important;
                color: var(--text-navy);
                min-height: 100vh;
            }

            .qr-wrapper {
                max-width: 520px;
                margin: 0 auto;
                min-height: 100vh;
                background-color: #F59E0B;
                padding-bottom: 90px;
                position: relative;
            }

            .qr-cover {
                position: relative;
                height: 190px;
                width: 100%;
                overflow: hidden;
                background: url('public/assets/images/venue/media_1786726876335.jpg') center/cover no-repeat;
                border-radius: 0 0 28px 28px;
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            }

            .qr-cover::after {
                content: '';
                position: absolute;
                inset: 0;
                background: linear-gradient(to bottom, rgba(0,0,0,0.55) 0%, rgba(0,0,0,0.3) 100%);
            }

            .qr-cover-title {
                position: absolute;
                inset: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 2;
                color: #FFFFFF;
                font-family: var(--font-heading);
                font-size: 1.8rem;
                font-weight: 900;
                letter-spacing: 1px;
                text-transform: uppercase;
                text-align: center;
                text-shadow: 0 3px 12px rgba(0, 0, 0, 0.7);
                margin-top: -22px;
            }

            .qr-logo-mobile {
                position: relative;
                width: 100px;
                height: 100px;
                border-radius: 50%;
                background: #FFFFFF;
                border: 4px solid #FFFFFF;
                margin: -50px auto 14px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.18);
                overflow: hidden;
                z-index: 3;
            }

            .qr-logo-mobile img { width: 100%; height: 100%; object-fit: cover; }
            .qr-logo-desktop { display: none !important; }

            .qr-category-bar {
                display: flex;
                gap: 0.6rem;
                overflow-x: auto;
                padding: 0 1.1rem 0.5rem;
                margin-bottom: 1rem;
                scrollbar-width: none;
                -webkit-overflow-scrolling: touch;
            }
            .qr-category-bar::-webkit-scrollbar { display: none; }

            .qr-pill-btn {
                background: #FFF8F0;
                color: var(--text-navy);
                border: none;
                padding: 0.65rem 1.25rem;
                border-radius: var(--radius-full);
                font-family: var(--font-heading);
                font-weight: 800;
                font-size: 0.88rem;
                white-space: nowrap;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 0.45rem;
                flex-shrink: 0;
                min-height: 44px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
                transition: var(--transition-fast);
            }

            .qr-pill-btn.active {
                background: var(--text-navy);
                color: #FFFFFF;
                box-shadow: 0 6px 18px rgba(18, 30, 43, 0.28);
            }

            .qr-pill-btn.active i { color: #F59E0B; }

            .qr-body { padding: 0 1.1rem; }

            .qr-section-title {
                font-family: var(--font-heading);
                font-size: 1.05rem;
                font-weight: 900;
                color: var(--text-navy);
                margin-bottom: 0.75rem;
                display: flex;
                align-items: center;
                gap: 0.45rem;
            }
        }

        /* -----------------------------------------------------------------
           2. DESKTOP (>= 769px) — Cream canvas catalogue
           ----------------------------------------------------------------- */
        @media (min-width: 769px) {
            body {
                background-color: var(--bg-canvas) !important;
                color: var(--text-navy);
            }

            .qr-wrapper {
                max-width: 1280px;
                margin: 30px auto;
                padding: 0 1.5rem 70px;
            }

            .qr-cover {
                position: relative;
                height: 230px;
                width: 100%;
                border-radius: var(--radius-lg);
                overflow: hidden;
                background: linear-gradient(to right, rgba(18, 30, 43, 0.88) 0%, rgba(18, 30, 43, 0.45) 100%), url('public/assets/images/venue/media_1786726876335.jpg') center/cover no-repeat;
                box-shadow: var(--shadow-md);
            }

            .qr-logo-desktop {
                width: 125px;
                height: 125px;
                border-radius: 50%;
                background: #FFFFFF;
                border: 4px solid #FFFFFF;
                box-shadow: 0 12px 30px rgba(0, 0, 0, 0.35);
                overflow: hidden;
                position: absolute;
                left: 35px;
                top: 50%;
                transform: translateY(-50%);
                z-index: 3;
                display: block;
            }

            .qr-logo-desktop img { width: 100%; height: 100%; object-fit: cover; }
            .qr-logo-mobile { display: none !important; }

            .qr-cover-title {
                position: absolute;
                right: 35px;
                top: 50%;
                transform: translateY(-50%);
                color: #FFFFFF;
                font-family: var(--font-heading);
                font-size: 2.6rem;
                font-weight: 900;
                letter-spacing: 1.5px;
                text-transform: uppercase;
                text-shadow: 0 4px 15px rgba(0, 0, 0, 0.6);
                z-index: 2;
            }

            .qr-category-bar {
                display: flex;
                gap: 0.75rem;
                margin: 2rem 0 1.5rem;
                justify-content: center;
                flex-wrap: wrap;
            }

            .qr-pill-btn {
                background: #FFFFFF;
                border: 1.5px solid var(--border-subtle);
                color: var(--text-navy);
                padding: 0.7rem 1.4rem;
                border-radius: var(--radius-full);
                font-family: var(--font-heading);
                font-weight: 800;
                font-size: 0.93rem;
                cursor: pointer;
                transition: var(--transition-fast);
                display: flex;
                align-items: center;
                gap: 0.5rem;
                min-height: 44px;
                box-shadow: var(--shadow-sm);
            }

            .qr-pill-btn:hover { border-color: var(--orange-primary); }

            .qr-pill-btn.active {
                background: var(--bg-navy);
                color: #FFFFFF;
                border-color: var(--bg-navy);
                box-shadow: var(--shadow-navy);
            }

            .qr-pill-btn.active i { color: var(--orange-primary); }

            .qr-body { padding: 0; }

            .qr-section-title {
                font-family: var(--font-heading);
                font-size: 1.25rem;
                font-weight: 900;
                color: var(--text-navy);
                margin-bottom: 1rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
        }

        /* Shared search bar */
        .qr-search {
            max-width: 540px;
            margin: 0 auto 1.5rem;
            position: relative;
        }

        .qr-search input {
            width: 100%;
            padding: 0.85rem 1.2rem 0.85rem 2.9rem;
            background: #FFFFFF;
            border: 1.5px solid var(--orange-border);
            border-radius: var(--radius-full);
            font-family: var(--font-body);
            font-size: 0.94rem;
            font-weight: 600;
            color: var(--text-navy);
            outline: none;
            box-shadow: var(--shadow-sm);
            transition: var(--transition-fast);
        }

        .qr-search input:focus {
            border-color: var(--orange-primary);
            box-shadow: 0 0 0 3px rgba(255, 138, 0, 0.18);
        }

        .qr-search i {
            position: absolute;
            left: 1.15rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--orange-primary);
            font-size: 1.05rem;
            pointer-events: none;
        }

        /* Table badge shown when arriving from a table QR code */
        .qr-table-chip {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin: 0 auto 1.1rem;
            max-width: 540px;
            background: var(--bg-navy);
            color: #FFFFFF;
            font-family: var(--font-heading);
            font-weight: 800;
            font-size: 0.9rem;
            padding: 0.65rem 1.2rem;
            border-radius: var(--radius-full);
            box-shadow: var(--shadow-navy);
        }

        .qr-table-chip i { color: var(--orange-primary); }
    </style>
</head>
<body>

<div class="qr-wrapper">
    <!-- Cover banner -->
    <div class="qr-cover">
        <h1 class="qr-cover-title"><?= htmlspecialchars($qr_settings['header_text']) ?></h1>
        <div class="qr-logo-desktop">
            <img src="public/assets/images/logo.jpg" alt="Tezgah Burger logosu">
        </div>
    </div>

    <div class="qr-logo-mobile">
        <img src="public/assets/images/logo.jpg" alt="Tezgah Burger logosu">
    </div>

    <!-- Category pills -->
    <div class="qr-category-bar" role="tablist" aria-label="Menü kategorileri">
        <button class="qr-pill-btn tab-btn <?= empty($selected_cat_id) ? 'active' : '' ?>"
                data-category="all" role="tab" aria-selected="<?= empty($selected_cat_id) ? 'true' : 'false' ?>">
            <i class="fa-solid fa-utensils"></i> Tümü
        </button>
        <?php foreach ($categories as $cat): $is_sel = $selected_cat_id === intval($cat['id']); ?>
            <button class="qr-pill-btn tab-btn <?= $is_sel ? 'active' : '' ?>"
                    data-category="<?= $cat['id'] ?>" role="tab" aria-selected="<?= $is_sel ? 'true' : 'false' ?>">
                <i class="fa-solid <?= htmlspecialchars($cat['icon'] ?: 'fa-burger') ?>"></i> <?= htmlspecialchars($cat['name']) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <div class="qr-body">
        <?php if ($preset_table): ?>
            <div class="qr-table-chip">
                <i class="fa-solid fa-chair"></i> <?= htmlspecialchars($preset_table) ?> — siparişiniz doğrudan masanıza iletilecek
            </div>
        <?php endif; ?>

        <!-- Live search -->
        <div class="qr-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <label for="menuSearchInput" class="sr-only" style="position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0 0 0 0);">Menüde ara</label>
            <input type="search" id="menuSearchInput" placeholder="Menüde lezzet ara... (örn: Smash, Truffle)">
        </div>

        <!-- Product cards -->
        <div class="menu-grid" id="productGrid">
            <?php foreach ($products as $prod):
                $is_hidden = (!empty($selected_cat_id) && intval($prod['category_id']) !== $selected_cat_id);
                echo renderMenuCard($prod, $is_hidden);
            endforeach; ?>

            <div class="menu-empty-state" id="menuEmptyState" style="display:none;">
                <i class="fa-solid fa-magnifying-glass" style="font-size:1.6rem; display:block; margin-bottom:0.6rem; color:var(--orange-primary);"></i>
                Aramanızla eşleşen ürün bulunamadı.
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/order_modal.php'; ?>

<script>
    window.TEZGAH_PRESET_TABLE = <?= $preset_table ? json_encode($preset_table) : 'null' ?>;
</script>
<script src="public/assets/js/main.js"></script>
</body>
</html>
