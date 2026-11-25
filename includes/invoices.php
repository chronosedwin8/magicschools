<?php
/**
 * Facturación con el marco español.
 *
 * La factura es un documento distinto del comprobante de pago: lleva su propia
 * numeración correlativa por serie, desglosa la base imponible y la cuota de
 * IVA, e identifica fiscalmente al expedidor y al destinatario, como exige el
 * Reglamento de facturación (Real Decreto 1619/2012, artículo 6).
 *
 * Al emitirla se copian todos los datos dentro de la fila: una factura no puede
 * cambiar después, aunque se editen la configuración o los datos del cliente.
 *
 * Lo que este módulo NO hace, y hay que resolver fuera de aquí:
 *  - No es un sistema informático de facturación verificable (Veri*factu,
 *    Real Decreto 1007/2023): no encadena registros con huella ni los remite a
 *    la AEAT, ni imprime el QR de verificación.
 *  - No decide la tributación de cada operación. El tipo y el régimen se
 *    configuran en Administración y los fija quien lleva la contabilidad.
 */

/** Ajustes de facturación, con sus valores por omisión. */
function invoice_config(): array
{
    return [
        'serie'          => setting('invoice_serie', 'A') ?: 'A',
        'seller_name'    => setting('invoice_seller_name', ''),
        'seller_tax_id'  => setting('invoice_seller_tax_id', ''),
        'seller_address' => setting('invoice_seller_address', ''),
        'seller_registry' => setting('invoice_seller_registry', ''),
        'seller_email'   => setting('invoice_seller_email', SUPPORT_EMAIL),
        'tax_mode'       => setting('invoice_tax_mode', 'repercutido'),
        'tax_rate'       => (float) setting('invoice_tax_rate', '21'),
        'tax_note'       => setting('invoice_tax_note', ''),
        'legal_note'     => setting('invoice_legal_note', invoice_legal_note_default()),
        'concept'        => setting('invoice_concept', 'Licencia de uso de la plataforma ' . BRAND_NAME),
    ];
}

/** Texto legal del pie de la factura, editable desde Administración. */
function invoice_legal_note_default(): string
{
    return "Operación de comercio electrónico: la contratación se realizó por vía electrónica a través de "
        . "https://www.magicschools.es, conforme a la Ley 34/2002 de servicios de la sociedad de la información y "
        . "de comercio electrónico (LSSI-CE) y al Real Decreto Legislativo 1/2007 de defensa de los consumidores y usuarios.\n"
        . "Esta factura se expide conforme al Reglamento de facturación aprobado por el Real Decreto 1619/2012.\n"
        . "La empresa expedidora tributa en España y cumple allí la totalidad de sus obligaciones fiscales. "
        . "No mantiene establecimiento permanente en otros países de Latinoamérica.";
}

/** ¿Está completa la configuración mínima para poder facturar? */
function invoice_config_missing(array $cfg = []): array
{
    $cfg = $cfg ?: invoice_config();
    $faltan = [];
    if (trim($cfg['seller_name']) === '')    $faltan[] = 'la razón social del expedidor';
    if (trim($cfg['seller_tax_id']) === '')  $faltan[] = 'el NIF del expedidor';
    if (trim($cfg['seller_address']) === '') $faltan[] = 'el domicilio fiscal del expedidor';
    if (trim($cfg['serie']) === '')          $faltan[] = 'la serie de facturación';
    return $faltan;
}

/**
 * Desglose de un importe que YA incluye el impuesto.
 * base = total / (1 + tipo);  cuota = total − base.
 *
 * @return array{base:float, cuota:float, total:float, tipo:float}
 */
function invoice_breakdown(float $total, float $tipo, string $modo = 'repercutido'): array
{
    if ($modo !== 'repercutido' || $tipo <= 0) {
        return ['base' => round($total, 2), 'cuota' => 0.0, 'total' => round($total, 2), 'tipo' => 0.0];
    }
    $base = round($total / (1 + $tipo / 100), 2);
    return ['base' => $base, 'cuota' => round($total - $base, 2), 'total' => round($total, 2), 'tipo' => $tipo];
}

/** Mención obligatoria cuando la operación no lleva IVA repercutido. */
function invoice_tax_note(string $modo, string $personalizada = ''): string
{
    if (trim($personalizada) !== '') {
        return trim($personalizada);
    }
    return match ($modo) {
        'no_sujeta' => 'Operación no sujeta al IVA español por las reglas de localización de los servicios '
            . 'prestados por vía electrónica (artículos 69 y 70 de la Ley 37/1992 del IVA).',
        'exenta'    => 'Operación exenta de IVA (artículo 20 de la Ley 37/1992 del IVA).',
        default     => '',
    };
}

function invoice_for_order(int $orderId): ?array
{
    return db_one('SELECT * FROM invoices WHERE order_id = ?', [$orderId]) ?: null;
}

function invoice_by_id(int $id): ?array
{
    return db_one('SELECT * FROM invoices WHERE id = ?', [$id]) ?: null;
}

/**
 * Emite la factura de un pedido pagado.
 *
 * La numeración se reserva dentro de una transacción con bloqueo de la serie,
 * para que dos emisiones simultáneas no repitan ni salten un número.
 *
 * @param array $datos destinatario e importes que el administrador confirma
 * @return array{ok:bool, invoice?:array, error?:string}
 */
