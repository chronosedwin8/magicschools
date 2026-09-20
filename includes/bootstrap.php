<?php
/**
 * Carga común: configuración, sesión, base de datos y utilidades.
 */
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('AMSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/mercadopago.php';
require_once __DIR__ . '/mp_checkout.php';
require_once __DIR__ . '/licenses.php';
require_once __DIR__ . '/plans.php';

apply_plan_overrides();
