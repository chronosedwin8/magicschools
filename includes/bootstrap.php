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
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/seo.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/licenses.php';
require_once __DIR__ . '/plans.php';
require_once __DIR__ . '/ai.php';
require_once __DIR__ . '/ai_tools.php';
require_once __DIR__ . '/ai_tools_extra.php';
require_once __DIR__ . '/ai_render.php';
require_once __DIR__ . '/ai_render_extra.php';
require_once __DIR__ . '/mathtex.php';
require_once __DIR__ . '/usage.php';
require_once __DIR__ . '/ai_run.php';
require_once __DIR__ . '/ai_assist.php';
require_once __DIR__ . '/export.php';
require_once __DIR__ . '/tavus.php';
require_once __DIR__ . '/tutor.php';
require_once __DIR__ . '/partials/icon.php';

apply_plan_overrides();
