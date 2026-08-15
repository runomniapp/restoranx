<?php
// Config: Database Connection for Tezgah Burger
// Supports MySQL (phpMyAdmin) with automatic SQLite local fallback

$host = '127.0.0.1';
$dbname = 'tezgah_burger';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

$db = null;
$db_driver = 'sqlite';

try {
    // Try MySQL First
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $db = new PDO($dsn, $username, $password, $options);
    $db_driver = 'mysql';
} catch (PDOException $e) {
    // Fallback to SQLite database file for local instant execution without MySQL setup
    $sqlite_file = __DIR__ . '/database.sqlite';
    $db = new PDO("sqlite:" . $sqlite_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db_driver = 'sqlite';

    // Auto-create & update SQLite Tables individually
    initSqliteTables($db);
}

// Orders module tables are created for BOTH drivers so the panel works
// whether the project runs on MySQL (phpMyAdmin) or the SQLite fallback.
ensureOrderTables($db, $db_driver);

function ensureOrderTables($db, $driver) {
    if ($driver === 'mysql') {
        $db->exec("CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_code VARCHAR(20) NOT NULL,
            order_type VARCHAR(20) NOT NULL DEFAULT 'dine_in',
            table_no VARCHAR(50) DEFAULT NULL,
            customer_name VARCHAR(120) DEFAULT NULL,
            customer_phone VARCHAR(40) DEFAULT NULL,
            customer_address TEXT DEFAULT NULL,
            note TEXT DEFAULT NULL,
            item_count INT DEFAULT 0,
            total DECIMAL(10,2) NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'new',
            is_seen TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_orders_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT DEFAULT NULL,
            product_name VARCHAR(180) NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL DEFAULT 0,
            qty INT NOT NULL DEFAULT 1,
            line_total DECIMAL(10,2) NOT NULL DEFAULT 0,
            INDEX idx_order_items_order (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        return;
    } else {
        $db->exec("CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_code TEXT NOT NULL,
            order_type TEXT NOT NULL DEFAULT 'dine_in',
            table_no TEXT DEFAULT NULL,
            customer_name TEXT DEFAULT NULL,
            customer_phone TEXT DEFAULT NULL,
            customer_address TEXT DEFAULT NULL,
            note TEXT DEFAULT NULL,
            item_count INTEGER DEFAULT 0,
            total REAL NOT NULL DEFAULT 0,
            status TEXT NOT NULL DEFAULT 'new',
            is_seen INTEGER NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS order_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            product_id INTEGER DEFAULT NULL,
            product_name TEXT NOT NULL,
            unit_price REAL NOT NULL DEFAULT 0,
            qty INTEGER NOT NULL DEFAULT 1,
            line_total REAL NOT NULL DEFAULT 0
        )");
    }

    $db->exec("CREATE INDEX IF NOT EXISTS idx_orders_status ON orders (status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_order_items_order ON order_items (order_id)");
}

function initSqliteTables($db) {
    $db->exec("CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE,
        icon TEXT DEFAULT 'fa-burger',
        sort_order INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        category_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        slug TEXT NOT NULL,
        description TEXT,
        price REAL NOT NULL,
        original_price REAL DEFAULT NULL,
        discount_tag TEXT DEFAULT NULL,
        image TEXT DEFAULT NULL,
        allergens TEXT DEFAULT '',
        spiciness INTEGER DEFAULT 0,
        calories INTEGER DEFAULT NULL,
        is_active INTEGER DEFAULT 1,
        is_featured INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS campaigns (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        subtitle TEXT DEFAULT NULL,
        image TEXT DEFAULT NULL,
        discount_percentage REAL DEFAULT NULL,
        discount_amount REAL DEFAULT NULL,
        target_type TEXT DEFAULT 'all',
        target_id INTEGER DEFAULT NULL,
        start_date DATE DEFAULT NULL,
        end_date DATE DEFAULT NULL,
        is_active INTEGER DEFAULT 1,
        sort_order INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS qr_settings (
        id INTEGER PRIMARY KEY DEFAULT 1,
        fg_color TEXT DEFAULT '#C87A4B',
        bg_color TEXT DEFAULT '#121110',
        logo_url TEXT DEFAULT 'assets/images/logo.jpg',
        header_text TEXT DEFAULT 'TEZGAH BURGER',
        subheader_text TEXT DEFAULT 'Kahramanmaraş''ın Eşsiz Lezzet Durağı',
        table_note TEXT DEFAULT 'Masanıza Hoş Geldiniz - Menümüzü İncelemek İçin QR Kodu Okutunuz',
        footer_text TEXT DEFAULT 'Afiyet Olsun! - Instagram: @tezgahburger.ksu'
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS price_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        change_type TEXT NOT NULL,
        target_name TEXT NOT NULL,
        percentage REAL NOT NULL,
        affected_count INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Reset categories with expanded variety if missing or old count
    $catCount = $db->query("SELECT COUNT(*) as count FROM categories")->fetch()['count'];
    if ($catCount < 6) {
        $db->exec("DELETE FROM categories; DELETE FROM products;");
        
        $db->exec("INSERT INTO categories (id, name, slug, icon, sort_order) VALUES
            (1, 'Smash Burgerler', 'smash-burgerler', 'fa-burger', 1),
            (2, 'Özel & Truffle Burgerler', 'truffle-burgerler', 'fa-crown', 2),
            (3, 'Tavuk Lezzetleri', 'tavuk-lezzetleri', 'fa-drumstick-bite', 3),
            (4, 'Kampanyalı Menüler', 'kampanyali-menuler', 'fa-fire', 4),
            (5, 'Çıtır Atıştırmalıklar', 'atistirmaliklar', 'fa-cookie-bite', 5),
            (6, 'Tatlılar & İçecekler', 'tatlilar-icecekler', 'fa-ice-cream', 6)");

        $db->exec("INSERT INTO products (id, category_id, name, slug, description, price, original_price, discount_tag, image, allergens, spiciness, calories, is_featured) VALUES
            (1, 1, 'Tezgah Special Double Smash Burger', 'tezgah-special-smash-burger', '200gr Çift Kat Dana Kaburga Köftesi, Karamelize Soğan, Özel Tezgah Sos, Çift Cheddar Peyniri, Çıtır Soğan ve Jalapeno Turşusu.', 340.00, 400.00, '%15 İNDİRİM', 'assets/images/food/tezgah_special_1786727453215.jpg', 'gluten,egg,milk', 1, 850, 1),
            (2, 1, 'Classic Double Cheeseburger', 'classic-double-cheeseburger', '180gr Çift Smash Dana Köfte, Bol Erimiş Cheddar Peyniri, Kornişon Turşu, Küp Soğan ve Özel Burger Sosu.', 310.00, 360.00, '%14 DAMPİNG', 'assets/images/food/cheeseburger_1786729371441.jpg', 'gluten,egg,milk', 0, 790, 1),
            (3, 1, 'Hero Double Bacon Smash Burger', 'hero-double-bacon-burger', '180gr Dana Kıyma Köftesi, Füme Dana Kaburga Bacon, Duble Cheddar, Brioche Ekmek ve Trüflü Mayonez.', 360.00, NULL, NULL, 'assets/images/food/tezgah_hero_burger_1786727437034.jpg', 'gluten,egg,milk,mustard', 0, 920, 1),
            (4, 2, 'Trüflü Mantarlı Gourmet Burger', 'truflu-mantarli-gourmet-burger', 'Sote Edilmiş Trüf Mantarları, Swiss Peyniri, Mühürlenmiş Smash Köfte ve Özel Trüf Mayonez.', 390.00, 450.00, '%13 İNDİRİM', 'assets/images/food/truffle_burger_1786729391585.jpg', 'gluten,egg,milk', 0, 880, 1),
            (5, 3, 'Crispy Buttermilk Chicken Burger', 'crispy-buttermilk-chicken', 'Özel Baharatlarla Marine Edilmiş Çıtır Tavuk Göğsü, Lahana Coleslaw, Tatlı Acı Mayonez ve Brioche Ekmek.', 270.00, 320.00, '%16 DAMPİNG', 'assets/images/food/crispy_chicken_1786727470747.jpg', 'gluten,egg,milk,sesame', 2, 740, 1),
            (6, 4, 'Efsane İkili Kampanya Menüsü', 'efsane-ikili-kampanya-menusu', '1x Tezgah Special Burger + 1x Crispy Chicken + Büyük Boy Peynirli Patates Kızartması + 2x Soğuk İçecek.', 580.00, 720.00, '%19 FIRSAT', 'assets/images/food/tezgah_special_1786727453215.jpg', 'gluten,egg,milk,mustard,sesame', 1, 1550, 1),
            (7, 5, 'Çıtır Soğan Halkaları (8''li)', 'citir-sogan-halkalari', 'Özel Sarımsaklı Dip Sos İle Altın Sarısı Çıtır Soğan Halkaları.', 130.00, NULL, NULL, 'assets/images/food/onion_rings_1786729438800.jpg', 'gluten,egg', 0, 420, 0),
            (8, 5, 'Loaded Cheese & Jalapeno Fries', 'loaded-cheese-fries', 'Özel Baharatlı Patates Kızartması, Sıcak Cheddar Sos, Çıtır Bacon Parçaları ve Jalapeno Turşusu.', 160.00, NULL, NULL, 'assets/images/food/crispy_chicken_1786727470747.jpg', 'milk', 1, 510, 0),
            (9, 6, 'Maraş Kesme Dondurmalı Sufle', 'maras-dondurmali-sufle', 'Sıcak Çikolatalı Akışkan Sufle, Yanında Hakiki Kahramanmaraş Kesme Dondurması İle.', 180.00, 220.00, '%18 İNDİRİM', 'assets/images/food/sufle_1786729414852.jpg', 'gluten,egg,milk,nuts', 0, 480, 0)");
    }
}

function getDB() {
    global $db;
    return $db;
}
