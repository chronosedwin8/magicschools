<?php
/**
 * Descarga de un documento generado: HTML y PDF.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_login();
$gen = ai_generation((int) ($_GET['gen'] ?? 0), $user);
if (!$gen || $gen['status'] !== 'done') {
    flash('error', 'Ese documento no está disponible para descargar.');
    redirect('portal/documentos.php');
}

$formato = in_array($_GET['f'] ?? '', ['html', 'pdf'], true) ? $_GET['f'] : 'html';
$titulo = (string) ($gen['title'] ?: $gen['tool_name']);
$html = export_document_html($titulo, (string) $gen['output_text'], [
    'herramienta' => (string) $gen['tool_name'],
    'autor'       => (string) $gen['author_name'],
    'institucion' => (string) ($user['institution'] ?? ''),
    'fecha'       => fecha($gen['created_at'], true),
]);

if ($formato === 'pdf') {
    export_pdf($html, ai_filename($titulo, 'pdf'));
}

header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="' . ai_filename($titulo, 'html') . '"');
echo $html;
