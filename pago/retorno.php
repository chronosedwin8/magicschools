<?php
/**
 * URL de retorno (back_urls) de Mercado Pago.
 * Verifica el pago directamente contra la API antes de activar la licencia.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$reference = (string) ($_GET['external_reference'] ?? '');
$paymentId = (string) ($_GET['payment_id'] ?? $_GET['collection_id'] ?? '');
$order = $reference !== '' ? order_by_reference($reference) : null;

if (!$order) {
    flash('error', 'No encontramos la orden asociada al pago.');
    redirect(current_user() ? 'portal/' : '');
}

// Consultar el estado real del pago (nunca confiar en los parámetros de la URL)
if (mp_enabled() && $paymentId !== '' && $paymentId !== 'null') {
    $payment = mp_get_payment($paymentId);
    if ($payment && (string) ($payment['external_reference'] ?? '') === $order['reference']) {
        $order = apply_payment($payment) ?? $order;
    }
} elseif (mp_enabled() && $order['status'] !== 'approved') {
    // Retorno de PSE (callback_url) u otro sin payment_id: consultar por la referencia
    $payment = $order['mp_payment_id'] ? mp_get_payment((string) $order['mp_payment_id']) : null;
    $order = ($payment && ($payment['external_reference'] ?? '') === $order['reference'])
        ? (apply_payment($payment) ?? $order)
        : (sync_order_with_mp($order) ?? $order);
}

// Sesión: la orden pertenece al cliente que la creó
$user = current_user();
if (!$user || (int) $user['id'] !== (int) $order['user_id']) {
    // El comprador puede volver en otro navegador: se pide iniciar sesión
    $_SESSION['_intended'] = url('pago/resultado.php?ref=' . urlencode($order['reference']));
    flash('info', 'Inicia sesión para ver el estado de tu compra ' . $order['reference'] . '.');
    redirect('portal/login.php');
}

switch ($order['status']) {
    case 'approved':
        flash('success', '¡Pago aprobado! Tu ' . $order['plan_name'] . ' ya está activa. Agrega ahora a los usuarios de tu institución.');
        $license = db_one('SELECT id FROM licenses WHERE order_id = ?', [$order['id']]);
        redirect($license ? 'portal/licencia.php?id=' . $license['id'] : 'portal/');
    case 'review':
        flash('warning', 'Recibimos tu pago y lo estamos verificando. Te contactaremos si necesitamos algo más.');
        redirect('portal/pedidos.php');
    default:
        // Pendiente o rechazado: página de resultado con opción de reintentar
        redirect('pago/resultado.php?ref=' . urlencode($order['reference']));
}
