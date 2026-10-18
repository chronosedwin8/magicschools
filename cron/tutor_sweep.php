<?php
/**
 * Barrido de las conversaciones del tutor. Se ejecuta cada minuto:
 *
 *   * * * * * /usr/bin/php /home/magicschools/htdocs/www.magicschools.es/cron/tutor_sweep.php
 *
 * Es la última red de seguridad contra el gasto: si el navegador del docente
 * desaparece, si el servidor se reinicia a mitad de una llamada o si Tavus no
 * responde al crear, esto cierra lo que haya quedado abierto cobrándose solo.
 *
 * Solo corre por consola: por web devuelve 403.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este script solo se ejecuta por consola.\n");
}

require_once __DIR__ . '/../includes/bootstrap.php';

$inicio = microtime(true);
$hechas = tutor_sweep();
$pendientes = ai_expire_stale();          // de paso, generaciones de IA colgadas

$total = array_sum($hechas);
$resumen = sprintf(
    'vencidas=%d inactivas=%d colgadas=%d huerfanas=%d perdidas=%d generaciones=%d (%.2fs)',
    $hechas['vencidas'], $hechas['inactivas'], $hechas['colgadas'], $hechas['huerfanas'], $hechas['perdidas'],
    (int) $pendientes, microtime(true) - $inicio
);

// El registro solo se escribe cuando hubo algo que cerrar, para no llenar el log
// con un renglón por minuto.
if ($total > 0 || (int) $pendientes > 0) {
    app_log('tavus', 'barrido: ' . $resumen);
}
echo date('Y-m-d H:i:s') . ' barrido ' . $resumen . "\n";
