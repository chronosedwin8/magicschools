<?php
/**
 * PLANTILLA de configuración. Copia este archivo como config.php y completa
 * los datos. config.php NO se versiona (ver .gitignore).
 */
// ---------------------------------------------------------------------------
// Marca
// ---------------------------------------------------------------------------
define('BRAND_NAME', 'AulaMágica IA');          // Nombre visible del producto
define('BRAND_SHORT', 'AulaMágica');
define('COMPANY_NAME', 'AulaMágica S.L.');      // Razón social real del vendedor
// Identificación del prestador: la LSSI-CE (Ley 34/2002, art. 10) exige mostrar
// denominación social, NIF, domicilio, correo y datos registrales.
// Estos tres datos son opcionales: si se dejan vacíos, no se muestran en ningún lado.
// La actividad es solo en línea, sin establecimiento abierto al público.
define('COMPANY_COUNTRY', 'España');
define('COMPANY_NIF', '');        // p. ej. 'B12345678'
define('COMPANY_ADDRESS', '');    // domicilio fiscal, si se desea publicar
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
// Cobros: se usan links de pago de Mercado Pago (uno por plan).
// El link de cada plan se define abajo en $PLANS['...']['payment_link'] y se
// puede cambiar en cualquier momento desde Administración > Precios y planes.
// El pago ocurre fuera del sitio, así que la licencia se activa cuando el
// administrador confirma el pedido en Administración > Pedidos y pagos.
// ---------------------------------------------------------------------------
define('PAYMENT_PROVIDER', 'Mercado Pago');

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
        'payment_link' => 'https://mpago.li/2QYmYxX',
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
        'payment_link' => 'https://mpago.li/18fzoqq',
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
