<?php
/**
 * Instalador: crea la base de datos y las tablas.
 * Uso: http://localhost:8080/magicschool/install.php  o  php install.php
 * Por seguridad, elimina o bloquea este archivo en producción.
 */
require_once __DIR__ . '/config.php';

$cli = PHP_SAPI === 'cli';

// En producción el instalador solo se ejecuta por consola (php install.php)
if (!$cli && IS_PRODUCTION) {
    http_response_code(403);
    exit('No disponible.');
}
$out = [];

try {
    $pdo = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', DB_HOST, DB_PORT), DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec('USE `' . DB_NAME . '`');
    $out[] = 'Base de datos "' . DB_NAME . '" lista.';

    $sql = file_get_contents(__DIR__ . '/database/schema.sql');
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
        if (preg_match('/^(--[^\n]*\n\s*)*$/', $stmt)) {
            continue;
        }
        $pdo->exec($stmt);
    }
    $out[] = 'Tablas creadas / verificadas correctamente.';

    // Migración: columna role en instalaciones anteriores
    $hasRole = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role'")->fetchColumn();
    if (!$hasRole) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('customer','admin') NOT NULL DEFAULT 'customer' AFTER password_hash");
        $out[] = 'Columna "role" agregada a usuarios.';
    }

    // Migración: enlace de pago pendiente (cupón Efecty / banco PSE)
    $hasPayUrl = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'payment_url'")->fetchColumn();
    if (!$hasPayUrl) {
        $pdo->exec('ALTER TABLE orders ADD COLUMN payment_url VARCHAR(500) NULL AFTER mp_payment_id');
        $out[] = 'Columna "payment_url" agregada a pedidos.';
    }

    // Cuenta de administrador: se crea si no existe; si existe, se promueve a admin
    // (la contraseña de una cuenta existente no se sobrescribe).
    $adminEmail = strtolower(ADMIN_EMAIL);
    $st = $pdo->prepare('SELECT id, role FROM users WHERE email = ?');
    $st->execute([$adminEmail]);
    $admin = $st->fetch(PDO::FETCH_ASSOC);
    if (!$admin) {
        $pdo->prepare("INSERT INTO users (name, email, password_hash, role, institution, document_type, document_number)
                       VALUES (?, ?, ?, 'admin', ?, 'NIT', '-')")
            ->execute([ADMIN_NAME, $adminEmail, ADMIN_PASSWORD_HASH, 'Administración ' . BRAND_SHORT]);
        $out[] = 'Administrador ' . $adminEmail . ' creado.';
    } elseif ($admin['role'] !== 'admin') {
        $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?")->execute([$admin['id']]);
        $out[] = 'Usuario ' . $adminEmail . ' promovido a administrador.';
    } else {
        $out[] = 'Administrador ' . $adminEmail . ' verificado.';
    }
    $ok = true;
} catch (PDOException $e) {
    $out[] = 'Error: ' . $e->getMessage();
    $ok = false;
}

if ($cli) {
    echo implode(PHP_EOL, $out), PHP_EOL;
    exit($ok ? 0 : 1);
}
?><!doctype html>
<html lang="es"><head><meta charset="utf-8"><title>Instalación</title>
<style>body{font-family:system-ui;background:#f6f2ff;display:grid;place-items:center;min-height:100vh;margin:0}
.box{background:#fff;padding:32px 40px;border-radius:16px;box-shadow:0 10px 40px rgba(60,20,140,.12);max-width:520px}
h1{color:#3b1a8f;margin-top:0}li{margin:6px 0}a{color:#6b3ef2;font-weight:600}</style></head>
<body><div class="box"><h1><?= $ok ? '✅ Instalación completa' : '⚠️ Error en la instalación' ?></h1>
<ul><?php foreach ($out as $line): ?><li><?= htmlspecialchars($line) ?></li><?php endforeach; ?></ul>
<?php if ($ok): ?><p><a href="./">Ir al sitio →</a></p><?php endif; ?></div></body></html>
