<?php
/**
 * Migrations. Each runs once. Idempotent.
 * All SQL is MariaDB/MySQL compatible — no exotic syntax.
 */

return [
    '001_users' => "CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(150) NOT NULL,
        email VARCHAR(190) NULL,
        phone VARCHAR(40) NOT NULL,
        password VARCHAR(255) NULL,
        address TEXT NULL,
        city VARCHAR(80) NULL,
        area VARCHAR(80) NULL,
        postal_code VARCHAR(20) NULL,
        status ENUM('active','blocked') NOT NULL DEFAULT 'active',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    '002_admins' => "CREATE TABLE IF NOT EXISTS admins (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('superadmin','admin') NOT NULL DEFAULT 'superadmin',
        last_login DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    '003_categories' => "CREATE TABLE IF NOT EXISTS categories (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        parent_id INT UNSIGNED NULL,
        name VARCHAR(150) NOT NULL,
        slug VARCHAR(180) NOT NULL UNIQUE,
        description TEXT NULL,
        image VARCHAR(255) NULL,
        icon VARCHAR(80) NULL,
        position INT NOT NULL DEFAULT 0,
        status ENUM('active','inactive') NOT NULL DEFAULT 'active',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_position (position)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    '004_products' => "CREATE TABLE IF NOT EXISTS products (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        slug VARCHAR(220) NOT NULL UNIQUE,
        sku VARCHAR(80) NOT NULL,
        category_id INT UNSIGNED NULL,
        short_description VARCHAR(500) NULL,
        description TEXT NULL,
        specifications TEXT NULL,
        price DECIMAL(12,2) NOT NULL DEFAULT 0,
        sale_price DECIMAL(12,2) NULL,
        cost DECIMAL(12,2) NULL,
        stock INT NOT NULL DEFAULT 0,
        low_stock_threshold INT NOT NULL DEFAULT 5,
        weight DECIMAL(10,2) NULL,
        brand VARCHAR(120) NULL,
        tags VARCHAR(255) NULL,
        main_image VARCHAR(255) NULL,
        gallery TEXT NULL,
        status ENUM('active','inactive','draft') NOT NULL DEFAULT 'active',
        is_featured TINYINT(1) NOT NULL DEFAULT 0,
        is_bestseller TINYINT(1) NOT NULL DEFAULT 0,
        is_new TINYINT(1) NOT NULL DEFAULT 1,
        seo_title VARCHAR(255) NULL,
        seo_description TEXT NULL,
        rating_avg DECIMAL(3,2) NOT NULL DEFAULT 0,
        rating_count INT NOT NULL DEFAULT 0,
        view_count INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL,
        INDEX idx_status (status),
        INDEX idx_category (category_id),
        INDEX idx_sku (sku),
        INDEX idx_featured (is_featured),
        INDEX idx_bestseller (is_bestseller),
        INDEX idx_new (is_new),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    '005_product_variants' => "CREATE TABLE IF NOT EXISTS product_variants (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        product_id INT UNSIGNED NOT NULL,
        name VARCHAR(80) NOT NULL,
        value VARCHAR(80) NOT NULL,
        price_adjust DECIMAL(12,2) NOT NULL DEFAULT 0,
        stock INT NOT NULL DEFAULT 0,
        sku VARCHAR(80) NULL,
        INDEX idx_product (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    '006_orders' => "CREATE TABLE IF NOT EXISTS orders (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        order_number VARCHAR(40) NOT NULL UNIQUE,
        user_id INT UNSIGNED NULL,
        full_name VARCHAR(150) NOT NULL,
        phone VARCHAR(40) NOT NULL,
        email VARCHAR(190) NULL,
        address TEXT NOT NULL,
        city VARCHAR(80) NOT NULL,
        area VARCHAR(80) NULL,
        postal_code VARCHAR(20) NULL,
        subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
        discount DECIMAL(12,2) NOT NULL DEFAULT 0,
        shipping_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
        total DECIMAL(12,2) NOT NULL DEFAULT 0,
        coupon_code VARCHAR(50) NULL,
        shipping_method VARCHAR(40) NULL,
        payment_method VARCHAR(40) NOT NULL,
        transaction_id VARCHAR(120) NULL,
        payment_proof VARCHAR(255) NULL,
        order_status ENUM('pending','approved','processing','shipped','out_for_delivery','delivered','cancelled') NOT NULL DEFAULT 'pending',
        payment_status ENUM('pending','submitted','verified','failed','refunded','cod_pending','cod_received') NOT NULL DEFAULT 'pending',
        notes TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL,
        INDEX idx_order_number (order_number),
        INDEX idx_phone (phone),
        INDEX idx_status (order_status),
        INDEX idx_payment (payment_status),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    '007_order_items' => "CREATE TABLE IF NOT EXISTS order_items (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        order_id INT UNSIGNED NOT NULL,
        product_id INT UNSIGNED NULL,
        name VARCHAR(200) NOT NULL,
        sku VARCHAR(80) NULL,
        price DECIMAL(12,2) NOT NULL,
        qty INT NOT NULL,
        subtotal DECIMAL(12,2) NOT NULL,
        variant_info VARCHAR(255) NULL,
        INDEX idx_order (order_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    '008_payment_methods' => "CREATE TABLE IF NOT EXISTS payment_methods (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(40) NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL,
        logo VARCHAR(255) NULL,
        instructions TEXT NULL,
        merchant_number VARCHAR(80) NULL,
        api_url VARCHAR(255) NULL,
        api_key VARCHAR(255) NULL,
        app_key VARCHAR(255) NULL,
        secret_key VARCHAR(255) NULL,
        api_user VARCHAR(120) NULL,
        api_pass VARCHAR(255) NULL,
        mode ENUM('manual','api') NOT NULL DEFAULT 'manual',
        status ENUM('active','inactive') NOT NULL DEFAULT 'active',
        position INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    '009_settings' => "CREATE TABLE IF NOT EXISTS settings (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT NULL,
        setting_group VARCHAR(50) NOT NULL DEFAULT 'general'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    '010_notifications' => "CREATE TABLE IF NOT EXISTS notifications (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        channel VARCHAR(40) NOT NULL,
        event VARCHAR(60) NOT NULL,
        payload TEXT NULL,
        status ENUM('queued','sent','failed') NOT NULL DEFAULT 'queued',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    '011_coupons' => "CREATE TABLE IF NOT EXISTS coupons (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) NOT NULL UNIQUE,
        type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
        value DECIMAL(12,2) NOT NULL DEFAULT 0,
        min_order DECIMAL(12,2) NOT NULL DEFAULT 0,
        max_discount DECIMAL(12,2) NULL,
        expiry_date DATE NULL,
        usage_limit INT NULL,
        used_count INT NOT NULL DEFAULT 0,
        status ENUM('active','inactive') NOT NULL DEFAULT 'active',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    '012_shipping_zones' => "CREATE TABLE IF NOT EXISTS shipping_zones (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(80) NOT NULL,
        fee DECIMAL(12,2) NOT NULL DEFAULT 0,
        eta_min INT NOT NULL DEFAULT 1,
        eta_max INT NOT NULL DEFAULT 3,
        status ENUM('active','inactive') NOT NULL DEFAULT 'active'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    '013_pages' => "CREATE TABLE IF NOT EXISTS pages (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(180) NOT NULL UNIQUE,
        title VARCHAR(200) NOT NULL,
        content LONGTEXT NULL,
        status ENUM('published','draft') NOT NULL DEFAULT 'published',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    '014_navigation' => "CREATE TABLE IF NOT EXISTS navigation_items (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        location ENUM('desktop','mobile','footer','bottom') NOT NULL DEFAULT 'desktop',
        parent_id INT UNSIGNED NULL,
        label VARCHAR(80) NOT NULL,
        url VARCHAR(255) NULL,
        icon VARCHAR(80) NULL,
        position INT NOT NULL DEFAULT 0,
        status ENUM('active','inactive') NOT NULL DEFAULT 'active'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    '015_homepage_sections' => "CREATE TABLE IF NOT EXISTS homepage_sections (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        section_key VARCHAR(60) NOT NULL UNIQUE,
        title VARCHAR(160) NULL,
        subtitle VARCHAR(255) NULL,
        content TEXT NULL,
        image VARCHAR(255) NULL,
        button_text VARCHAR(80) NULL,
        button_url VARCHAR(255) NULL,
        config TEXT NULL,
        position INT NOT NULL DEFAULT 0,
        status ENUM('enabled','disabled') NOT NULL DEFAULT 'enabled'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    '016_login_attempts' => "CREATE TABLE IF NOT EXISTS login_attempts (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(190) NOT NULL,
        ip VARCHAR(60) NOT NULL,
        success TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    '017_activity_logs' => "CREATE TABLE IF NOT EXISTS activity_logs (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        actor VARCHAR(80) NOT NULL,
        action VARCHAR(120) NOT NULL,
        details TEXT NULL,
        ip VARCHAR(60) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    '018_payment_logs' => "CREATE TABLE IF NOT EXISTS payment_logs (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        order_id INT UNSIGNED NULL,
        method VARCHAR(40) NOT NULL,
        request_payload TEXT NULL,
        response_payload TEXT NULL,
        status VARCHAR(20) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    '019_wishlist' => "CREATE TABLE IF NOT EXISTS wishlist (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NULL,
        session_id VARCHAR(80) NULL,
        product_id INT UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    '020_migrations' => "CREATE TABLE IF NOT EXISTS migrations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(100) NOT NULL UNIQUE,
        batch INT NOT NULL DEFAULT 1,
        executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];
