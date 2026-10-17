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
    payment_link VARCHAR(500) NULL,
    updated_by  INT UNSIGNED NULL,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================================================
-- Módulo de herramientas de IA para docentes
-- ===========================================================================

-- Perfil del docente: se pregunta una vez y se inyecta en todos los prompts
CREATE TABLE IF NOT EXISTS teacher_profiles (
    user_id     INT UNSIGNED NOT NULL PRIMARY KEY,
    grades      VARCHAR(120) NULL,
    areas       VARCHAR(190) NULL,
    subjects    VARCHAR(190) NULL,
    approach    VARCHAR(255) NULL,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_teacher_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Una fila por generación. Conserva nombre y correo para que el historial
-- sobreviva a la baja del docente (los miembros se borran físicamente).
CREATE TABLE IF NOT EXISTS ai_generations (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    license_id     INT UNSIGNED NOT NULL,
    user_id        INT UNSIGNED NULL,
    author_name    VARCHAR(120) NULL,
    author_email   VARCHAR(190) NULL,
    tool_slug      VARCHAR(40)  NOT NULL,
    tool_name      VARCHAR(100) NOT NULL,
    status         ENUM('pending','done','error') NOT NULL DEFAULT 'pending',
    title          VARCHAR(190) NULL,
    input_json     MEDIUMTEXT NULL,
    output_text    MEDIUMTEXT NULL,
    output_format  VARCHAR(10) NOT NULL DEFAULT 'markdown',
    model          VARCHAR(60) NULL,
    prompt_tokens  INT UNSIGNED NOT NULL DEFAULT 0,
    output_tokens  INT UNSIGNED NOT NULL DEFAULT 0,
    cost_micro_usd BIGINT UNSIGNED NOT NULL DEFAULT 0,
    error          VARCHAR(255) NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    finished_at    DATETIME NULL,
    INDEX idx_ai_gen_license (license_id, created_at),
    INDEX idx_ai_gen_user (user_id, created_at),
    INDEX idx_ai_gen_status (status, created_at),
    CONSTRAINT fk_ai_gen_license FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE,
    CONSTRAINT fk_ai_gen_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contador por licencia, usuario y mes. user_id = 0 agrega el total de la licencia.
CREATE TABLE IF NOT EXISTS ai_usage_month (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    license_id     INT UNSIGNED NOT NULL,
    user_id        INT UNSIGNED NOT NULL DEFAULT 0,
    period         CHAR(6) NOT NULL,
    generations    INT UNSIGNED NOT NULL DEFAULT 0,
    prompt_tokens  BIGINT UNSIGNED NOT NULL DEFAULT 0,
    output_tokens  BIGINT UNSIGNED NOT NULL DEFAULT 0,
    cost_micro_usd BIGINT UNSIGNED NOT NULL DEFAULT 0,
    updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usage (license_id, user_id, period),
    CONSTRAINT fk_ai_usage_license FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Resultados que el docente guarda en su biblioteca (puede editarlos)
CREATE TABLE IF NOT EXISTS ai_documents (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    generation_id INT UNSIGNED NULL,
    license_id    INT UNSIGNED NOT NULL,
    user_id       INT UNSIGNED NULL,
    tool_slug     VARCHAR(40)  NOT NULL,
    title         VARCHAR(190) NOT NULL,
    content       MEDIUMTEXT NOT NULL,
    format        VARCHAR(10) NOT NULL DEFAULT 'markdown',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ai_doc_license (license_id, created_at),
    INDEX idx_ai_doc_user (user_id, created_at),
    CONSTRAINT fk_ai_doc_generation FOREIGN KEY (generation_id) REFERENCES ai_generations(id) ON DELETE SET NULL,
    CONSTRAINT fk_ai_doc_license FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE,
    CONSTRAINT fk_ai_doc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Declaración de la institución sobre la autorización de los acudientes
-- (Ley 1581 de 2012: datos de menores y datos sensibles)
CREATE TABLE IF NOT EXISTS ai_consents (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    license_id  INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NULL,
    version     VARCHAR(20) NOT NULL DEFAULT '1',
    ip_hash     CHAR(64) NULL,
    accepted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_consent (license_id, version),
    CONSTRAINT fk_ai_consent_license FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Solicitudes de los titulares sobre sus datos personales
-- (Ley 1581 de 2012: acceso, actualización, rectificación, supresión y revocatoria)
CREATE TABLE IF NOT EXISTS data_requests (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kind        VARCHAR(20)  NOT NULL,
    name        VARCHAR(120) NOT NULL,
    email       VARCHAR(190) NOT NULL,
    document    VARCHAR(40)  NULL,
    institution VARCHAR(190) NULL,
    detail      TEXT NULL,
    status      ENUM('pendiente','en_tramite','resuelta') NOT NULL DEFAULT 'pendiente',
    answered_at DATETIME NULL,
    note        VARCHAR(255) NULL,
    ip_hash     CHAR(64) NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_data_requests_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
