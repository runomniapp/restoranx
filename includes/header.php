<?php
require_once __DIR__ . '/functions.php';
$qr_settings = getQRSettings();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Tezgah Burger | Kahramanmaraş Artisanal Smash Burger</title>
    <meta name="description" content="Tezgah Burger Kahramanmaraş - Özel yapım dana kaburga smash burgerler, çıtır tavuk lezzetleri ve taze atıştırmalıklar. Hızlı Sipariş ve Dijital Menü!">
    
    <!-- Google Fonts & FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="public/assets/css/style.css">
</head>
<body>

<!-- Floating Pill Header -->
<header class="glass-header" id="mainHeader">
    <div class="header-container">
        <!-- Logo -->
        <a href="index.php" class="brand-logo">
            <img src="public/assets/images/logo.jpg" alt="Tezgah Burger Logo">
            <div class="brand-text">
                <span class="brand-title">TEZGAH BURGER</span>
                <span class="brand-subtitle">KAHRAMANMARAŞ</span>
            </div>
        </a>

        <!-- Main Nav (Sleek Typography Without Icons) -->
        <ul class="nav-menu" id="navMenu">
            <li><a href="index.php" class="nav-link active">Ana Sayfa</a></li>
            <li><a href="qr.php" class="nav-link" style="color: var(--orange-primary);">Menü</a></li>
            <li><a href="index.php#campaigns" class="nav-link">Fırsatlar</a></li>
            <li><a href="index.php#venue" class="nav-link">Restoranımız</a></li>
            <li><a href="index.php#contact" class="nav-link">İletişim</a></li>
            
            <!-- Mobile Drawer Action -->
            <li class="mobile-menu-actions">
                <button onclick="openOrderModal()" class="btn-orange btn-block">
                    <i class="fa-solid fa-basket-shopping"></i> Sepetim (<span class="cart-count-badge">0</span>)
                </button>
            </li>
        </ul>

        <!-- Desktop Header Actions & Mobile Toggle -->
        <div class="header-actions">
            <button onclick="openOrderModal()" class="btn-navy desktop-only-btn" id="headerCartBtn">
                <i class="fa-solid fa-basket-shopping" style="color: var(--orange-primary);"></i> Sepetim (<span class="cart-count-badge">0</span>)
            </button>
            <button class="mobile-toggle" id="mobileToggleBtn" aria-label="Menü Aç">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
    </div>
</header>
