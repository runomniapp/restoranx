-- Tezgah Burger Kahramanmaraş Database Schema & Seed Data
-- Compatible with phpMyAdmin (MySQL) and SQLite

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(50) DEFAULT 'fa-burger',
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    original_price DECIMAL(10,2) DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    allergens VARCHAR(255) DEFAULT '', -- JSON or comma separated: gluten,egg,milk,nuts,mustard,sesame
    spiciness TINYINT DEFAULT 0, -- 0: Mild, 1: Medium, 2: Spicy, 3: Extra Spicy
    calories INT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    is_featured TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    subtitle VARCHAR(255) DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    discount_percentage DECIMAL(5,2) DEFAULT NULL,
    discount_amount DECIMAL(10,2) DEFAULT NULL,
    target_type VARCHAR(20) DEFAULT 'all', -- 'all', 'category', 'product'
    target_id INT DEFAULT NULL,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS qr_settings (
    id INT PRIMARY KEY DEFAULT 1,
    fg_color VARCHAR(10) DEFAULT '#C87A4B',
    bg_color VARCHAR(10) DEFAULT '#121110',
    logo_url VARCHAR(255) DEFAULT 'assets/images/logo.jpg',
    header_text VARCHAR(150) DEFAULT 'TEZGAH BURGER',
    subheader_text VARCHAR(255) DEFAULT 'Kahramanmaraş\'ın Eşsiz Lezzet Durağı',
    table_note VARCHAR(255) DEFAULT 'Masanıza Hoş Geldiniz - Menümüzü İncelemek İçin QR Kodu Okutunuz',
    footer_text VARCHAR(255) DEFAULT 'Bizi Sosyal Medyada Takip Edin: @tezgahburger.ksu'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS price_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    change_type VARCHAR(20) NOT NULL, -- 'product', 'category', 'global'
    target_name VARCHAR(150) NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    affected_count INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial Seed Data: Categories
INSERT INTO categories (id, name, slug, icon, sort_order) VALUES
(1, 'Smash Burgerler', 'smash-burgerler', 'fa-burger', 1),
(2, 'Tavuk Burgerler', 'tavuk-burgerler', 'fa-drumstick-bite', 2),
(3, 'Kampanyalı Menüler', 'kampanyali-menuler', 'fa-fire', 3),
(4, 'Atıştırmalıklar & Yan Ürünler', 'atistirmaliklar', 'fa-cookie-bite', 4),
(5, 'İçecekler & Tatlılar', 'icecekler-tatlilar', 'fa-wine-glass', 5)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Initial Seed Data: Products
INSERT INTO products (id, category_id, name, slug, description, price, original_price, image, allergens, spiciness, calories, is_featured) VALUES
(1, 1, 'Tezgah Special Smash Burger', 'tezgah-special-smash-burger', '200gr Çift Kat Dana Kaburga Köftesi, Karamelize Soğan, Özel Tezgah Sos, Çift Cheddar Peyniri, Çıtır Soğan ve Jalapeno Turşusu.', 340.00, 390.00, 'assets/images/food/tezgah_special_1786727453215.jpg', 'gluten,egg,milk', 1, 850, 1),
(2, 1, 'Hero Double Bacon Burger', 'hero-double-bacon-burger', '180gr Dana Kıyma Köftesi, Füme Dana Kaburga, Duble Cheddar, Brioche Ekmek ve Trüflü Mayonez.', 360.00, NULL, 'assets/images/food/tezgah_hero_burger_1786727437034.jpg', 'gluten,egg,milk,mustard', 0, 920, 1),
(3, 2, 'Crispy Buttermilk Chicken', 'crispy-buttermilk-chicken', 'Özel Baharatlarla Marine Edilmiş Çıtır Tavuk Göğsü, Lahana Coleslaw, Tatlı Acı Mayonez ve Brioche Ekmek.', 280.00, 320.00, 'assets/images/food/crispy_chicken_1786727470747.jpg', 'gluten,egg,milk,sesame', 2, 740, 1),
(4, 3, 'Efsane İkili Kampanya Menüsü', 'efsane-ikili-kampanya-menusu', '1x Tezgah Special Burger + 1x Crispy Chicken + Büyük Boy Peynirli Patates Kızartması + 2x Soğuk İçecek.', 580.00, 720.00, 'assets/images/food/tezgah_special_1786727453215.jpg', 'gluten,egg,milk,mustard,sesame', 1, 1550, 1),
(5, 4, 'Loaded Cheese & Jalapeno Fries', 'loaded-cheese-jalapeno-fries', 'Özel Baharatlı Çıtır Patates, Sıcak Cheddar Sos, Çıtır Bacon Parçaları ve İnce Kıyım Jalapeno.', 160.00, NULL, 'assets/images/food/crispy_chicken_1786727470747.jpg', 'milk', 1, 510, 0),
(6, 5, 'Maraş Kesme Dondurmalı Sufle', 'maras-kesme-dondurmali-sufle', 'Sıcak Çikolatalı Akışkan Sufle, Yanında Hakiki Kahramanmaraş Kesme Dondurması ile.', 180.00, NULL, 'assets/images/food/tezgah_hero_burger_1786727437034.jpg', 'gluten,egg,milk,nuts', 0, 480, 0)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Initial Seed Data: Campaigns
INSERT INTO campaigns (id, title, subtitle, image, discount_percentage, discount_amount, target_type, target_id, start_date, end_date, is_active, sort_order) VALUES
(1, 'Haftanın Özel Tezgah Fırsatı!', 'Tezgah Special Burger Menü alana 2. İçecek ve Patates Hediye!', 'assets/images/food/tezgah_hero_burger_1786727437034.jpg', 20.00, NULL, 'product', 1, '2026-08-01', '2026-08-31', 1, 1),
(2, 'Tavuk Severlere %15 İndirim', 'Tüm Tavuk Burger Çeşitlerinde Geçerli Muhteşem Kampanya.', 'assets/images/food/crispy_chicken_1786727470747.jpg', 15.00, NULL, 'category', 2, '2026-08-01', '2026-08-31', 1, 2)
ON DUPLICATE KEY UPDATE title=VALUES(title);

-- Initial Seed Data: QR Settings
INSERT INTO qr_settings (id, fg_color, bg_color, logo_url, header_text, subheader_text, table_note, footer_text) VALUES
(1, '#C87A4B', '#121110', 'assets/images/logo.jpg', 'TEZGAH BURGER', 'Kahramanmaraş\'ın Eşsiz Lezzet Durağı', 'Masanıza Hoş Geldiniz - Dijital Menümüzü İncelemek İçin QR Kodu Okutunuz', 'Afiyet Olsun! - Instagram: @tezgahburger.ksu')
ON DUPLICATE KEY UPDATE header_text=VALUES(header_text);

-- =====================================================================
-- ORDERS MODULE (Masa Siparişi + Paket / Adrese Teslim)
-- =====================================================================

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(20) NOT NULL,
    order_type VARCHAR(20) NOT NULL DEFAULT 'dine_in', -- 'dine_in', 'delivery', 'pickup'
    table_no VARCHAR(50) DEFAULT NULL,
    customer_name VARCHAR(120) DEFAULT NULL,
    customer_phone VARCHAR(40) DEFAULT NULL,
    customer_address TEXT DEFAULT NULL,
    note TEXT DEFAULT NULL,
    item_count INT DEFAULT 0,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'new', -- 'new','preparing','ready','completed','cancelled'
    is_seen TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_orders_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT DEFAULT NULL,
    product_name VARCHAR(180) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    qty INT NOT NULL DEFAULT 1,
    line_total DECIMAL(10,2) NOT NULL DEFAULT 0,
    INDEX idx_order_items_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
