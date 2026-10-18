<?php
/**
 * PLANTILLA de configuración. Copia este archivo como config.php y completa
 * los datos. config.php NO se versiona (ver .gitignore).
 */
// ---------------------------------------------------------------------------
// Marca
// ---------------------------------------------------------------------------
define('BRAND_NAME', 'MagicSchools');           // Nombre visible del producto
define('BRAND_SHORT', 'MagicSchools');
define('BRAND_SUFFIX', '');                     // Sufijo del logotipo (p. ej. 'IA'); vacío = sin sufijo
// ---------------------------------------------------------------------------
// VENDEDOR: la empresa que factura al cliente final y firma los términos.
// Su país determina el marco legal que se muestra en legal.php:
//   'Colombia' -> Ley 1480 de 2011 y Ley 1581 de 2012
//   'España'   -> LSSI-CE (Ley 34/2002) y RGPD / LOPDGDD
// Los campos vacíos no se muestran en ninguna página.
// ---------------------------------------------------------------------------
define('COMPANY_NAME', 'Grupo Logic SAS Latinoamerica');  // Razón social del vendedor
define('COMPANY_COUNTRY', 'Colombia');
define('COMPANY_TAX_ID', '');     // NIT (Colombia) o NIF (España). Ej. '901234567-8'
define('COMPANY_ADDRESS', '');    // domicilio fiscal, si se desea publicar
define('COMPANY_REGISTRY', '');   // datos registrales (Cámara de Comercio / Registro Mercantil)

// Segunda entidad del grupo (opcional). Se muestra como operadora, no como vendedora.
define('OPERATOR_NAME', '');      // p. ej. la sociedad española del grupo
define('OPERATOR_COUNTRY', '');
define('OPERATOR_TAX_ID', '');

// Distribución autorizada (opcional): si vendes licencias de otra plataforma,
// decláralo aquí y se mostrará de forma visible en el sitio.
define('RESELLER_OF', '');        // p. ej. 'MagicSchool' si distribuyes una plataforma de terceros
define('RESELLER_TERRITORY', ''); // p. ej. 'Colombia y Latinoamérica'

// URL donde el cliente usa la plataforma contratada (opcional).
// Si se define, aparece un acceso visible en el sitio y en el portal.
define('PLATFORM_URL', '');

define('SUPPORT_EMAIL', 'gestion@grupologiclatam.com');
define('SUPPORT_PHONE', '');   // Opcional: si se deja vacío, no se muestra en ninguna página

/** Etiqueta de la identificación fiscal según el país del vendedor. */
function tax_label(): string
{
    return COMPANY_COUNTRY === 'España' ? 'NIF' : 'NIT';
}

/** ¿El marco legal aplicable es el español? */
function is_spanish_law(): bool
{
    return COMPANY_COUNTRY === 'España';
}

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
define('ADMIN_PASSWORD_HASH', '');

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
// Herramientas de IA (Google Gemini)
// La clave viaja en la cabecera x-goog-api-key, nunca en la URL ni en los logs.
// ---------------------------------------------------------------------------
define('AI_PROVIDER', 'gemini');
define('AI_API_KEY', '');
define('AI_MODEL_FAST', 'gemini-2.5-flash');   // herramientas del día a día
define('AI_MODEL_SMART', 'gemini-2.5-pro');    // documentos largos (PIAR)
define('AI_TIMEOUT', 180);                     // segundos de espera a la API
define('AI_MAX_PENDING_SECONDS', 420);         // una generación colgada se marca como error

// Precio por millón de tokens en dólares, para calcular el costo de cada generación.
// Se usa en el panel de costos para verificar el margen.
$AI_PRICES = [
    'gemini-2.5-flash' => ['in' => 0.30, 'out' => 2.50],
    'gemini-2.5-pro'   => ['in' => 1.25, 'out' => 10.00],
];

