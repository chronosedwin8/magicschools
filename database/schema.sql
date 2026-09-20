-- Esquema de base de datos del portal de licencias
CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(120) NOT NULL,
    email           VARCHAR(190) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    role            ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    institution    VARCHAR(190) NOT NULL,
    document_type   VARCHAR(10)  NOT NULL DEFAULT 'NIT',
    document_number VARCHAR(30)  NOT NULL,
    phone           VARCHAR(30)  NULL,
    city            VARCHAR(80)  NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at   DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference           VARCHAR(40)  NOT NULL UNIQUE,
    user_id             INT UNSIGNED NOT NULL,
    plan_code           VARCHAR(30)  NOT NULL,
    plan_name           VARCHAR(100) NOT NULL,
    seats               INT UNSIGNED NOT NULL,
    amount              DECIMAL(14,2) NOT NULL,
    currency            CHAR(3) NOT NULL DEFAULT 'COP',
    status              ENUM('pending','approved','rejected','cancelled','refunded','charged_back','review') NOT NULL DEFAULT 'pending',
    status_detail       VARCHAR(120) NULL,
    mp_preference_id    VARCHAR(80)  NULL,
    mp_payment_id       VARCHAR(40)  NULL,
    payment_url         VARCHAR(500) NULL,
    payment_method      VARCHAR(40)  NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NULL,
    paid_at             DATETIME NULL,
    INDEX idx_orders_user (user_id),
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS licenses (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id    INT UNSIGNED NOT NULL UNIQUE,
    user_id     INT UNSIGNED NOT NULL,
    plan_code   VARCHAR(30)  NOT NULL,
    plan_name   VARCHAR(100) NOT NULL,
    license_key VARCHAR(40)  NOT NULL UNIQUE,
    seats       INT UNSIGNED NOT NULL,
    starts_at   DATETIME NOT NULL,
    expires_at  DATETIME NOT NULL,
    status      ENUM('active','suspended') NOT NULL DEFAULT 'active',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_licenses_user (user_id),
    CONSTRAINT fk_licenses_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_licenses_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS license_members (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    license_id  INT UNSIGNED NOT NULL,
    name        VARCHAR(120) NOT NULL,
    email       VARCHAR(190) NOT NULL,
    role        VARCHAR(30)  NOT NULL DEFAULT 'docente',
    area        VARCHAR(120) NULL,
    access_code VARCHAR(20)  NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_member (license_id, email),
    CONSTRAINT fk_members_license FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_events (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id      INT UNSIGNED NOT NULL,
    mp_payment_id VARCHAR(40)  NULL,
    status        VARCHAR(30)  NOT NULL,
    status_detail VARCHAR(120) NULL,
    amount        DECIMAL(14,2) NULL,
    payload       MEDIUMTEXT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_events_order (order_id),
    CONSTRAINT fk_events_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_requests (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120) NOT NULL,
    email       VARCHAR(190) NOT NULL,
    institution VARCHAR(190) NULL,
    phone       VARCHAR(30)  NULL,
    message     TEXT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ajustes de planes editados desde el panel de administración
-- (reemplazan los valores por defecto definidos en config.php)
CREATE TABLE IF NOT EXISTS plan_settings (
    code        VARCHAR(30)  NOT NULL PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    tagline     VARCHAR(200) NULL,
    price       DECIMAL(14,2) NOT NULL,
    seats       INT UNSIGNED NOT NULL,
    months      INT UNSIGNED NOT NULL,
    featured    TINYINT(1) NOT NULL DEFAULT 0,
    features    TEXT NULL,
    updated_by  INT UNSIGNED NULL,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
