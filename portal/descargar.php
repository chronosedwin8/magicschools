<?php
/**
 * Descarga de un documento generado: HTML, PDF, Word y PowerPoint.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_login();
$gen = ai_generation((int) ($_GET['gen'] ?? 0), $user);
if (!$gen || $gen['status'] !== 'done') {
    flash('error', 'Ese documento no está disponible para descargar.');
    redirect('portal/documentos.php');
}

$tool = ai_tool((string) $gen['tool_slug']);
$permitidos = (array) ($tool['exports'] ?? ['html']);
$formato = (string) ($_GET['f'] ?? 'html');
if (!in_array($formato, $permitidos, true) && $formato !== 'html') {
    $formato = 'html';
}

$titulo = (string) ($gen['title'] ?: $gen['tool_name']);
$markdown = (string) $gen['output_text'];
$meta = [
    'herramienta' => (string) $gen['tool_name'],
    'autor'       => (string) $gen['author_name'],
    'institucion' => (string) ($user['institution'] ?? ''),
    'fecha'       => fecha($gen['created_at'], true),
];

switch ($formato) {
    case 'pdf':
        export_pdf(export_document_html($titulo, $markdown, $meta), ai_filename($titulo, 'pdf'));

    case 'docx':
        export_docx($markdown, $titulo, $meta, ai_filename($titulo, 'docx'));

    case 'pptx':
        $datos = json_decode((string) $gen['output_json'], true);
        if (!is_array($datos) || empty($datos['diapositivas'])) {
            // Sin la estructura original no se pueden armar las diapositivas
            flash('warning', 'Este documento se generó antes de que existiera la exportación a PowerPoint. Vuelve a generarlo para descargarlo en ese formato.');
            redirect('portal/herramienta.php?gen=' . $gen['id']);
        }
        export_pptx($datos, $titulo, $meta, ai_filename($titulo, 'pptx'));

    default:
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . ai_filename($titulo, 'html') . '"');
        echo export_document_html($titulo, $markdown, $meta);
}