// ---------------------------------------------------------------------------
// Tutor con avatar (Tavus). Se activa en la fase 4.
// El cobro corre por minuto desde que se crea la conversación, así que los
// topes y el corte por inactividad no son opcionales.
// ---------------------------------------------------------------------------
define('TAVUS_API_KEY', '');                 // clave de Tavus
define('TAVUS_REPLICA_ID', 'r72f7f7f7c8b');  // avatar "Daniel"
define('TAVUS_PERSONA_ID', '');              // se crea con el script de preparación
define('TAVUS_MAX_MINUTES', 15);             // duración máxima de una conversación
define('TAVUS_IDLE_SECONDS', 120);           // 2 min sin interacción cierran la llamada
define('TAVUS_MONTHLY_BUDGET_MIN', 300);     // tope de minutos al mes para todo el sitio
define('TAVUS_COST_PER_MIN_USD', 0.35);      // tarifa de Tavus, para calcular el margen     // tope de minutos al mes para todo el sitio

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
        'audience'    => 'institucion',
        'payment_link' => 'https://mpago.li/2QYmYxX',
        // Herramientas de IA: generaciones al mes por licencia y tope por docente
        'ai_monthly_quota' => 1500,
        'ai_member_quota'  => 150,
        // Tutor con avatar: minutos al mes por licencia y tope por docente
        'tutor_minutes'        => 40,
        'tutor_member_minutes' => 10,
        'features'    => [
            'Hasta 60 usuarios (docentes y directivos)',
            '28 herramientas de IA para docentes, y crecen cada mes',
            'Material listo para entregar a los estudiantes',
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
        'audience'    => 'institucion',
        'payment_link' => 'https://mpago.li/18fzoqq',
        'ai_monthly_quota' => 5000,
        'ai_member_quota'  => 200,
        'tutor_minutes'        => 100,
        'tutor_member_minutes' => 10,
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
    // -----------------------------------------------------------------------
    // Planes individuales para docentes (cobro mensual).
    // Los precios se fijaron con los costos de IA REALES medidos en el panel
    // de costos, dejando como mínimo un 60 % de margen después de la API y de
    // la comisión de la pasarela:  precio = costo / (0,40 − comisión).
    // -----------------------------------------------------------------------
    'docente' => [
        'code'        => 'docente',
        'name'        => 'Docente Esencial',
        'tagline'     => 'Para un docente que quiere ahorrar horas de preparación.',
        'price'       => 24900,
        'seats'       => 1,
        'months'      => 1,
        'featured'    => false,
        'audience'    => 'docente',
        'payment_link' => '',
        'ai_monthly_quota' => 120,
        'ai_member_quota'  => 120,
        'tutor_minutes'        => 0,
        'tutor_member_minutes' => 0,
        'features'    => [
            '120 generaciones de IA al mes',
            'Las 12 herramientas del catálogo',
            'Descargas en PDF, Word, PowerPoint y HTML',
            'Biblioteca con todos tus documentos',
            'Cobro mensual, cancelas cuando quieras',
        ],
    ],
    'docente_pro' => [
        'code'        => 'docente_pro',
        'name'        => 'Docente Pro',
        'tagline'     => 'Para quien prepara PIAR, evaluaciones y material cada semana.',
        'price'       => 49900,
        'seats'       => 1,
        'months'      => 1,
        'featured'    => true,
        'audience'    => 'docente',
        'payment_link' => '',
        'ai_monthly_quota' => 400,
        'ai_member_quota'  => 400,
        'tutor_minutes'        => 0,
        'tutor_member_minutes' => 0,
        'features'    => [
            '400 generaciones de IA al mes',
            'PIAR completo según el Decreto 1421 de 2017',
            'Generador de exámenes con hoja de respuestas',
            'Refinamiento ilimitado de resultados',
            'Descargas en PDF, Word, PowerPoint y HTML',
            'Cobro mensual, cancelas cuando quieras',
        ],
    ],
    'docente_tutor' => [
        'code'        => 'docente_tutor',
        'name'        => 'Docente Pro + Tutor',
        'tagline'     => 'Todo lo anterior y conversaciones con el tutor con avatar.',
        'price'       => 129900,
        'seats'       => 1,
        'months'      => 1,
        'featured'    => false,
        'audience'    => 'docente',
        'payment_link' => '',
        'ai_monthly_quota' => 400,
        'ai_member_quota'  => 400,
        'tutor_minutes'        => 20,
        'tutor_member_minutes' => 20,
        'features'    => [
            'Todo lo del plan Docente Pro',
            '20 minutos al mes con el tutor con avatar',
            'Práctica de clases, retroalimentación y dudas pedagógicas',
            'La conversación se cierra sola tras 2 minutos sin interacción',
            'Cobro mensual, cancelas cuando quieras',
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
