<?php
/**
 * PLANTILLA de configuración. Copia este archivo como config.php y completa
 * las credenciales. config.php NO se versiona (ver .gitignore).
 */
// ---------------------------------------------------------------------------
// Marca
// ---------------------------------------------------------------------------
define('BRAND_NAME', 'AulaMágica IA');          // Nombre visible del producto
define('BRAND_SHORT', 'AulaMágica');
define('COMPANY_NAME', 'AulaMágica S.L.');      // Razón social real del vendedor
// Identificación del prestador: la LSSI-CE (Ley 34/2002, art. 10) exige mostrar
// denominación social, NIF, domicilio, correo y datos registrales.
define('COMPANY_COUNTRY', 'España');
define('COMPANY_NIF', 'NIF pendiente');
define('COMPANY_ADDRESS', 'Domicilio pendiente, España');
define('COMPANY_REGISTRY', '');   // p. ej. 'Inscrita en el Registro Mercantil de Madrid, tomo X, folio Y, hoja M-Z'
define('SUPPORT_EMAIL', 'soporte@aulamagica.co');
define('SUPPORT_PHONE', '+57 300 000 0000');

// ---------------------------------------------------------------------------
// Entorno y URL base (sin barra final)
// En el dominio de producción se fuerza https://magicschools.es; en local
// (localhost:8080/magicschool) la URL se detecta automáticamente.
// ---------------------------------------------------------------------------
define('PRODUCTION_DOMAIN', 'magicschools.es');
// El servidor redirige magicschools.es -> www.magicschools.es, así que www es el canónico
define('CANONICAL_URL', 'https://www.' . PRODUCTION_DOMAIN);
$__host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
define('IS_PRODUCTION', $__host === PRODUCTION_DOMAIN || str_ends_with($__host, '.' . PRODUCTION_DOMAIN));
define('BASE_URL', IS_PRODUCTION ? CANONICAL_URL : '');
unset($__host);

// ---------------------------------------------------------------------------
// Administrador del sitio (se crea con install.php en local y en producción).
// Solo se guarda el hash bcrypt de la contraseña. Para cambiarla desde consola:
//   php -r "echo password_hash('NuevaClave', PASSWORD_DEFAULT);"
// y luego cámbiala en Portal > Mi cuenta (install.php no sobrescribe claves existentes).
// ---------------------------------------------------------------------------
define('ADMIN_EMAIL', 'chronosedwin8@gmail.com');
define('ADMIN_NAME', 'Administrador');
define('ADMIN_PASSWORD_HASH', ''); // php -r "echo password_hash('TuClave', PASSWORD_DEFAULT);"

// ---------------------------------------------------------------------------
// Base de datos MySQL
// ---------------------------------------------------------------------------
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'magicschool');
define('DB_USER', '');
define('DB_PASS', '');

// ---------------------------------------------------------------------------
// Mercado Pago (Checkout Pro)
// Credenciales: https://www.mercadopago.com.co/developers/panel/app
//  - MP_ACCESS_TOKEN: "Access Token" (TEST-... para pruebas, APP_USR-... producción)
//  - MP_PUBLIC_KEY:   "Public Key"
//  - MP_WEBHOOK_SECRET: clave secreta de notificaciones (Webhooks) para validar firma
// Si MP_ACCESS_TOKEN está vacío y DEMO_MODE = true se usa un simulador de pago local.
// ---------------------------------------------------------------------------
// Credenciales de PRODUCCIÓN (cobros reales)
define('MP_ACCESS_TOKEN', '');   // Access Token de Mercado Pago
define('MP_PUBLIC_KEY', '');     // Public Key de Mercado Pago
define('MP_WEBHOOK_SECRET', ''); // Clave secreta de Webhooks
// Nombre que aparece en el extracto de la tarjeta / detalle del pago.
// Usa el nombre de la aplicación de Mercado Pago. Máx. 22 caracteres, sin tildes.
define('MP_STATEMENT_DESCRIPTOR', 'MAGICSCHOOLS');
// 'custom' = pantalla de cobro propia en el sitio (Checkout API: tarjeta, PSE, Efecty)
// 'pro'    = redirección a Checkout Pro de Mercado Pago
define('MP_CHECKOUT_MODE', 'custom');
define('MP_SANDBOX', false);       // false = cobros reales (init_point)
define('DEMO_MODE', false);        // Simulador desactivado

// ---------------------------------------------------------------------------
// Planes / licencias (valores en COP)
// ---------------------------------------------------------------------------
define('CURRENCY', 'COP');

$PLANS = [
    'escuela' => [
        'code'        => 'escuela',
        'name'        => 'Licencia Escuela',
        'tagline'     => 'Para una institución educativa con una o varias jornadas.',
        'price'       => 4600000,
        'seats'       => 60,
        'months'      => 12,
        'featured'    => false,
        'features'    => [
            'Hasta 60 usuarios (docentes y directivos)',
            'Más de 80 herramientas de IA para docentes',
            'Más de 50 herramientas para estudiantes',
            'Panel de administración de la institución',
            'Capacitación de arranque virtual (2 horas)',
            'Soporte por correo y chat',
            'Vigencia de 12 meses',
        ],
    ],
    'volumen' => [
        'code'        => 'volumen',
        'name'        => 'Licencia por Volumen',
        'tagline'     => 'Para redes de colegios, sedes múltiples y secretarías.',
        'price'       => 10000000,
        'seats'       => 250,
        'months'      => 12,
        'featured'    => true,
        'features'    => [
            'Hasta 250 usuarios en varias sedes',
            'Todo lo incluido en la Licencia Escuela',
            'Herramientas personalizadas para tu red',
            'Reportes de uso e impacto por sede',
            'Plan de formación docente (8 horas)',
            'Gerente de cuenta dedicado',
            'Soporte prioritario',
            'Vigencia de 12 meses',
        ],
    ],
];

// Roles disponibles al agregar usuarios a una licencia
$MEMBER_ROLES = [
    'docente'       => 'Docente',
    'coordinador'   => 'Coordinador(a)',
    'directivo'     => 'Directivo(a)',
    'administrador' => 'Administrador(a)',
    'orientador'    => 'Orientador(a)',
];

// Zona horaria
date_default_timezone_set('America/Bogota');

// Mostrar errores solo en desarrollo
define('APP_DEBUG', !IS_PRODUCTION);   // Errores visibles solo en local
ini_set('display_errors', APP_DEBUG ? '1' : '0');
error_reporting(E_ALL);
