<?php
/**
 * Carga común del panel de administración: exige rol admin y define el menú.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_admin();
$isAdminArea = true;
$areaLabel = 'Administración';
$nav = [
    'dashboard' => ['admin/', '📊', 'Resumen'],
    'pedidos'   => ['admin/pedidos.php', '🧾', 'Pedidos y pagos'],
    'licencias' => ['admin/licencias.php', '🎟️', 'Licencias'],
    'clientes'  => ['admin/clientes.php', '🏫', 'Clientes'],
    'precios'   => ['admin/precios.php', '💲', 'Precios y planes'],
    'contactos' => ['admin/contactos.php', '✉️', 'Solicitudes de contacto'],
    'cuenta'    => ['portal/cuenta.php', '👤', 'Mi cuenta'],
];

$ORDER_STATUS = [
    'approved'     => ['Aprobado', 'success'],
    'pending'      => ['Pendiente', 'warning'],
    'review'       => ['En verificación', 'warning'],
    'rejected'     => ['Rechazado', 'danger'],
    'cancelled'    => ['Cancelado', 'muted'],
    'refunded'     => ['Reembolsado', 'muted'],
    'charged_back' => ['Contracargo', 'danger'],
];

/** Redirige de vuelta a la misma página conservando filtros. */
function admin_back(string $page): never
{
    $qs = $_POST['_qs'] ?? '';
    redirect('admin/' . $page . ($qs !== '' ? '?' . preg_replace('/[^\w=&%.\-+@]/', '', $qs) : ''));
}

function admin_qs_field(): string
{
    return '<input type="hidden" name="_qs" value="' . e($_SERVER['QUERY_STRING'] ?? '') . '">';
}