function invoice_issue(array $order, array $datos, ?int $adminId = null): array
{
    if (($order['status'] ?? '') !== 'approved') {
        return ['ok' => false, 'error' => 'Solo se factura un pedido con el pago confirmado.'];
    }
    if ($ya = invoice_for_order((int) $order['id'])) {
        return ['ok' => true, 'invoice' => $ya];
    }
    $cfg = invoice_config();
    if ($faltan = invoice_config_missing($cfg)) {
        return ['ok' => false, 'error' => 'Antes de facturar falta configurar ' . implode(', ', $faltan) . '.'];
    }

    $modo  = in_array($datos['tax_mode'] ?? $cfg['tax_mode'], ['repercutido', 'no_sujeta', 'exenta'], true)
        ? ($datos['tax_mode'] ?? $cfg['tax_mode']) : 'repercutido';
    $tipo  = max(0.0, min(100.0, (float) ($datos['tax_rate'] ?? $cfg['tax_rate'])));
    $total = round((float) $order['amount'], 2);
    $d     = invoice_breakdown($total, $tipo, $modo);

    $pdo = db();
    try {
        $pdo->beginTransaction();

        // Reserva del siguiente número de la serie
        $serie = mb_substr(trim($cfg['serie']), 0, 10);
        $pdo->prepare('INSERT IGNORE INTO invoice_series (serie, last_number) VALUES (?, 0)')->execute([$serie]);
        $st = $pdo->prepare('SELECT last_number FROM invoice_series WHERE serie = ? FOR UPDATE');
        $st->execute([$serie]);
        $numero = (int) $st->fetchColumn() + 1;
        $pdo->prepare('UPDATE invoice_series SET last_number = ? WHERE serie = ?')->execute([$numero, $serie]);

        $completo = $serie . '-' . str_pad((string) $numero, 5, '0', STR_PAD_LEFT);
        $fechaOperacion = substr((string) ($order['paid_at'] ?: $order['created_at']), 0, 10);

        $pdo->prepare(
            'INSERT INTO invoices (order_id, serie, numero, numero_completo, issued_at, operation_date, order_reference,
                 seller_name, seller_tax_id, seller_address, seller_registry, seller_email,
                 buyer_name, buyer_tax_id, buyer_address, buyer_country, buyer_email,
                 concept, currency, total, tax_mode, tax_rate, tax_base, tax_amount, tax_note, legal_note, created_by)
             VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            (int) $order['id'], $serie, $numero, $completo, $fechaOperacion, (string) $order['reference'],
            $cfg['seller_name'], $cfg['seller_tax_id'], $cfg['seller_address'], $cfg['seller_registry'] ?: null, $cfg['seller_email'] ?: null,
            mb_substr(trim((string) ($datos['buyer_name'] ?? '')), 0, 160),
            mb_substr(trim((string) ($datos['buyer_tax_id'] ?? '')), 0, 40) ?: null,
            mb_substr(trim((string) ($datos['buyer_address'] ?? '')), 0, 255) ?: null,
            mb_substr(trim((string) ($datos['buyer_country'] ?? '')), 0, 60) ?: null,
            mb_substr(trim((string) ($datos['buyer_email'] ?? '')), 0, 190) ?: null,
            mb_substr(trim((string) ($datos['concept'] ?? $cfg['concept'])), 0, 255),
            (string) $order['currency'], $total, $modo, $d['tipo'], $d['base'], $d['cuota'],
            invoice_tax_note($modo, (string) ($datos['tax_note'] ?? $cfg['tax_note'])) ?: null,
            (string) ($datos['legal_note'] ?? $cfg['legal_note']),
            $adminId,
        ]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        app_log('facturacion', 'No se pudo emitir la factura', ['pedido' => $order['reference'], 'error' => $e->getMessage()]);
        return ['ok' => false, 'error' => 'No se pudo emitir la factura. Revisa el registro de la aplicación.'];
    }

    app_log('facturacion', 'Factura emitida', ['numero' => $completo, 'pedido' => $order['reference'], 'admin' => $adminId]);
    return ['ok' => true, 'invoice' => invoice_for_order((int) $order['id'])];
}

/** Próximo número que se usará en la serie. */
function invoice_next_number(string $serie): int
{
    $row = db_one('SELECT last_number FROM invoice_series WHERE serie = ?', [$serie]);
    return (int) ($row['last_number'] ?? 0) + 1;
}

/**
 * Fija el próximo número de la serie, para continuar la numeración que ya lleva
 * la contabilidad de la empresa.
 *
 * @return ?string mensaje de error, o null si quedó fijado
 */
function invoice_set_next_number(string $serie, int $proximo): ?string
{
    if ($proximo < 1 || $proximo > 99999999) {
        return 'El próximo número debe estar entre 1 y 99.999.999.';
    }
    $usado = db_one('SELECT MAX(numero) n FROM invoices WHERE serie = ?', [$serie]);
    if ((int) ($usado['n'] ?? 0) >= $proximo) {
        return 'En la serie ' . $serie . ' ya existe la factura n.º ' . (int) $usado['n']
            . ': el próximo número debe ser mayor.';
    }
    db_exec('INSERT INTO invoice_series (serie, last_number) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE last_number = VALUES(last_number)', [$serie, $proximo - 1]);
    return null;
}

/** Importe con dos decimales, como corresponde en una factura. */
function invoice_money(float $amount, string $currency = ''): string
{
    return number_format($amount, 2, ',', '.') . ($currency !== '' ? ' ' . $currency : '');
}
